<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentPathSetting;

echo "Fix: Erstelle fehlende DocumentPathSetting für Project direct_marketing_invoice\n";
echo str_repeat("=", 80) . "\n";

try {
    // Erstelle die fehlende DocumentPathSetting für Projects
    $setting = DocumentPathSetting::create([
        'documentable_type' => 'App\Models\Project',
        'category' => 'direct_marketing_invoice',
        'path_template' => 'projekte/{project_number}/abrechnungen/direktvermarktung',
        'description' => 'Direktvermarktung Rechnungen für Projekte'
    ]);
    
    echo "✅ DocumentPathSetting erstellt:\n";
    echo "- ID: {$setting->id}\n";
    echo "- Typ: {$setting->documentable_type}\n";
    echo "- Kategorie: {$setting->category}\n";
    echo "- Pfad-Template: {$setting->path_template}\n";
    
    // Teste sofort
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "Test nach Erstellung:\n";
    
    $project = \App\Models\Project::first();
    if ($project) {
        $directory = \App\Services\DocumentStorageService::getUploadDirectoryForModel(
            'projects',
            $project,
            ['category' => 'direct_marketing_invoice']
        );
        
        echo "✅ Project: {$project->project_number}\n";
        echo "✅ Neuer Pfad: {$directory}\n";
        echo "✅ Erwarteter Pfad: projekte/{$project->project_number}/abrechnungen/direktvermarktung\n";
        
        if ($directory === "projekte/{$project->project_number}/abrechnungen/direktvermarktung") {
            echo "🎉 ERFOLG! Pfad wird korrekt generiert!\n";
        } else {
            echo "❌ Problem: Pfad stimmt noch nicht überein\n";
        }
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
