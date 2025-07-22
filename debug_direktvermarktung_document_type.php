<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentType;
use App\Models\DocumentPathSetting;
use App\Models\Project;
use App\Services\DocumentStorageService;

echo "Debug: Direktvermarktung Rechnung DocumentType für Projects\n";
echo str_repeat("=", 80) . "\n";

try {
    // 1. Finde DocumentType "Direktvermarktung Rechnung"
    $documentType = DocumentType::where('name', 'Direktvermarktung Rechnung')->first();
    if (!$documentType) {
        echo "ERROR: DocumentType 'Direktvermarktung Rechnung' nicht gefunden!\n";
        return;
    }
    
    echo "✅ DocumentType gefunden:\n";
    echo "- ID: {$documentType->id}\n";
    echo "- Name: {$documentType->name}\n";
    echo "- Key: {$documentType->key}\n";
    
    // 2. Prüfe DocumentPathSettings für SolarPlant (funktioniert)
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "SolarPlant DocumentPathSettings für Key '{$documentType->key}':\n";
    
    $solarPlantSettings = DocumentPathSetting::where('documentable_type', 'App\Models\SolarPlant')
        ->where('category', $documentType->key)
        ->get();
        
    if ($solarPlantSettings->count() > 0) {
        foreach ($solarPlantSettings as $setting) {
            echo "✅ SolarPlant: {$setting->path_template}\n";
        }
    } else {
        echo "❌ Keine SolarPlant Settings für '{$documentType->key}'\n";
    }
    
    // 3. Prüfe DocumentPathSettings für Project (sollte fehlen)
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "Project DocumentPathSettings für Key '{$documentType->key}':\n";
    
    $projectSettings = DocumentPathSetting::where('documentable_type', 'App\Models\Project')
        ->where('category', $documentType->key)
        ->get();
        
    if ($projectSettings->count() > 0) {
        foreach ($projectSettings as $setting) {
            echo "✅ Project: {$setting->path_template}\n";
        }
    } else {
        echo "❌ PROBLEM GEFUNDEN: Keine Project Settings für '{$documentType->key}'\n";
        echo "Das erklärt warum der Fallback-Pfad verwendet wird!\n";
    }
    
    // 4. Teste was der DocumentStorageService für Projects macht
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "DocumentStorageService Test für Project:\n";
    
    $project = Project::first();
    if ($project) {
        echo "Test-Project: {$project->project_number}\n";
        
        $directory = DocumentStorageService::getUploadDirectoryForModel(
            'projects',
            $project,
            ['category' => $documentType->key]
        );
        
        echo "Aktueller Pfad: {$directory}\n";
        echo "Erwarteter Pfad: projekte/{$project->project_number}/abrechnungen/direktvermarktung\n";
        
        if ($directory === "projects-documents") {
            echo "❌ FALLBACK WIRD VERWENDET!\n";
        }
    }
    
    // 5. Zeige alle Project DocumentPathSettings
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "Alle Project DocumentPathSettings:\n";
    
    $allProjectSettings = DocumentPathSetting::where('documentable_type', 'App\Models\Project')->get();
    foreach ($allProjectSettings as $setting) {
        echo "- Kategorie: " . ($setting->category ?: 'NULL') . " → {$setting->path_template}\n";
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
