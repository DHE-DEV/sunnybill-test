<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Container\Container;
use App\Models\UserTablePreference;
use App\Traits\HasPersistentTableState;

class TestResource
{
    use HasPersistentTableState;
    
    public $tableFilters = [];
    public $tableSearch = '';
    public $tableSortColumn = '';
    public $tableSortDirection = '';
    
    public function mount(): void
    {
        // Override parent mount since we don't have a real parent
        $this->loadTableState();
    }
    
    protected function getTableName(): string
    {
        return 'test_table';
    }
}

echo "=== Filter Persistence Test ===\n\n";

// Test 1: Check configuration loading
echo "1. Testing configuration loading:\n";
$saveFilterEnabled = config('app.save_filter', false);
echo "SAVE_FILTER config value: " . ($saveFilterEnabled ? 'true' : 'false') . "\n";

if (!$saveFilterEnabled) {
    echo "⚠️  SAVE_FILTER is not enabled in config. Set SAVE_FILTER=true in your .env file.\n\n";
} else {
    echo "✅ SAVE_FILTER is enabled in config.\n\n";
}

// Test 2: Check if user_table_preferences table exists
echo "2. Testing database table existence:\n";
try {
    $tableExists = Schema::hasTable('user_table_preferences');
    if ($tableExists) {
        echo "✅ user_table_preferences table exists.\n";
        
        // Check columns
        $columns = Schema::getColumnListing('user_table_preferences');
        echo "📋 Table columns: " . implode(', ', $columns) . "\n";
    } else {
        echo "❌ user_table_preferences table does not exist.\n";
        echo "Run: php artisan migrate\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "\n";
}

echo "\n3. Testing UserTablePreference model:\n";
try {
    // Test saving preferences
    UserTablePreference::savePreferences(1, 'test_table', [
        'filters' => ['status' => 'active'],
        'search' => ['global' => 'test search'],
        'sort' => ['column' => 'name', 'direction' => 'asc']
    ]);
    echo "✅ Successfully saved test preferences.\n";
    
    // Test loading preferences
    $preferences = UserTablePreference::getPreferences(1, 'test_table');
    if ($preferences) {
        echo "✅ Successfully loaded preferences:\n";
        echo "   - Filters: " . json_encode($preferences['filters'] ?? []) . "\n";
        echo "   - Search: " . json_encode($preferences['search'] ?? []) . "\n";
        echo "   - Sort: " . json_encode($preferences['sort'] ?? []) . "\n";
    } else {
        echo "❌ Could not load preferences.\n";
    }
    
    // Clean up test data
    UserTablePreference::where('user_id', 1)->where('table_name', 'test_table')->delete();
    echo "✅ Cleaned up test data.\n";
    
} catch (Exception $e) {
    echo "❌ Error testing model: " . $e->getMessage() . "\n";
}

echo "\n4. Testing HasPersistentTableState trait:\n";
try {
    $testResource = new TestResource();
    
    // Test shouldSaveFilters method
    $reflection = new ReflectionClass($testResource);
    $method = $reflection->getMethod('shouldSaveFilters');
    $method->setAccessible(true);
    
    $shouldSave = $method->invoke($testResource);
    echo "shouldSaveFilters() returns: " . ($shouldSave ? 'true' : 'false') . "\n";
    
    if ($shouldSave) {
        echo "✅ Trait correctly detects that filter saving is enabled.\n";
    } else {
        echo "⚠️  Trait detects that filter saving is disabled.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing trait: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\nTo use this functionality:\n";
echo "1. Add 'SAVE_FILTER=true' to your .env file\n";
echo "2. Add 'use HasPersistentTableState;' to your Filament Resource classes\n";
echo "3. Make sure the migration has been run: php artisan migrate\n";
echo "\nThe trait will automatically save and restore:\n";
echo "- Table filters\n";
echo "- Search terms\n";
echo "- Sort column and direction\n";
echo "- Per user and per table\n";
