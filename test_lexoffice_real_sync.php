<?php

require_once 'vendor/autoload.php';

use App\Models\Customer;
use App\Services\LexofficeService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST: ECHTE LEXOFFICE-SYNCHRONISATION ===\n\n";

// Suche Max Mustermann
$customer = Customer::where('name', 'Max Mustermann')->first();

if (!$customer) {
    echo "❌ Max Mustermann nicht gefunden!\n";
    exit;
}

echo "✅ Kunde gefunden:\n";
echo "   ID: {$customer->id}\n";
echo "   Name: {$customer->name}\n";
echo "   Lexoffice-ID: {$customer->lexoffice_id}\n";
echo "   Letzte Sync: " . ($customer->lexoffice_synced_at ? $customer->lexoffice_synced_at->format('d.m.Y H:i:s') : 'Nie') . "\n\n";

$lexofficeService = new LexofficeService();

echo "=== TESTE LEXOFFICE-VERBINDUNG ===\n";
$connectionTest = $lexofficeService->testConnection();
if ($connectionTest['success']) {
    echo "✅ Verbindung erfolgreich\n";
    echo "   Firma: {$connectionTest['company']}\n";
    echo "   Email: {$connectionTest['email']}\n\n";
} else {
    echo "❌ Verbindung fehlgeschlagen: {$connectionTest['error']}\n";
    exit;
}

echo "=== STARTE SYNCHRONISATION ===\n";
$syncResult = $lexofficeService->syncCustomer($customer);

echo "📋 Synchronisations-Ergebnis:\n";
echo "   Success: " . ($syncResult['success'] ? 'JA' : 'NEIN') . "\n";

if ($syncResult['success']) {
    echo "   Action: " . ($syncResult['action'] ?? 'Nicht gesetzt') . "\n";
    echo "   Message: " . ($syncResult['message'] ?? 'Nicht gesetzt') . "\n";
    echo "   Lexoffice-ID: " . ($syncResult['lexoffice_id'] ?? 'Nicht gesetzt') . "\n";
    
    // Prüfe ob Sync-Zeitstempel aktualisiert wurde
    $customer->refresh();
    echo "   Neue Sync-Zeit: " . ($customer->lexoffice_synced_at ? $customer->lexoffice_synced_at->format('d.m.Y H:i:s') : 'Nicht gesetzt') . "\n";
    
    echo "\n✅ SYNCHRONISATION ERFOLGREICH!\n";
} else {
    echo "   Fehler: " . ($syncResult['error'] ?? 'Unbekannter Fehler') . "\n";
    
    if (isset($syncResult['conflict']) && $syncResult['conflict']) {
        echo "   Konflikt-Details:\n";
        echo "     Lokal geändert: " . ($syncResult['local_updated'] ?? 'Unbekannt') . "\n";
        echo "     Lexoffice geändert: " . ($syncResult['lexoffice_updated'] ?? 'Unbekannt') . "\n";
        echo "     Letzte Sync: " . ($syncResult['last_synced'] ?? 'Unbekannt') . "\n";
    }
    
    echo "\n❌ SYNCHRONISATION FEHLGESCHLAGEN!\n";
}

echo "\n=== PRÜFE AKTUELLE LOGS ===\n";
$latestLog = \App\Models\LexofficeLog::where('entity_id', $customer->id)
    ->where('type', 'customer')
    ->orderBy('created_at', 'desc')
    ->first();

if ($latestLog) {
    echo "📋 Neuester Log-Eintrag:\n";
    echo "   Zeitpunkt: {$latestLog->created_at->format('d.m.Y H:i:s')}\n";
    echo "   Action: {$latestLog->action}\n";
    echo "   Status: {$latestLog->status}\n";
    
    if ($latestLog->error_message) {
        echo "   Fehler: {$latestLog->error_message}\n";
    }
    
    if ($latestLog->request_data) {
        echo "   Request-Daten (Auszug):\n";
        $requestData = $latestLog->request_data;
        if (isset($requestData['addresses'])) {
            foreach ($requestData['addresses'] as $i => $addr) {
                echo "     Adresse {$i}: isPrimary = " . var_export($addr['isPrimary'] ?? 'nicht gesetzt', true) . "\n";
            }
        }
    }
} else {
    echo "❌ Kein Log-Eintrag gefunden\n";
}

echo "\n=== TEST ABGESCHLOSSEN ===\n";
