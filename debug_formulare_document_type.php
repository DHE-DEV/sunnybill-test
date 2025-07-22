<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentType;
use App\Models\DocumentPathSetting;
use App\Models\Project;

echo "Debug: Warum wird 'Formulare' als 'sonstiges' gemappt?\n";
echo str_repeat("=", 80) . "\n";

try {
    // 1. Finde DocumentType "Formulare"
    $documentType = DocumentType::where('name', 'Formulare')->first();
    if (!$documentType) {
        echo "ERROR: DocumentType 'Formulare' nicht gefunden!\n";
        return;
    }
    
    echo "🔍 DocumentType gefunden:\n";
    echo "- Name: {$documentType->name}\n";
    echo "- Key: {$documentType->key}\n";
    
    // 2. Aktuelle DocumentPathSetting für "Formulare"
    $currentSetting = DocumentPathSetting::where('documentable_type', 'App\Models\Project')
        ->where('category', $documentType->key)
        ->first();
    
    if ($currentSetting) {
        echo "\n✅ Aktuelle DocumentPathSetting:\n";
        echo "- ID: {$currentSetting->id}\n";
        echo "- Template: {$currentSetting->path_template}\n";
        echo "- Beschreibung: {$currentSetting->description}\n";
        
        echo "\n❌ PROBLEM: '{$documentType->name}' wird auf 'sonstiges' gemappt!\n";
        echo "Das ist nicht sinnvoll für Formulare.\n";
    } else {
        echo "\n❌ PROBLEM: Keine DocumentPathSetting für '{$documentType->key}' gefunden!\n";
    }
    
    // 3. Besserer Pfad für Formulare
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "💡 Verbesserungsvorschlag:\n";
    echo "Aktuell: projekte/{project_number}/sonstiges\n";
    echo "Besser:  projekte/{project_number}/formulare\n";
    
    // 4. Weitere DocumentTypes mit 'sonstiges' prüfen
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "📋 Alle Project DocumentPathSettings mit 'sonstiges':\n";
    
    $sonstigeSettings = DocumentPathSetting::where('documentable_type', 'App\Models\Project')
        ->where('path_template', 'like', '%/sonstiges')
        ->get();
    
    foreach ($sonstigeSettings as $setting) {
        $docType = DocumentType::where('key', $setting->category)->first();
        $docTypeName = $docType ? $docType->name : 'Unbekannt';
        echo "- {$setting->category} → {$docTypeName}\n";
    }
    
    echo "\n🤔 Analyse: Welche davon brauchen spezifischere Pfade?\n";
    
    // Mapping-Vorschläge
    $betterMappings = [
        'formulare' => 'projekte/{project_number}/formulare',
        'delivery_note' => 'projekte/{project_number}/lieferscheine',
        'ordering_material' => 'projekte/{project_number}/bestellungen',
        'commissioning' => 'projekte/{project_number}/inbetriebnahme',
        'legal_document' => 'projekte/{project_number}/rechtsdokumente',
        'information' => 'projekte/{project_number}/informationen',
    ];
    
    echo "\n💡 Verbesserungsvorschläge für spezifischere Pfade:\n";
    foreach ($betterMappings as $key => $path) {
        $docType = DocumentType::where('key', $key)->first();
        if ($docType) {
            echo "- {$docType->name} (Key: {$key})\n";
            echo "  Aktuell: projekte/{project_number}/sonstiges\n";
            echo "  Besser:  {$path}\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
