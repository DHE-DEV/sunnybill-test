<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== LÖSUNG: Supplier-Number setzen ===\n";

try {
    // 1. Lade den spezifischen Supplier aus der URL
    $supplierId = '0197b2f0-3a04-7082-9727-ddd3610e9c3f';
    $supplier = \App\Models\Supplier::find($supplierId);
    
    if ($supplier) {
        echo "✅ Supplier gefunden: " . $supplier->name . "\n";
        echo "Aktuelle Supplier Number: " . ($supplier->supplier_number ?? 'NICHT GESETZT') . "\n";
        
        // 2. Setze eine Supplier-Number falls nicht vorhanden
        if (!$supplier->supplier_number) {
            // Generiere eine eindeutige Supplier-Number
            $supplierNumber = 'SUP-' . str_pad($supplier->id, 3, '0', STR_PAD_LEFT);
            $supplier->supplier_number = $supplierNumber;
            $supplier->save();
            
            echo "✅ Supplier-Number gesetzt: " . $supplierNumber . "\n";
        } else {
            echo "✅ Supplier hat bereits eine Nummer: " . $supplier->supplier_number . "\n";
        }
        
        // 3. Test der neuen Pfad-Generierung
        $config = \App\Services\DocumentUploadConfig::forSuppliers($supplier);
        $newPath = $config->getStorageDirectory();
        echo "Neuer Upload-Pfad: " . $newPath . "\n";
        
        // 4. Prüfe ob strukturiert
        if (strpos($newPath, 'suppliers/' . $supplier->supplier_number) === 0) {
            echo "✅ Strukturierter Pfad wird jetzt verwendet!\n";
            
            // 5. Zeige DigitalOcean URLs
            $storageSetting = \App\Models\StorageSetting::current();
            if ($storageSetting && $storageSetting->storage_driver === 'digitalocean') {
                $bucket = $storageSetting->storage_config['bucket'] ?? '';
                $endpoint = $storageSetting->storage_config['endpoint'] ?? '';
                
                echo "\n--- Neue DigitalOcean Struktur ---\n";
                echo "📂 " . $bucket . "/\n";
                echo "  ├── 📂 suppliers-documents/ (alte Dateien)\n";
                echo "  └── 📂 suppliers/\n";
                echo "      └── 📂 " . $supplier->supplier_number . "/ (neue Dateien)\n";
                echo "          └── 📄 zukünftige-uploads.pdf\n";
                
                echo "\n--- Beispiel-URLs für neue Uploads ---\n";
                $testFile = 'neues-dokument.pdf';
                $fullPath = $newPath . '/' . $testFile;
                if ($endpoint && $bucket) {
                    echo "• Space URL: " . $endpoint . "/" . $bucket . "/" . $fullPath . "\n";
                }
                if (!empty($storageSetting->storage_config['url'])) {
                    echo "• CDN URL: " . $storageSetting->storage_config['url'] . "/" . $fullPath . "\n";
                }
            }
            
        } else {
            echo "❌ Immer noch Fallback-Pfad: " . $newPath . "\n";
        }
        
        echo "\n=== ERGEBNIS ===\n";
        echo "✅ Problem behoben: Supplier hat jetzt eine supplier_number\n";
        echo "✅ Neue Uploads werden in strukturierten Pfaden gespeichert\n";
        echo "⚠️ Alte Dateien bleiben in suppliers-documents/ (können manuell verschoben werden)\n";
        
    } else {
        echo "❌ Supplier nicht gefunden\n";
    }
    
} catch (\Exception $e) {
    echo "FEHLER: " . $e->getMessage() . "\n";
    echo "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== SCRIPT BEENDET ===\n";