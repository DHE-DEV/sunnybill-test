<?php

require_once 'vendor/autoload.php';

use App\Models\Customer;
use App\Services\LexofficeService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST: ROLES-OBJEKT FIX ===\n\n";

// Suche Max Mustermann
$customer = Customer::where('name', 'Max Mustermann')->first();

if (!$customer) {
    echo "❌ Max Mustermann nicht gefunden!\n";
    exit;
}

echo "✅ Kunde gefunden: {$customer->name}\n\n";

$lexofficeService = new LexofficeService();

// Verwende Reflection um private Methoden zu testen
$reflection = new ReflectionClass($lexofficeService);
$prepareCustomerDataMethod = $reflection->getMethod('prepareCustomerData');
$prepareCustomerDataMethod->setAccessible(true);

$customerData = $prepareCustomerDataMethod->invoke($lexofficeService, $customer);

echo "=== TESTE ROLES-STRUKTUR ===\n";
echo "Roles-Wert: " . var_export($customerData['roles'], true) . "\n";
echo "Customer-Wert: " . var_export($customerData['roles']['customer'], true) . "\n";
echo "Customer-Typ: " . gettype($customerData['roles']['customer']) . "\n\n";

echo "=== JSON-OUTPUT ===\n";
$json = json_encode($customerData, JSON_PRETTY_PRINT);
echo $json . "\n\n";

echo "=== PRÜFE SPEZIFISCH ===\n";
if (isset($customerData['roles']['customer'])) {
    $customer_role = $customerData['roles']['customer'];
    
    if (is_object($customer_role)) {
        echo "✅ Customer-Role ist ein Objekt\n";
        if (empty((array)$customer_role)) {
            echo "✅ Customer-Role ist ein leeres Objekt\n";
        } else {
            echo "❌ Customer-Role ist nicht leer: " . json_encode($customer_role) . "\n";
        }
    } elseif (is_array($customer_role)) {
        echo "❌ Customer-Role ist ein Array (sollte Objekt sein)\n";
        if (empty($customer_role)) {
            echo "   Aber es ist wenigstens leer\n";
        } else {
            echo "   Und es ist nicht leer: " . json_encode($customer_role) . "\n";
        }
    } else {
        echo "❌ Customer-Role ist weder Objekt noch Array: " . gettype($customer_role) . "\n";
    }
}

echo "\n=== ERWARTETES VS. AKTUELLES JSON ===\n";
echo "Erwartet: \"customer\": {}\n";
echo "Aktuell:  ";
if (preg_match('/"customer":\s*([^,}]+)/', $json, $matches)) {
    echo "\"customer\": " . trim($matches[1]) . "\n";
} else {
    echo "Nicht gefunden\n";
}

echo "\n=== TEST ABGESCHLOSSEN ===\n";
