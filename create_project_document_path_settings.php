<?php

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentPathSetting;

echo "Erstelle DocumentPathSettings für Projekte...\n";

try {
    // Erstelle alle Standard-DocumentPathSettings
    DocumentPathSetting::createDefaults();
    
    echo "✅ DocumentPathSettings erfolgreich erstellt!\n";
    
    // Zeige alle Projekt-spezifischen Settings
    $projectSettings = DocumentPathSetting::where('documentable_type', 'App\Models\Project')
        ->where('is_active', true)
        ->orderBy('category')
        ->get();
    
    echo "\n📋 Erstellte Projekt-DocumentPathSettings:\n";
    echo str_repeat("=", 50) . "\n";
    
    foreach ($projectSettings as $setting) {
        $category = $setting->category ?: '(Standard)';
        echo sprintf(
            "%-15s | %s\n",
            $category,
            $setting->path_template
        );
    }
    
    echo str_repeat("=", 50) . "\n";
    echo "Gesamt: " . $projectSettings->count() . " Einstellungen\n";
    
} catch (Exception $e) {
    echo "❌ Fehler beim Erstellen der DocumentPathSettings:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nFertig!\n";
