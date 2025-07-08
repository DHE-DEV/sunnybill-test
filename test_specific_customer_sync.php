<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== SPEZIFISCHER KUNDEN-SYNCHRONISATION TEST ===\n\n";

// Finde den spezifischen Kunden
$customerId = '0197e61b-a32f-737c-8cec-cb56fd2f71c3';
$customer = Customer::find($customerId);

if (!$customer) {
    echo "❌ Kunde mit ID {$customerId} nicht gefunden.\n";
    exit;
}

echo "=== KUNDEN-DETAILS ===\n";
echo "ID: {$customer->id}\n";
echo "Name: {$customer->name}\n";
echo "Kundentyp: {$customer->customer_type}\n";
echo "Firmenname: " . ($customer->company_name ?: 'LEER') . "\n";
echo "Email: " . ($customer->email ?: 'LEER') . "\n";
echo "Telefon: " . ($customer->phone ?: 'LEER') . "\n";
echo "Lexoffice ID: " . ($customer->lexoffice_id ?: 'KEINE') . "\n";
echo "Zuletzt synchronisiert: " . ($customer->lexoffice_synced_at ? $customer->lexoffice_synced_at->format('d.m.Y H:i:s') : 'NIE') . "\n\n";

echo "=== ADRESS-DETAILS ===\n";
echo "Standard-Adresse:\n";
echo "  Straße: " . ($customer->street ?: 'LEER') . "\n";
echo "  Zusatz: " . ($customer->address_line_2 ?: 'LEER') . "\n";
echo "  PLZ: " . ($customer->postal_code ?: 'LEER') . "\n";
echo "  Stadt: " . ($customer->city ?: 'LEER') . "\n";
echo "  Bundesland: " . ($customer->state ?: 'LEER') . "\n";
echo "  Land: " . ($customer->country ?: 'LEER') . "\n";
echo "  Ländercode: " . ($customer->country_code ?: 'LEER') . "\n\n";

// Prüfe separate Adressen
$billingAddress = $customer->billingAddress;
$shippingAddress = $customer->shippingAddress;

echo "Rechnungsadresse: " . ($billingAddress ? 'VORHANDEN' : 'NICHT VORHANDEN') . "\n";
echo "Lieferadresse: " . ($shippingAddress ? 'VORHANDEN' : 'NICHT VORHANDEN') . "\n\n";

echo "=== TESTE DATEN-VORBEREITUNG ===\n";

$service = new LexofficeService();

// Verwende Reflection um private Methode zu testen
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('prepareCustomerData');
$method->setAccessible(true);

try {
    $customerData = $method->invoke($service, $customer);
    
    echo "✅ Daten-Vorbereitung erfolgreich!\n";
    echo "Vorbereitete Daten:\n";
    echo json_encode($customerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
} catch (Exception $e) {
    echo "❌ Fehler bei Daten-Vorbereitung: " . $e->getMessage() . "\n\n";
}

echo "=== TESTE SYNCHRONISATION ===\n";
echo "Führe Synchronisation durch...\n";

$result = $service->exportCustomer($customer);

if ($result['success']) {
    echo "✅ Synchronisation erfolgreich!\n";
    echo "Aktion: {$result['action']}\n";
    echo "Lexoffice ID: {$result['lexoffice_id']}\n";
    
    // Lade Kunde neu
    $customer->refresh();
    echo "Zuletzt synchronisiert: " . ($customer->lexoffice_synced_at ? $customer->lexoffice_synced_at->format('d.m.Y H:i:s') : 'NIE') . "\n";
} else {
    echo "❌ Synchronisation fehlgeschlagen!\n";
    echo "Fehler: {$result['error']}\n\n";
    
    echo "=== DETAILLIERTE FEHLER-ANALYSE ===\n";
    
    // Prüfe häufige Probleme
    $issues = [];
    
    if (empty(trim($customer->name))) {
        $issues[] = "Name ist leer";
    }
    
    if ($customer->customer_type === 'business' && empty($customer->company_name)) {
        $issues[] = "Firmenname fehlt bei Geschäftskunde";
    }
    
    if ($customer->email && !filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
        $issues[] = "Ungültige E-Mail-Adresse: {$customer->email}";
    }
    
    // Adress-Validierung
    if ($customer->street && $customer->city && $customer->postal_code) {
        $cleanPostalCode = preg_replace('/[^0-9]/', '', $customer->postal_code);
        if (strlen($cleanPostalCode) !== 5) {
            $issues[] = "Ungültige PLZ: {$customer->postal_code} (bereinigt: {$cleanPostalCode})";
        }
    }
    
    if (empty($issues)) {
        echo "Keine offensichtlichen Datenprobleme gefunden.\n";
        echo "Das Problem liegt wahrscheinlich in der Lexoffice API-Kommunikation.\n";
    } else {
        echo "Gefundene Probleme:\n";
        foreach ($issues as $issue) {
            echo "- {$issue}\n";
        }
    }
}

echo "\n=== LEXOFFICE LOGS PRÜFEN ===\n";
$logs = \App\Models\LexofficeLog::where('entity_id', $customer->id)
                                ->orderBy('created_at', 'desc')
                                ->limit(3)
                                ->get();

if ($logs->isEmpty()) {
    echo "Keine Lexoffice-Logs für diesen Kunden gefunden.\n";
} else {
    echo "Letzte 3 Lexoffice-Logs:\n";
    foreach ($logs as $log) {
        echo "- {$log->created_at->format('d.m.Y H:i:s')}: {$log->action} - {$log->status}\n";
        if ($log->error_message) {
            echo "  Fehler: {$log->error_message}\n";
        }
    }
}
