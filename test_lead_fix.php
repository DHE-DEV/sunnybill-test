<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Customer;

// Bootstrap the Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Test creating a customer with customer_type = 'lead'
    $testCustomer = new Customer([
        'name' => 'Test Lead Company',
        'company_name' => 'Tech Solutions GmbH',
        'contact_person' => 'Anna Schmidt',
        'department' => 'Einkauf',
        'email' => 'anna.schmidt@tech-solutions.de',
        'phone' => '+49 40 987654321',
        'website' => 'https://www.tech-solutions.de',
        'street' => 'Innovationsallee 42',
        'postal_code' => '20095',
        'city' => 'Hamburg',
        'state' => 'Hamburg',
        'country' => 'Deutschland',
        'country_code' => 'DE',
        'customer_type' => 'lead',
        'ranking' => 'A',
        'notes' => 'Großes Potenzial für Solaranlagen-Projekt',
        'is_active' => true,
    ]);

    // Save the customer to test the database constraint
    $testCustomer->save();
    
    echo "✅ SUCCESS: Customer with type 'lead' created successfully!\n";
    echo "Customer ID: " . $testCustomer->id . "\n";
    echo "Customer Type: " . $testCustomer->customer_type . "\n";
    echo "Display Name: " . $testCustomer->display_name . "\n";
    echo "Is Lead: " . ($testCustomer->isLead() ? 'Yes' : 'No') . "\n";
    
    // Clean up test data
    $testCustomer->delete();
    echo "✅ Test customer deleted successfully\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
