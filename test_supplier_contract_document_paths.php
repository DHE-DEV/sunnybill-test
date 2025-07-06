<?php

require_once 'vendor/autoload.php';

use App\Models\SupplierContract;
use App\Services\DocumentUploadConfig;
use App\Services\DocumentStorageService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: SupplierContract DocumentPathSettings Integration ===\n\n";

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
    echo "   Internal Number: " . ($contract->contract_internal_number ?? 'N/A') . "\n";
    echo "   Supplier: " . ($contract->supplier?->company_name ?? 'N/A') . "\n";
    echo "   Supplier Number: " . ($contract->supplier?->supplier_number ?? 'N/A') . "\n\n";
    
    // 2. Teste DocumentUploadConfig::forSupplierContracts()
    echo "2. DocumentUploadConfig::forSupplierContracts() Test:\n";
    
    $config = DocumentUploadConfig::forSupplierContracts($contract)
        ->setAdditionalData([
            'supplier_contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'contract_internal_number' => $contract->contract_internal_number,
            'supplier_name' => $contract->supplier?->company_name,
            'supplier_number' => $contract->supplier?->supplier_number,
        ]);
    
    echo "   Path Type: " . $config->get('pathType') . "\n";
    echo "   Model Class: " . get_class($config->get('model')) . "\n";
    echo "   Storage Directory: " . $config->getStorageDirectory() . "\n";
    echo "   Disk Name: " . $config->getDiskName() . "\n\n";
    
    // 3. Teste Pfad-Vorschau
    echo "3. Pfad-Vorschau:\n";
    $preview = $config->previewPath();
    
    echo "   Resolved Path: " . $preview['resolved_path'] . "\n";
    echo "   Template: " . $preview['template'] . "\n";
    echo "   Is Fallback: " . ($preview['is_fallback'] ? 'Ja' : 'Nein') . "\n";
    
    if (!empty($preview['placeholders_used'])) {
        echo "   Verwendete Platzhalter:\n";
        foreach ($preview['placeholders_used'] as $placeholder => $value) {
            echo "     {" . $placeholder . "} => " . $value . "\n";
        }
    }
    echo "\n";
    
    // 4. Teste verschiedene Kategorien
    echo "4. Test verschiedener Kategorien:\n";
    
    $testCategories = [
        null => 'Standard',
        'contracts' => 'Verträge',
        'correspondence' => 'Korrespondenz',
        'invoices' => 'Rechnungen'
    ];
    
    foreach ($testCategories as $category => $categoryName) {
        $additionalData = [
            'supplier_contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'contract_internal_number' => $contract->contract_internal_number,
            'supplier_name' => $contract->supplier?->company_name,
            'supplier_number' => $contract->supplier?->supplier_number,
        ];
        
        if ($category) {
            $additionalData['category'] = $category;
        }
        
        $uploadDir = DocumentStorageService::getUploadDirectoryForModel(
            'supplier_contracts',
            $contract,
            $additionalData
        );
        
        echo "   " . $categoryName . ": " . $uploadDir . "\n";
        
        // Prüfe ob das neue Format verwendet wird
        if (str_starts_with($uploadDir, 'vertraege/')) {
            echo "     ✅ Verwendet neues Format\n";
        } else {
            echo "     ❌ Verwendet altes Format!\n";
        }
    }
    
    // 5. Teste direkte DocumentStorageService Aufrufe
    echo "\n5. Direkte DocumentStorageService Tests:\n";
    
    // Standard-Pfad
    $standardPath = DocumentStorageService::getUploadDirectoryForModel(
        'supplier_contracts',
        $contract,
        [
            'supplier_number' => $contract->supplier?->supplier_number,
            'contract_number' => $contract->contract_number,
        ]
    );
    echo "   Standard: " . $standardPath . "\n";
    
    // Mit Jahr für Rechnungen
    $invoicePath = DocumentStorageService::getUploadDirectoryForModel(
        'supplier_contracts',
        $contract,
        [
            'supplier_number' => $contract->supplier?->supplier_number,
            'contract_number' => $contract->contract_number,
            'category' => 'invoices',
            'year' => date('Y'),
        ]
    );
    echo "   Rechnungen (mit Jahr): " . $invoicePath . "\n";
    
    echo "\n✅ Test abgeschlossen!\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
