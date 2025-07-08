<?php

require_once 'vendor/autoload.php';

// Laravel bootstrappen
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== TEST: VERSION CHECK ===\n\n";

// Kunde finden
$customer = Customer::where('name', 'Max Mustermann')->first();

if (!$customer) {
    echo "❌ Kunde 'Max Mustermann' nicht gefunden\n";
    exit(1);
}

if (!$customer->lexoffice_id) {
    echo "❌ Kunde hat keine Lexoffice-ID\n";
    exit(1);
}

echo "✅ Kunde gefunden:\n";
echo "   ID: {$customer->id}\n";
echo "   Name: {$customer->name}\n";
echo "   Lexoffice-ID: {$customer->lexoffice_id}\n\n";

// Lexoffice Service
$lexofficeService = new LexofficeService();

echo "=== AKTUELLE VERSION VON LEXOFFICE ABRUFEN ===\n";

try {
    // Direkt von Lexoffice abrufen
    $client = new \GuzzleHttp\Client([
        'base_uri' => 'https://api.lexoffice.io/v1/',
        'headers' => [
            'Authorization' => 'Bearer ' . config('services.lexoffice.api_key'),
            'Accept' => 'application/json',
        ],
        'timeout' => 30,
    ]);
    
    $response = $client->get("contacts/{$customer->lexoffice_id}");
    $lexofficeData = json_decode($response->getBody()->getContents(), true);
    
    echo "✅ Lexoffice-Daten abgerufen\n";
    echo "📋 AKTUELLE VERSION: {$lexofficeData['version']}\n";
    echo "📋 LETZTE ÄNDERUNG: " . ($lexofficeData['updatedDate'] ?? 'Unbekannt') . "\n\n";
    
    echo "=== VERGLEICH ===\n";
    echo "Gesendete Version: 8\n";
    echo "Aktuelle Version:  {$lexofficeData['version']}\n";
    
    if ($lexofficeData['version'] != 8) {
        echo "❌ VERSION KONFLIKT! Die gesendete Version (8) ist nicht aktuell!\n";
        echo "💡 Das könnte der Grund für den HTTP 400 Fehler sein.\n\n";
        
        echo "=== LÖSUNG ===\n";
        echo "1. Aktuelle Version verwenden: {$lexofficeData['version']}\n";
        echo "2. Oder Version komplett weglassen bei Updates\n";
    } else {
        echo "✅ Version ist korrekt\n";
    }
    
    echo "\n=== VOLLSTÄNDIGE LEXOFFICE-DATEN ===\n";
    echo json_encode($lexofficeData, JSON_PRETTY_PRINT) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler beim Abrufen der Lexoffice-Daten: " . $e->getMessage() . "\n";
}

echo "\n=== TEST ABGESCHLOSSEN ===\n";
