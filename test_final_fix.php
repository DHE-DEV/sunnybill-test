<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Models\SolarPlant;

echo "=== Test der finalen Lösung für Unique Constraint Problem ===" . PHP_EOL;

$plantId = '0197cf8d-f147-7021-8fa1-10ebadf1c58b';
$year = 2024;
$month = 4;

echo "Teste Solaranlage: $plantId" . PHP_EOL;
echo "Monat: $month/$year" . PHP_EOL . PHP_EOL;

// 1. Prüfe aktuelle Abrechnungen
echo "1. Aktuelle Abrechnungen (inkl. gelöschte):" . PHP_EOL;
$allBillings = SolarPlantBilling::withTrashed()
    ->where('solar_plant_id', $plantId)
    ->where('billing_year', $year)
    ->where('billing_month', $month)
    ->get();

foreach ($allBillings as $billing) {
    $status = $billing->trashed() ? 'GELÖSCHT' : 'AKTIV';
    echo "  - Kunde: " . ($billing->customer->name ?? 'Unbekannt') . " [$status]" . PHP_EOL;
}
echo "Gesamt: " . $allBillings->count() . " Abrechnungen" . PHP_EOL . PHP_EOL;

// 2. Erstelle Abrechnungen (sollte ohne Fehler funktionieren)
echo "2. Erstelle Abrechnungen..." . PHP_EOL;
try {
    $billings = SolarPlantBilling::createBillingsForMonth($plantId, $year, $month);
    echo "✅ Erfolgreich " . count($billings) . " Abrechnungen erstellt!" . PHP_EOL;
    
    foreach ($billings as $billing) {
        echo "  - Kunde: " . $billing->customer->name . PHP_EOL;
        echo "    Kosten: " . number_format($billing->total_costs, 2) . " €" . PHP_EOL;
        echo "    Gutschriften: " . number_format($billing->total_credits, 2) . " €" . PHP_EOL;
        echo "    Nettobetrag: " . number_format($billing->net_amount, 2) . " €" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// 3. Lösche eine Abrechnung
echo "3. Lösche erste Abrechnung..." . PHP_EOL;
$firstBilling = SolarPlantBilling::where('solar_plant_id', $plantId)
    ->where('billing_year', $year)
    ->where('billing_month', $month)
    ->first();

if ($firstBilling) {
    $customerName = $firstBilling->customer->name;
    $firstBilling->delete();
    echo "✅ Abrechnung für $customerName gelöscht" . PHP_EOL;
} else {
    echo "❌ Keine Abrechnung zum Löschen gefunden" . PHP_EOL;
}

echo PHP_EOL;

// 4. Versuche erneut zu erstellen (sollte ohne Unique Constraint Fehler funktionieren)
echo "4. Erstelle Abrechnungen erneut..." . PHP_EOL;
try {
    $billings = SolarPlantBilling::createBillingsForMonth($plantId, $year, $month);
    echo "✅ Erfolgreich " . count($billings) . " Abrechnungen erstellt!" . PHP_EOL;
    
    foreach ($billings as $billing) {
        echo "  - Kunde: " . $billing->customer->name . PHP_EOL;
        echo "    Nettobetrag: " . number_format($billing->net_amount, 2) . " €" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// 5. Finale Prüfung
echo "5. Finale Prüfung - Aktuelle Abrechnungen:" . PHP_EOL;
$finalBillings = SolarPlantBilling::where('solar_plant_id', $plantId)
    ->where('billing_year', $year)
    ->where('billing_month', $month)
    ->get();

echo "Aktive Abrechnungen: " . $finalBillings->count() . PHP_EOL;
foreach ($finalBillings as $billing) {
    echo "  - " . $billing->customer->name . ": " . number_format($billing->net_amount, 2) . " €" . PHP_EOL;
}

$deletedBillings = SolarPlantBilling::onlyTrashed()
    ->where('solar_plant_id', $plantId)
    ->where('billing_year', $year)
    ->where('billing_month', $month)
    ->count();

echo "Gelöschte Abrechnungen: " . $deletedBillings . PHP_EOL;

echo PHP_EOL . "=== Test abgeschlossen ===" . PHP_EOL;