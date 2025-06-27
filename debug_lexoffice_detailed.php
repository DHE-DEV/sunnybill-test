<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Detaillierte Lexoffice-Analyse...\n\n";

// Test-Kunde abrufen
$customer = App\Models\Customer::first();
if (!$customer) {
    echo "❌ Kein Kunde gefunden\n";
    exit;
}

echo "👤 Test-Kunde:\n";
echo "   Name: {$customer->name}\n";
echo "   E-Mail: " . ($customer->email ?: 'NICHT GESETZT') . "\n";
echo "   Telefon: " . ($customer->phone ?: 'NICHT GESETZT') . "\n";
echo "   Straße: " . ($customer->street ?: 'NICHT GESETZT') . "\n";
echo "   PLZ: " . ($customer->postal_code ?: 'NICHT GESETZT') . "\n";
echo "   Stadt: " . ($customer->city ?: 'NICHT GESETZT') . "\n";
echo "   Land: " . ($customer->country ?: 'NICHT GESETZT') . "\n\n";

// Lexoffice-Service instanziieren
$lexofficeService = new App\Services\LexofficeService();

// Kundendaten vorbereiten (private Methode über Reflection aufrufen)
$reflection = new ReflectionClass($lexofficeService);
$method = $reflection->getMethod('prepareCustomerData');
$method->setAccessible(true);

$customerData = $method->invoke($lexofficeService, $customer);

echo "📤 Vorbereitete Kundendaten für Lexoffice:\n";
echo json_encode($customerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Alternative Datenstrukturen testen
echo "🚀 Test verschiedener Datenstrukturen:\n\n";

// Test 1: Minimale Struktur
echo "Test 1: Minimale Struktur\n";
$minimalData = [
    'roles' => [
        'customer' => []
    ],
    'company' => [
        'name' => 'Mustermann GmbH'
    ],
    'emailAddresses' => [
        [
            'emailAddress' => 'info@mustermann-gmbh.de'
        ]
    ]
];

echo "📤 Minimale Daten:\n";
echo json_encode($minimalData, JSON_PRETTY_PRINT) . "\n";

try {
    $client = new GuzzleHttp\Client([
        'base_uri' => 'https://api.lexoffice.io/v1/',
        'headers' => [
            'Authorization' => 'Bearer ' . config('services.lexoffice.api_key'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
        'timeout' => 30,
    ]);

    $response = $client->post('contacts', [
        'json' => $minimalData
    ]);

    echo "✅ Test 1 Erfolg! Status: " . $response->getStatusCode() . "\n";
    $responseData = json_decode($response->getBody()->getContents(), true);
    echo "📥 Antwort: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";

} catch (GuzzleHttp\Exception\RequestException $e) {
    echo "❌ Test 1 Fehler: " . $e->getResponse()->getStatusCode() . "\n";
    $responseBody = $e->getResponse()->getBody()->getContents();
    echo "📥 Fehler-Antwort: " . $responseBody . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 2: Person statt Company
echo "Test 2: Person statt Company\n";
$personData = [
    'roles' => [
        'customer' => []
    ],
    'person' => [
        'firstName' => 'Max',
        'lastName' => 'Mustermann'
    ],
    'emailAddresses' => [
        [
            'emailAddress' => 'info@mustermann-gmbh.de'
        ]
    ]
];

echo "📤 Person-Daten:\n";
echo json_encode($personData, JSON_PRETTY_PRINT) . "\n";

try {
    $response = $client->post('contacts', [
        'json' => $personData
    ]);

    echo "✅ Test 2 Erfolg! Status: " . $response->getStatusCode() . "\n";
    $responseData = json_decode($response->getBody()->getContents(), true);
    echo "📥 Antwort: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";

} catch (GuzzleHttp\Exception\RequestException $e) {
    echo "❌ Test 2 Fehler: " . $e->getResponse()->getStatusCode() . "\n";
    $responseBody = $e->getResponse()->getBody()->getContents();
    echo "📥 Fehler-Antwort: " . $responseBody . "\n";
}

echo "\n🎯 Detaillierte Analyse abgeschlossen!\n";