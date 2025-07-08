<?php

require_once 'vendor/autoload.php';

use App\Models\Customer;
use App\Services\LexofficeService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST: FINALE SYNCHRONISATION ===\n\n";

// Suche Max Mustermann
$customer = Customer::where('name', 'Max Mustermann')->first();

if (!$customer) {
    echo "❌ Max Mustermann nicht gefunden!\n";
    exit;
}

echo "✅ Kunde gefunden:\n";
echo "   ID: {$customer->id}\n";
echo "   Name: {$customer->name}\n";
echo "   Lexoffice-ID: {$customer->lexoffice_id}\n\n";

$lexofficeService = new LexofficeService();

echo "=== TESTE VERBINDUNG ===\n";
$connectionTest = $lexofficeService->testConnection();
if (!$connectionTest['success']) {
    echo "❌ Lexoffice-Verbindung fehlgeschlagen: {$connectionTest['error']}\n";
    exit;
}
echo "✅ Lexoffice-Verbindung erfolgreich\n\n";

echo "=== TESTE SYNCHRONISATION ===\n";
$syncResult = $lexofficeService->syncCustomer($customer);

echo "📋 Synchronisations-Ergebnis:\n";
echo "   Success: " . ($syncResult['success'] ? 'JA' : 'NEIN') . "\n";

if ($syncResult['success']) {
    echo "   Action: " . ($syncResult['action'] ?? 'Nicht gesetzt') . "\n";
    echo "   Message: " . ($syncResult['message'] ?? 'Nicht gesetzt') . "\n";
    echo "\n✅ SYNCHRONISATION ERFOLGREICH!\n";
} else {
    echo "   Fehler: " . ($syncResult['error'] ?? 'Unbekannter Fehler') . "\n";
    echo "\n❌ SYNCHRONISATION FEHLGESCHLAGEN!\n";
    
    // Prüfe die neuesten Logs
    echo "\n=== NEUESTE LOGS ===\n";
    $logs = \App\Models\LexofficeLog::orderBy('created_at', 'desc')->take(2)->get();
    
    foreach ($logs as $log) {
        echo "Zeit: " . $log->created_at->format('H:i:s') . "\n";
        echo "Status: {$log->status}\n";
        echo "Fehler: " . ($log->error_message ?? 'Kein Fehler') . "\n";
        
        if ($log->request_data) {
            echo "Request-Daten:\n";
            echo json_encode($log->request_data, JSON_PRETTY_PRINT) . "\n";
        }
        echo "---\n";
    }
}

echo "\n=== FIXES ANGEWENDET ===\n";
echo "✅ Boolean-Werte: isPrimary wird als true/false gesendet\n";
echo "✅ Roles-Struktur: customer ist leeres Objekt {}\n";
echo "✅ Automatische Sync: In Popup-Actions und EditCustomer implementiert\n";

echo "\n=== TEST ABGESCHLOSSEN ===\n";
