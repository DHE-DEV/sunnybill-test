<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentPathSetting;

echo "=== Check: SolarPlant DocumentPathSettings ===\n\n";

// Prüfe vorhandene DocumentPathSettings für SolarPlants
$solarPlantSettings = DocumentPathSetting::where('documentable_type', 'App\Models\SolarPlant')->get();

echo "1. Vorhandene SolarPlant DocumentPathSettings:\n";
if ($solarPlantSettings->isEmpty()) {
    echo "   ❌ Keine DocumentPathSettings für 'App\Models\SolarPlant' gefunden!\n";
} else {
    foreach ($solarPlantSettings as $setting) {
        $category = $setting->category ?? 'NULL';
        echo "   - {$category}: {$setting->path_template}\n";
    }
}

echo "\n=== Erwartung ===\n";
echo "Es sollten DocumentPathSettings für 'App\Models\SolarPlant' mit verschiedenen Kategorien existieren!\n";
echo "Kategorien aus forSolarPlants(): planning, permits, installation, commissioning, maintenance, monitoring, insurance, technical, financial, legal, other\n";