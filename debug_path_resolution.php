<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DETAILLIERTE PFAD-DIAGNOSE ===\n";

try {
    // 1. Lade den spezifischen Supplier
    $supplierId = '0197b2f0-3a04-7082-9727-ddd3610e9c3f';
    $supplier = \App\Models\Supplier::find($supplierId);
    
    if (!$supplier) {
        echo "❌ Supplier nicht gefunden\n";
        exit;
    }
    
    echo "✅ Supplier gefunden: " . $supplier->name . "\n";
    echo "Supplier Number: " . ($supplier->supplier_number ?? 'NICHT GESETZT') . "\n";
    echo "Supplier ID: " . $supplier->id . "\n\n";
    
    // 2. Prüfe StorageSetting
    echo "--- STORAGE SETTING ANALYSE ---\n";
    $storageSetting = \App\Models\StorageSetting::current();
    
    if (!$storageSetting) {
        echo "❌ Keine StorageSetting gefunden!\n";
        echo "Das ist der Grund für den Fallback-Pfad.\n";
        exit;
    }
    
    echo "✅ StorageSetting gefunden (ID: " . $storageSetting->id . ")\n";
    echo "Storage Driver: " . $storageSetting->storage_driver . "\n";
    echo "Is Active: " . ($storageSetting->is_active ? 'Ja' : 'Nein') . "\n";
    
    // 3. Prüfe Storage-Pfade
    echo "\n--- STORAGE PFADE ANALYSE ---\n";
    $storagePaths = $storageSetting->getStoragePaths();
    
    if (empty($storagePaths)) {
        echo "❌ Keine Storage-Pfade konfiguriert!\n";
        echo "storage_paths Spalte: " . ($storageSetting->storage_paths ?? 'NULL') . "\n";
    } else {
        echo "✅ Storage-Pfade gefunden:\n";
        foreach ($storagePaths as $type => $config) {
            echo "  - {$type}: " . json_encode($config) . "\n";
        }
    }
    
    // 4. Prüfe spezifischen Suppliers-Pfad
    echo "\n--- SUPPLIERS PFAD ANALYSE ---\n";
    $suppliersPath = $storageSetting->getStoragePath('suppliers');
    
    if (!$suppliersPath) {
        echo "❌ Kein 'suppliers' Pfad konfiguriert!\n";
        echo "Verfügbare Pfad-Typen: " . implode(', ', array_keys($storagePaths)) . "\n";
    } else {
        echo "✅ Suppliers Pfad gefunden:\n";
        echo "  Template: " . ($suppliersPath['path'] ?? 'nicht definiert') . "\n";
        echo "  Beschreibung: " . ($suppliersPath['description'] ?? 'keine') . "\n";
    }
    
    // 5. Test Pfad-Auflösung
    echo "\n--- PFAD-AUFLÖSUNG TEST ---\n";
    
    try {
        $resolvedPath = $storageSetting->resolvePath('suppliers', $supplier);
        echo "✅ Resolved Path: " . $resolvedPath . "\n";
        
        // Prüfe ob strukturiert
        if (strpos($resolvedPath, 'suppliers/') === 0) {
            echo "✅ Strukturierter Pfad wird verwendet!\n";
        } else {
            echo "❌ Fallback-Pfad wird verwendet: " . $resolvedPath . "\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Fehler bei Pfad-Auflösung: " . $e->getMessage() . "\n";
        echo "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    // 6. Test DocumentUploadConfig
    echo "\n--- DOCUMENT UPLOAD CONFIG TEST ---\n";
    
    try {
        $config = \App\Services\DocumentUploadConfig::forSuppliers($supplier);
        echo "Config erstellt ✅\n";
        echo "PathType: " . ($config->get('pathType') ?? 'nicht gesetzt') . "\n";
        echo "Model gesetzt: " . ($config->get('model') ? 'Ja' : 'Nein') . "\n";
        
        $storageDirectory = $config->getStorageDirectory();
        echo "Storage Directory: " . $storageDirectory . "\n";
        
        if ($storageDirectory === 'suppliers-documents') {
            echo "❌ Fallback-Pfad wird verwendet!\n";
            
            // Prüfe warum
            echo "\n--- FALLBACK URSACHEN-ANALYSE ---\n";
            
            // Test DocumentStorageService direkt
            $directPath = \App\Services\DocumentStorageService::getUploadDirectoryForModel('suppliers', $supplier);
            echo "DocumentStorageService direkt: " . $directPath . "\n";
            
            // Test ohne Model
            $pathWithoutModel = \App\Services\DocumentStorageService::getUploadDirectoryForModel('suppliers');
            echo "Ohne Model: " . $pathWithoutModel . "\n";
            
        } else {
            echo "✅ Strukturierter Pfad wird verwendet!\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Fehler bei DocumentUploadConfig: " . $e->getMessage() . "\n";
        echo "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    // 7. Fallback-Pfade prüfen
    echo "\n--- FALLBACK PFADE ---\n";
    $fallbackPaths = $storageSetting->getFallbackPaths();
    foreach ($fallbackPaths as $type => $path) {
        echo "  - {$type}: {$path}\n";
    }
    
    echo "\n=== DIAGNOSE ABGESCHLOSSEN ===\n";
    
} catch (\Exception $e) {
    echo "KRITISCHER FEHLER: " . $e->getMessage() . "\n";
    echo "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}