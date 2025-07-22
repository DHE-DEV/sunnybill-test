<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use App\Models\DocumentType;
use App\Services\DocumentStorageService;

echo "Test: Project Document Upload Path Generation\n";
echo str_repeat("=", 80) . "\n";

try {
    // Hole erstes Project (oder erstelle Dummy)
    $project = Project::first();
    if (!$project) {
        echo "Kein Project gefunden - erstelle Dummy-Daten\n";
        $project = new Project([
            'id' => 1,
            'project_number' => 'PRJ-2025-0001',
            'name' => 'Test Projekt',
            'status' => 'aktiv'
        ]);
    }
    
    echo "Test-Project: {$project->project_number} - {$project->name}\n";
    
    // Teste DocumentType "Genehmigung" (ID: 2, Key: permits)
    $documentType = DocumentType::where('name', 'Genehmigung')->first();
    if (!$documentType) {
        echo "ERROR: DocumentType 'Genehmigung' nicht gefunden!\n";
        return;
    }
    
    echo "DocumentType: {$documentType->name} (Key: {$documentType->key})\n";
    
    // Simuliere was der DocumentFormBuilder macht
    echo "\n--- Simulation: DocumentFormBuilder directory() callback ---\n";
    
    // Simuliere Form-Data
    $get_data = [
        'document_type_id' => $documentType->id,
        'category' => null, // Wird erst später gesetzt
    ];
    
    echo "Initial get() data: " . json_encode($get_data) . "\n";
    
    // DocumentType-based category logic (aus DocumentFormBuilder)
    $category = $get_data['category'];
    $documentTypeId = $get_data['document_type_id'];
    
    if ($documentTypeId && !$category) {
        $documentTypeForPath = DocumentType::find($documentTypeId);
        $category = $documentTypeForPath?->key;
        echo "Kategorie von DocumentType geholt: {$category}\n";
    }
    
    // Teste DocumentStorageService
    echo "\n--- Test: DocumentStorageService::getUploadDirectoryForModel ---\n";
    
    $pathType = 'projects';
    $model = $project;
    $additionalData = $category ? ['category' => $category] : [];
    
    echo "Parameter:\n";
    echo "- pathType: {$pathType}\n";
    echo "- model: " . get_class($model) . " (ID: {$model->id})\n";
    echo "- additionalData: " . json_encode($additionalData) . "\n";
    
    $directory = DocumentStorageService::getUploadDirectoryForModel(
        $pathType,
        $model,
        $additionalData
    );
    
    echo "\nResultat: {$directory}\n";
    
    // Windows-Format für Anzeige
    $windowsPath = str_replace('/', '\\', $directory);
    echo "Windows-Format: {$windowsPath}\\\n";
    
    // Teste auch Pfad-Vorschau
    echo "\n--- Test: DocumentStorageService::previewPath ---\n";
    $preview = DocumentStorageService::previewPath($pathType, $model, $additionalData);
    
    echo "Preview-Resultat:\n";
    echo "- resolved_path: {$preview['resolved_path']}\n";
    echo "- template: {$preview['template']}\n";
    echo "- is_fallback: " . ($preview['is_fallback'] ? 'true' : 'false') . "\n";
    echo "- placeholders_used: " . json_encode($preview['placeholders_used']) . "\n";
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
