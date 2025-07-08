<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

echo "=== LEXOFFICE MINIMAL TEST ===\n\n";

$customer = Customer::first();
if (!$customer) {
    echo "Kein Kunde gefunden.\n";
    exit;
}

echo "Teste mit Kunde: {$customer->name}\n\n";

// Minimale Kundendaten - nur Name und Rolle
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

echo "Zu sendende Daten (minimal):\n";
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
    echo "Sende minimale Anfrage an Lexoffice...\n";
    $response = $client->post('contacts', [
        'json' => $data
    ]);
    
    $responseData = json_decode($response->getBody()->getContents(), true);
    echo "✓ Erfolg!\n";
    echo "Lexoffice ID: " . $responseData['id'] . "\n";
    echo "Response:\n";
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    
    // Jetzt teste mit Adresse
    echo "\n=== TESTE MIT ADRESSE ===\n\n";
    
    $dataWithAddress = $data;
    $dataWithAddress['addresses'] = [
        [
            'street' => 'Friedrich-Ebert-Str. 42a',
            'zip' => '64331',
            'city' => 'Weiterstadt',
            'countryCode' => 'DE',
            'isPrimary' => true
        ]
    ];
    
    echo "Zu sendende Daten (mit Adresse):\n";
    echo json_encode($dataWithAddress, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "Sende Anfrage mit Adresse an Lexoffice...\n";
    $response2 = $client->post('contacts', [
        'json' => $dataWithAddress
    ]);
    
    $responseData2 = json_decode($response2->getBody()->getContents(), true);
    echo "✓ Auch mit Adresse erfolgreich!\n";
    echo "Lexoffice ID: " . $responseData2['id'] . "\n";
    
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
        
        if (isset($response['IssueList'])) {
            echo "\nFehler-Details:\n";
            foreach ($response['IssueList'] as $issue) {
                echo "- " . ($issue['source'] ?? 'unknown') . ": " . ($issue['i18nKey'] ?? 'unknown') . " (" . ($issue['type'] ?? 'unknown') . ")\n";
            }
        }
    }
}
