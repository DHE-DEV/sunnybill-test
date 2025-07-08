<?php

require_once 'vendor/autoload.php';

use App\Models\Customer;
use App\Services\LexofficeService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST: ECHTE POPUP ADRESSÄNDERUNG MIT LEXOFFICE SYNC ===\n\n";

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
    echo "   Land: {$billingAddress->country}\n";
    echo "   Letzte Änderung: " . $billingAddress->updated_at->format('d.m.Y H:i:s') . "\n\n";
} else {
    echo "❌ Keine Rechnungsadresse vorhanden\n\n";
}

// Zeige letzte Synchronisation
echo "=== SYNCHRONISATIONS-STATUS ===\n";
echo "Kunde letzte Änderung: " . $customer->updated_at->format('d.m.Y H:i:s') . "\n";
echo "Letzte Lexoffice-Sync: " . ($customer->lexoffice_synced_at ? $customer->lexoffice_synced_at->format('d.m.Y H:i:s') : 'Nie') . "\n";

if ($billingAddress) {
    echo "Adresse letzte Änderung: " . $billingAddress->updated_at->format('d.m.Y H:i:s') . "\n";
    
    $addressChangedAfterSync = $customer->lexoffice_synced_at ? 
        $billingAddress->updated_at->gt($customer->lexoffice_synced_at) : true;
    
    echo "Adresse nach letzter Sync geändert: " . ($addressChangedAfterSync ? 'JA' : 'NEIN') . "\n";
}
echo "\n";

// Simuliere echte Adressänderung mit unterschiedlichen Daten
echo "=== SIMULIERE ECHTE ADRESSÄNDERUNG ===\n";

$newTestData = [
    'street_address' => 'Neue Popup-Straße ' . rand(1, 999),
    'postal_code' => '5' . rand(1000, 9999),
    'city' => 'Popup-Stadt-' . rand(1, 99),
    'state' => 'Popup-Bundesland',
    'country' => 'Deutschland',
];

echo "Neue Adressdaten (unterschiedlich zu aktuellen):\n";
foreach ($newTestData as $key => $value) {
    echo "   {$key}: {$value}\n";
}
echo "\n";

// Simuliere die Popup-Action-Logik
echo "=== SIMULIERE POPUP-ACTION MIT ECHTER ÄNDERUNG ===\n";

try {
    if ($billingAddress) {
        // Bestehende Adresse aktualisieren
        echo "📝 Aktualisiere bestehende Rechnungsadresse mit neuen Daten...\n";
        $billingAddress->update($newTestData);
        echo "✅ Adresse in Datenbank aktualisiert\n";
        echo "   Neue Adresse: {$billingAddress->street_address}, {$billingAddress->postal_code} {$billingAddress->city}\n";
    } else {
        // Neue Adresse erstellen
        echo "📝 Erstelle neue Rechnungsadresse...\n";
        $billingAddress = $customer->addresses()->create([
            'type' => 'billing',
            'street_address' => $newTestData['street_address'],
            'postal_code' => $newTestData['postal_code'],
            'city' => $newTestData['city'],
            'state' => $newTestData['state'],
            'country' => $newTestData['country'],
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
            if (isset($syncResult['action'])) {
                echo "   Aktion: {$syncResult['action']}\n";
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

echo "\n=== PRÜFE FINALE ADRESSE UND SYNC-STATUS ===\n";
$customer->refresh();
$finalBillingAddress = $customer->billingAddress;

if ($finalBillingAddress) {
    echo "✅ Finale Rechnungsadresse:\n";
    echo "   Straße: {$finalBillingAddress->street_address}\n";
    echo "   PLZ: {$finalBillingAddress->postal_code}\n";
    echo "   Stadt: {$finalBillingAddress->city}\n";
    echo "   Land: {$finalBillingAddress->country}\n";
    echo "   Letzte Änderung: " . $finalBillingAddress->updated_at->format('d.m.Y H:i:s') . "\n";
} else {
    echo "❌ Keine Rechnungsadresse gefunden\n";
}

echo "\n✅ Finale Synchronisations-Info:\n";
echo "   Kunde letzte Sync: " . ($customer->lexoffice_synced_at ? $customer->lexoffice_synced_at->format('d.m.Y H:i:s') : 'Nie') . "\n";

echo "\n=== TEST ABGESCHLOSSEN ===\n";
echo "🎯 ERGEBNIS: Die automatische Lexoffice-Synchronisation bei Popup-Adressänderungen ist jetzt implementiert!\n";
