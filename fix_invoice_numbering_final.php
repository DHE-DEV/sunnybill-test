<?php

// Final fix for invoice numbering issue
// This handles the transition from "RG-2025-" format to empty prefix format

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\SolarPlantBilling;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FINAL INVOICE NUMBERING FIX ===" . PHP_EOL;

// 1. Check current state
echo "\n1. Current state analysis:" . PHP_EOL;
$companySettings = CompanySetting::current();
echo "Company prefix: '" . ($companySettings->invoice_number_prefix ?: '[empty]') . "'" . PHP_EOL;
echo "Include year: " . ($companySettings->invoice_number_include_year ? 'YES' : 'NO') . PHP_EOL;

// Check existing records
$totalBillings = SolarPlantBilling::withTrashed()->count();
$withRgPrefix = SolarPlantBilling::withTrashed()->where('invoice_number', 'LIKE', 'RG-2025-%')->count();
$withEmptyPrefix = SolarPlantBilling::withTrashed()->where('invoice_number', 'NOT LIKE', '%-%')->where('invoice_number', 'REGEXP', '^[0-9]+$')->count();

echo "Total billings: {$totalBillings}" . PHP_EOL;
echo "With 'RG-2025-' prefix: {$withRgPrefix}" . PHP_EOL;
echo "With empty prefix (numbers only): {$withEmptyPrefix}" . PHP_EOL;

// Get highest numbers for both formats
$highestRg = SolarPlantBilling::withTrashed()
    ->where('invoice_number', 'LIKE', 'RG-2025-%')
    ->orderBy('invoice_number', 'desc')
    ->value('invoice_number');

$highestEmpty = SolarPlantBilling::withTrashed()
    ->where('invoice_number', 'NOT LIKE', '%-%')
    ->where('invoice_number', 'REGEXP', '^[0-9]+$')
    ->orderByRaw('CAST(invoice_number AS UNSIGNED) desc')
    ->value('invoice_number');

echo "Highest RG-2025- number: " . ($highestRg ?: 'none') . PHP_EOL;
echo "Highest empty prefix number: " . ($highestEmpty ?: 'none') . PHP_EOL;

// 2. Determine the approach
echo "\n2. Recommended approach:" . PHP_EOL;

if ($withRgPrefix > 0 && $withEmptyPrefix == 0) {
    echo "RECOMMENDATION: Update company settings to continue with RG-2025- format" . PHP_EOL;
    echo "This maintains consistency with existing {$withRgPrefix} records." . PHP_EOL;
    
    $approach = 'update_settings';
} elseif ($withRgPrefix > 0 && $withEmptyPrefix > 0) {
    echo "MIXED FORMATS DETECTED: Need to standardize all records" . PHP_EOL;
    echo "Will migrate all to empty prefix format as per current settings." . PHP_EOL;
    
    $approach = 'migrate_to_empty';
} else {
    echo "CLEAN START: Continue with current empty prefix settings" . PHP_EOL;
    
    $approach = 'clean_start';
}

echo "\nProceed with this approach? (y/n): ";
$handle = fopen("php://stdin", "r");
$input = trim(fgets($handle));
fclose($handle);

if (strtolower($input) !== 'y') {
    echo "Operation cancelled." . PHP_EOL;
    exit;
}

// 3. Apply the fix
echo "\n3. Applying fix..." . PHP_EOL;

try {
    DB::beginTransaction();
    
    if ($approach === 'update_settings') {
        // Update company settings to match existing format
        $companySettings->update([
            'invoice_number_prefix' => 'RG',
            'invoice_number_include_year' => true
        ]);
        
        echo "✓ Updated company settings to: RG-2025-XXXXXX format" . PHP_EOL;
        
    } elseif ($approach === 'migrate_to_empty') {
        // Migrate all RG-2025- records to empty prefix format
        $rgRecords = SolarPlantBilling::withTrashed()
            ->where('invoice_number', 'LIKE', 'RG-2025-%')
            ->get();
            
        $nextNumber = $withEmptyPrefix > 0 ? intval($highestEmpty) + 1 : 1;
        
        foreach ($rgRecords as $record) {
            $newNumber = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            $record->update(['invoice_number' => $newNumber]);
            $nextNumber++;
        }
        
        echo "✓ Migrated {$rgRecords->count()} records from RG-2025- to empty prefix format" . PHP_EOL;
        
    } else {
        echo "✓ Keeping current settings (empty prefix)" . PHP_EOL;
    }
    
    DB::commit();
    echo "✓ Database changes committed" . PHP_EOL;
    
} catch (Exception $e) {
    DB::rollBack();
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// 4. Test the fix
echo "\n4. Testing invoice number generation:" . PHP_EOL;

try {
    // Test single number generation
    $singleNumber = SolarPlantBilling::generateInvoiceNumber();
    echo "Next single number: {$singleNumber}" . PHP_EOL;
    
    // Test batch generation
    $batchNumbers = SolarPlantBilling::generateBatchInvoiceNumbers(3);
    echo "Next batch numbers: " . implode(', ', $batchNumbers) . PHP_EOL;
    
    // Verify no duplicates in batch
    if (count($batchNumbers) === count(array_unique($batchNumbers))) {
        echo "✓ Batch generation produces unique numbers" . PHP_EOL;
    } else {
        echo "❌ Batch generation has duplicates!" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . PHP_EOL;
}

// 5. Final verification
echo "\n5. Final state:" . PHP_EOL;
$companySettings = CompanySetting::current();
echo "Company prefix: '" . ($companySettings->invoice_number_prefix ?: '[empty]') . "'" . PHP_EOL;
echo "Include year: " . ($companySettings->invoice_number_include_year ? 'YES' : 'NO') . PHP_EOL;

echo "\n=== FIX COMPLETED ===" . PHP_EOL;
echo "You can now create billings with multiple participations without duplicate invoice number errors." . PHP_EOL;
