<?php

require_once 'vendor/autoload.php';

use App\Models\DocumentPathSetting;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Fix: SupplierContract Standard-Pfad korrigieren ===\n\n";

try {
    // 1. Finde die Standard DocumentPathSetting für SupplierContract
    $standardSetting = DocumentPathSetting::where('documentable_type', 'App\Models\SupplierContract')
        ->whereNull('category')
        ->first();
    
    if (!$standardSetting) {
        echo "❌ Standard DocumentPathSetting für SupplierContract nicht gefunden!\n";
        exit(1);
    }
    
    echo "1. Aktuelle Standard-Einstellung:\n";
    echo "   ID: " . $standardSetting->id . "\n";
    echo "   Documentable Type: " . $standardSetting->documentable_type . "\n";
    echo "   Category: " . ($standardSetting->category ?? 'NULL') . "\n";
    echo "   Aktueller Pfad: " . $standardSetting->path_template . "\n";
    echo "   Beschreibung: " . $standardSetting->description . "\n\n";
    
    // 2. Aktualisiere auf das neue Format
    $newTemplate = 'vertraege/{supplier_number}/{contract_number}';
    
    echo "2. Aktualisiere auf neues Format:\n";
    echo "   Neuer Pfad: " . $newTemplate . "\n";
    
    $standardSetting->update([
        'path_template' => $newTemplate,
        'description' => 'Standard-Pfad für Lieferantenvertrag-Dokumente',
        'updated_at' => now(),
    ]);
    
    echo "   ✅ Standard-Pfad aktualisiert!\n\n";
    
    // 3. Überprüfe alle SupplierContract DocumentPathSettings
    echo "3. Alle SupplierContract DocumentPathSettings:\n";
    
    $allSettings = DocumentPathSetting::where('documentable_type', 'App\Models\SupplierContract')
        ->orderBy('category')
        ->get();
    
    foreach ($allSettings as $setting) {
        $category = $setting->category ?? 'Standard';
        echo "   " . $category . ": " . $setting->path_template . "\n";
    }
    
    echo "\n✅ Korrektur abgeschlossen!\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
