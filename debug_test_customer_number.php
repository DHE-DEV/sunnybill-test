<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Customer;
use App\Models\CompanySetting;

class DebugTest extends TestCase
{
    use RefreshDatabase;

    public function debug()
    {
        echo "=== Test Environment Debug ===\n";
        
        // Erstelle CompanySetting wie im Test
        $cs = CompanySetting::factory()->create([
            'customer_number_prefix' => 'K',
        ]);
        
        echo "CompanySetting ID: " . $cs->id . "\n";
        echo "Prefix: '" . $cs->customer_number_prefix . "'\n";
        
        // Teste current() Methode
        $current = CompanySetting::current();
        echo "Current CompanySetting ID: " . ($current ? $current->id : 'null') . "\n";
        echo "Current Prefix: '" . ($current ? $current->customer_number_prefix : 'null') . "'\n";
        
        // Teste Kundennummer-Generierung
        $generated = $current ? $current->generateCustomerNumber(1) : 'no current setting';
        echo "Generated Number: '$generated'\n";
        
        // Teste Customer Erstellung
        $customer = Customer::create([
            'customer_type' => 'private',
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);
        
        echo "Customer Number: '" . $customer->customer_number . "'\n";
    }
}

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Set test environment
$app['env'] = 'testing';
putenv('APP_ENV=testing');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');

// Run debug
$test = new DebugTest();
$test->setUp();
$test->debug();