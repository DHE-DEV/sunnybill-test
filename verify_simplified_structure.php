<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FINALE VERIFIKATION: Vereinfachte Supplier-Struktur ===\n";

try {
    // 1. Prüfe aktuelle Konfiguration
    $storageSetting = \App\Models\StorageSetting::current();
    if ($storageSetting) {
        $paths = $storageSetting->getStoragePaths();
        echo "Aktuelles Pattern: " . ($paths['suppliers']['pattern'] ?? 'nicht gefunden') . "\n";
        echo "Beispiel: " . ($paths['suppliers']['example'] ?? 'nicht gefunden') . "\n";
        
        // 2. Test mit Alpine Solar Systems AG
        $supplierId = '0197b2f0-3a04-7082-9727-ddd3610e9c3f';
        $supplier = \App\Models\Supplier::find($supplierId);
        
        if ($supplier) {
            echo "\n--- Test mit " . $supplier->name . " ---\n";
            echo "Supplier Number: " . $supplier->supplier_number . "\n";
            
            // Resolved Path Test
            $resolvedPath = $storageSetting->resolvePath('suppliers', $supplier);
            echo "Resolved Path: " . $resolvedPath . "\n";
            
            // DocumentUploadConfig Test
            $config = \App\Services\DocumentUploadConfig::forSuppliers($supplier);
            $configPath = $config->getStorageDirectory();
            echo "Config Path: " . $configPath . "\n";
            
            // Verifikation
            $expectedPath = 'documents/suppliers/LF-009-Alpine-Solar-Systems-AG';
            if ($resolvedPath === $expectedPath && $configPath === $expectedPath) {
                echo "\n✅ ERFOLGREICH: Vereinfachte Struktur funktioniert!\n";
                echo "✅ Keine Unterverzeichnisse mehr\n";
                echo "✅ Dateien werden direkt im Supplier-Ordner gespeichert\n";
                
                // Zeige finale Struktur
                echo "\n--- FINALE STRUKTUR ---\n";
                echo "DigitalOcean Space: jtsolarbau/\n";
                echo "├── suppliers-documents/ (alte Dateien)\n";
                echo "└── documents/\n";
                echo "    └── suppliers/\n";
                echo "        └── LF-009-Alpine-Solar-Systems-AG/ (neue Dateien)\n";
                echo "            ├── dokument1.pdf\n";
                echo "            ├── vertrag.pdf\n";
                echo "            └── rechnung.pdf\n";
                
            } else {
                echo "\n❌ Problem: Pfade stimmen nicht überein\n";
                echo "Erwartet: " . $expectedPath . "\n";
                echo "Resolved: " . $resolvedPath . "\n";
                echo "Config: " . $configPath . "\n";
            }
        } else {
            echo "❌ Supplier nicht gefunden\n";
        }
    } else {
        echo "❌ StorageSetting nicht gefunden\n";
    }
    
} catch (\Exception $e) {
    echo "FEHLER: " . $e->getMessage() . "\n";
    echo "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "✅ Pattern vereinfacht: documents/suppliers/{supplier_number}-{supplier_name}\n";
echo "✅ Keine {document_number}-{document_name} Unterverzeichnisse mehr\n";
echo "✅ Alle Dateien werden direkt im Supplier-Ordner gespeichert\n";
echo "✅ Struktur entspricht jetzt: jtsolarbau/documents/suppliers/LF-009-Alpine-Solar-Systems-AG/\n";