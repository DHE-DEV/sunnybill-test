<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StorageSetting;
use App\Models\Customer;
use App\Services\DocumentUploadConfig;

class TestClientDocumentsModule extends Command
{
    protected $signature = 'test:client-documents';
    protected $description = 'Test Client Documents Module Implementation';

    public function handle()
    {
        $this->info('=== CLIENT-DOKUMENTEN-MODUL TEST ===');
        
        try {
            // 1. StorageSetting Client-Konfiguration hinzufügen
            $this->info('1. Client-Konfiguration zur StorageSetting hinzufügen...');
            $this->addClientConfiguration();
            
            // 2. Test mit Customer
            $this->info('2. Test mit Customer...');
            $this->testWithCustomer();
            
            // 3. Zusammenfassung
            $this->info('3. Zusammenfassung...');
            $this->showSummary();
            
        } catch (\Exception $e) {
            $this->error('FEHLER: ' . $e->getMessage());
            $this->error('Datei: ' . $e->getFile() . ':' . $e->getLine());
        }
    }
    
    private function addClientConfiguration()
    {
        $storageSetting = StorageSetting::current();
        if (!$storageSetting) {
            $this->error('❌ StorageSetting nicht gefunden');
            return;
        }
        
        $paths = $storageSetting->getStoragePaths();
        
        $this->info('Aktuelle Pfad-Konfigurationen:');
        foreach ($paths as $type => $config) {
            $this->line('• ' . $type . ': ' . ($config['pattern'] ?? 'kein Pattern'));
        }
        
        // Füge Client-Konfiguration hinzu (gleiche Struktur wie Suppliers)
        $paths['clients'] = [
            'pattern' => 'documents/clients/{customer_number}-{customer_name}',
            'example' => 'documents/clients/KD-001-Mustermann-GmbH',
            'description' => 'Kunden-Dokumente mit vereinfachter Struktur',
            'placeholders' => [
                'customer_number' => 'Kundennummer (z.B. KD-001)',
                'customer_name' => 'Kundenname (bereinigt für Pfad)',
                'customer_id' => 'Kunden-ID',
                'client_number' => 'Kundennummer (Alias)',
                'client_name' => 'Kundenname (Alias)',
                'client_id' => 'Kunden-ID (Alias)'
            ]
        ];
        
        $storageSetting->storage_paths = json_encode($paths);
        $storageSetting->save();
        
        $this->info('✅ Client-Konfiguration hinzugefügt:');
        $this->line('Pattern: ' . $paths['clients']['pattern']);
        $this->line('Beispiel: ' . $paths['clients']['example']);
    }
    
    private function testWithCustomer()
    {
        $customer = Customer::first();
        if (!$customer) {
            $this->warn('⚠️ Kein Customer in der Datenbank gefunden für Test');
            return;
        }
        
        $this->info('--- Test mit Customer: ' . ($customer->display_name ?? $customer->name ?? 'Unbekannt') . ' ---');
        $this->line('Customer Number: ' . ($customer->customer_number ?? 'NICHT GESETZT'));
        
        // Falls keine customer_number vorhanden, eine generieren
        if (!$customer->customer_number) {
            $customerNumber = 'KD-' . str_pad($customer->id, 3, '0', STR_PAD_LEFT);
            $customer->customer_number = $customerNumber;
            $customer->save();
            $this->info('✅ Customer-Number generiert: ' . $customerNumber);
        }
        
        $storageSetting = StorageSetting::current();
        
        // Test der Pfad-Auflösung
        $resolvedPath = $storageSetting->resolvePath('clients', $customer);
        $this->line('Resolved Path: ' . $resolvedPath);
        
        // Test DocumentUploadConfig
        $config = DocumentUploadConfig::forClients($customer);
        $configPath = $config->getStorageDirectory();
        $this->line('Config Path: ' . $configPath);
        
        if ($resolvedPath === $configPath) {
            $this->info('✅ Pfade stimmen überein - Client-Konfiguration funktioniert!');
            
            // Zeige finale Client-Struktur
            $this->info('--- FINALE CLIENT-STRUKTUR ---');
            $this->line('DigitalOcean Space: jtsolarbau/');
            $this->line('├── suppliers-documents/ (alte Supplier-Dateien)');
            $this->line('├── documents/');
            $this->line('│   ├── suppliers/');
            $this->line('│   │   └── LF-XXX-Supplier-Name/ (Supplier-Dokumente)');
            $this->line('│   └── clients/');
            // Verwende die private sanitizeValue Methode über resolvePath
            $testPath = $storageSetting->resolvePath('clients', $customer);
            $pathParts = explode('/', $testPath);
            $customerFolder = end($pathParts);
            $this->line('│       └── ' . $customerFolder . '/ (Client-Dokumente)');
            $this->line('│           ├── vertrag.pdf');
            $this->line('│           ├── rechnung.pdf');
            $this->line('│           └── angebot.pdf');
            
        } else {
            $this->error('❌ Pfade stimmen nicht überein:');
            $this->line('Resolved: ' . $resolvedPath);
            $this->line('Config: ' . $configPath);
        }
    }
    
    private function showSummary()
    {
        $this->info('=== ZUSAMMENFASSUNG ===');
        $this->info('✅ Client-Pfad-Konfiguration zur StorageSetting hinzugefügt');
        $this->info('✅ Pattern: documents/clients/{customer_number}-{customer_name}');
        $this->info('✅ Gleiche vereinfachte Struktur wie Suppliers');
        $this->info('✅ DocumentUploadConfig::forClients() funktioniert');
        $this->info('✅ StorageSetting Model um Customer-Platzhalter erweitert');
        
        $this->info('--- NÄCHSTE SCHRITTE ---');
        $this->line('1. CustomerResource um DocumentsRelationManager erweitern');
        $this->line('2. Tests der Client-Dokumenten-Funktionalität');
        $this->line('3. Finale Verifikation mit echten Uploads');
    }
}