<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\SupplierContract;
use App\Models\DocumentPathSetting;
use Illuminate\Foundation\Application;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debug: DocumentPathSetting::getPathConfig ===\n\n";

try {
    // 1. Lade den SupplierContract
    $contract = SupplierContract::with('supplier')->find('0197cf8d-f15e-7234-9dad-e6d7bc5b1e49');
    
    if (!$contract) {
        echo "❌ SupplierContract nicht gefunden!\n";
        exit(1);
    }
    
    echo "1. Test-Contract geladen:\n";
    echo "   Contract: {$contract->contract_number}\n";
    echo "   Supplier: {$contract->supplier->supplier_number}\n\n";
    
    // 2. Teste DocumentPathSetting::getPathConfig
    $documentableType = get_class($contract);
    $category = 'contract';
    
    echo "2. Suche DocumentPathSetting:\n";
    echo "   DocumentableType: {$documentableType}\n";
    echo "   Category: {$category}\n\n";
    
    // 3. Direkte Datenbankabfrage
    echo "3. Alle DocumentPathSettings in der Datenbank:\n";
    $allSettings = DocumentPathSetting::all();
    foreach ($allSettings as $setting) {
        echo "   - Type: {$setting->documentable_type}, Category: " . ($setting->category ?? 'NULL') . ", Template: {$setting->path_template}\n";
    }
    echo "\n";
    
    // 4. Teste getPathConfig mit verschiedenen Parametern
    echo "4. getPathConfig Tests:\n";
    
    // Test A: Mit Kategorie
    $pathSetting = DocumentPathSetting::getPathConfig($documentableType, $category);
    echo "   A) Mit Category '{$category}': " . ($pathSetting ? "GEFUNDEN - {$pathSetting->path_template}" : "NICHT GEFUNDEN") . "\n";
    
    // Test B: Ohne Kategorie
    $pathSetting = DocumentPathSetting::getPathConfig($documentableType, null);
    echo "   B) Ohne Category: " . ($pathSetting ? "GEFUNDEN - {$pathSetting->path_template}" : "NICHT GEFUNDEN") . "\n";
    
    // Test C: Mit anderen Kategorien
    $testCategories = ['invoice', 'certificate', 'manual', 'other'];
    foreach ($testCategories as $testCategory) {
        $pathSetting = DocumentPathSetting::getPathConfig($documentableType, $testCategory);
        echo "   C) Mit Category '{$testCategory}': " . ($pathSetting ? "GEFUNDEN - {$pathSetting->path_template}" : "NICHT GEFUNDEN") . "\n";
    }
    
    echo "\n";
    
    // 5. Teste die SQL-Query direkt
    echo "5. Direkte SQL-Query:\n";
    $query = DocumentPathSetting::where('documentable_type', $documentableType);
    echo "   Base Query Count: " . $query->count() . "\n";
    
    $queryWithCategory = DocumentPathSetting::where('documentable_type', $documentableType)
        ->where('category', $category);
    echo "   Mit Category '{$category}': " . $queryWithCategory->count() . "\n";
    
    $queryWithNullCategory = DocumentPathSetting::where('documentable_type', $documentableType)
        ->whereNull('category');
    echo "   Mit NULL Category: " . $queryWithNullCategory->count() . "\n";
    
    echo "\n✅ Debug abgeschlossen!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}