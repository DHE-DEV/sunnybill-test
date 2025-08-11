<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database setup
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'database' => $_ENV['DB_DATABASE'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== Direkte Datenbank-Abfrage: Customer Daten ===\n\n";

// Direkte Abfrage des Test-Kunden
$customerId = '019889c5-2f74-7046-9674-289de55c684f';

$customer = Capsule::table('customers')
    ->where('id', $customerId)
    ->first();

if ($customer) {
    echo "✅ Kunde gefunden: {$customer->name}\n\n";
    
    echo "🔍 ALLE Datenbankfelder für diesen Kunden:\n";
    echo "------------------------------------------------\n";
    
    foreach (get_object_vars($customer) as $field => $value) {
        $displayValue = $value ?? 'NULL';
        if (is_string($value) && empty(trim($value))) {
            $displayValue = '(LEER)';
        }
        echo "- {$field}: {$displayValue}\n";
    }
    
    echo "\n📧 Wichtige Felder im Detail:\n";
    echo "------------------------------------------------\n";
    echo "- EMAIL: " . ($customer->email ?? 'NULL') . "\n";
    echo "- PHONE: " . ($customer->phone ?? 'NULL') . "\n"; 
    echo "- STREET: " . ($customer->street ?? 'NULL') . "\n";
    echo "- POSTAL_CODE: " . ($customer->postal_code ?? 'NULL') . "\n";
    echo "- CITY: " . ($customer->city ?? 'NULL') . "\n";
    echo "- FAX: " . ($customer->fax ?? 'NULL') . "\n";
    echo "- WEBSITE: " . ($customer->website ?? 'NULL') . "\n";
    
} else {
    echo "❌ Kunde nicht gefunden!\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

// Teste auch noch einen anderen Kunden
echo "\n=== Test mit anderem Kunden ===\n";

$allCustomers = Capsule::table('customers')
    ->select('id', 'name', 'email', 'phone', 'street', 'postal_code', 'city')
    ->limit(5)
    ->get();

foreach ($allCustomers as $cust) {
    echo "Kunde: {$cust->name}\n";
    echo "  - Email: " . ($cust->email ?? 'NULL') . "\n";
    echo "  - Phone: " . ($cust->phone ?? 'NULL') . "\n";
    echo "  - Address: " . ($cust->street ?? 'NULL') . " " . ($cust->postal_code ?? 'NULL') . " " . ($cust->city ?? 'NULL') . "\n\n";
}

echo "🎉 Datenbankprüfung abgeschlossen!\n";
