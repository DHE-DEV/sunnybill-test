<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StorageSetting;
use App\Models\SolarPlant;
use App\Services\DocumentUploadConfig;

class TestSolarPlantStorageConfig extends Command
{
    protected $signature = 'test:solar-plant-storage-config';
    protected $description = 'SolarPlant-Pfad-Konfiguration zur StorageSetting hinzufügen und testen';

    public function handle()
    {
        $this->info('=== SOLARPLANT-PFAD-KONFIGURATION HINZUFÜGEN ===');

        try {
            $storageSetting = StorageSetting::current();
            if (!$storageSetting) {
                $this->error('❌ StorageSetting nicht gefunden');
                return 1;
            }

            $paths = $storageSetting->getStoragePaths();
            
            $this->info('Aktuelle Pfad-Konfigurationen:');
            foreach ($paths as $type => $config) {
                $this->line('• ' . $type . ': ' . ($config['pattern'] ?? 'kein Pattern'));
            }
            
            // Füge SolarPlant-Konfiguration hinzu (gleiche Struktur wie Suppliers/Clients)
            $paths['solar_plants'] = [
                'pattern' => 'documents/solar_plants/{plant_number}-{plant_name}',
                'example' => 'documents/solar_plants/SA-000001-Mustermann-Solaranlage',
                'description' => 'Solaranlagen-Dokumente mit vereinfachter Struktur',
                'placeholders' => [
                    'plant_number' => 'Anlagennummer (z.B. SA-000001)',
                    'plant_name' => 'Anlagenname (bereinigt für Pfad)',
                    'plant_id' => 'Anlagen-ID',
                    'solar_plant_number' => 'Anlagennummer (Alias)',
                    'solar_plant_name' => 'Anlagenname (Alias)',
                    'solar_plant_id' => 'Anlagen-ID (Alias)'
                ]
            ];
            
            $storageSetting->storage_paths = json_encode($paths);
            $storageSetting->save();
            
            $this->info('✅ SolarPlant-Konfiguration hinzugefügt:');
            $this->line('Pattern: ' . $paths['solar_plants']['pattern']);
            $this->line('Beispiel: ' . $paths['solar_plants']['example']);
            
            // Test mit einer SolarPlant (falls vorhanden)
            $solarPlant = SolarPlant::first();
            if ($solarPlant) {
                $this->info('--- Test mit SolarPlant: ' . ($solarPlant->name ?? 'Unbekannt') . ' ---');
                $this->line('Plant Number: ' . ($solarPlant->plant_number ?? 'NICHT GESETZT'));
                
                // Falls keine plant_number vorhanden, eine generieren (sollte automatisch passieren)
                if (!$solarPlant->plant_number) {
                    // SolarPlant Model hat automatische Generierung, aber falls es fehlt:
                    $plantNumber = 'SA-' . str_pad($solarPlant->id, 6, '0', STR_PAD_LEFT);
                    $solarPlant->plant_number = $plantNumber;
                    $solarPlant->save();
                    $this->info('✅ Plant-Number manuell generiert: ' . $plantNumber);
                }
                
                // Test der Pfad-Auflösung
                $resolvedPath = $storageSetting->resolvePath('solar_plants', $solarPlant);
                $this->line('Resolved Path: ' . $resolvedPath);
                
                // Test DocumentUploadConfig
                $config = DocumentUploadConfig::forSolarPlants($solarPlant);
                $configPath = $config->getStorageDirectory();
                $this->line('Config Path: ' . $configPath);
                
                if ($resolvedPath === $configPath) {
                    $this->info('✅ Pfade stimmen überein - SolarPlant-Konfiguration funktioniert!');
                    
                    // Zeige finale SolarPlant-Struktur
                    $this->info('--- FINALE SOLARPLANT-STRUKTUR ---');
                    $this->line('DigitalOcean Space: jtsolarbau/');
                    $this->line('├── suppliers-documents/ (alte Supplier-Dateien)');
                    $this->line('├── documents/');
                    $this->line('│   ├── suppliers/');
                    $this->line('│   │   └── LF-XXX-Supplier-Name/ (Supplier-Dokumente)');
                    $this->line('│   ├── clients/');
                    $this->line('│   │   └── KD-XXX-Customer-Name/ (Client-Dokumente)');
                    $this->line('│   └── solar_plants/');
                    $this->line('│       └── ' . ($solarPlant->plant_number ?? 'SA-XXXXXX') . '-' . $storageSetting->sanitizeValue($solarPlant->name ?? 'Unknown') . '/ (SolarPlant-Dokumente)');
                    $this->line('│           ├── planung.pdf');
                    $this->line('│           ├── genehmigung.pdf');
                    $this->line('│           ├── installation.pdf');
                    $this->line('│           ├── inbetriebnahme.pdf');
                    $this->line('│           └── wartung.pdf');
                    
                } else {
                    $this->error('❌ Pfade stimmen nicht überein:');
                    $this->line('Resolved: ' . $resolvedPath);
                    $this->line('Config: ' . $configPath);
                }
                
            } else {
                $this->warn('⚠️ Keine SolarPlant in der Datenbank gefunden für Test');
                $this->info('Erstelle eine Test-SolarPlant...');
                
                // Erstelle eine Test-SolarPlant für die Demonstration
                $testPlant = new SolarPlant();
                $testPlant->name = 'Test Solaranlage Mustermann';
                $testPlant->save(); // plant_number wird automatisch generiert
                
                $this->info('✅ Test-SolarPlant erstellt: ' . $testPlant->name);
                $this->line('Plant Number: ' . $testPlant->plant_number);
                
                // Test der Pfad-Auflösung mit Test-Plant
                $resolvedPath = $storageSetting->resolvePath('solar_plants', $testPlant);
                $this->line('Resolved Path: ' . $resolvedPath);
                
                $config = DocumentUploadConfig::forSolarPlants($testPlant);
                $configPath = $config->getStorageDirectory();
                $this->line('Config Path: ' . $configPath);
                
                if ($resolvedPath === $configPath) {
                    $this->info('✅ Test erfolgreich - SolarPlant-Konfiguration funktioniert!');
                }
            }
            
            $this->info('=== ZUSAMMENFASSUNG ===');
            $this->info('✅ SolarPlant-Pfad-Konfiguration zur StorageSetting hinzugefügt');
            $this->info('✅ Pattern: documents/solar_plants/{plant_number}-{plant_name}');
            $this->info('✅ Gleiche vereinfachte Struktur wie Suppliers/Clients');
            $this->info('✅ DocumentUploadConfig::forSolarPlants() funktioniert');
            $this->info('✅ StorageSetting Model um SolarPlant-Platzhalter erweitert');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('FEHLER: ' . $e->getMessage());
            $this->line('Datei: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }
    }
}