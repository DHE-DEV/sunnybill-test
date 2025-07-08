<?php

require_once 'vendor/autoload.php';

use App\Models\Customer;
use App\Services\LexofficeService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST: POPUP LEXOFFICE SYNCHRONISATION ===\n\n";

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

// Prüfe aktuelle Rechnungsadresse
$billingAddress = $customer->billingAddress;
echo "=== AKTUELLE RECHNUNGSADRESSE ===\n";
if ($billingAddress) {
    echo "✅ Rechnungsadresse vorhanden:\n";
    echo "   Straße: {$billingAddress->street_address}\n";
    echo "   PLZ: {$billingAddress->postal_code}\n";
    echo "   Stadt: {$billingAddress->city}\n";
    echo "   Land: {$billingAddress->country}\n\n";
} else {
    echo "❌ Keine Rechnungsadresse vorhanden\n\n";
}

// Simuliere Popup-Adressänderung
echo "=== SIMULIERE POPUP-ADRESSÄNDERUNG ===\n";

$testData = [
    'street_address' => 'Test-Straße 123',
    'postal_code' => '12345',
    'city' => 'Test-Stadt',
    'state' => 'Test-Bundesland',
    'country' => 'Deutschland',
];

echo "Neue Adressdaten:\n";
foreach ($testData as $key => $value) {
    echo "   {$key}: {$value}\n";
}
echo "\n";

// Simuliere die Popup-Action-Logik
echo "=== SIMULIERE POPUP-ACTION ===\n";

try {
    if ($billingAddress) {
        // Bestehende Adresse aktualisieren
        echo "📝 Aktualisiere bestehende Rechnungsadresse...\n";
        $billingAddress->update($testData);
        echo "✅ Adresse in Datenbank aktualisiert\n";
    } else {
        // Neue Adresse erstellen
        echo "📝 Erstelle neue Rechnungsadresse...\n";
        $billingAddress = $customer->addresses()->create([
            'type' => 'billing',
            'street_address' => $testData['street_address'],
            'postal_code' => $testData['postal_code'],
            'city' => $testData['city'],
            'state' => $testData['state'],
            'country' => $testData['country'],
            'is_primary' => false,
        ]);
        echo "✅ Neue Adresse in Datenbank erstellt\n";
    }

    // Automatische Lexoffice-Synchronisation
    echo "\n=== AUTOMATISCHE LEXOFFICE-SYNCHRONISATION ===\n";
    $lexofficeMessage = '';
    
    if ($customer->lexoffice_id) {
        echo "✅ Lexoffice-ID vorhanden, starte Synchronisation...\n";
        
        $lexofficeService = new LexofficeService();
        echo "📡 Rufe syncCustomer() auf...\n";
        
        $syncResult = $lexofficeService->syncCustomer($customer);
        
        echo "📋 Synchronisations-Ergebnis:\n";
        echo "   Success: " . ($syncResult['success'] ? 'JA' : 'NEIN') . "\n";
        
        if ($syncResult['success']) {
            $lexofficeMessage = ' und automatisch in Lexoffice synchronisiert';
            echo "✅ Synchronisation erfolgreich!\n";
            if (isset($syncResult['message'])) {
                echo "   Nachricht: {$syncResult['message']}\n";
            }
        } else {
            $lexofficeMessage = ' (Lexoffice-Synchronisation fehlgeschlagen: ' . $syncResult['error'] . ')';
            echo "❌ Synchronisation fehlgeschlagen!\n";
            echo "   Fehler: {$syncResult['error']}\n";
        }
    } else {
        echo "❌ Keine Lexoffice-ID vorhanden\n";
    }

    echo "\n=== FINALE BENACHRICHTIGUNG ===\n";
    $finalMessage = 'Die Rechnungsadresse wurde erfolgreich ' . ($billingAddress->wasRecentlyCreated ? 'erstellt' : 'aktualisiert') . $lexofficeMessage . '.';
    echo "📢 Benachrichtigung: {$finalMessage}\n";

} catch (\Exception $e) {
    echo "❌ FEHLER: {$e->getMessage()}\n";
    echo "Stack Trace:\n{$e->getTraceAsString()}\n";
}

echo "\n=== PRÜFE FINALE ADRESSE ===\n";
$customer->refresh();
$finalBillingAddress = $customer->billingAddress;

if ($finalBillingAddress) {
    echo "✅ Finale Rechnungsadresse:\n";
    echo "   Straße: {$finalBillingAddress->street_address}\n";
    echo "   PLZ: {$finalBillingAddress->postal_code}\n";
    echo "   Stadt: {$finalBillingAddress->city}\n";
    echo "   Land: {$finalBillingAddress->country}\n";
} else {
    echo "❌ Keine Rechnungsadresse gefunden\n";
}

echo "\n=== TEST ABGESCHLOSSEN ===\n";
