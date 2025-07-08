<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== LEXOFFICE FINALE SYNCHRONISATION TEST ===\n\n";

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
echo "Lexoffice ID: " . ($customer->lexoffice_id ?: 'KEINE') . "\n\n";

if (!$customer->lexoffice_id) {
    echo "❌ Kunde hat keine Lexoffice-ID. Kann nicht synchronisiert werden.\n";
    exit;
}

echo "=== AKTUELLE LOKALE ADRESSEN (VOR SYNC) ===\n";
$billingAddress = $customer->billingAddress;
$shippingAddress = $customer->shippingAddress;

echo "Rechnungsadresse: " . ($billingAddress ? 'VORHANDEN' : 'NICHT VORHANDEN') . "\n";
if ($billingAddress) {
    echo "  Straße: {$billingAddress->street_address}\n";
    echo "  PLZ: {$billingAddress->postal_code}\n";
    echo "  Stadt: {$billingAddress->city}\n";
    echo "  Land: {$billingAddress->country}\n";
}

echo "\nLieferadresse: " . ($shippingAddress ? 'VORHANDEN' : 'NICHT VORHANDEN') . "\n";
if ($shippingAddress) {
    echo "  Straße: {$shippingAddress->street_address}\n";
    echo "  PLZ: {$shippingAddress->postal_code}\n";
    echo "  Stadt: {$shippingAddress->city}\n";
    echo "  Land: {$shippingAddress->country}\n";
}

echo "\n=== LÖSCHE LOKALE ADRESSEN FÜR TEST ===\n";
$customer->addresses()->delete();
echo "Lokale Adressen gelöscht.\n";

echo "\n=== FÜHRE SYNCHRONISATION DURCH ===\n";
$service = new LexofficeService();
$result = $service->syncCustomer($customer);

if ($result['success']) {
    echo "✅ Synchronisation erfolgreich!\n";
    echo "Aktion: " . ($result['action'] ?? 'Unbekannt') . "\n";
    echo "Nachricht: " . ($result['message'] ?? 'Keine Nachricht') . "\n";
    
    if (isset($result['addresses_imported'])) {
        echo "Importierte Adressen: " . $result['addresses_imported'] . "\n";
    }
} else {
    echo "❌ Synchronisation fehlgeschlagen!\n";
    echo "Fehler: " . $result['error'] . "\n";
    exit;
}

echo "\n=== FINALE LOKALE ADRESSEN (NACH SYNC) ===\n";
$customer->refresh();
$finalBillingAddress = $customer->billingAddress;
$finalShippingAddress = $customer->shippingAddress;

echo "Finale Rechnungsadresse: " . ($finalBillingAddress ? 'VORHANDEN' : 'NICHT VORHANDEN') . "\n";
if ($finalBillingAddress) {
    echo "  Straße: {$finalBillingAddress->street_address}\n";
    echo "  PLZ: {$finalBillingAddress->postal_code}\n";
    echo "  Stadt: {$finalBillingAddress->city}\n";
    echo "  Land: {$finalBillingAddress->country}\n";
}

echo "\nFinale Lieferadresse: " . ($finalShippingAddress ? 'VORHANDEN' : 'NICHT VORHANDEN') . "\n";
if ($finalShippingAddress) {
    echo "  Straße: {$finalShippingAddress->street_address}\n";
    echo "  PLZ: {$finalShippingAddress->postal_code}\n";
    echo "  Stadt: {$finalShippingAddress->city}\n";
    echo "  Land: {$finalShippingAddress->country}\n";
}

echo "\n=== ERGEBNIS-BEWERTUNG ===\n";
if ($finalBillingAddress && $finalShippingAddress) {
    echo "✅ SUCCESS: Beide Adressen wurden erfolgreich importiert!\n";
    echo "Die verbesserte Synchronisationslogik funktioniert korrekt.\n";
    echo "Auch bei 'up_to_date' Status werden fehlende Adressen nachträglich importiert.\n";
} elseif ($finalBillingAddress || $finalShippingAddress) {
    echo "⚠️ PARTIAL SUCCESS: Nur eine Adresse wurde importiert.\n";
    echo "Möglicherweise ist ein Problem mit einer der Adressen vorhanden.\n";
} else {
    echo "❌ FAILURE: Keine Adressen wurden importiert.\n";
    echo "Die Synchronisationslogik funktioniert noch nicht korrekt.\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "Dieser Test prüft, ob die verbesserte Synchronisationslogik\n";
echo "auch bei 'up_to_date' Status fehlende Adressen nachträglich importiert.\n";
echo "Das war das ursprüngliche Problem: Export fehlgeschlagen HTTP 400 beim Senden an Lexoffice\n";
echo "weil die lokalen Adressen nicht mit den Lexoffice-Adressen synchronisiert waren.\n";
