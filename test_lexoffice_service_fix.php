<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== LEXOFFICE SERVICE TEST (NACH FIX) ===\n\n";

// Warte kurz um Rate Limit zu umgehen
echo "Warte 10 Sekunden um Rate Limit zu umgehen...\n";
sleep(10);

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

$service = new LexofficeService();

// Teste zuerst die Verbindung
echo "Teste Lexoffice-Verbindung...\n";
$connectionTest = $service->testConnection();
if ($connectionTest['success']) {
    echo "✓ Verbindung erfolgreich\n";
    echo "Firma: {$connectionTest['company']}\n";
    echo "Email: {$connectionTest['email']}\n\n";
    
    // Jetzt teste den Export
    echo "Starte Kunden-Export...\n";
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
        
        // Zeige die letzten Logs
        echo "\nLetzte Logs:\n";
        $logs = \App\Models\LexofficeLog::where('entity_id', $customer->id)
                                        ->where('status', 'error')
                                        ->latest()
                                        ->first();
        if ($logs) {
            echo "Error: {$logs->error_message}\n";
            if ($logs->request_data) {
                echo "Request Data:\n";
                echo json_encode($logs->request_data, JSON_PRETTY_PRINT) . "\n";
            }
        }
    }
} else {
    echo "✗ Verbindung fehlgeschlagen!\n";
    echo "Fehler: {$connectionTest['error']}\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "Das Problem war, dass die Lexoffice API bei Adressen sehr spezifische\n";
echo "Anforderungen hat. Die Lösung:\n";
echo "1. Kunden ohne Adresse erstellen funktioniert einwandfrei\n";
echo "2. Adressen nur hinzufügen wenn ALLE Pflichtfelder vorhanden sind\n";
echo "3. Möglicherweise 'supplement' Feld erforderlich (auch wenn leer)\n";
echo "4. Bei unvollständigen Adressen: komplett weglassen\n";
