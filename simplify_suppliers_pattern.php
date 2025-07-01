<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== VEREINFACHUNG: Suppliers Pattern ohne Unterverzeichnis ===\n";

try {
    $storageSetting = \App\Models\StorageSetting::current();
    if ($storageSetting) {
        $paths = $storageSetting->getStoragePaths();
        
        echo "Aktuelles suppliers Pattern: " . ($paths['suppliers']['pattern'] ?? 'nicht gefunden') . "\n";
        
        // Vereinfache das Pattern zu: documents/suppliers/{supplier_number}-{supplier_name}
        $paths['suppliers']['pattern'] = 'documents/suppliers/{supplier_number}-{supplier_name}';
        $paths['suppliers']['example'] = 'documents/suppliers/LF-001-SolarTech-Deutschland-GmbH/dokument.pdf';
        
        $storageSetting->storage_paths = json_encode($paths);
        $storageSetting->save();
        
        echo "✅ Pattern vereinfacht zu: " . $paths['suppliers']['pattern'] . "\n";
        echo "✅ Beispiel: " . $paths['suppliers']['example'] . "\n";
        
        // Test mit dem Supplier
        $supplierId = '0197b2f0-3a04-7082-9727-ddd3610e9c3f';
        $supplier = \App\Models\Supplier::find($supplierId);
        
        if ($supplier) {
            $resolvedPath = $storageSetting->resolvePath('suppliers', $supplier);
            echo "\n--- Test mit Alpine Solar Systems AG ---\n";
            echo "Supplier Number: " . $supplier->supplier_number . "\n";
            echo "Neuer resolved Path: " . $resolvedPath . "\n";
            
            // Erwarteter Pfad: documents/suppliers/LF-009-Alpine-Solar-Systems-AG
            if ($resolvedPath === 'documents/suppliers/LF-009-Alpine-Solar-Systems-AG') {
                echo "✅ Perfekte Struktur: documents/suppliers/LF-009-Alpine-Solar-Systems-AG/\n";
                echo "✅ Dateien werden direkt in diesem Ordner gespeichert\n";
            } else {
                echo "❌ Struktur noch nicht korrekt: " . $resolvedPath . "\n";
            }
            
            // Test DocumentUploadConfig
            $config = \App\Services\DocumentUploadConfig::forSuppliers($supplier);
            $configPath = $config->getStorageDirectory();
            echo "DocumentUploadConfig Path: " . $configPath . "\n";
            
            if ($configPath === 'documents/suppliers/LF-009-Alpine-Solar-Systems-AG') {
                echo "✅ DocumentUploadConfig verwendet vereinfachte Struktur!\n";
            } else {
                echo "❌ DocumentUploadConfig verwendet noch komplexe Struktur: " . $configPath . "\n";
            }
        }
        
    } else {
        echo "❌ StorageSetting nicht gefunden\n";
    }
    
} catch (\Exception $e) {
    echo "FEHLER: " . $e->getMessage() . "\n";
    echo "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FERTIG ===\n";
echo "Neue Struktur: jtsolarbau/documents/suppliers/LF-009-Alpine-Solar-Systems-AG/dokument.pdf\n";
echo "Alle Dateien werden direkt im Supplier-Ordner gespeichert.\n";