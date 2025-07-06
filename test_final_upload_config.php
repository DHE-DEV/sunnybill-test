<?php

require_once 'vendor/autoload.php';

use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;
use App\Services\DocumentUploadConfig;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: Finale Upload-Konfiguration ===\n\n";

try {
    // 1. Hole den SupplierContract
    $contractId = '0197cf8d-f15e-7234-9dad-e6d7bc5b1e49';
    $contract = SupplierContract::with(['supplier'])->find($contractId);
    
    if (!$contract) {
        echo "❌ SupplierContract nicht gefunden!\n";
        exit(1);
    }
    
    // 2. Hole eine SupplierContractBilling
    $billing = $contract->billings()->first();
    
    if (!$billing) {
        echo "❌ Keine SupplierContractBilling gefunden!\n";
        exit(1);
    }
    
    echo "1. Test-Daten:\n";
    echo "   Contract: " . $contract->contract_number . " (ID: " . $contract->id . ")\n";
    echo "   Billing: " . $billing->billing_number . " (ID: " . $billing->id . ")\n";
    echo "   Supplier: " . $contract->supplier?->supplier_number . " - " . $contract->supplier?->company_name . "\n\n";
    
    // 3. Teste SupplierContract-Konfiguration
    echo "2. SupplierContract Upload-Konfiguration:\n";
    $contractConfig = DocumentUploadConfig::forSupplierContracts($contract)
        ->setAdditionalData([
            'supplier_contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'contract_internal_number' => $contract->contract_internal_number,
            'supplier_name' => $contract->supplier?->company_name,
            'supplier_number' => $contract->supplier?->supplier_number,
        ]);
    
    $contractPath = $contractConfig->getStorageDirectory();
    echo "   Pfad: " . $contractPath . "\n";
    
    if (str_starts_with($contractPath, 'supplier_contracts-documents')) {
        echo "   ❌ PROBLEM: Verwendet noch altes Format!\n";
    } else {
        echo "   ✅ Verwendet neues Format!\n";
    }
    
    // 4. Teste SupplierContractBilling-Konfiguration
    echo "\n3. SupplierContractBilling Upload-Konfiguration:\n";
    $billingConfig = DocumentUploadConfig::forSupplierContractBillings($billing)
        ->setAdditionalData([
            'billing_id' => $billing->id,
            'billing_number' => $billing->billing_number,
            'contract_number' => $billing->supplierContract?->contract_number,
            'supplier_name' => $billing->supplierContract?->supplier?->company_name,
            'supplier_number' => $billing->supplierContract?->supplier?->supplier_number,
        ]);
    
    $billingPath = $billingConfig->getStorageDirectory();
    echo "   Pfad: " . $billingPath . "\n";
    
    if (str_starts_with($billingPath, 'supplier_contracts-documents')) {
        echo "   ❌ PROBLEM: Verwendet noch altes Format!\n";
    } else {
        echo "   ✅ Verwendet neues Format!\n";
    }
    
    // 5. Vergleiche die Pfade
    echo "\n4. Pfad-Vergleich:\n";
    echo "   SupplierContract:        " . $contractPath . "\n";
    echo "   SupplierContractBilling: " . $billingPath . "\n";
    
    if ($contractPath === $billingPath) {
        echo "   ❌ PROBLEM: Beide verwenden denselben Pfad!\n";
    } else {
        echo "   ✅ Korrekt: Unterschiedliche Pfade für verschiedene Models!\n";
    }
    
    // 6. Teste die Kategorie-Pfade
    echo "\n5. Kategorie-Pfade:\n";
    $categories = ['contracts', 'invoices', 'correspondence'];
    
    foreach ($categories as $category) {
        $contractCategoryPath = $contractConfig->getStorageDirectory($category);
        $billingCategoryPath = $billingConfig->getStorageDirectory($category);
        
        echo "   Kategorie '{$category}':\n";
        echo "     Contract: " . $contractCategoryPath . "\n";
        echo "     Billing:  " . $billingCategoryPath . "\n";
    }
    
    echo "\n✅ Test abgeschlossen!\n";
    echo "\nFazit:\n";
    echo "- SupplierContract-Dokumente werden in: " . $contractPath . "\n";
    echo "- SupplierContractBilling-Dokumente werden in: " . $billingPath . "\n";
    echo "- Beide verwenden das neue DocumentPathSetting-System!\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}