<?php

require_once 'vendor/autoload.php';

// Laravel bootstrappen
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== FINALE FIX VERIFIKATION ===\n\n";

// Kunde finden
$customer = Customer::where('name', 'Max Mustermann')->first();

if (!$customer) {
    echo "❌ Kunde 'Max Mustermann' nicht gefunden\n";
    exit(1);
}

echo "✅ Kunde gefunden:\n";
echo "   ID: {$customer->id}\n";
echo "   Name: {$customer->name}\n";
echo "   Lexoffice-ID: {$customer->lexoffice_id}\n\n";

// Lexoffice Service
$lexofficeService = new LexofficeService();

echo "=== TESTE JSON-SERIALISIERUNG ===\n";

// Test der neuen stdClass-Implementierung
$testData = [
    'roles' => [
        'customer' => new \stdClass()
    ],
    'person' => [
        'firstName' => 'Max',
        'lastName' => 'Mustermann'
    ]
];

$jsonOutput = json_encode($testData, JSON_PRETTY_PRINT);
echo "📋 JSON-Output:\n";
echo $jsonOutput . "\n\n";

// Prüfe ob customer als leeres Objekt serialisiert wird
if (strpos($jsonOutput, '"customer": {}') !== false) {
    echo "✅ KORREKT: customer wird als leeres Objekt {} serialisiert\n";
} else {
    echo "❌ FEHLER: customer wird nicht korrekt serialisiert\n";
    echo "   Erwartet: \"customer\": {}\n";
    echo "   Gefunden: " . (strpos($jsonOutput, '"customer"') ? "Andere Struktur" : "Nicht gefunden") . "\n";
}

echo "\n=== WARTE AUF RATE LIMIT (30 Sekunden) ===\n";
echo "Lexoffice Rate Limit: Warten bis API wieder verfügbar ist...\n";

// 30 Sekunden warten
for ($i = 30; $i > 0; $i--) {
    echo "\rVerbleibende Zeit: {$i} Sekunden...";
    sleep(1);
}
echo "\n\n";

echo "=== TESTE SYNCHRONISATION ===\n";

try {
    $result = $lexofficeService->syncCustomer($customer);
    
    echo "📋 Synchronisations-Ergebnis:\n";
    echo "   Success: " . ($result['success'] ? 'JA' : 'NEIN') . "\n";
    
    if ($result['success']) {
        echo "   Action: " . ($result['action'] ?? 'Unbekannt') . "\n";
        echo "   Message: " . ($result['message'] ?? 'Keine Nachricht') . "\n";
        echo "✅ SYNCHRONISATION ERFOLGREICH!\n";
    } else {
        echo "   Fehler: " . ($result['error'] ?? 'Unbekannter Fehler') . "\n";
        
        if (strpos($result['error'], '429') !== false) {
            echo "⚠️  RATE LIMIT: Noch nicht abgelaufen, bitte später erneut versuchen\n";
        } elseif (strpos($result['error'], '400') !== false) {
            echo "❌ HTTP 400: Strukturproblem noch nicht behoben\n";
        } else {
            echo "❌ ANDERER FEHLER\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFIKATION ABGESCHLOSSEN ===\n";
