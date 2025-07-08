<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

echo "=== LEXOFFICE ADDRESS DEBUG ===\n\n";

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

// Basis-Kundendaten
$baseData = [
    'roles' => [
        'customer' => (object)[]
    ],
    'person' => [
        'firstName' => 'Test',
        'lastName' => 'Address'
    ]
];

// Verschiedene Adress-Varianten testen
$addressVariants = [
    'Minimal' => [
        'street' => 'Friedrich-Ebert-Str. 42a',
        'city' => 'Weiterstadt',
        'countryCode' => 'DE'
    ],
    'Mit ZIP' => [
        'street' => 'Friedrich-Ebert-Str. 42a',
        'zip' => '64331',
        'city' => 'Weiterstadt',
        'countryCode' => 'DE'
    ],
    'Mit isPrimary' => [
        'street' => 'Friedrich-Ebert-Str. 42a',
        'zip' => '64331',
        'city' => 'Weiterstadt',
        'countryCode' => 'DE',
        'isPrimary' => true
    ],
    'Nur Stadt und Land' => [
        'city' => 'Weiterstadt',
        'countryCode' => 'DE',
        'isPrimary' => true
    ],
    'Mit supplement' => [
        'street' => 'Friedrich-Ebert-Str. 42a',
        'supplement' => '',
        'zip' => '64331',
        'city' => 'Weiterstadt',
        'countryCode' => 'DE',
        'isPrimary' => true
    ]
];

foreach ($addressVariants as $variantName => $address) {
    echo "=== TESTE VARIANTE: {$variantName} ===\n";
    
    $testData = $baseData;
    $testData['addresses'] = [$address];
    
    echo "Adresse:\n";
    echo json_encode($address, JSON_PRETTY_PRINT) . "\n\n";
    
    try {
        $response = $client->post('contacts', [
            'json' => $testData
        ]);
        
        $responseData = json_decode($response->getBody()->getContents(), true);
        echo "✓ ERFOLG! Lexoffice ID: " . $responseData['id'] . "\n";
        
        // Erfolgreich - wir können aufhören
        break;
        
    } catch (RequestException $e) {
        echo "✗ FEHLER!\n";
        echo "Status Code: " . $e->getResponse()->getStatusCode() . "\n";
        
        $responseBody = $e->getResponse()->getBody()->getContents();
        $response = json_decode($responseBody, true);
        
        if ($response && isset($response['IssueList'])) {
            foreach ($response['IssueList'] as $issue) {
                echo "- " . ($issue['source'] ?? 'unknown') . ": " . ($issue['i18nKey'] ?? 'unknown') . "\n";
            }
        }
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// Zusätzlich: Teste einen existierenden Kontakt abrufen um die Struktur zu sehen
echo "=== TESTE EXISTIERENDEN KONTAKT ABRUFEN ===\n";
try {
    $response = $client->get('contacts', [
        'query' => [
            'customer' => true,
            'size' => 1
        ]
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    if (!empty($data['content'])) {
        $contact = $data['content'][0];
        echo "Beispiel-Kontakt Struktur:\n";
        echo json_encode($contact, JSON_PRETTY_PRINT) . "\n";
        
        if (isset($contact['addresses'])) {
            echo "\nAdress-Struktur:\n";
            echo json_encode($contact['addresses'], JSON_PRETTY_PRINT) . "\n";
        }
    }
} catch (RequestException $e) {
    echo "Fehler beim Abrufen: " . $e->getMessage() . "\n";
}
