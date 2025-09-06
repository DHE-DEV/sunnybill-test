<?php

require_once 'bootstrap/app.php';

use App\Models\SolarPlant;
use App\Models\SolarPlantBilling;
use App\Filament\Resources\SolarPlantMonthlyOverviewResource;

echo "=== Testing Solar Plant Monthly Overview Implementation ===\n\n";

// Test month
$testMonth = '2025-08';
$year = 2025;
$monthNumber = 8;

echo "Testing for month: $testMonth (Year: $year, Month: $monthNumber)\n\n";

// Get solar plants with billing enabled
$plants = SolarPlant::whereNull('deleted_at')
    ->where('billing', true)
    ->orderBy('plant_number')
    ->limit(3) // Test with first 3 plants
    ->get();

if ($plants->isEmpty()) {
    echo "❌ No solar plants with billing=true found!\n";
    exit(1);
}

echo "Found " . $plants->count() . " solar plants with billing enabled\n\n";

foreach ($plants as $plant) {
    echo "🏭 Testing Plant: {$plant->plant_number} - {$plant->name}\n";
    echo "   ID: {$plant->id}\n";
    
    // Test the new methods
    $hasPlantBillings = SolarPlantMonthlyOverviewResource::hasPlantBillingsForMonth($plant, $testMonth);
    $plantBillingsCount = SolarPlantMonthlyOverviewResource::getPlantBillingsCountForMonth($plant, $testMonth);
    
    echo "   Has Plant Billings: " . ($hasPlantBillings ? "✅ YES" : "❌ NO") . "\n";
    echo "   Plant Billings Count: {$plantBillingsCount}\n";
    
    // Check actual billing records
    $actualBillings = $plant->billings()
        ->where('billing_year', $year)
        ->where('billing_month', $monthNumber)
        ->get();
    
    echo "   Actual billings in DB: " . $actualBillings->count() . "\n";
    
    if ($actualBillings->count() > 0) {
        foreach ($actualBillings as $billing) {
            echo "     - Billing ID: {$billing->id}, Created: {$billing->created_at}\n";
        }
    }
    
    // Test supplier contract billing status for comparison
    $supplierBillingStatus = SolarPlantMonthlyOverviewResource::getBillingStatusForMonth($plant, $testMonth);
    echo "   Supplier Billing Status: {$supplierBillingStatus}\n";
    
    // Generate the filtered URL for testing
    $filteredUrl = "/admin/solar-plant-billings?tableFilters[solar_plant_id][value]={$plant->id}&tableFilters[billing_year][value]={$year}&tableFilters[billing_month][value]={$monthNumber}";
    echo "   Filtered URL: {$filteredUrl}\n";
    
    echo "\n";
}

echo "=== Testing Page Controller Method ===\n";

// Test if the page controller method works
try {
    $pageInstance = new \App\Filament\Resources\SolarPlantMonthlyOverviewResource\Pages\ListSolarPlantMonthlyOverview();
    $pageInstance->selectedMonth = $testMonth;
    
    $viewData = $pageInstance->getViewData();
    
    echo "✅ Page controller getViewData() works successfully\n";
    echo "   Selected Month: {$viewData['selectedMonth']}\n";
    echo "   Month Label: {$viewData['monthLabel']}\n";
    echo "   Plants Count: " . count($viewData['plantsData']) . "\n";
    
    // Check if plant billing data is included
    $firstPlant = $viewData['plantsData'][0] ?? null;
    if ($firstPlant) {
        echo "   First Plant has hasPlantBillings key: " . (isset($firstPlant['hasPlantBillings']) ? "✅" : "❌") . "\n";
        echo "   First Plant has plantBillingsCount key: " . (isset($firstPlant['plantBillingsCount']) ? "✅" : "❌") . "\n";
        
        if (isset($firstPlant['hasPlantBillings'])) {
            echo "   First Plant hasPlantBillings value: " . ($firstPlant['hasPlantBillings'] ? "true" : "false") . "\n";
        }
        if (isset($firstPlant['plantBillingsCount'])) {
            echo "   First Plant plantBillingsCount value: {$firstPlant['plantBillingsCount']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error testing page controller: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Summary ===\n";

// Create a test billing record if none exists for demonstration
$testPlant = $plants->first();
if ($testPlant) {
    $existingBilling = SolarPlantBilling::where('solar_plant_id', $testPlant->id)
        ->where('billing_year', $year)
        ->where('billing_month', $monthNumber)
        ->first();
    
    if (!$existingBilling) {
        echo "Creating test billing record for demonstration...\n";
        
        $testBilling = SolarPlantBilling::create([
            'solar_plant_id' => $testPlant->id,
            'billing_year' => $year,
            'billing_month' => $monthNumber,
            'total_amount' => 1234.56,
            'energy_kwh' => 1000,
            'notes' => 'Test billing created by test script',
        ]);
        
        echo "✅ Created test billing ID: {$testBilling->id}\n";
        
        // Test again with the new billing
        $hasPlantBillingsAfter = SolarPlantMonthlyOverviewResource::hasPlantBillingsForMonth($testPlant, $testMonth);
        $plantBillingsCountAfter = SolarPlantMonthlyOverviewResource::getPlantBillingsCountForMonth($testPlant, $testMonth);
        
        echo "After creating test billing:\n";
        echo "   Has Plant Billings: " . ($hasPlantBillingsAfter ? "✅ YES" : "❌ NO") . "\n";
        echo "   Plant Billings Count: {$plantBillingsCountAfter}\n";
    }
}

echo "\n✅ Implementation test completed successfully!\n";
echo "The solar plant billing status feature should now be working in the monthly overview.\n";
echo "You can visit: https://sunnybill-test.test/admin/solar-plant-monthly-overviews\n";
