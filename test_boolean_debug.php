<?php

require_once 'vendor/autoload.php';

use App\Models\Customer;
use App\Services\LexofficeService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG: BOOLEAN-WERTE IN LEXOFFICE-DATEN ===\n\n";

// Suche einen Kunden mit Lexoffice-ID
$customer = Customer::whereNotNull('lexoffice_id')->first();

if (!$customer) {
    echo "❌ Kein Kunde mit Lexoffice-ID gefunden!\n";
    exit;
}

echo "✅ Kunde gefunden: {$customer->name}\n\n";

$lexofficeService = new LexofficeService();

// Verwende Reflection um private Methoden zu testen
$reflection = new ReflectionClass($lexofficeService);

echo "=== TEST 1: formatAddressForLexoffice() ===\n";
$formatMethod = $reflection->getMethod('formatAddressForLexoffice');
$formatMethod->setAccessible(true);

// Test mit isPrimary = true
$address1 = $formatMethod->invoke($lexofficeService, 
    'Teststraße 123', 
    null, 
    '12345', 
    'Teststadt', 
    null, 
    'Deutschland', 
    true
);

echo "Adresse mit isPrimary=true:\n";
echo "isPrimary Wert: " . var_export($address1['isPrimary'], true) . "\n";
echo "isPrimary Typ: " . gettype($address1['isPrimary']) . "\n";
echo "JSON: " . json_encode($address1, JSON_PRETTY_PRINT) . "\n\n";

// Test mit isPrimary = false
$address2 = $formatMethod->invoke($lexofficeService, 
    'Teststraße 456', 
    null, 
    '54321', 
    'Teststadt2', 
    null, 
    'Deutschland', 
    false
);

echo "Adresse mit isPrimary=false:\n";
echo "isPrimary Wert: " . var_export($address2['isPrimary'], true) . "\n";
echo "isPrimary Typ: " . gettype($address2['isPrimary']) . "\n";
echo "JSON: " . json_encode($address2, JSON_PRETTY_PRINT) . "\n\n";

echo "=== TEST 2: prepareCustomerAddresses() ===\n";
$prepareAddressesMethod = $reflection->getMethod('prepareCustomerAddresses');
$prepareAddressesMethod->setAccessible(true);

$addresses = $prepareAddressesMethod->invoke($lexofficeService, $customer);

echo "Alle Adressen:\n";
foreach ($addresses as $i => $addr) {
    echo "Adresse {$i}:\n";
    echo "  isPrimary Wert: " . var_export($addr['isPrimary'], true) . "\n";
    echo "  isPrimary Typ: " . gettype($addr['isPrimary']) . "\n";
    echo "  Street: {$addr['street']}\n";
}

echo "\nJSON aller Adressen:\n";
echo json_encode($addresses, JSON_PRETTY_PRINT) . "\n\n";

echo "=== TEST 3: prepareCustomerData() ===\n";
$prepareCustomerDataMethod = $reflection->getMethod('prepareCustomerData');
$prepareCustomerDataMethod->setAccessible(true);

$customerData = $prepareCustomerDataMethod->invoke($lexofficeService, $customer);

echo "Vollständige Kundendaten:\n";
if (isset($customerData['addresses'])) {
    foreach ($customerData['addresses'] as $i => $addr) {
        echo "Adresse {$i} in Kundendaten:\n";
        echo "  isPrimary Wert: " . var_export($addr['isPrimary'], true) . "\n";
        echo "  isPrimary Typ: " . gettype($addr['isPrimary']) . "\n";
    }
}

echo "\nJSON der vollständigen Kundendaten:\n";
echo json_encode($customerData, JSON_PRETTY_PRINT) . "\n\n";

echo "=== TEST 4: JSON-ENCODING VERHALTEN ===\n";

$testData = [
    'boolean_true' => true,
    'boolean_false' => false,
    'int_1' => 1,
    'int_0' => 0,
    'string_1' => '1',
    'string_empty' => '',
    'cast_true' => (bool) true,
    'cast_false' => (bool) false,
    'cast_1' => (bool) 1,
    'cast_0' => (bool) 0,
];

echo "Test verschiedener Werte:\n";
foreach ($testData as $key => $value) {
    echo "{$key}: " . var_export($value, true) . " (Typ: " . gettype($value) . ")\n";
}

echo "\nJSON-Encoding:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n";

echo "\n=== TEST ABGESCHLOSSEN ===\n";
