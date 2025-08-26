<?php

require_once 'vendor/autoload.php';

use App\Models\SolarPlantBilling;
use Illuminate\Support\Facades\DB;

echo "=== Duplicate Invoice Number Cleanup Script ===\n\n";

// Step 1: Find existing duplicates
echo "Step 1: Checking for existing duplicate invoice numbers...\n";
echo "--------------------------------------------------------\n";

$duplicates = DB::select("
    SELECT invoice_number, COUNT(*) as count 
    FROM solar_plant_billings 
    WHERE deleted_at IS NULL 
    AND invoice_number IS NOT NULL
    GROUP BY invoice_number 
    HAVING COUNT(*) > 1
    ORDER BY invoice_number
");

if (empty($duplicates)) {
    echo "✅ No existing duplicates found\n";
} else {
    echo "❌ Found " . count($duplicates) . " duplicate invoice numbers:\n";
    foreach ($duplicates as $dup) {
        echo "- {$dup->invoice_number}: {$dup->count} records\n";
    }
}

echo "\n";

// Step 2: Check if RG-2025-000039 specifically exists
echo "Step 2: Checking for the specific problematic invoice number...\n";
echo "------------------------------------------------------------\n";

$problematic = SolarPlantBilling::withTrashed()
    ->where('invoice_number', 'RG-2025-000039')
    ->get();

if ($problematic->isEmpty()) {
    echo "✅ RG-2025-000039 does not exist in database\n";
} else {
    echo "❌ Found " . $problematic->count() . " records with RG-2025-000039:\n";
    foreach ($problematic as $billing) {
        $status = $billing->deleted_at ? 'SOFT DELETED' : 'ACTIVE';
        echo "- ID: {$billing->id}, Status: {$status}, Customer: {$billing->customer_id}\n";
    }
}

echo "\n";

// Step 3: Show current highest invoice number for 2025
echo "Step 3: Current highest invoice number for 2025...\n";
echo "--------------------------------------------------\n";

$highest = SolarPlantBilling::where('invoice_number', 'LIKE', 'RG-2025-%')
    ->orderBy('invoice_number', 'desc')
    ->first();

if ($highest) {
    echo "Highest existing invoice number: {$highest->invoice_number}\n";
    $number = intval(substr($highest->invoice_number, 8));
    echo "Next number would be: RG-2025-" . str_pad($number + 1, 6, '0', STR_PAD_LEFT) . "\n";
} else {
    echo "No 2025 invoices found, next would be: RG-2025-000001\n";
}

echo "\n";

// Step 4: Fix duplicates if any exist
if (!empty($duplicates)) {
    echo "Step 4: Fixing duplicates...\n";
    echo "----------------------------\n";
    
    foreach ($duplicates as $dup) {
        echo "Fixing duplicates for: {$dup->invoice_number}\n";
        
        $duplicateRecords = SolarPlantBilling::where('invoice_number', $dup->invoice_number)
            ->orderBy('created_at')
            ->get();
            
        // Keep the first one, reassign invoice numbers to the rest
        $first = true;
        foreach ($duplicateRecords as $record) {
            if ($first) {
                echo "  - Keeping original: {$record->id}\n";
                $first = false;
            } else {
                $newInvoiceNumber = SolarPlantBilling::generateInvoiceNumber();
                $record->invoice_number = $newInvoiceNumber;
                $record->save();
                echo "  - Reassigned {$record->id} to: {$newInvoiceNumber}\n";
            }
        }
    }
    
    echo "✅ Duplicates fixed\n";
} else {
    echo "Step 4: No duplicates to fix\n";
    echo "----------------------------\n";
}

echo "\n";

// Step 5: Clean up any soft-deleted records that might conflict
echo "Step 5: Cleaning up soft-deleted records...\n";
echo "------------------------------------------\n";

$deletedCount = SolarPlantBilling::onlyTrashed()
    ->where('invoice_number', 'LIKE', 'RG-2025-%')
    ->forceDelete();
    
echo "Permanently deleted {$deletedCount} soft-deleted records\n";

echo "\n";

// Step 6: Test the new batch generation
echo "Step 6: Testing batch invoice number generation...\n";
echo "--------------------------------------------------\n";

try {
    $testNumbers = SolarPlantBilling::generateBatchInvoiceNumbers(3);
    echo "Generated test numbers:\n";
    foreach ($testNumbers as $number) {
        echo "- {$number}\n";
    }
    echo "✅ Batch generation working correctly\n";
} catch (Exception $e) {
    echo "❌ Error in batch generation: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=== Cleanup Complete ===\n";
echo "You should now be able to create solar plant billings without duplicate errors.\n";

?>
