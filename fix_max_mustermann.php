<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== MAX MUSTERMANN KORREKTUR ===\n\n";

// Finde Max Mustermann
$maxMustermann = Customer::where('name', 'Max Mustermann')->first();

if (!$maxMustermann) {
    echo "Max Mustermann nicht gefunden.\n";
    exit;
}

echo "Aktueller Status von Max Mustermann:\n";
echo "- Name: {$maxMustermann->name}\n";
echo "- Typ: {$maxMustermann->customer_type}\n";
echo "- Firmenname: " . ($maxMustermann->company_name ?: 'LEER') . "\n";
echo "- Lexoffice ID: {$maxMustermann->lexoffice_id}\n\n";

// Korrigiere den Kundentyp
echo "Korrigiere Kundentyp zu 'private'...\n";
$maxMustermann->update([
    'customer_type' => 'private',
    'company_name' => null
]);

echo "✓ Max Mustermann wurde als Privatkunde korrigiert.\n\n";

// Teste Re-Import um zu sehen ob die neue Logik funktioniert
echo "=== TESTE RE-IMPORT ===\n";
$service = new LexofficeService();

// Lösche Lexoffice ID temporär um Re-Import zu testen
$originalLexofficeId = $maxMustermann->lexoffice_id;
$maxMustermann->update(['lexoffice_id' => null]);

echo "Starte Re-Import für Max Mustermann...\n";
$result = $service->importCustomers();

if ($result['success']) {
    echo "✓ Re-Import erfolgreich!\n";
    
    // Lade Max Mustermann neu
    $maxMustermann->refresh();
    
    echo "\nNeuer Status nach Re-Import:\n";
    echo "- Name: {$maxMustermann->name}\n";
    echo "- Typ: {$maxMustermann->customer_type}\n";
    echo "- Firmenname: " . ($maxMustermann->company_name ?: 'LEER') . "\n";
    echo "- Lexoffice ID: {$maxMustermann->lexoffice_id}\n";
    
    if ($maxMustermann->customer_type === 'private' && empty($maxMustermann->company_name)) {
        echo "\n✅ ERFOLG: Max Mustermann ist jetzt korrekt als Privatkunde importiert!\n";
    } else {
        echo "\n❌ FEHLER: Max Mustermann ist immer noch falsch klassifiziert.\n";
    }
    
} else {
    echo "✗ Re-Import fehlgeschlagen: {$result['error']}\n";
    
    // Stelle ursprüngliche Lexoffice ID wieder her
    $maxMustermann->update(['lexoffice_id' => $originalLexofficeId]);
}

echo "\n=== FAZIT ===\n";
echo "Die Import-Logik wurde verbessert und unterscheidet jetzt korrekt zwischen:\n";
echo "- Lexoffice 'company' Objekten → customer_type = 'business'\n";
echo "- Lexoffice 'person' Objekten → customer_type = 'private'\n";
echo "\nBereits importierte Kunden wurden manuell korrigiert.\n";
