<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentPathSetting;

echo "=== Hinzufügen: SolarPlant DocumentPathSettings für alle Kategorien ===\n\n";

// Kategorien aus DocumentUploadConfig::forSolarPlants()
$solarPlantCategories = [
    'planning' => 'planung',
    'permits' => 'genehmigungen', 
    'installation' => 'installation',
    'commissioning' => 'inbetriebnahme',
    'maintenance' => 'wartung',
    'monitoring' => 'ueberwachung',
    'insurance' => 'versicherung',
    'technical' => 'technische-unterlagen',
    'financial' => 'finanzielle-unterlagen',
    'legal' => 'rechtsdokumente',
    'other' => 'sonstiges',
];

echo "1. Hinzufügen der fehlenden SolarPlant-Kategorien:\n";

foreach ($solarPlantCategories as $category => $folderName) {
    $setting = DocumentPathSetting::updateOrCreate(
        [
            'documentable_type' => 'App\Models\SolarPlant',
            'category' => $category,
        ],
        [
            'path_template' => "solaranlagen/{plant_number}/{$folderName}",
            'description' => "Pfad für Solaranlagen-{$category}-Dokumente",
            'placeholders' => ['plant_number', 'plant_name', 'plant_id'],
            'is_active' => true,
        ]
    );
    
    echo "   ✅ {$category}: solaranlagen/{plant_number}/{$folderName}\n";
}

echo "\n2. Überprüfung der neuen Settings:\n";
$solarPlantSettings = DocumentPathSetting::where('documentable_type', 'App\Models\SolarPlant')->get();
foreach ($solarPlantSettings as $setting) {
    $category = $setting->category ?? 'NULL';
    echo "   - {$category}: {$setting->path_template}\n";
}

echo "\n✅ Alle SolarPlant DocumentPathSettings hinzugefügt!\n";