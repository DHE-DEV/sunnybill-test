<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🔍 Checking customers table structure...\n";

try {
    // Check if customers table exists
    if (!Schema::hasTable('customers')) {
        echo "❌ Customers table does not exist!\n";
        exit(1);
    }
    
    echo "✅ Customers table exists\n";
    
    // Get actual table structure
    echo "\n📋 Current table structure:\n";
    $columns = DB::select("DESCRIBE customers");
    
    foreach ($columns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
    
    // Check for company_name column specifically
    $hasCompanyName = Schema::hasColumn('customers', 'company_name');
    echo "\n🔍 Company name column check:\n";
    echo "  - company_name exists: " . ($hasCompanyName ? "✅ YES" : "❌ NO") . "\n";
    
    if (!$hasCompanyName) {
        echo "\n⚠️  Missing company_name column! Running migration...\n";
        
        // Run the specific migration that adds company_name
        DB::statement("ALTER TABLE customers ADD COLUMN company_name VARCHAR(255) NULL AFTER name");
        DB::statement("ALTER TABLE customers ADD COLUMN contact_person VARCHAR(255) NULL AFTER company_name");
        DB::statement("ALTER TABLE customers ADD COLUMN website VARCHAR(255) NULL AFTER email");
        DB::statement("ALTER TABLE customers ADD COLUMN tax_number VARCHAR(255) NULL AFTER country");
        DB::statement("ALTER TABLE customers ADD COLUMN vat_id VARCHAR(255) NULL AFTER tax_number");
        DB::statement("ALTER TABLE customers ADD COLUMN notes TEXT NULL AFTER vat_id");
        DB::statement("ALTER TABLE customers ADD COLUMN lexoffice_synced_at DATETIME NULL AFTER lexoffice_id");
        DB::statement("ALTER TABLE customers ADD COLUMN is_active BOOLEAN DEFAULT 1 AFTER notes");
        DB::statement("ALTER TABLE customers ADD COLUMN customer_type ENUM('business','private') DEFAULT 'business' AFTER is_active");
        
        // Add indexes
        try {
            DB::statement("ALTER TABLE customers ADD INDEX idx_customer_type_is_active (customer_type, is_active)");
        } catch (\Exception $e) {
            echo "  - Index idx_customer_type_is_active might already exist: " . $e->getMessage() . "\n";
        }
        
        try {
            DB::statement("ALTER TABLE customers ADD INDEX idx_company_name (company_name)");
        } catch (\Exception $e) {
            echo "  - Index idx_company_name might already exist: " . $e->getMessage() . "\n";
        }
        
        echo "✅ Migration completed!\n";
        
        // Verify the fix
        if (Schema::hasColumn('customers', 'company_name')) {
            echo "✅ company_name column now exists!\n";
        } else {
            echo "❌ company_name column still missing after migration!\n";
        }
    }
    
    echo "\n🧹 Clearing Laravel caches...\n";
    
    // Clear various Laravel caches
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    echo "  - Config cache cleared\n";
    
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    echo "  - Route cache cleared\n";
    
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "  - View cache cleared\n";
    
    try {
        \Illuminate\Support\Facades\Artisan::call('model:clear');
        echo "  - Model cache cleared\n";
    } catch (\Exception $e) {
        echo "  - Model cache clear not available (Laravel < 11)\n";
    }
    
    echo "\n🧪 Testing the API endpoint...\n";
    
    // Test the actual query that was failing
    try {
        $customers = DB::table('customers')
            ->select('id', 'name', 'company_name')
            ->limit(1)
            ->get();
        
        echo "✅ Query with company_name works! Found " . count($customers) . " customer(s)\n";
        
        if (count($customers) > 0) {
            $customer = $customers[0];
            echo "  Sample customer: ID={$customer->id}, Name={$customer->name}, Company={$customer->company_name}\n";
        }
    } catch (\Exception $e) {
        echo "❌ Query still fails: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ Fix completed! Try accessing the API endpoint again.\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
