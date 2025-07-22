<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentPathSetting;

echo "Fix: Verbessere spezifische DocumentPathSettings (weg von 'sonstiges')\n";
echo str_repeat("=", 80) . "\n";

try {
    // Mapping von DocumentType-Keys zu besseren Pfaden
    $improvements = [
        'formulare' => [
            'path' => 'projekte/{project_number}/formulare',
            'description' => 'Formulare für Projekte'
        ],
        'delivery_note' => [
            'path' => 'projekte/{project_number}/lieferscheine',
            'description' => 'Lieferscheine für Projekte'
        ],
        'ordering_material' => [
            'path' => 'projekte/{project_number}/bestellungen',
            'description' => 'Materialbestellungen für Projekte'
        ],
        'commissioning' => [
            'path' => 'projekte/{project_number}/inbetriebnahme',
            'description' => 'Inbetriebnahme-Dokumente für Projekte'
        ],
        'legal_document' => [
            'path' => 'projekte/{project_number}/rechtsdokumente',
            'description' => 'Rechtsdokumente für Projekte'
        ],
        'information' => [
            'path' => 'projekte/{project_number}/informationen',
            'description' => 'Informationsdokumente für Projekte'
        ],
    ];
    
    $updated = 0;
    $total = count($improvements);
    
    echo "🔧 Aktualisiere {$total} DocumentPathSettings...\n\n";
    
    foreach ($improvements as $category => $config) {
        // Finde das DocumentPathSetting
        $setting = DocumentPathSetting::where('documentable_type', 'App\Models\Project')
            ->where('category', $category)
            ->first();
        
        if ($setting) {
            $oldPath = $setting->path_template;
            $newPath = $config['path'];
            
            // Aktualisiere nur wenn sich der Pfad ändert
            if ($oldPath !== $newPath) {
                $setting->update([
                    'path_template' => $newPath,
                    'description' => $config['description']
                ]);
                
                echo "✅ Aktualisiert: {$category}\n";
                echo "   Vorher: {$oldPath}\n";
                echo "   Nachher: {$newPath}\n\n";
                
                $updated++;
            } else {
                echo "ℹ️ Bereits korrekt: {$category} → {$newPath}\n";
            }
        } else {
            echo "❌ Nicht gefunden: {$category}\n";
        }
    }
    
    echo str_repeat("-", 60) . "\n";
    echo "🎉 {$updated} von {$total} DocumentPathSettings aktualisiert!\n";
    
    // Teste eine Verbesserung
    if ($updated > 0) {
        echo "\n" . str_repeat("-", 60) . "\n";
        echo "🧪 Test nach Verbesserung:\n";
        
        $project = \App\Models\Project::first();
        if ($project) {
            // Teste "Formulare"
            $directory = \App\Services\DocumentStorageService::getUploadDirectoryForModel(
                'projects',
                $project,
                ['category' => 'formulare']
            );
            
            echo "✅ Test 'Formulare':\n";
            echo "   Project: {$project->project_number}\n";
            echo "   Pfad: {$directory}\n";
            echo "   Erwartet: projekte/{$project->project_number}/formulare\n";
            
            if ($directory === "projekte/{$project->project_number}/formulare") {
                echo "   🎉 PERFEKT! Spezifischer Pfad wird verwendet!\n";
            } else {
                echo "   ❌ Problem: Pfad stimmt nicht überein\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
