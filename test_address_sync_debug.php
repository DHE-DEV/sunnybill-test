<?php

require_once 'vendor/autoload.php';

use App\Models\Customer;
use App\Services\LexofficeService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG: ADRESS-SYNCHRONISATION ===\n\n";

// Suche einen Kunden mit Lexoffice-ID
$customer = Customer::whereNotNull('lexoffice_id')->first();

if (!$customer) {
    echo "❌ Kein Kunde mit Lexoffice-ID gefunden!\n";
    exit;
}

echo "✅ Kunde gefunden:\n";
echo "   ID: {$customer->id}\n";
echo "   Name: {$customer->name}\n";
echo "   Lexoffice-ID: {$customer->lexoffice_id}\n";
echo "   Letzte Sync: " . ($customer->lexoffice_synced_at ? $customer->lexoffice_synced_at->format('d.m.Y H:i:s') : 'Nie') . "\n\n";

// Debug: Prüfe hasLocalChanges Methode
$lexofficeService = new LexofficeService();

echo "=== DEBUG: hasLocalChanges() METHODE ===\n";

// Verwende Reflection um private Methode zu testen
$reflection = new ReflectionClass($lexofficeService);
$hasLocalChangesMethod = $reflection->getMethod('hasLocalChanges');
$hasLocalChangesMethod->setAccessible(true);

$hasChanges = $hasLocalChangesMethod->invoke($lexofficeService, $customer, $customer->lexoffice_synced_at);
echo "hasLocalChanges() Ergebnis: " . ($hasChanges ? 'JA' : 'NEIN') . "\n";

// Debug: Prüfe Adressen einzeln
$billingAddress = $customer->billingAddress;
if ($billingAddress) {
    echo "Rechnungsadresse gefunden:\n";
    echo "   Letzte Änderung: " . $billingAddress->updated_at->format('d.m.Y H:i:s') . "\n";
    echo "   Letzte Sync: " . ($customer->lexoffice_synced_at ? $customer->lexoffice_synced_at->format('d.m.Y H:i:s') : 'Nie') . "\n";
    
    if ($customer->lexoffice_synced_at) {
        $addressChanged = $billingAddress->updated_at->gt($customer->lexoffice_synced_at);
        echo "   Adresse nach Sync geändert: " . ($addressChanged ? 'JA' : 'NEIN') . "\n";
    }
}

echo "\n=== DEBUG: SYNC-RICHTUNG BESTIMMUNG ===\n";

// Teste die Sync-Richtung
try {
    $syncResult = $lexofficeService->syncCustomer($customer);
    
    echo "Sync-Ergebnis:\n";
    echo "   Success: " . ($syncResult['success'] ? 'JA' : 'NEIN') . "\n";
    echo "   Action: " . ($syncResult['action'] ?? 'Nicht gesetzt') . "\n";
    echo "   Message: " . ($syncResult['message'] ?? 'Nicht gesetzt') . "\n";
    
    if (!$syncResult['success']) {
        echo "   Fehler: " . $syncResult['error'] . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Exception: {$e->getMessage()}\n";
}

echo "\n=== DEBUG: DIREKTE ADRESS-EXPORT PRÜFUNG ===\n";

// Prüfe die prepareCustomerAddresses Methode
$prepareAddressesMethod = $reflection->getMethod('prepareCustomerAddresses');
$prepareAddressesMethod->setAccessible(true);

$addresses = $prepareAddressesMethod->invoke($lexofficeService, $customer);
echo "Vorbereitete Adressen für Lexoffice:\n";
echo json_encode($addresses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\n=== DEBUG: VOLLSTÄNDIGE KUNDENDATEN ===\n";

// Prüfe die prepareCustomerData Methode
try {
    $prepareCustomerDataMethod = $reflection->getMethod('prepareCustomerData');
    $prepareCustomerDataMethod->setAccessible(true);
    
    $customerData = $prepareCustomerDataMethod->invoke($lexofficeService, $customer);
    echo "Vorbereitete Kundendaten für Lexoffice:\n";
    echo json_encode($customerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler bei prepareCustomerData: {$e->getMessage()}\n";
}

echo "\n=== TEST ABGESCHLOSSEN ===\n";
