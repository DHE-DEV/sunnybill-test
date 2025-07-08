<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

echo "=== DIREKTER LEXOFFICE TEST ===\n\n";

$customer = Customer::first();
if (!$customer) {
    echo "Kein Kunde gefunden.\n";
    exit;
}

echo "Teste mit Kunde: {$customer->name}\n";
echo "Email: " . ($customer->email ?: 'LEER') . "\n";
echo "Telefon: " . ($customer->phone ?: 'LEER') . "\n";
echo "Straße: " . ($customer->street ?: 'LEER') . "\n";
echo "PLZ: " . ($customer->postal_code ?: 'LEER') . "\n";
echo "Stadt: " . ($customer->city ?: 'LEER') . "\n";
echo "Land: " . ($customer->country ?: 'LEER') . "\n\n";

// Kundendaten vorbereiten
$customerName = trim($customer->name);
$data = [
    'roles' => [
        'customer' => (object)[]
    ]
];

// Name aufteilen
$nameParts = explode(' ', $customerName, 2);
$firstName = trim($nameParts[0] ?? '');
$lastName = trim($nameParts[1] ?? '');

if (empty($firstName)) {
    $firstName = $customerName;
    $lastName = '';
}

$data['person'] = [
    'firstName' => $firstName,
    'lastName' => $lastName
];

// Adresse hinzufügen
if (!empty($customer->street) || !empty($customer->city)) {
    $address = [
        'isPrimary' => true
    ];
    
    if (!empty($customer->street)) {
        $address['street'] = trim($customer->street);
    }
    
    if (!empty($customer->postal_code)) {
        $cleanPostalCode = preg_replace('/[^0-9]/', '', $customer->postal_code);
        if (strlen($cleanPostalCode) === 5) {
            $address['zip'] = $cleanPostalCode;
        }
    }
    
    if (!empty($customer->city)) {
        $address['city'] = trim($customer->city);
    }
    
    $address['countryCode'] = 'DE';
    $data['addresses'] = [$address];
}

echo "Zu sendende Daten:\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

// HTTP-Client erstellen
$apiKey = config('services.lexoffice.api_key');
$client = new Client([
    'base_uri' => 'https://api.lexoffice.io/v1/',
    'headers' => [
        'Authorization' => 'Bearer ' . $apiKey,
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],
    'timeout' => 30,
]);

try {
    echo "Sende Anfrage an Lexoffice...\n";
    $response = $client->post('contacts', [
        'json' => $data
    ]);
    
    $responseData = json_decode($response->getBody()->getContents(), true);
    echo "✓ Erfolg!\n";
    echo "Lexoffice ID: " . $responseData['id'] . "\n";
    echo "Response:\n";
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    
} catch (RequestException $e) {
    echo "✗ Fehler!\n";
    echo "Status Code: " . $e->getResponse()->getStatusCode() . "\n";
    
    $responseBody = $e->getResponse()->getBody()->getContents();
    echo "Response Body:\n";
    echo $responseBody . "\n\n";
    
    $response = json_decode($responseBody, true);
    if ($response) {
        echo "Parsed Response:\n";
        echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
        
        if (isset($response['message'])) {
            echo "\nFehlermeldung: " . $response['message'] . "\n";
        }
        
        if (isset($response['violations'])) {
            echo "\nValidierungsfehler:\n";
            foreach ($response['violations'] as $violation) {
                echo "- " . ($violation['field'] ?? 'unknown') . ": " . ($violation['message'] ?? 'unknown') . "\n";
            }
        }
        
        if (isset($response['details'])) {
            echo "\nDetails:\n";
            foreach ($response['details'] as $detail) {
                if (is_array($detail)) {
                    echo "- " . implode(': ', $detail) . "\n";
                } else {
                    echo "- " . $detail . "\n";
                }
            }
        }
    }
}
