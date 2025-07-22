<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentType;
use App\Models\DocumentPathSetting;
use App\Models\Project;
use App\Services\DocumentStorageService;

echo "Debug: Warum werden die konfigurierten Pfade nicht verwendet?\n";
echo str_repeat("=", 80) . "\n";

try {
    // Test DocumentType "Genehmigung" (sollte 'permits' → 'projekte/{project_number}/genehmigungen' verwenden)
    $documentType = DocumentType::where('name', 'Genehmigung')->first();
    $project = Project::first();
    
    if (!$documentType || !$project) {
        echo "ERROR: DocumentType oder Project nicht gefunden!\n";
        return;
    }
    
    echo "🔍 Test-Setup:\n";
    echo "- DocumentType: {$documentType->name} (Key: {$documentType->key})\n";
    echo "- Project: {$project->project_number}\n";
    
    // 1. Teste DocumentPathSetting::getPathConfig() direkt
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "1. Test: DocumentPathSetting::getPathConfig() direkt\n";
    
    $pathSetting = DocumentPathSetting::getPathConfig('App\Models\Project', $documentType->key);
    
    if ($pathSetting) {
        echo "✅ PathSetting gefunden:\n";
        echo "   - ID: {$pathSetting->id}\n";
        echo "   - Template: {$pathSetting->path_template}\n";
        echo "   - Kategorie: {$pathSetting->category}\n";
        
        // Teste generatePath()
        $generatedPath = $pathSetting->generatePath($project, ['category' => $documentType->key]);
        echo "   - Generierter Pfad: {$generatedPath}\n";
        
    } else {
        echo "❌ PROBLEM: getPathConfig() findet nichts!\n";
        echo "   - Documentable Type: App\\Models\\Project\n";
        echo "   - Category: {$documentType->key}\n";
        
        // Manuelle Suche
        echo "\n   Manuelle Suche in der Datenbank:\n";
        $manual = DocumentPathSetting::where('documentable_type', 'App\Models\Project')
            ->where('category', $documentType->key)
            ->first();
            
        if ($manual) {
            echo "   ✅ Manuell gefunden: {$manual->path_template}\n";
        } else {
            echo "   ❌ Auch manuell nicht gefunden!\n";
            
            // Suche alle Project Settings
            echo "\n   Alle Project DocumentPathSettings:\n";
            $all = DocumentPathSetting::where('documentable_type', 'App\Models\Project')->get();
            foreach ($all as $setting) {
                echo "   - Kategorie: '" . ($setting->category ?: 'NULL') . "' → {$setting->path_template}\n";
            }
        }
    }
    
    // 2. Teste DocumentStorageService::getUploadDirectoryForModel()
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "2. Test: DocumentStorageService::getUploadDirectoryForModel()\n";
    
    $directory = DocumentStorageService::getUploadDirectoryForModel(
        'projects',
        $project,
        ['category' => $documentType->key]
    );
    
    echo "Resultat: {$directory}\n";
    echo "Erwartet: projekte/{$project->project_number}/genehmigungen\n";
    
    if ($directory === "projects-documents") {
        echo "❌ FALLBACK WIRD VERWENDET!\n";
    } elseif ($directory === "projekte/{$project->project_number}/genehmigungen") {
        echo "✅ KORREKTE KONFIGURATION WIRD VERWENDET!\n";
    } else {
        echo "⚠️ UNERWARTETER PFAD!\n";
    }
    
    // 3. Teste mit verschiedenen DocumentType-Keys
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "3. Test: Verschiedene DocumentType-Keys\n";
    
    $testDocumentTypes = DocumentType::take(5)->get();
    
    foreach ($testDocumentTypes as $testType) {
        echo "\n🧪 Test: {$testType->name} (Key: {$testType->key})\n";
        
        $pathSetting = DocumentPathSetting::getPathConfig('App\Models\Project', $testType->key);
        
        if ($pathSetting) {
            $generatedPath = $pathSetting->generatePath($project, ['category' => $testType->key]);
            echo "   ✅ Konfiguriert: {$generatedPath}\n";
        } else {
            echo "   ❌ Nicht konfiguriert für: {$testType->key}\n";
            
            // Fallback-Test
            $directory = DocumentStorageService::getUploadDirectoryForModel(
                'projects',
                $project,
                ['category' => $testType->key]
            );
            echo "   📁 Fallback: {$directory}\n";
        }
    }
    
    // 4. Cache-Prüfung
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "4. Cache-Information\n";
    
    // Prüfe ob Laravel Cache verwendet wird
    if (cache()->has('document_path_settings')) {
        echo "⚠️ DocumentPathSettings sind gecacht - möglicherweise veraltete Daten!\n";
        echo "Lösung: Cache leeren mit 'php artisan cache:clear'\n";
    } else {
        echo "✅ Kein Cache für DocumentPathSettings gefunden\n";
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
