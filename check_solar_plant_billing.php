<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlant;

try {
    $plant = SolarPlant::find('0198cbb7-34e7-71b7-9a45-3d921dfdee30');
    
    if ($plant) {
        echo "=== Solar Plant Data ===" . PHP_EOL;
        echo "ID: " . $plant->id . PHP_EOL;
        echo "Name: " . $plant->name . PHP_EOL;
        echo "Status: " . $plant->status . PHP_EOL;
        echo "Billing (boolean): " . ($plant->billing ? 'true' : 'false') . PHP_EOL;
        echo "Billing (raw): " . var_export($plant->billing, true) . PHP_EOL;
        echo "Is Active: " . ($plant->is_active ? 'true' : 'false') . PHP_EOL;
        
        // Check if billing field exists in database columns
        echo PHP_EOL . "=== Column Check ===" . PHP_EOL;
        $columns = \Schema::getColumnListing('solar_plants');
        echo "Billing column exists: " . (in_array('billing', $columns) ? 'YES' : 'NO') . PHP_EOL;
        
        // Check fillable fields
        echo PHP_EOL . "=== Model Configuration ===" . PHP_EOL;
        echo "Fillable fields include billing: " . (in_array('billing', $plant->getFillable()) ? 'YES' : 'NO') . PHP_EOL;
        echo "Casts include billing: " . (array_key_exists('billing', $plant->getCasts()) ? 'YES' : 'NO') . PHP_EOL;
        
    } else {
        echo "Plant not found with ID: 0198cbb7-34e7-71b7-9a45-3d921dfdee30" . PHP_EOL;
        
        // Try to find any plant with this partial ID
        $plants = SolarPlant::where('id', 'like', '%0198cbb7%')->get();
        if ($plants->count() > 0) {
            echo PHP_EOL . "Found similar plants:" . PHP_EOL;
            foreach ($plants as $p) {
                echo "- " . $p->id . " | " . $p->name . PHP_EOL;
            }
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
