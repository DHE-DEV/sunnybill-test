<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== LEXOFFICE FRESH CUSTOMER TEST ===\n\n";

$customer = Customer::first();
if (!$customer) {
    echo "Kein Kunde gefunden.\n";
    exit;
}

echo "Aktueller Kunde: {$customer->name}\n";
echo "Aktuelle Lexoffice ID: " . ($customer->lexoffice_id ?: 'KEINE') . "\n\n";

// Lexoffice ID zurücksetzen um einen neuen Kunden zu erstellen
if ($customer->lexoffice_id) {
    echo "Setze Lexoffice ID zurück um neuen Export zu testen...\n";
    $customer->update(['lexoffice_id' => null]);
    echo "✓ Lexoffice ID zurückgesetzt\n\n";
}

$service = new LexofficeService();

echo "Teste Kunden-Export (ohne Adresse)...\n";
$result = $service->exportCustomer($customer);

if ($result['success']) {
    echo "✓ Export erfolgreich!\n";
    echo "Action: {$result['action']}\n";
    echo "Lexoffice ID: {$result['lexoffice_id']}\n";
    
    // Zeige die gesendeten Daten
    echo "\nGesendete Daten:\n";
    $logs = \App\Models\LexofficeLog::where('entity_id', $customer->id)
                                    ->where('status', 'success')
                                    ->latest()
                                    ->first();
    if ($logs && $logs->request_data) {
        echo json_encode($logs->request_data, JSON_PRETTY_PRINT) . "\n";
    }
    
} else {
    echo "✗ Export fehlgeschlagen!\n";
    echo "Fehler: {$result['error']}\n";
    
    // Zeige die detaillierten Logs
    echo "\nDetaillierte Logs:\n";
    $logs = \App\Models\LexofficeLog::where('entity_id', $customer->id)
                                    ->latest()
                                    ->first();
    if ($logs) {
        echo "Status: {$logs->status}\n";
        echo "Error: {$logs->error_message}\n";
        if ($logs->request_data) {
            echo "Request Data:\n";
            echo json_encode($logs->request_data, JSON_PRETTY_PRINT) . "\n";
        }
        if ($logs->response_data) {
            echo "Response Data:\n";
            echo json_encode($logs->response_data, JSON_PRETTY_PRINT) . "\n";
        }
    }
}

echo "\n=== ANALYSE ===\n";
echo "Kunde hat vollständige Adresse:\n";
echo "- Straße: " . ($customer->street ?: 'FEHLT') . "\n";
echo "- PLZ: " . ($customer->postal_code ?: 'FEHLT') . "\n";
echo "- Stadt: " . ($customer->city ?: 'FEHLT') . "\n";
echo "- Land: " . ($customer->country ?: 'FEHLT') . "\n";

if (!empty($customer->street) && !empty($customer->city) && !empty($customer->postal_code)) {
    echo "→ Adresse sollte hinzugefügt werden\n";
} else {
    echo "→ Adresse wird weggelassen (unvollständig)\n";
}
