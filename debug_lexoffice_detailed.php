<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LexofficeLog;

echo "=== LEXOFFICE LOGS (Detailliert) ===\n\n";

$logs = LexofficeLog::latest()->take(3)->get();

if ($logs->isEmpty()) {
    echo "Keine Logs gefunden.\n";
    exit;
}

foreach ($logs as $log) {
    echo "[{$log->created_at}] {$log->type}/{$log->action} - {$log->status}\n";
    echo "Entity ID: {$log->entity_id}\n";
    echo "Lexoffice ID: {$log->lexoffice_id}\n";
    
    if ($log->error_message) {
        echo "ERROR: {$log->error_message}\n";
    }
    
    if ($log->request_data) {
        echo "REQUEST DATA:\n";
        print_r($log->request_data);
        echo "\n";
    }
    
    if ($log->response_data) {
        echo "RESPONSE DATA:\n";
        print_r($log->response_data);
        echo "\n";
    }
    
    echo str_repeat("=", 80) . "\n\n";
}

// Zusätzlich: Teste einen Kunden-Export
echo "=== TESTE KUNDEN-EXPORT ===\n\n";

use App\Models\Customer;
use App\Services\LexofficeService;

$customer = Customer::first();
if ($customer) {
    echo "Teste Export für Kunde: {$customer->name}\n";
    echo "Kunde ID: {$customer->id}\n";
    echo "Email: {$customer->email}\n";
    echo "Telefon: {$customer->phone}\n";
    echo "Straße: {$customer->street}\n";
    echo "PLZ: {$customer->postal_code}\n";
    echo "Stadt: {$customer->city}\n";
    echo "Land: {$customer->country}\n\n";
    
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
            echo "Lexoffice ID: {$result['lexoffice_id']}\n";
        } else {
            echo "✗ Export fehlgeschlagen!\n";
            echo "Fehler: {$result['error']}\n";
        }
    } else {
        echo "✗ Verbindung fehlgeschlagen!\n";
        echo "Fehler: {$connectionTest['error']}\n";
    }
} else {
    echo "Kein Kunde gefunden.\n";
}
