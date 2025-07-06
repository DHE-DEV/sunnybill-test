<?php

require_once 'vendor/autoload.php';

use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;
use App\Services\DocumentUploadConfig;
use App\Services\DocumentStorageService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debug: Upload-Kontext-Problem ===\n\n";

try {
    // 1. Hole den SupplierContract
    $contractId = '0197cf8d-f15e-7234-9dad-e6d7bc5b1e49';
    $contract = SupplierContract::with(['supplier'])->find($contractId);
    
    if (!$contract) {
        echo "❌ SupplierContract nicht gefunden!\n";
        exit(1);
    }
    
    echo "1. SupplierContract:\n";
    echo "   ID: " . $contract->id . "\n";
    echo "   Contract Number: " . $contract->contract_number . "\n";
    echo "   Supplier Number: " . ($contract->supplier?->supplier_number ?? 'N/A') . "\n\n";
    
    // 2. Hole eine SupplierContractBilling
    $billing = $contract->billings()->first();
    
    if (!$billing) {
        echo "❌ Keine SupplierContractBilling gefunden!\n";
        exit(1);
    }
    
    echo "2. SupplierContractBilling:\n";
    echo "   ID: " . $billing->id . "\n";
    echo "   Billing Number: " . $billing->billing_number . "\n";
    echo "   Billing Period: " . ($billing->billing_period ?? 'N/A') . "\n\n";
    
    // 3. Teste beide Konfigurationen
    echo "3. Vergleiche Upload-Konfigurationen:\n\n";
    
    // A) SupplierContract-Konfiguration (falsch für Billing-Dokumente)
    echo "A) SupplierContract-Konfiguration:\n";
    $contractConfig = DocumentUploadConfig::forSupplierContracts($contract)
        ->setAdditionalData([
            'supplier_contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'supplier_number' => $contract->supplier?->supplier_number,
        ]);
    
    $contractPath = $contractConfig->getStorageDirectory();
    echo "   Pfad: " . $contractPath . "\n";
    
    if (str_starts_with($contractPath, 'supplier_contracts-documents')) {
        echo "   ❌ Verwendet altes Format!\n";
    } else {
        echo "   ✅ Verwendet neues Format\n";
    }
    
    // B) SupplierContractBilling-Konfiguration (korrekt)
    echo "\nB) SupplierContractBilling-Konfiguration:\n";
    $billingConfig = DocumentUploadConfig::forSupplierContractBillings($billing)
        ->setAdditionalData([
            'billing_id' => $billing->id,
            'billing_number' => $billing->billing_number,
            'contract_number' => $billing->supplierContract?->contract_number,
            'supplier_number' => $billing->supplierContract?->supplier?->supplier_number,
        ]);
    
    $billingPath = $billingConfig->getStorageDirectory();
    echo "   Pfad: " . $billingPath . "\n";
    
    if (str_starts_with($billingPath, 'supplier_contracts-documents')) {
        echo "   ❌ Verwendet altes Format!\n";
    } else {
        echo "   ✅ Verwendet neues Format\n";
    }
    
    // 4. Prüfe DocumentPathSetting-Zuordnung
    echo "\n4. DocumentPathSetting-Zuordnung:\n";
    
    $contractPathSetting = \App\Models\DocumentPathSetting::getPathConfig('App\Models\SupplierContract');
    $billingPathSetting = \App\Models\DocumentPathSetting::getPathConfig('App\Models\SupplierContractBilling');
    
    echo "   SupplierContract PathSetting: " . ($contractPathSetting ? $contractPathSetting->path_template : 'NICHT GEFUNDEN') . "\n";
    echo "   SupplierContractBilling PathSetting: " . ($billingPathSetting ? $billingPathSetting->path_template : 'NICHT GEFUNDEN') . "\n";
    
    // 5. Teste DocumentStorageService direkt
    echo "\n5. DocumentStorageService direkt:\n";
    
    $directContractPath = DocumentStorageService::getUploadDirectoryForModel('supplier_contracts', $contract);
    $directBillingPath = DocumentStorageService::getUploadDirectoryForModel('supplier_contract_billings', $billing);
    
    echo "   supplier_contracts: " . $directContractPath . "\n";
    echo "   supplier_contract_billings: " . $directBillingPath . "\n";
    
    // 6. Das Problem identifizieren
    echo "\n6. Problem-Analyse:\n";
    
    if ($contractPath === $billingPath) {
        echo "   ✅ Beide Konfigurationen verwenden denselben Pfad\n";
    } else {
        echo "   ❌ PROBLEM: Unterschiedliche Pfade!\n";
        echo "       Contract: " . $contractPath . "\n";
        echo "       Billing:  " . $billingPath . "\n";
        echo "\n   URSACHE: Upload erfolgt über SupplierContract-Kontext,\n";
        echo "            aber Dokument wird an SupplierContractBilling angehängt!\n";
    }
    
    echo "\n✅ Debug abgeschlossen!\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}