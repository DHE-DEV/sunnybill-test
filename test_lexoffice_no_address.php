<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== LEXOFFICE TEST OHNE ADRESSE ===\n\n";

$customer = Customer::first();
if (!$customer) {
    echo "Kein Kunde gefunden.\n";
    exit;
}

// Temporär die Adresse entfernen um zu testen ob das Problem die Adresse ist
$originalStreet = $customer->street;
$originalPostalCode = $customer->postal_code;
$originalCity = $customer->city;
$originalCountry = $customer->country;

echo "Entferne temporär die Adresse...\n";
$customer->update([
    'street' => null,
    'postal_code' => null,
    'city' => null,
    'country' => 'DE', // Kann nicht null sein wegen DB-Constraint
    'lexoffice_id' => null // Auch ID zurücksetzen
]);

echo "Kunde ohne Adresse: {$customer->name}\n";
echo "Email: " . ($customer->email ?: 'LEER') . "\n";
echo "Telefon: " . ($customer->phone ?: 'LEER') . "\n\n";

$service = new LexofficeService();

echo "Teste Kunden-Export OHNE Adresse...\n";
$result = $service->exportCustomer($customer);

if ($result['success']) {
    echo "✓ Export OHNE Adresse erfolgreich!\n";
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
    echo "✗ Export OHNE Adresse fehlgeschlagen!\n";
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
    }
}

// Adresse wiederherstellen
echo "\nStelle ursprüngliche Adresse wieder her...\n";
$customer->update([
    'street' => $originalStreet,
    'postal_code' => $originalPostalCode,
    'city' => $originalCity,
    'country' => $originalCountry
]);

echo "✓ Adresse wiederhergestellt\n";

echo "\n=== FAZIT ===\n";
if ($result['success']) {
    echo "Das Problem liegt definitiv bei der Adresse!\n";
    echo "Kunden ohne Adresse können erfolgreich exportiert werden.\n";
    echo "Die Adress-Validierung in Lexoffice ist sehr strikt.\n";
} else {
    echo "Das Problem liegt nicht nur bei der Adresse.\n";
    echo "Auch ohne Adresse schlägt der Export fehl.\n";
}
