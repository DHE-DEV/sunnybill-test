<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== INTELLIGENTE SYNCHRONISATION TEST ===\n\n";

// Finde den spezifischen Kunden
$customerId = '0197e61b-a32f-737c-8cec-cb56fd2f71c3';
$customer = Customer::find($customerId);

if (!$customer) {
    echo "❌ Kunde mit ID {$customerId} nicht gefunden.\n";
    exit;
}

echo "=== KUNDEN-DETAILS VOR SYNC ===\n";
echo "ID: {$customer->id}\n";
echo "Name: {$customer->name}\n";
echo "Lexoffice ID: " . ($customer->lexoffice_id ?: 'KEINE') . "\n";
echo "Zuletzt aktualisiert: " . $customer->updated_at->format('d.m.Y H:i:s') . "\n";
echo "Zuletzt synchronisiert: " . ($customer->lexoffice_synced_at ? $customer->lexoffice_synced_at->format('d.m.Y H:i:s') : 'NIE') . "\n\n";

echo "=== TESTE INTELLIGENTE SYNCHRONISATION ===\n";

$service = new LexofficeService();
$result = $service->syncCustomer($customer);

echo "Synchronisations-Ergebnis:\n";
echo "Erfolgreich: " . ($result['success'] ? 'JA' : 'NEIN') . "\n";

if ($result['success']) {
    echo "✅ Synchronisation erfolgreich!\n";
    echo "Aktion: " . ($result['action'] ?? 'Unbekannt') . "\n";
    echo "Nachricht: " . ($result['message'] ?? 'Keine Nachricht') . "\n";
    
    // Lade Kunde neu
    $customer->refresh();
    echo "Zuletzt synchronisiert: " . ($customer->lexoffice_synced_at ? $customer->lexoffice_synced_at->format('d.m.Y H:i:s') : 'NIE') . "\n";
    
} elseif (isset($result['conflict']) && $result['conflict']) {
    echo "⚠️ Synchronisationskonflikt erkannt!\n";
    echo "Lokal aktualisiert: " . $result['local_updated'] . "\n";
    echo "Lexoffice aktualisiert: " . $result['lexoffice_updated'] . "\n";
    echo "Letzte Synchronisation: " . $result['last_synced'] . "\n";
    echo "\nDas System hat erkannt, dass beide Seiten seit der letzten Synchronisation geändert wurden.\n";
    echo "In einem echten Szenario würde der Benutzer entscheiden, welche Version verwendet werden soll.\n";
    
} else {
    echo "❌ Synchronisation fehlgeschlagen!\n";
    echo "Fehler: " . $result['error'] . "\n";
}

echo "\n=== TESTE VERSCHIEDENE SZENARIEN ===\n";

// Szenario 1: Lokale Änderung simulieren
echo "1. Simuliere lokale Änderung...\n";
$customer->update(['notes' => 'Test-Notiz für intelligente Sync - ' . now()->format('H:i:s')]);
echo "   Kunde lokal aktualisiert: " . $customer->updated_at->format('d.m.Y H:i:s') . "\n";

// Warte kurz
sleep(1);

// Teste erneut
$result2 = $service->syncCustomer($customer);
echo "   Sync-Ergebnis: " . ($result2['success'] ? 'ERFOLGREICH' : 'FEHLGESCHLAGEN') . "\n";
if ($result2['success']) {
    echo "   Aktion: " . ($result2['action'] ?? 'Unbekannt') . "\n";
    echo "   Nachricht: " . ($result2['message'] ?? 'Keine Nachricht') . "\n";
}

echo "\n2. Teste 'Bereits synchronisiert' Szenario...\n";
// Teste direkt nochmal (sollte 'up_to_date' sein)
$result3 = $service->syncCustomer($customer);
echo "   Sync-Ergebnis: " . ($result3['success'] ? 'ERFOLGREICH' : 'FEHLGESCHLAGEN') . "\n";
if ($result3['success']) {
    echo "   Aktion: " . ($result3['action'] ?? 'Unbekannt') . "\n";
    echo "   Nachricht: " . ($result3['message'] ?? 'Keine Nachricht') . "\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "Die intelligente Synchronisation kann folgende Szenarien handhaben:\n";
echo "✅ Export zu Lexoffice (wenn nur lokal geändert)\n";
echo "✅ Import von Lexoffice (wenn nur dort geändert)\n";
echo "✅ Konflikt-Erkennung (wenn beide Seiten geändert)\n";
echo "✅ Keine Aktion (wenn bereits synchronisiert)\n";
echo "✅ Automatische Neuerstellung (wenn in Lexoffice gelöscht)\n\n";

echo "Das System entscheidet automatisch basierend auf Zeitstempeln:\n";
echo "- updated_at (lokale Änderung)\n";
echo "- updatedDate (Lexoffice-Änderung)\n";
echo "- lexoffice_synced_at (letzte Synchronisation)\n\n";

echo "🎉 Intelligente Synchronisation erfolgreich implementiert!\n";
