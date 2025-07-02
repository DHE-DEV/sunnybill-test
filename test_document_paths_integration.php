<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;
use App\Services\DocumentUploadConfig;
use App\Services\DocumentStorageService;

echo "\n=== Test der DocumentPathSettings Integration ===\n\n";

// Test 1: Customer
echo "1. Customer Test:\n";
$customer = Customer::first();
if ($customer) {
    $config = DocumentUploadConfig::forClients()
        ->setModel($customer);
    
    echo "   Model: " . get_class($customer) . "\n";
    echo "   Customer Number: {$customer->customer_number}\n";
    echo "   Customer Name: {$customer->name}\n";
    echo "   Storage Directory: " . $config->getStorageDirectory() . "\n";
    echo "   Path Preview: " . json_encode($config->previewPath(), JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   Kein Customer gefunden\n";
}

echo "\n2. Supplier Test:\n";
$supplier = Supplier::first();
if ($supplier) {
    $config = DocumentUploadConfig::forSuppliers()
        ->setModel($supplier);
    
    echo "   Model: " . get_class($supplier) . "\n";
    echo "   Supplier Number: {$supplier->supplier_number}\n";
    echo "   Company Name: {$supplier->company_name}\n";
    echo "   Storage Directory: " . $config->getStorageDirectory() . "\n";
    echo "   Path Preview: " . json_encode($config->previewPath(), JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   Kein Supplier gefunden\n";
}

echo "\n3. SupplierContract Test:\n";
$contract = SupplierContract::first();
if ($contract) {
    $config = DocumentUploadConfig::forSupplierContracts()
        ->setModel($contract);
    
    echo "   Model: " . get_class($contract) . "\n";
    echo "   Contract Number: {$contract->contract_number}\n";
    echo "   Storage Directory: " . $config->getStorageDirectory() . "\n";
    echo "   Path Preview: " . json_encode($config->previewPath(), JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   Kein SupplierContract gefunden\n";
}

echo "\n4. SupplierContractBilling Test:\n";
$billing = SupplierContractBilling::first();
if ($billing) {
    $config = (new DocumentUploadConfig())
        ->setModel($billing);
    
    echo "   Model: " . get_class($billing) . "\n";
    echo "   Billing Number: {$billing->billing_number}\n";
    echo "   Storage Directory: " . $config->getStorageDirectory() . "\n";
    echo "   Path Preview: " . json_encode($config->previewPath(), JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   Kein SupplierContractBilling gefunden\n";
}

echo "\n5. Direkte Pfadgenerierung mit DocumentStorageService:\n";
if ($customer) {
    $path = DocumentStorageService::getUploadDirectoryForModel('customers', $customer);
    echo "   Customer Path: $path\n";
}
if ($supplier) {
    $path = DocumentStorageService::getUploadDirectoryForModel('suppliers', $supplier);
    echo "   Supplier Path: $path\n";
}
if ($contract) {
    $path = DocumentStorageService::getUploadDirectoryForModel('supplier_contracts', $contract);
    echo "   Contract Path: $path\n";
}
if ($billing) {
    $path = DocumentStorageService::getUploadDirectoryForModel('supplier_contract_billings', $billing);
    echo "   Billing Path: $path\n";
}

echo "\n";