<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentType;
use App\Models\Project;
use App\Services\DocumentFormBuilder;
use App\Services\DocumentUploadConfig;

echo "Debug: Frontend Pfad-Anzeige Problem\n";
echo str_repeat("=", 80) . "\n";

try {
    $project = Project::first();
    $documentType = DocumentType::where('name', 'Genehmigung')->first();
    
    if (!$project || !$documentType) {
        echo "ERROR: Project oder DocumentType nicht gefunden!\n";
        return;
    }
    
    echo "🔍 Frontend-Test Setup:\n";
    echo "- Project: {$project->project_number}\n";
    echo "- DocumentType: {$documentType->name} (Key: {$documentType->key})\n";
    
    // 1. Teste DocumentUploadConfig für Projects
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "1. Test: DocumentUploadConfig::forProjects()\n";
    
    $config = DocumentUploadConfig::forProjects($project);
    
    echo "Config-Eigenschaften:\n";
    echo "- pathType: " . ($config->get('pathType') ?: 'NULL') . "\n";
    echo "- model: " . ($config->get('model') ? get_class($config->get('model')) : 'NULL') . "\n";
    echo "- useDocumentTypes: " . ($config->get('useDocumentTypes') ? 'true' : 'false') . "\n";
    echo "- categories (count): " . count($config->get('categories', [])) . "\n";
    
    // Teste storageDirectory
    $storageDirectory = $config->getStorageDirectory();
    echo "- storageDirectory: {$storageDirectory}\n";
    
    // 2. Teste DocumentFormBuilder
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "2. Test: DocumentFormBuilder Konfiguration\n";
    
    $formBuilder = DocumentFormBuilder::make($config->toArray());
    
    // Simuliere was passiert wenn DocumentType ausgewählt wird
    echo "\n🧪 Simulation: DocumentType-Auswahl\n";
    
    // Simuliere Form-Daten wie sie vom Frontend kommen würden
    $simulatedGetData = [
        'document_type_id' => $documentType->id,
        'category' => null, // Wird erst durch DocumentType gesetzt
    ];
    
    echo "Simulierte Get-Daten: " . json_encode($simulatedGetData) . "\n";
    
    // Simuliere die Logik aus DocumentFormBuilder::createFileUploadField
    $category = $simulatedGetData['category'];
    $documentTypeId = $simulatedGetData['document_type_id'];
    
    if ($documentTypeId && !$category) {
        $documentTypeForPath = DocumentType::find($documentTypeId);
        $category = $documentTypeForPath?->key;
        echo "Category von DocumentType geholt: {$category}\n";
    }
    
    $additionalData = $category ? ['category' => $category] : [];
    echo "AdditionalData: " . json_encode($additionalData) . "\n";
    
    // Teste den gleichen Service-Aufruf wie im DocumentFormBuilder
    $directory = \App\Services\DocumentStorageService::getUploadDirectoryForModel(
        $config->get('pathType'),
        $config->get('model'),
        array_merge($config->get('additionalData', []), $additionalData)
    );
    
    echo "Final directory: {$directory}\n";
    echo "Expected: projekte/{$project->project_number}/genehmigungen\n";
    
    // 3. Teste createPathPreviewField Logik
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "3. Test: Path Preview Field Logik\n";
    
    // Diese Logik ist aus DocumentFormBuilder::createPathPreviewField
    if ($config->get('pathType') && $config->get('model')) {
        $pathType = $config->get('pathType');
        $model = $config->get('model');
        $additionalData = array_merge(
            $config->get('additionalData', []),
            $category ? ['category' => $category] : []
        );
        
        echo "Path Preview Parameter:\n";
        echo "- pathType: {$pathType}\n";
        echo "- model: " . get_class($model) . "\n";
        echo "- additionalData: " . json_encode($additionalData) . "\n";
        
        $previewPath = \App\Services\DocumentStorageService::getUploadDirectoryForModel(
            $pathType,
            $model,
            $additionalData
        );
        
        echo "Preview Path: {$previewPath}\n";
        
        // Vollständigen Pfad mit Storage-Basis anzeigen (wie im Frontend)
        $diskName = $config->getDiskName();
        $windowsPath = str_replace('/', '\\', $previewPath);
        $displayPath = "({$diskName}) {$windowsPath}\\";
        
        echo "Frontend Display Path: 📁 {$displayPath}\n";
        
        if ($previewPath === "projects-documents") {
            echo "❌ FALLBACK WIRD IM FRONTEND VERWENDET!\n";
        } else {
            echo "✅ KORREKTE PFADE IM FRONTEND!\n";
        }
    }
    
    // 4. Problem-Identifikation
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "4. Problem-Analyse\n";
    
    // Mögliche Probleme:
    $possibleIssues = [];
    
    if (!$config->get('useDocumentTypes')) {
        $possibleIssues[] = "useDocumentTypes ist nicht aktiviert";
    }
    
    if (!$config->get('pathType')) {
        $possibleIssues[] = "pathType ist nicht gesetzt";
    }
    
    if (!$config->get('model')) {
        $possibleIssues[] = "model ist nicht gesetzt";
    }
    
    if (empty($possibleIssues)) {
        echo "✅ Alle Konfigurationen sehen korrekt aus!\n";
        echo "🤔 Das Problem könnte in der Frontend-Reaktivität liegen:\n";
        echo "   - FileUpload-Feld reagiert nicht auf DocumentType-Änderungen\n";
        echo "   - Browser-Cache zeigt alte Pfade an\n";
        echo "   - JavaScript-Reaktivität funktioniert nicht\n";
        echo "\n💡 Lösungsvorschläge:\n";
        echo "   - Browser-Cache leeren\n";
        echo "   - Filament-Assets neu kompilieren\n";
        echo "   - Page neu laden\n";
    } else {
        echo "❌ Gefundene Probleme:\n";
        foreach ($possibleIssues as $issue) {
            echo "   - {$issue}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
