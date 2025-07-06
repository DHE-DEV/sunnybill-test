<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Supplier;
use App\Services\DocumentUploadConfig;
use App\Services\DocumentStorageService;

echo "=== Test: Supplier Upload-Directories ===\n\n";

// Test-Supplier laden
$supplier = Supplier::first();
if (!$supplier) {
    echo "❌ Kein Supplier gefunden!\n";
    exit(1);
}

echo "1. Test-Supplier: {$supplier->name} ({$supplier->supplier_number})\n\n";

// Test DocumentUploadConfig für Suppliers
$config = DocumentUploadConfig::forSuppliers($supplier);
echo "2. DocumentUploadConfig pathType: " . $config->get('pathType') . "\n";

// Test Upload-Directory
try {
    $uploadDir = $config->getStorageDirectory();
    echo "3. Upload-Directory: {$uploadDir}\n\n";
} catch (Exception $e) {
    echo "❌ Fehler beim Abrufen des Upload-Directory: " . $e->getMessage() . "\n\n";
}

// Test verschiedene Kategorien
echo "4. Upload-Directories für verschiedene Kategorien:\n";
$categories = ['contract', 'invoice', 'certificate', 'correspondence', 'technical', 'quality', 'other', null];

foreach ($categories as $category) {
    try {
        $directory = DocumentStorageService::getUploadDirectoryForModel(
            'suppliers',
            $supplier,
            ['category' => $category]
        );
        $categoryLabel = $category ?? 'NULL';
        echo "   {$categoryLabel}: {$directory}\n";
    } catch (Exception $e) {
        echo "   {$category}: ❌ Fehler - " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Test abgeschlossen!\n";
echo "\n=== Erwartung ===\n";
echo "Alle Pfade sollten mit 'lieferanten/{$supplier->supplier_number}/' beginnen\n";
echo "und kategorie-spezifische Unterordner haben!\n";