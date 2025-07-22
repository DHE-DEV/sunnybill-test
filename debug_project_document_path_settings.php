<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentPathSetting;

echo "Alle DocumentPathSettings für Projects:\n";
echo str_repeat("=", 80) . "\n";

try {
    $projectSettings = DocumentPathSetting::where('documentable_type', 'App\Models\Project')->get();
    
    if ($projectSettings->isEmpty()) {
        echo "KEINE DocumentPathSettings für Projects gefunden!\n";
        echo "Das ist das Problem - die Settings müssen erstellt werden.\n";
        
        echo "\nErstelle DocumentPathSettings für Projects...\n";
        DocumentPathSetting::createDefaults();
        
        echo "\nNach dem Erstellen:\n";
        $projectSettings = DocumentPathSetting::where('documentable_type', 'App\Models\Project')->get();
    }
    
    foreach ($projectSettings as $setting) {
        echo sprintf("Typ: %s | Kategorie: %s | Pfad: %s\n", 
            $setting->documentable_type, 
            $setting->category ?: 'NULL',
            $setting->path_template
        );
    }
    
    echo str_repeat("=", 80) . "\n";
    echo "Gesamt: " . $projectSettings->count() . " Project-Settings\n";
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
