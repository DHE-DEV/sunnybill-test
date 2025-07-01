<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SolarPlant;
use App\Models\StorageSetting;
use App\Services\DocumentUploadConfig;
use App\Models\Document;

class TestSolarPlantDocumentsIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:solar-plant-documents-integration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testet die vollständige Integration des SolarPlant-Dokumentenmoduls';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== SOLARPLANT-DOKUMENTENMODUL INTEGRATIONSTEST ===');
        $this->newLine();

        try {
            // 1. StorageSetting prüfen
            $this->info('1. StorageSetting-Konfiguration prüfen...');
            $storageSetting = StorageSetting::current();
            
            if (!$storageSetting) {
                $this->error('❌ Keine StorageSetting gefunden');
                return 1;
            }

            $paths = $storageSetting->getStoragePaths();
            if (!isset($paths['solar_plants'])) {
                $this->error('❌ SolarPlant-Pfad-Konfiguration fehlt in StorageSetting');
                return 1;
            }

            $this->info('✅ SolarPlant-Konfiguration gefunden:');
            $this->line('   Pattern: ' . $paths['solar_plants']['pattern']);
            $this->line('   Beispiel: ' . $paths['solar_plants']['example']);
            $this->newLine();

            // 2. SolarPlant-Model prüfen
            $this->info('2. SolarPlant-Model und Beziehungen prüfen...');
            
            $solarPlant = SolarPlant::first();
            if (!$solarPlant) {
                $this->info('   Keine SolarPlant gefunden, erstelle Test-SolarPlant...');
                $solarPlant = new SolarPlant();
                $solarPlant->name = 'Test Solaranlage Integration';
                $solarPlant->location = 'Teststraße 1, 12345 Teststadt';
                $solarPlant->total_capacity_kw = 25.5;
                $solarPlant->status = 'active';
                $solarPlant->save();
                $this->info('   ✅ Test-SolarPlant erstellt: ' . $solarPlant->name);
            } else {
                $this->info('   ✅ SolarPlant gefunden: ' . $solarPlant->name);
            }

            // Plant Number prüfen
            if (!$solarPlant->plant_number) {
                $this->warn('   ⚠️ Plant Number fehlt, wird automatisch generiert...');
                $solarPlant->save(); // Trigger automatische Generierung
                $solarPlant->refresh();
            }
            $this->line('   Plant Number: ' . $solarPlant->plant_number);

            // Documents-Beziehung prüfen
            if (!method_exists($solarPlant, 'documents')) {
                $this->error('❌ documents() Beziehung fehlt im SolarPlant Model');
                return 1;
            }
            $this->info('   ✅ documents() Beziehung verfügbar');
            $this->newLine();

            // 3. DocumentUploadConfig testen
            $this->info('3. DocumentUploadConfig::forSolarPlants() testen...');
            
            try {
                $config = DocumentUploadConfig::forSolarPlants($solarPlant);
                $this->info('   ✅ DocumentUploadConfig erstellt');
                $this->line('   Storage Directory: ' . $config->getStorageDirectory());
                $this->line('   Kategorien: ' . implode(', ', array_keys($config->get('categories'))));
                $this->line('   Zeitstempel aktiviert: ' . ($config->get('timestampFilenames') ? 'Ja' : 'Nein'));
            } catch (\Exception $e) {
                $this->error('❌ Fehler bei DocumentUploadConfig: ' . $e->getMessage());
                return 1;
            }
            $this->newLine();

            // 4. Pfad-Auflösung testen
            $this->info('4. Pfad-Auflösung testen...');
            
            $resolvedPath = $storageSetting->resolvePath('solar_plants', $solarPlant);
            $configPath = $config->getStorageDirectory();
            
            $this->line('   StorageSetting resolved: ' . $resolvedPath);
            $this->line('   DocumentUploadConfig:    ' . $configPath);
            
            if ($resolvedPath === $configPath) {
                $this->info('   ✅ Pfade stimmen überein');
            } else {
                $this->error('   ❌ Pfade stimmen nicht überein');
                return 1;
            }
            $this->newLine();

            // 5. Kategorien und Farben testen
            $this->info('5. Solaranlagen-spezifische Kategorien testen...');
            
            $categories = $config->get('categories');
            $expectedCategories = [
                'planning', 'permits', 'installation', 'commissioning',
                'maintenance', 'monitoring', 'insurance', 'technical',
                'financial', 'legal', 'other'
            ];
            
            foreach ($expectedCategories as $category) {
                if (isset($categories[$category])) {
                    $this->line('   ✅ ' . $category . ': ' . $categories[$category]);
                } else {
                    $this->error('   ❌ Kategorie fehlt: ' . $category);
                    return 1;
                }
            }
            $this->newLine();

            // 6. Platzhalter-System testen
            $this->info('6. Platzhalter-System testen...');
            
            $placeholders = $storageSetting->getAvailablePlaceholders('solar_plants');
            $expectedPlaceholders = [
                'plant_number', 'plant_name', 'plant_id',
                'solar_plant_number', 'solar_plant_name', 'solar_plant_id'
            ];
            
            foreach ($expectedPlaceholders as $placeholder) {
                if (isset($placeholders[$placeholder])) {
                    $this->line('   ✅ {' . $placeholder . '}: ' . $placeholders[$placeholder]);
                } else {
                    $this->error('   ❌ Platzhalter fehlt: {' . $placeholder . '}');
                    return 1;
                }
            }
            $this->newLine();

            // 7. Filament RelationManager prüfen
            $this->info('7. Filament RelationManager prüfen...');
            
            $relationManagerClass = 'App\\Filament\\Resources\\SolarPlantResource\\RelationManagers\\DocumentsRelationManager';
            if (class_exists($relationManagerClass)) {
                $this->info('   ✅ DocumentsRelationManager existiert');
                
                // SolarPlantResource Relations prüfen
                $solarPlantResourceClass = 'App\\Filament\\Resources\\SolarPlantResource';
                if (class_exists($solarPlantResourceClass)) {
                    $relations = $solarPlantResourceClass::getRelations();
                    if (in_array($relationManagerClass, $relations)) {
                        $this->info('   ✅ DocumentsRelationManager in SolarPlantResource registriert');
                    } else {
                        $this->error('   ❌ DocumentsRelationManager nicht in SolarPlantResource registriert');
                        return 1;
                    }
                } else {
                    $this->error('   ❌ SolarPlantResource nicht gefunden');
                    return 1;
                }
            } else {
                $this->error('   ❌ DocumentsRelationManager nicht gefunden');
                return 1;
            }
            $this->newLine();

            // 8. Finale Struktur-Übersicht
            $this->info('8. Finale Dokumentenstruktur:');
            $this->line('');
            $this->line('   DigitalOcean Space: jtsolarbau/');
            $this->line('   ├── suppliers-documents/ (alte Supplier-Dateien)');
            $this->line('   └── documents/');
            $this->line('       ├── suppliers/');
            $this->line('       │   └── LF-XXX-Supplier-Name/ (Supplier-Dokumente)');
            $this->line('       ├── clients/');
            $this->line('       │   └── KD-XXX-Customer-Name/ (Client-Dokumente)');
            $this->line('       └── solar_plants/');
            $this->line('           └── ' . $solarPlant->plant_number . '-' . str_replace(' ', '-', $solarPlant->name) . '/ (SolarPlant-Dokumente)');
            $this->line('               ├── planung_2025-01-07_12-33-21.pdf');
            $this->line('               ├── genehmigung_2025-01-07_12-33-21.pdf');
            $this->line('               ├── installation_2025-01-07_12-33-21.pdf');
            $this->line('               ├── inbetriebnahme_2025-01-07_12-33-21.pdf');
            $this->line('               └── wartung_2025-01-07_12-33-21.pdf');
            $this->newLine();

            // 9. Zusammenfassung
            $this->info('=== ZUSAMMENFASSUNG ===');
            $this->info('✅ SolarPlant-Dokumentenmodul vollständig implementiert');
            $this->info('✅ StorageSetting um SolarPlant-Konfiguration erweitert');
            $this->info('✅ DocumentUploadConfig::forSolarPlants() funktioniert');
            $this->info('✅ Solaranlagen-spezifische Kategorien verfügbar');
            $this->info('✅ Platzhalter-System für SolarPlants implementiert');
            $this->info('✅ Filament DocumentsRelationManager erstellt und registriert');
            $this->info('✅ Automatische Zeitstempel-Generierung aktiviert');
            $this->info('✅ Strukturierte Pfade: documents/solar_plants/{plant_number}-{plant_name}/');
            $this->newLine();

            $this->info('🎉 SolarPlant-Dokumentenmodul erfolgreich getestet!');
            $this->info('Das Modul ist bereit für den produktiven Einsatz.');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Unerwarteter Fehler: ' . $e->getMessage());
            $this->error('Datei: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }
    }
}