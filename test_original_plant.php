<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Models\SolarPlant;
use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;

echo "=== Test der ursprünglichen Solaranlage aus der URL ===" . PHP_EOL;

$plantId = '0197d1dd-7669-7084-8a1c-01134f48ed16';
$year = 2024;
$month = 4;

echo "Teste Solaranlage: $plantId" . PHP_EOL;
echo "Monat: $month/$year" . PHP_EOL . PHP_EOL;

// 1. Prüfe die Solaranlage
echo "1. Solaranlage prüfen:" . PHP_EOL;
$plant = SolarPlant::find($plantId);
if (!$plant) {
    echo "❌ Solaranlage nicht gefunden!" . PHP_EOL;
    exit(1);
}

echo "✅ Solaranlage gefunden: " . $plant->name . PHP_EOL;
echo "Aktive Verträge: " . $plant->activeSupplierContracts()->count() . PHP_EOL;

// 2. Prüfe Verträge und deren Abrechnungen
echo PHP_EOL . "2. Verträge und Abrechnungen:" . PHP_EOL;
$contracts = $plant->activeSupplierContracts()->get();
foreach ($contracts as $contract) {
    echo "Vertrag: " . $contract->title . PHP_EOL;
    $billing = $contract->billings()
        ->where('billing_year', $year)
        ->where('billing_month', $month)
        ->first();
    
    if ($billing) {
        echo "  ✅ Abrechnung vorhanden: " . number_format($billing->total_amount, 2) . " €" . PHP_EOL;
    } else {
        echo "  ❌ Keine Abrechnung für $month/$year" . PHP_EOL;
    }
}

// 3. Prüfe bestehende Solaranlagen-Abrechnungen
echo PHP_EOL . "3. Bestehende Solaranlagen-Abrechnungen:" . PHP_EOL;
$existingBillings = SolarPlantBilling::withTrashed()
    ->where('solar_plant_id', $plantId)
    ->where('billing_year', $year)
    ->where('billing_month', $month)
    ->get();

if ($existingBillings->count() > 0) {
    foreach ($existingBillings as $billing) {
        $status = $billing->trashed() ? 'GELÖSCHT' : 'AKTIV';
        $customerName = $billing->customer ? $billing->customer->name : 'Unbekannt';
        echo "  - Kunde: $customerName [$status]" . PHP_EOL;
        if (!$billing->trashed()) {
            echo "    Kosten: " . number_format($billing->total_costs, 2) . " €" . PHP_EOL;
            echo "    Gutschriften: " . number_format($billing->total_credits, 2) . " €" . PHP_EOL;
            echo "    Nettobetrag: " . number_format($billing->net_amount, 2) . " €" . PHP_EOL;
        }
    }
} else {
    echo "  Keine Solaranlagen-Abrechnungen vorhanden" . PHP_EOL;
}

// 4. Teste Kostenberechnung für einen Kunden
echo PHP_EOL . "4. Teste Kostenberechnung:" . PHP_EOL;
$participations = $plant->participations()->get();
if ($participations->count() > 0) {
    $firstParticipation = $participations->first();
    $customerId = $firstParticipation->customer_id;
    $customerName = $firstParticipation->customer ? $firstParticipation->customer->name : 'Unbekannt';
    
    echo "Teste für Kunde: $customerName" . PHP_EOL;
    
    try {
        $costData = SolarPlantBilling::calculateCostsForCustomer($plantId, $customerId, $year, $month);
        echo "✅ Kostenberechnung erfolgreich:" . PHP_EOL;
        echo "  Kosten: " . number_format($costData['total_costs'], 2) . " €" . PHP_EOL;
        echo "  Gutschriften: " . number_format($costData['total_credits'], 2) . " €" . PHP_EOL;
        echo "  Nettobetrag: " . number_format($costData['net_amount'], 2) . " €" . PHP_EOL;
        echo "  Kostenpositionen: " . count($costData['cost_breakdown']) . PHP_EOL;
        echo "  Gutschriftenpositionen: " . count($costData['credit_breakdown']) . PHP_EOL;
    } catch (Exception $e) {
        echo "❌ Fehler bei Kostenberechnung: " . $e->getMessage() . PHP_EOL;
    }
} else {
    echo "❌ Keine Kundenbeteiligungen gefunden" . PHP_EOL;
}

// 5. Versuche Abrechnungen zu erstellen
echo PHP_EOL . "5. Erstelle Solaranlagen-Abrechnungen:" . PHP_EOL;
try {
    $billings = SolarPlantBilling::createBillingsForMonth($plantId, $year, $month);
    echo "✅ Erfolgreich " . count($billings) . " Abrechnungen erstellt!" . PHP_EOL;
    
    foreach ($billings as $billing) {
        $customerName = $billing->customer ? $billing->customer->name : 'Unbekannt';
        echo "  - Kunde: $customerName" . PHP_EOL;
        echo "    Kosten: " . number_format($billing->total_costs, 2) . " €" . PHP_EOL;
        echo "    Gutschriften: " . number_format($billing->total_credits, 2) . " €" . PHP_EOL;
        echo "    Nettobetrag: " . number_format($billing->net_amount, 2) . " €" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Fehler beim Erstellen: " . $e->getMessage() . PHP_EOL;
    echo "Details: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}

// 6. Finale Prüfung
echo PHP_EOL . "6. Finale Prüfung:" . PHP_EOL;
$finalBillings = SolarPlantBilling::where('solar_plant_id', $plantId)
    ->where('billing_year', $year)
    ->where('billing_month', $month)
    ->get();

echo "Aktive Solaranlagen-Abrechnungen: " . $finalBillings->count() . PHP_EOL;
foreach ($finalBillings as $billing) {
    $customerName = $billing->customer ? $billing->customer->name : 'Unbekannt';
    echo "  - $customerName: " . number_format($billing->net_amount, 2) . " €" . PHP_EOL;
}

echo PHP_EOL . "=== Test abgeschlossen ===" . PHP_EOL;