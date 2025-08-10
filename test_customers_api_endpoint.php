<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Testing customers API endpoint...\n";

try {
    // Test the same endpoint that was failing
    $response = file_get_contents('https://demo.voltmaster.cloud/api/app/customers', false, stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Accept: application/json',
                'Content-Type: application/json'
            ],
            'ignore_errors' => true
        ]
    ]));
    
    if ($response === false) {
        echo "❌ Failed to fetch from API endpoint\n";
    } else {
        echo "✅ API endpoint responded successfully!\n";
        
        $data = json_decode($response, true);
        
        if (isset($data['success']) && $data['success']) {
            echo "✅ API returned success response\n";
            if (isset($data['data'])) {
                echo "📊 Found " . count($data['data']) . " customers\n";
                
                if (count($data['data']) > 0) {
                    $firstCustomer = $data['data'][0];
                    echo "  Sample customer:\n";
                    echo "    - ID: " . ($firstCustomer['id'] ?? 'N/A') . "\n";
                    echo "    - Name: " . ($firstCustomer['name'] ?? 'N/A') . "\n"; 
                    echo "    - Company: " . ($firstCustomer['company_name'] ?? 'N/A') . "\n";
                }
            }
        } else {
            echo "❌ API returned error response:\n";
            echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    
    // Try a local test using the Customer model directly
    echo "\n🔄 Testing with Customer model directly...\n";
    
    try {
        $customers = \App\Models\Customer::select('id', 'name', 'company_name')->limit(3)->get();
        
        echo "✅ Direct model query works! Found " . count($customers) . " customers\n";
        
        foreach ($customers as $customer) {
            echo "  - {$customer->name} (Company: {$customer->company_name})\n";
        }
        
    } catch (\Exception $e2) {
        echo "❌ Direct model query failed: " . $e2->getMessage() . "\n";
    }
}
