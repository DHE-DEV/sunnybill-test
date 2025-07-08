<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== LEXOFFICE SYNCED_AT TEST ===\n\n";

// Finde Max Mustermann
$maxMustermann = Customer::where('name', 'Max Mustermann')->first();

if (!$maxMustermann) {
    echo "❌ Max Mustermann nicht gefunden.\n";
    exit;
}

echo "Max Mustermann vor Synchronisation:\n";
echo "- ID: {$maxMustermann->id}\n";
echo "- Lexoffice ID: " . ($maxMustermann->lexoffice_id ?: 'KEINE') . "\n";
echo "- Zuletzt synchronisiert: " . ($maxMustermann->lexoffice_synced_at ? $maxMustermann->lexoffice_synced_at->format('d.m.Y H:i:s') : 'NIE') . "\n\n";

// Teste Synchronisation
echo "Führe Synchronisation durch...\n";
$service = new LexofficeService();
$result = $service->exportCustomer($maxMustermann);

if ($result['success']) {
    echo "✅ Synchronisation erfolgreich!\n";
    echo "Aktion: {$result['action']}\n";
    echo "Lexoffice ID: {$result['lexoffice_id']}\n\n";
    
    // Lade Kunde neu
    $maxMustermann->refresh();
    
    echo "Max Mustermann nach Synchronisation:\n";
    echo "- ID: {$maxMustermann->id}\n";
    echo "- Lexoffice ID: " . ($maxMustermann->lexoffice_id ?: 'KEINE') . "\n";
    echo "- Zuletzt synchronisiert: " . ($maxMustermann->lexoffice_synced_at ? $maxMustermann->lexoffice_synced_at->format('d.m.Y H:i:s') : 'NIE') . "\n";
    
    if ($maxMustermann->lexoffice_synced_at) {
        echo "✅ lexoffice_synced_at wurde korrekt gesetzt!\n";
    } else {
        echo "❌ lexoffice_synced_at wurde NICHT gesetzt!\n";
    }
} else {
    echo "❌ Synchronisation fehlgeschlagen: {$result['error']}\n";
}

echo "\n=== ALLE KUNDEN MIT LEXOFFICE_SYNCED_AT ===\n";
$customersWithSyncedAt = Customer::whereNotNull('lexoffice_synced_at')->get();

if ($customersWithSyncedAt->isEmpty()) {
    echo "Keine Kunden mit lexoffice_synced_at gefunden.\n";
} else {
    foreach ($customersWithSyncedAt as $customer) {
        echo "- {$customer->name}: {$customer->lexoffice_synced_at->format('d.m.Y H:i:s')}\n";
    }
}

echo "\n=== TESTE IMPORT ===\n";
echo "Führe Import durch (sollte lexoffice_synced_at setzen)...\n";
$importResult = $service->importCustomers();

if ($importResult['success']) {
    echo "✅ Import erfolgreich! Importierte Kunden: {$importResult['imported']}\n";
    
    // Prüfe erneut
    $customersWithSyncedAt = Customer::whereNotNull('lexoffice_synced_at')->get();
    echo "Kunden mit lexoffice_synced_at nach Import: {$customersWithSyncedAt->count()}\n";
    
    foreach ($customersWithSyncedAt as $customer) {
        echo "- {$customer->name}: {$customer->lexoffice_synced_at->format('d.m.Y H:i:s')}\n";
    }
} else {
    echo "❌ Import fehlgeschlagen: {$importResult['error']}\n";
}
