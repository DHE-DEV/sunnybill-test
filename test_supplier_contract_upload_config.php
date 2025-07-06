<?php

require_once 'vendor/autoload.php';

use App\Models\SupplierContract;
use App\Services\DocumentUploadConfig;
use App\Services\DocumentFormBuilder;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: SupplierContract Upload-Konfiguration ===\n\n";

try {
    // 1. Hole einen SupplierContract
    $contract = SupplierContract::with(['supplier'])->first();
    
    if (!$contract) {
        echo "❌ Kein SupplierContract gefunden!\n";
        exit(1);
    }
    
    echo "1. Test-SupplierContract:\n";
    echo "   ID: " . $contract->id . "\n";
    echo "   Contract Number: " . ($contract->contract_number ?? 'N/A') . "\n";
    echo "   Supplier: " . ($contract->supplier?->company_name ?? 'N/A') . "\n";
    echo "   Supplier Number: " . ($contract->supplier?->supplier_number ?? 'N/A') . "\n\n";
    
    // 2. Erstelle DocumentUploadConfig
    echo "2. DocumentUploadConfig erstellen:\n";
    
    $config = DocumentUploadConfig::forSupplierContracts($contract)
        ->setAdditionalData([
            'supplier_contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'contract_internal_number' => $contract->contract_internal_number,
            'supplier_name' => $contract->supplier?->company_name,
            'supplier_number' => $contract->supplier?->supplier_number,
        ]);
    
    echo "   ✅ Config erstellt\n";
    echo "   Path Type: " . $config->get('pathType') . "\n";
    echo "   Model Class: " . get_class($config->get('model')) . "\n";
    echo "   Storage Directory: " . $config->getStorageDirectory() . "\n\n";
    
    // 3. Konvertiere zu Array (wie DocumentUploadTrait es macht)
    echo "3. Konvertierung zu Array (wie DocumentUploadTrait):\n";
    
    $configArray = $config->toArray();
    
    // Füge dynamische Properties hinzu
    $configArray['storageDirectory'] = $config->getStorageDirectory();
    $configArray['diskName'] = $config->getDiskName();
    $configArray['pathType'] = $config->get('pathType');
    $configArray['model'] = $config->get('model');
    $configArray['additionalData'] = $config->get('additionalData');
    
    echo "   ✅ Array erstellt\n";
    echo "   Keys: " . implode(', ', array_keys($configArray)) . "\n";
    echo "   Storage Directory: " . ($configArray['storageDirectory'] ?? 'nicht gesetzt') . "\n";
    echo "   Path Type: " . ($configArray['pathType'] ?? 'nicht gesetzt') . "\n";
    echo "   Has Model: " . (isset($configArray['model']) ? 'ja' : 'nein') . "\n";
    echo "   Model Class: " . (isset($configArray['model']) ? get_class($configArray['model']) : 'nicht gesetzt') . "\n\n";
    
    // 4. Teste DocumentFormBuilder
    echo "4. DocumentFormBuilder Test:\n";
    
    $formBuilder = DocumentFormBuilder::make($configArray);
    echo "   ✅ FormBuilder erstellt\n";
    
    // 5. Teste verschiedene Upload-Verzeichnisse
    echo "\n5. Test Upload-Verzeichnisse für verschiedene Kategorien:\n";
    
    $testCategories = [
        null => 'Standard (keine Kategorie)',
        'contracts' => 'Verträge',
        'correspondence' => 'Korrespondenz',
        'invoices' => 'Rechnungen'
    ];
    
    foreach ($testCategories as $category => $categoryName) {
        // Simuliere die Logik aus DocumentFormBuilder::createFileUploadField()
        if ($configArray['pathType'] && $configArray['model']) {
            $pathType = $configArray['pathType'];
            $model = $configArray['model'];
            $additionalData = array_merge(
                $configArray['additionalData'] ?? [],
                $category ? ['category' => $category] : []
            );
            
            $uploadDir = \App\Services\DocumentStorageService::getUploadDirectoryForModel(
                $pathType,
                $model,
                $additionalData
            );
            
            echo "   " . $categoryName . ": " . $uploadDir . "\n";
            
            // Prüfe Format
            if (str_starts_with($uploadDir, 'vertraege/')) {
                echo "     ✅ Verwendet neues Format\n";
            } else {
                echo "     ❌ Verwendet altes Format!\n";
            }
        } else {
            echo "   " . $categoryName . ": Fallback auf storageDirectory = " . ($configArray['storageDirectory'] ?? 'nicht gesetzt') . "\n";
        }
    }
    
    echo "\n✅ Test abgeschlossen!\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
