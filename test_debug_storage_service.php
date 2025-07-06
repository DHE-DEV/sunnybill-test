<?php

require_once 'vendor/autoload.php';

use App\Models\SupplierContract;
use App\Services\DocumentStorageService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debug: DocumentStorageService ===\n\n";

try {
    // 1. Hole den SupplierContract
    $contractId = '0197cf8d-f15e-7234-9dad-e6d7bc5b1e49';
    $contract = SupplierContract::with(['supplier'])->find($contractId);
    
    if (!$contract) {
        echo "❌ SupplierContract nicht gefunden!\n";
        exit(1);
    }
    
    echo "1. Test-Daten:\n";
    echo "   Contract: " . $contract->contract_number . "\n";
    echo "   Supplier: " . $contract->supplier?->supplier_number . "\n\n";
    
    // 2. Teste DocumentStorageService direkt
    echo "2. DocumentStorageService::getUploadDirectoryForModel Tests:\n\n";
    
    // Test mit 'supplier_contracts'
    echo "A) pathType = 'supplier_contracts':\n";
    try {
        $path1 = DocumentStorageService::getUploadDirectoryForModel('supplier_contracts', $contract);
        echo "   Pfad: " . $path1 . "\n";
    } catch (Exception $e) {
        echo "   ❌ Fehler: " . $e->getMessage() . "\n";
    }
    
    // Test mit 'SupplierContract' (Model-Name)
    echo "\nB) pathType = 'SupplierContract':\n";
    try {
        $path2 = DocumentStorageService::getUploadDirectoryForModel('SupplierContract', $contract);
        echo "   Pfad: " . $path2 . "\n";
    } catch (Exception $e) {
        echo "   ❌ Fehler: " . $e->getMessage() . "\n";
    }
    
    // Test mit 'App\\Models\\SupplierContract' (vollständiger Klassenname)
    echo "\nC) pathType = 'App\\Models\\SupplierContract':\n";
    try {
        $path3 = DocumentStorageService::getUploadDirectoryForModel('App\\Models\\SupplierContract', $contract);
        echo "   Pfad: " . $path3 . "\n";
    } catch (Exception $e) {
        echo "   ❌ Fehler: " . $e->getMessage() . "\n";
    }
    
    // 3. Prüfe DocumentPathSettings direkt
    echo "\n3. DocumentPathSetting::getPathConfig Tests:\n\n";
    
    $pathConfig = \App\Models\DocumentPathSetting::getPathConfig('App\\Models\\SupplierContract');
    if ($pathConfig) {
        echo "   PathConfig gefunden:\n";
        echo "   Template: " . $pathConfig->path_template . "\n";
        echo "   Placeholders: " . json_encode($pathConfig->placeholders) . "\n";
    } else {
        echo "   ❌ Keine PathConfig für 'App\\Models\\SupplierContract' gefunden!\n";
    }
    
    // 4. Teste die Platzhalter-Ersetzung
    echo "\n4. Platzhalter-Test:\n";
    
    $additionalData = [
        'supplier_contract_id' => $contract->id,
        'contract_number' => $contract->contract_number,
        'contract_internal_number' => $contract->contract_internal_number,
        'supplier_name' => $contract->supplier?->company_name,
        'supplier_number' => $contract->supplier?->supplier_number,
    ];
    
    echo "   Additional Data: " . json_encode($additionalData, JSON_PRETTY_PRINT) . "\n";
    
    // Test mit zusätzlichen Daten
    try {
        $path4 = DocumentStorageService::getUploadDirectoryForModel('supplier_contracts', $contract, $additionalData);
        echo "   Pfad mit Additional Data: " . $path4 . "\n";
    } catch (Exception $e) {
        echo "   ❌ Fehler: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ Debug abgeschlossen!\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}