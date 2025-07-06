<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlant;
use App\Models\SolarPlantBilling;
use App\Models\Customer;

echo "=== TEST: LÖSCHEN UND ERNEUT ERSTELLEN ===" . PHP_EOL;

$plantId = '0197cf8d-f147-7021-8fa1-10ebadf1c58b';

echo "1. Aktuelle Abrechnungen anzeigen..." . PHP_EOL;

$currentBillings = SolarPlantBilling::where('solar_plant_id', $plantId)
    ->where('billing_year', 2024)
    ->where('billing_month', 4)
    ->get();

echo "Aktuelle Abrechnungen: " . $currentBillings->count() . PHP_EOL;

foreach ($currentBillings as $billing) {
    $customer = Customer::find($billing->customer_id);
    echo "  - Kunde: " . ($customer->name ?? 'Unbekannt') . 
         " | Kosten: " . $billing->total_costs . 
         " | Gutschriften: " . $billing->total_credits . 
         " | Netto: " . $billing->net_amount . PHP_EOL;
}

echo PHP_EOL . "2. Lösche alle Abrechnungen (Soft Delete)..." . PHP_EOL;

$deletedCount = SolarPlantBilling::where('solar_plant_id', $plantId)
    ->where('billing_year', 2024)
    ->where('billing_month', 4)
    ->delete();

echo "Gelöschte Abrechnungen: " . $deletedCount . PHP_EOL;

echo PHP_EOL . "3. Prüfe gelöschte Abrechnungen..." . PHP_EOL;

$trashedBillings = SolarPlantBilling::onlyTrashed()
    ->where('solar_plant_id', $plantId)
    ->where('billing_year', 2024)
    ->where('billing_month', 4)
    ->get();

echo "Gelöschte Abrechnungen: " . $trashedBillings->count() . PHP_EOL;

foreach ($trashedBillings as $billing) {
    $customer = Customer::find($billing->customer_id);
    echo "  - Kunde: " . ($customer->name ?? 'Unbekannt') . 
         " | Gelöscht am: " . $billing->deleted_at . PHP_EOL;
}

echo PHP_EOL . "4. Versuche erneut zu erstellen (sollte wiederherstellen)..." . PHP_EOL;

try {
    $newBillings = SolarPlantBilling::createBillingsForMonth($plantId, 2024, 4);
    echo "✅ Erfolgreich " . count($newBillings) . " Abrechnungen erstellt/wiederhergestellt!" . PHP_EOL;
    
    foreach ($newBillings as $billing) {
        $customer = Customer::find($billing->customer_id);
        echo "  - Kunde: " . ($customer->name ?? 'Unbekannt') . PHP_EOL;
        echo "    Kosten: " . $billing->total_costs . " EUR" . PHP_EOL;
        echo "    Gutschriften: " . $billing->total_credits . " EUR" . PHP_EOL;
        echo "    Nettobetrag: " . $billing->net_amount . " EUR" . PHP_EOL;
        echo "    Status: " . $billing->status . PHP_EOL;
        echo "    Gelöscht: " . ($billing->deleted_at ? 'JA' : 'NEIN') . PHP_EOL;
        echo "---" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Fehler beim Erstellen der Abrechnungen:" . PHP_EOL;
    echo "Nachricht: " . $e->getMessage() . PHP_EOL;
    echo "Datei: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}

echo PHP_EOL . "5. Prüfe finale Situation..." . PHP_EOL;

$finalBillings = SolarPlantBilling::where('solar_plant_id', $plantId)
    ->where('billing_year', 2024)
    ->where('billing_month', 4)
    ->get();

echo "Aktive Abrechnungen: " . $finalBillings->count() . PHP_EOL;

$finalTrashedBillings = SolarPlantBilling::onlyTrashed()
    ->where('solar_plant_id', $plantId)
    ->where('billing_year', 2024)
    ->where('billing_month', 4)
    ->count();

echo "Gelöschte Abrechnungen: " . $finalTrashedBillings . PHP_EOL;

echo PHP_EOL . "6. Test der doppelten Erstellung (sollte 0 neue erstellen)..." . PHP_EOL;

try {
    $duplicateBillings = SolarPlantBilling::createBillingsForMonth($plantId, 2024, 4);
    echo "✅ Zweiter Aufruf erfolgreich: " . count($duplicateBillings) . " neue Abrechnungen (sollte 0 sein)" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Fehler beim zweiten Aufruf: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== TEST ABGESCHLOSSEN ===" . PHP_EOL;