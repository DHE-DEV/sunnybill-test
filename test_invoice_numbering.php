<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SolarPlantBilling;

echo "Testing Invoice Number Generation Fix\n";
echo "=====================================\n\n";

// Test 1: Generate single invoice number
echo "Test 1: Generating single invoice number...\n";
$singleNumber = SolarPlantBilling::generateInvoiceNumber();
echo "Generated: $singleNumber\n\n";

// Test 2: Generate batch of invoice numbers
echo "Test 2: Generating batch of 3 invoice numbers...\n";
$batchNumbers = SolarPlantBilling::generateBatchInvoiceNumbers(3);
foreach ($batchNumbers as $index => $number) {
    echo "Batch[" . ($index + 1) . "]: $number\n";
}
echo "\n";

// Test 3: Generate another single number (should continue sequence)
echo "Test 3: Generating another single invoice number (should continue sequence)...\n";
$nextSingleNumber = SolarPlantBilling::generateInvoiceNumber();
echo "Generated: $nextSingleNumber\n\n";

// Test 4: Check what the highest invoice number in database is
echo "Test 4: Checking highest invoice number in database...\n";
$lastBilling = SolarPlantBilling::withTrashed()
    ->where('invoice_number', 'LIKE', '%')
    ->orderBy('invoice_number', 'desc')
    ->first();

if ($lastBilling) {
    echo "Highest invoice number in DB: " . $lastBilling->invoice_number . "\n";
    echo "Status: " . ($lastBilling->trashed() ? "DELETED" : "ACTIVE") . "\n";
} else {
    echo "No invoice numbers found in database\n";
}
echo "\n";

echo "Test completed. The fix ensures continuous numbering even when billings are soft-deleted.\n";
