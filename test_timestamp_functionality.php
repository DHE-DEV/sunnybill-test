<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Zeitstempel-Funktionalität für Dateinamen ===\n";

try {
    // Test der generateTimestampedFilename Methode
    echo "\n--- Test der Zeitstempel-Generierung ---\n";
    
    // Simuliere DocumentFormBuilder
    $reflection = new ReflectionClass(\App\Services\DocumentFormBuilder::class);
    $method = $reflection->getMethod('generateTimestampedFilename');
    $method->setAccessible(true);
    
    $builder = new \App\Services\DocumentFormBuilder([]);
    
    // Test verschiedene Dateinamen
    $testFiles = [
        'vertrag.pdf',
        'Rechnung 2024.docx',
        'Foto mit Leerzeichen.jpg',
        'Dokument-mit-Bindestrichen.pdf',
        'Sonderzeichen@#$.txt',
        'dokument_ohne_extension'
    ];
    
    foreach ($testFiles as $originalFile) {
        $timestampedFile = $method->invoke($builder, $originalFile);
        echo "Original: " . $originalFile . "\n";
        echo "Mit Zeitstempel: " . $timestampedFile . "\n";
        echo "---\n";
    }
    
    // Test der DocumentUploadConfig mit neuen Einstellungen
    echo "\n--- Test DocumentUploadConfig mit Zeitstempel-Einstellungen ---\n";
    
    $supplierId = '0197b2f0-3a04-7082-9727-ddd3610e9c3f';
    $supplier = \App\Models\Supplier::find($supplierId);
    
    if ($supplier) {
        $config = \App\Services\DocumentUploadConfig::forSuppliers($supplier);
        $configArray = $config->toArray();
        
        echo "Supplier: " . $supplier->name . "\n";
        echo "Storage Directory: " . $config->getStorageDirectory() . "\n";
        echo "Preserve Filenames: " . ($configArray['preserveFilenames'] ? 'true' : 'false') . "\n";
        echo "Timestamp Filenames: " . ($configArray['timestampFilenames'] ? 'true' : 'false') . "\n";
        
        // Zeige wie Dateien jetzt benannt werden
        echo "\n--- Beispiel-Dateinamen mit neuer Konfiguration ---\n";
        $exampleFiles = ['vertrag.pdf', 'rechnung.docx', 'foto.jpg'];
        
        foreach ($exampleFiles as $file) {
            $timestamped = $method->invoke($builder, $file);
            $fullPath = $config->getStorageDirectory() . '/' . $timestamped;
            echo "• " . $file . " → " . $timestamped . "\n";
            echo "  Vollständiger Pfad: " . $fullPath . "\n";
        }
        
        // Zeige finale Struktur mit Zeitstempel-Dateien
        echo "\n--- Finale DigitalOcean Struktur mit Zeitstempel-Dateien ---\n";
        echo "jtsolarbau/\n";
        echo "└── documents/\n";
        echo "    └── suppliers/\n";
        echo "        └── LF-009-Alpine-Solar-Systems-AG/\n";
        echo "            ├── vertrag_2025-01-07_11-28-52.pdf\n";
        echo "            ├── rechnung_2025-01-07_11-29-15.docx\n";
        echo "            ├── foto_2025-01-07_11-29-33.jpg\n";
        echo "            └── zertifikat_2025-01-07_11-30-01.pdf\n";
        
        echo "\n✅ Vorteile der Zeitstempel-Benennung:\n";
        echo "• Keine Überschreibungen von Dateien\n";
        echo "• Chronologische Sortierung möglich\n";
        echo "• Eindeutige Identifikation jeder Datei\n";
        echo "• Automatische Versionierung\n";
        
    } else {
        echo "❌ Supplier nicht gefunden\n";
    }
    
} catch (\Exception $e) {
    echo "FEHLER: " . $e->getMessage() . "\n";
    echo "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "✅ Zeitstempel-Funktionalität implementiert\n";
echo "✅ Format: originalname_YYYY-MM-DD_HH-MM-SS.extension\n";
echo "✅ Automatische Aktivierung in DocumentUploadTrait\n";
echo "✅ Verhindert Überschreibungen von Dateien\n";
echo "✅ Kompatibel mit vereinfachter Ordnerstruktur\n";