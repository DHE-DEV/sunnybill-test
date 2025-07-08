<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== LEXOFFICE ECHTE ADRESSEN ÜBERPRÜFUNG ===\n\n";

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

echo "=== HOLE ECHTE LEXOFFICE-DATEN ===\n";

$service = new LexofficeService();

// Verwende Reflection um auf den HTTP Client zuzugreifen
$reflection = new ReflectionClass($service);
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
    
    echo "✅ Lexoffice-Daten erfolgreich abgerufen!\n\n";
    
    echo "=== LEXOFFICE ROHDATEN ===\n";
    echo json_encode($lexofficeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    echo "=== LEXOFFICE ADRESSEN ANALYSE ===\n";
    
    if (isset($lexofficeData['addresses']) && is_array($lexofficeData['addresses'])) {
        $totalAddresses = 0;
        
        // Zähle alle Adressen
        if (isset($lexofficeData['addresses']['billing'])) {
            $totalAddresses += count($lexofficeData['addresses']['billing']);
        }
        if (isset($lexofficeData['addresses']['shipping'])) {
            $totalAddresses += count($lexofficeData['addresses']['shipping']);
        }
        
        echo "Anzahl Adressen in Lexoffice: {$totalAddresses}\n\n";
        
        // Billing-Adressen
        if (isset($lexofficeData['addresses']['billing']) && is_array($lexofficeData['addresses']['billing'])) {
            foreach ($lexofficeData['addresses']['billing'] as $index => $address) {
                echo "--- BILLING Adresse " . ($index + 1) . " ---\n";
                echo "Straße: " . ($address['street'] ?? 'LEER') . "\n";
                echo "PLZ: " . ($address['zip'] ?? 'LEER') . "\n";
                echo "Stadt: " . ($address['city'] ?? 'LEER') . "\n";
                echo "Land: " . ($address['countryCode'] ?? 'LEER') . "\n";
                echo "Supplement: " . ($address['supplement'] ?? 'LEER') . "\n";
                echo "Vollständige Daten: " . json_encode($address, JSON_UNESCAPED_UNICODE) . "\n\n";
            }
        }
        
        // Shipping-Adressen
        if (isset($lexofficeData['addresses']['shipping']) && is_array($lexofficeData['addresses']['shipping'])) {
            foreach ($lexofficeData['addresses']['shipping'] as $index => $address) {
                echo "--- SHIPPING Adresse " . ($index + 1) . " ---\n";
                echo "Straße: " . ($address['street'] ?? 'LEER') . "\n";
                echo "PLZ: " . ($address['zip'] ?? 'LEER') . "\n";
                echo "Stadt: " . ($address['city'] ?? 'LEER') . "\n";
                echo "Land: " . ($address['countryCode'] ?? 'LEER') . "\n";
                echo "Supplement: " . ($address['supplement'] ?? 'LEER') . "\n";
                echo "Vollständige Daten: " . json_encode($address, JSON_UNESCAPED_UNICODE) . "\n\n";
            }
        }
    } else {
        echo "❌ Keine Adressen in Lexoffice-Daten gefunden.\n";
    }
    
    echo "=== AKTUELLE LOKALE ADRESSEN ===\n";
    
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
    
    echo "\n=== VERGLEICH UND EMPFEHLUNGEN ===\n";
    
    if (isset($lexofficeData['addresses'])) {
        $totalAddresses = 0;
        if (isset($lexofficeData['addresses']['billing'])) {
            $totalAddresses += count($lexofficeData['addresses']['billing']);
        }
        if (isset($lexofficeData['addresses']['shipping'])) {
            $totalAddresses += count($lexofficeData['addresses']['shipping']);
        }
        
        if ($totalAddresses > 0) {
            echo "✅ Lexoffice hat {$totalAddresses} Adressen - Import sollte möglich sein\n";
            echo "Billing-Adressen: " . (isset($lexofficeData['addresses']['billing']) ? count($lexofficeData['addresses']['billing']) : 0) . "\n";
            echo "Shipping-Adressen: " . (isset($lexofficeData['addresses']['shipping']) ? count($lexofficeData['addresses']['shipping']) : 0) . "\n";
        } else {
            echo "⚠️ Lexoffice hat keine Adressen\n";
        }
    } else {
        echo "⚠️ Lexoffice hat keine Adressen-Struktur\n";
    }
    
    echo "=== TESTE ERNEUTE SYNCHRONISATION ===\n";
    
    // Lösche bestehende Adressen
    $customer->addresses()->delete();
    echo "Bestehende lokale Adressen gelöscht.\n";
    
    // Führe Synchronisation durch
    $result = $service->syncCustomer($customer);
    
    if ($result['success']) {
        echo "✅ Synchronisation erfolgreich!\n";
        echo "Aktion: " . ($result['action'] ?? 'Unbekannt') . "\n";
        echo "Nachricht: " . ($result['message'] ?? 'Keine Nachricht') . "\n";
        
        if (isset($result['addresses_imported'])) {
            echo "Importierte Adressen: " . $result['addresses_imported'] . "\n";
        }
        
        // Prüfe Ergebnis
        $customer->refresh();
        $finalBillingAddress = $customer->billingAddress;
        $finalShippingAddress = $customer->shippingAddress;
        
        echo "\n=== ERGEBNIS NACH SYNCHRONISATION ===\n";
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
        
    } else {
        echo "❌ Synchronisation fehlgeschlagen!\n";
        echo "Fehler: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fehler beim Abrufen der Lexoffice-Daten:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "Dieser Test zeigt die echten Lexoffice-Adressen und vergleicht sie mit den lokalen Adressen.\n";
echo "Falls Unterschiede bestehen, muss die Import-Logik angepasst werden.\n";
