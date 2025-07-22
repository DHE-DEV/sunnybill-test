<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentType;
use App\Models\DocumentPathSetting;

echo "Fix: Erstelle alle fehlenden DocumentPathSettings für Projects\n";
echo str_repeat("=", 80) . "\n";

try {
    // 1. Hole alle DocumentTypes
    $documentTypes = DocumentType::all();
    echo "📋 Gefundene DocumentTypes: " . $documentTypes->count() . "\n\n";
    
    // 2. Hole existierende Project DocumentPathSettings
    $existingProjectSettings = DocumentPathSetting::where('documentable_type', 'App\Models\Project')
        ->whereNotNull('category')
        ->pluck('category')
        ->toArray();
    
    echo "✅ Bereits vorhandene Project-Settings: " . count($existingProjectSettings) . "\n";
    foreach ($existingProjectSettings as $category) {
        echo "   - {$category}\n";
    }
    
    // 3. Finde fehlende DocumentTypes
    $missingTypes = [];
    foreach ($documentTypes as $documentType) {
        if (!in_array($documentType->key, $existingProjectSettings)) {
            $missingTypes[] = $documentType;
        }
    }
    
    echo "\n❌ Fehlende DocumentTypes für Projects: " . count($missingTypes) . "\n";
    foreach ($missingTypes as $type) {
        echo "   - {$type->name} (Key: {$type->key})\n";
    }
    
    if (empty($missingTypes)) {
        echo "\n🎉 Alle DocumentTypes haben bereits Project-Pfade!\n";
        return;
    }
    
    // 4. Erstelle fehlende DocumentPathSettings
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "Erstelle fehlende DocumentPathSettings...\n\n";
    
    $created = 0;
    foreach ($missingTypes as $documentType) {
        // Bestimme den passenden Pfad basierend auf dem DocumentType
        $pathTemplate = determineProjectPathForDocumentType($documentType->key, $documentType->name);
        
        $setting = DocumentPathSetting::create([
            'documentable_type' => 'App\Models\Project',
            'category' => $documentType->key,
            'path_template' => $pathTemplate,
            'description' => $documentType->name . ' für Projekte'
        ]);
        
        echo "✅ Erstellt: {$documentType->name}\n";
        echo "   - Key: {$documentType->key}\n";
        echo "   - Pfad: {$pathTemplate}\n\n";
        
        $created++;
    }
    
    echo str_repeat("-", 60) . "\n";
    echo "🎉 Erfolgreich {$created} DocumentPathSettings für Projects erstellt!\n";
    
    // 5. Test mit einem Projekt
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "Test mit ersten fehlenden DocumentType...\n";
    
    $project = \App\Models\Project::first();
    if ($project && !empty($missingTypes)) {
        $testType = $missingTypes[0];
        
        $directory = \App\Services\DocumentStorageService::getUploadDirectoryForModel(
            'projects',
            $project,
            ['category' => $testType->key]
        );
        
        echo "✅ Test-Project: {$project->project_number}\n";
        echo "✅ Test-DocumentType: {$testType->name}\n";
        echo "✅ Generierter Pfad: {$directory}\n";
        
        if ($directory !== "projects-documents") {
            echo "🎉 ERFOLG! Fallback wird nicht mehr verwendet!\n";
        } else {
            echo "❌ Problem: Fallback wird immer noch verwendet\n";
        }
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

/**
 * Bestimmt den passenden Projekt-Pfad basierend auf dem DocumentType
 */
function determineProjectPathForDocumentType(string $key, string $name): string
{
    // Mapping von DocumentType-Keys zu sinnvollen Projekt-Ordnern
    $pathMappings = [
        // Verträge und Angebote
        'contract' => 'projekte/{project_number}/vertraege',
        'contracts' => 'projekte/{project_number}/vertraege',
        'proposal' => 'projekte/{project_number}/angebote',
        'offer' => 'projekte/{project_number}/angebote',
        
        // Rechnungen und Abrechnungen
        'invoice' => 'projekte/{project_number}/rechnungen',
        'invoices' => 'projekte/{project_number}/rechnungen',
        'bill' => 'projekte/{project_number}/rechnungen',
        'billing' => 'projekte/{project_number}/abrechnungen',
        'direct_marketing_invoice' => 'projekte/{project_number}/abrechnungen/direktvermarktung',
        'supplier_invoice' => 'projekte/{project_number}/rechnungen/lieferanten',
        
        // Zertifikate und Genehmigungen
        'certificate' => 'projekte/{project_number}/zertifikate',
        'certificates' => 'projekte/{project_number}/zertifikate',
        'permit' => 'projekte/{project_number}/genehmigungen',
        'permits' => 'projekte/{project_number}/genehmigungen',
        'approval' => 'projekte/{project_number}/genehmigungen',
        
        // Installation und Wartung
        'installation' => 'projekte/{project_number}/installation',
        'maintenance' => 'projekte/{project_number}/wartung',
        'handover' => 'projekte/{project_number}/uebergabe',
        
        // Technische Unterlagen
        'technical' => 'projekte/{project_number}/technische-unterlagen',
        'manual' => 'projekte/{project_number}/technische-unterlagen/handbuecher',
        'specification' => 'projekte/{project_number}/technische-unterlagen/spezifikationen',
        'plan' => 'projekte/{project_number}/planung',
        'planning' => 'projekte/{project_number}/planung',
        'drawing' => 'projekte/{project_number}/planung/zeichnungen',
        
        // Dokumentation und Berichte
        'documentation' => 'projekte/{project_number}/dokumentation',
        'report' => 'projekte/{project_number}/berichte',
        'protocol' => 'projekte/{project_number}/protokolle',
        'progress' => 'projekte/{project_number}/fortschrittsberichte',
        
        // Korrespondenz und Kommunikation
        'correspondence' => 'projekte/{project_number}/korrespondenz',
        'email' => 'projekte/{project_number}/korrespondenz/emails',
        'letter' => 'projekte/{project_number}/korrespondenz/briefe',
        
        // Fotos und Medien
        'photo' => 'projekte/{project_number}/fotos',
        'photos' => 'projekte/{project_number}/fotos',
        'image' => 'projekte/{project_number}/fotos',
        'video' => 'projekte/{project_number}/medien/videos',
        
        // Qualität und Tests
        'quality' => 'projekte/{project_number}/qualitaet',
        'test' => 'projekte/{project_number}/tests',
        'inspection' => 'projekte/{project_number}/pruefungen',
        
        // Sonstige
        'other' => 'projekte/{project_number}/sonstiges',
        'misc' => 'projekte/{project_number}/sonstiges',
        'general' => 'projekte/{project_number}/allgemein',
    ];
    
    // Exakte Übereinstimmung suchen
    if (isset($pathMappings[$key])) {
        return $pathMappings[$key];
    }
    
    // Teilstring-Matching für ähnliche Keys
    foreach ($pathMappings as $mappingKey => $path) {
        if (strpos($key, $mappingKey) !== false || strpos($mappingKey, $key) !== false) {
            return $path;
        }
    }
    
    // Fallback: Basierend auf dem Namen des DocumentType einen sinnvollen Pfad ableiten
    $nameLower = strtolower($name);
    
    if (strpos($nameLower, 'rechnung') !== false || strpos($nameLower, 'invoice') !== false) {
        return 'projekte/{project_number}/rechnungen';
    }
    if (strpos($nameLower, 'vertrag') !== false || strpos($nameLower, 'contract') !== false) {
        return 'projekte/{project_number}/vertraege';
    }
    if (strpos($nameLower, 'zertifikat') !== false || strpos($nameLower, 'certificate') !== false) {
        return 'projekte/{project_number}/zertifikate';
    }
    if (strpos($nameLower, 'genehmigung') !== false || strpos($nameLower, 'permit') !== false) {
        return 'projekte/{project_number}/genehmigungen';
    }
    if (strpos($nameLower, 'foto') !== false || strpos($nameLower, 'photo') !== false) {
        return 'projekte/{project_number}/fotos';
    }
    
    // Letzter Fallback: Sonstiges
    return 'projekte/{project_number}/sonstiges';
}
