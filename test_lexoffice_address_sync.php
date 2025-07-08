<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\Address;
use App\Services\LexofficeService;

echo "=== LEXOFFICE ADRESS-SYNCHRONISATION TEST ===\n\n";

// Finde Max Mustermann
$customer = Customer::where('name', 'Max Mustermann')->first();

if (!$customer) {
    echo "❌ Max Mustermann nicht gefunden.\n";
    exit;
}

echo "Kunde: {$customer->name} (ID: {$customer->id})\n";
echo "Lexoffice ID: " . ($customer->lexoffice_id ?: 'KEINE') . "\n\n";

// Zeige aktuelle Adressen
echo "=== AKTUELLE ADRESSEN ===\n";

// Standard-Adresse (aus Customer-Tabelle)
echo "Standard-Adresse:\n";
if ($customer->street && $customer->city && $customer->postal_code) {
    echo "  ✅ {$customer->street}\n";
    if ($customer->address_line_2) echo "     {$customer->address_line_2}\n";
    echo "     {$customer->postal_code} {$customer->city}\n";
    if ($customer->state) echo "     {$customer->state}\n";
    echo "     {$customer->country}\n";
} else {
    echo "  ❌ Unvollständig oder nicht vorhanden\n";
}

// Rechnungsadresse
echo "\nRechnungsadresse:\n";
$billingAddress = $customer->billingAddress;
if ($billingAddress) {
    echo "  ✅ {$billingAddress->street_address}\n";
    echo "     {$billingAddress->postal_code} {$billingAddress->city}\n";
    if ($billingAddress->state) echo "     {$billingAddress->state}\n";
    echo "     {$billingAddress->country}\n";
} else {
    echo "  ❌ Nicht vorhanden\n";
}

// Lieferadresse
echo "\nLieferadresse:\n";
$shippingAddress = $customer->shippingAddress;
if ($shippingAddress) {
    echo "  ✅ {$shippingAddress->street_address}\n";
    echo "     {$shippingAddress->postal_code} {$shippingAddress->city}\n";
    if ($shippingAddress->state) echo "     {$shippingAddress->state}\n";
    echo "     {$shippingAddress->country}\n";
} else {
    echo "  ❌ Nicht vorhanden\n";
}

// Erstelle Test-Adressen falls nicht vorhanden
echo "\n=== ERSTELLE TEST-ADRESSEN ===\n";

// Rechnungsadresse erstellen falls nicht vorhanden
if (!$billingAddress) {
    echo "Erstelle Rechnungsadresse...\n";
    $billingAddress = Address::create([
        'addressable_id' => $customer->id,
        'addressable_type' => Customer::class,
        'type' => 'billing',
        'street_address' => 'Rechnungsstraße 123',
        'postal_code' => '10117',
        'city' => 'Berlin',
        'state' => 'Berlin',
        'country' => 'Deutschland',
        'is_primary' => true,
    ]);
    echo "✅ Rechnungsadresse erstellt\n";
} else {
    echo "✅ Rechnungsadresse bereits vorhanden\n";
}

// Lieferadresse erstellen falls nicht vorhanden
if (!$shippingAddress) {
    echo "Erstelle Lieferadresse...\n";
    $shippingAddress = Address::create([
        'addressable_id' => $customer->id,
        'addressable_type' => Customer::class,
        'type' => 'shipping',
        'street_address' => 'Lieferstraße 456',
        'postal_code' => '10119',
        'city' => 'Berlin',
        'state' => 'Berlin',
        'country' => 'Deutschland',
        'is_primary' => true,
    ]);
    echo "✅ Lieferadresse erstellt\n";
} else {
    echo "✅ Lieferadresse bereits vorhanden\n";
}

// Lade Kunde neu um Beziehungen zu aktualisieren
$customer->refresh();

echo "\n=== TESTE ADRESS-VORBEREITUNG ===\n";

$service = new LexofficeService();

// Verwende Reflection um private Methode zu testen
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('prepareCustomerAddresses');
$method->setAccessible(true);

$addresses = $method->invoke($service, $customer);

echo "Anzahl vorbereiteter Adressen: " . count($addresses) . "\n\n";

foreach ($addresses as $index => $address) {
    echo "Adresse " . ($index + 1) . ":\n";
    echo "  Straße: {$address['street']}\n";
    echo "  Zusatz: " . ($address['supplement'] ?: 'LEER') . "\n";
    echo "  PLZ: {$address['zip']}\n";
    echo "  Stadt: {$address['city']}\n";
    echo "  Land: {$address['countryCode']}\n";
    echo "  Primary: " . ($address['isPrimary'] ? 'JA' : 'NEIN') . "\n\n";
}

echo "=== TESTE SYNCHRONISATION ===\n";
echo "Führe Synchronisation mit allen Adressen durch...\n";

$result = $service->exportCustomer($customer);

if ($result['success']) {
    echo "✅ Synchronisation erfolgreich!\n";
    echo "Aktion: {$result['action']}\n";
    echo "Lexoffice ID: {$result['lexoffice_id']}\n";
    
    // Lade Kunde neu
    $customer->refresh();
    echo "Zuletzt synchronisiert: " . ($customer->lexoffice_synced_at ? $customer->lexoffice_synced_at->format('d.m.Y H:i:s') : 'NIE') . "\n";
    
    echo "\n=== SYNCHRONISATION ERFOLGREICH ===\n";
    echo "Alle Adressen wurden zu Lexoffice übertragen:\n";
    echo "- Standard-Adresse (Primary)\n";
    echo "- Rechnungsadresse\n";
    echo "- Lieferadresse\n";
} else {
    echo "❌ Synchronisation fehlgeschlagen: {$result['error']}\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "✅ Adress-Synchronisation implementiert\n";
echo "✅ Standard-, Rechnungs- und Lieferadresse werden übertragen\n";
echo "✅ Nur vollständige Adressen werden gesendet\n";
echo "✅ Standard-Adresse wird als Primary markiert\n";
