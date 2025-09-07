<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\OnceApiService;
use App\Models\SimCard;
use Illuminate\Support\Facades\Log;

echo "=== SIM Card Import Test ===\n\n";

try {
    // 1. Test OnceApiService
    echo "1. Testing OnceApiService...\n";
    $apiService = new OnceApiService();
    
    // Test connection first
    if (!$apiService->testConnection()) {
        echo "   ✗ API connection failed\n";
        exit(1);
    }
    echo "   ✓ API connection successful\n";
    
    // Get SIM cards from API
    echo "\n2. Fetching SIM cards from 1nce API...\n";
    $simCardsData = $apiService->getSimCards();
    
    if (empty($simCardsData)) {
        echo "   ✗ No SIM cards retrieved from API\n";
        exit(1);
    }
    
    echo "   ✓ Retrieved " . count($simCardsData) . " SIM cards from API\n";
    
    // Show sample data
    if (isset($simCardsData[0])) {
        echo "   Sample data:\n";
        $sample = $simCardsData[0];
        foreach (['iccid', 'msisdn', 'imsi', 'provider', 'status'] as $field) {
            echo "     - {$field}: " . ($sample[$field] ?? 'N/A') . "\n";
        }
    }
    
    // 3. Test database import
    echo "\n3. Testing database import...\n";
    
    $importedCount = 0;
    $skippedCount = 0;
    
    foreach ($simCardsData as $simData) {
        // Check if SIM card already exists
        $existingSim = SimCard::where('iccid', $simData['iccid'])->first();
        
        if ($existingSim) {
            echo "   - Skipping existing SIM: {$simData['iccid']}\n";
            $skippedCount++;
            continue;
        }
        
        // Create new SIM card
        try {
            $simCard = SimCard::create($simData);
            echo "   ✓ Imported SIM: {$simCard->iccid} (Status: {$simCard->status})\n";
            $importedCount++;
        } catch (Exception $e) {
            echo "   ✗ Failed to import SIM {$simData['iccid']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n4. Import Summary:\n";
    echo "   - Imported: {$importedCount} SIM cards\n";
    echo "   - Skipped: {$skippedCount} SIM cards\n";
    echo "   - Total in database: " . SimCard::count() . "\n";
    
    // 5. Verify database contents
    echo "\n5. Database verification:\n";
    $allSims = SimCard::all();
    
    foreach ($allSims as $sim) {
        echo "   - {$sim->iccid}: {$sim->msisdn} ({$sim->status}) - {$sim->provider}\n";
    }
    
    echo "\n✓ SIM card import test completed successfully!\n";

} catch (Exception $e) {
    echo "\n✗ Import test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Test Complete ===\n";
