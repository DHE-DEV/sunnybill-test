<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Testing direct Customer model query...\n";

try {
    // Test the same query that was failing in the TaskApiController
    $customers = \App\Models\Customer::select('id', 'name', 'company_name')->limit(5)->get();
    
    echo "✅ Direct Customer model query works! Found " . count($customers) . " customers\n";
    
    if (count($customers) > 0) {
        echo "\n📋 Sample customers:\n";
        foreach ($customers as $customer) {
            echo "  - ID: {$customer->id}\n";
            echo "    Name: {$customer->name}\n";
            echo "    Company: " . ($customer->company_name ?: 'N/A') . "\n";
            echo "    ---\n";
        }
    }
    
    // Also test the query from the TaskApiController context
    echo "\n🔧 Testing TaskApiController query structure...\n";
    
    $query = \App\Models\Customer::select('id', 'name', 'company_name');
    
    // Simulate some potential filters that might be applied
    $query->where('deleted_at', null);
    
    $testCustomers = $query->limit(3)->get();
    
    echo "✅ TaskApiController query structure works! Found " . count($testCustomers) . " customers\n";
    
    // Test the raw SQL to see what's actually being generated
    $sql = \App\Models\Customer::select('id', 'name', 'company_name')->toSql();
    echo "\n🔍 Generated SQL: " . $sql . "\n";
    
} catch (\Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✅ Database structure fix verification complete!\n";
