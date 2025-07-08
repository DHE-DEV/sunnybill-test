<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== LEXOFFICE ADRESSEN FORCE-IMPORT ===\n\n";

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

echo "=== FORCE IMPORT VON LEXOFFICE-ADRESSEN ===\n";

$service = new LexofficeService();

// Verwende Reflection um auf private Methoden zuzugreifen
$reflection = new ReflectionClass($service);

// Hole Lexoffice-Daten
$clientProperty = $reflection->getProperty('client');
$clientProperty->setAccessible(true);
$client = $clientProperty->getValue($service);

try {
    $response = $client->get("contacts/{$customer->lexoffice_id}");
    $lexofficeData = json_decode($response->getBody()->getContents(), true);
    
    if (!$lexofficeData) {
        echo "❌ Kunde nicht in Lexoffice gefunden.\n";
        exit;
    }
    
    echo "✅ Lexoffice-Daten erfolgreich abgerufen!\n";
    
    // Verwende Reflection um auf die private importCustomerAddressesFromLexoffice Methode zuzugreifen
    $importMethod = $reflection->getMethod('importCustomerAddressesFromLexoffice');
    $importMethod->setAccessible(true);
    
    // Lösche bestehende Adressen
    $customer->addresses()->delete();
    echo "Bestehende lokale Adressen gelöscht.\n";
    
    // Führe direkten Adress-Import durch
    $addressesImported = $importMethod->invoke($service, $customer, $lexofficeData['addresses'] ?? []);
    
    echo "✅ Adressen-Import abgeschlossen!\n";
    echo "Importierte Adressen: {$addressesImported}\n\n";
    
    // Prüfe Ergebnis
    $customer->refresh();
    $finalBillingAddress = $customer->billingAddress;
    $finalShippingAddress = $customer->shippingAddress;
    
    echo "=== ERGEBNIS NACH FORCE-IMPORT ===\n";
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
    
    if ($addressesImported > 0) {
        echo "\n✅ SUCCESS: Adressen wurden erfolgreich von Lexoffice importiert!\n";
        echo "Das Problem war, dass die Synchronisation 'up_to_date' zurückgab und daher keine Adressen importiert hat.\n";
        echo "Die Lexoffice-Adressen sind jetzt korrekt in der lokalen Datenbank gespeichert.\n";
    } else {
        echo "\n❌ PROBLEM: Keine Adressen wurden importiert.\n";
        echo "Möglicherweise ist ein Problem mit der Import-Logik vorhanden.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fehler beim Force-Import:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "Dieser Test führt einen direkten Import der Lexoffice-Adressen durch,\n";
echo "unabhängig vom Synchronisationsstatus des Kunden.\n";
