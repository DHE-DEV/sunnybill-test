<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SolarPlant;
use App\Services\DocumentUploadConfig;
use App\Services\DocumentStorageService;

echo "=== Test: SolarPlant Upload-Directories ===\n\n";

// Test-SolarPlant laden
$solarPlant = SolarPlant::first();
if (!$solarPlant) {
    echo "❌ Keine SolarPlant gefunden!\n";
    exit(1);
}

echo "1. Test-SolarPlant: {$solarPlant->name} ({$solarPlant->plant_number})\n\n";

// Test DocumentUploadConfig für SolarPlants
$config = DocumentUploadConfig::forSolarPlants($solarPlant);
echo "2. DocumentUploadConfig pathType: " . $config->get('pathType') . "\n";

// Test Upload-Directory
try {
    $uploadDir = $config->getStorageDirectory();
    echo "3. Upload-Directory: {$uploadDir}\n\n";
} catch (Exception $e) {
    echo "❌ Fehler beim Abrufen des Upload-Directory: " . $e->getMessage() . "\n\n";
}

// Test verschiedene Kategorien aus forSolarPlants()
echo "4. Upload-Directories für verschiedene Kategorien:\n";
$categories = ['planning', 'permits', 'installation', 'commissioning', 'maintenance', 'monitoring', 'insurance', 'technical', 'financial', 'legal', 'other', null];

foreach ($categories as $category) {
    try {
        $directory = DocumentStorageService::getUploadDirectoryForModel(
            'solar_plants',
            $solarPlant,
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
echo "Alle Pfade sollten mit 'solaranlagen/{$solarPlant->plant_number}/' beginnen\n";
echo "und kategorie-spezifische Unterordner haben!\n";