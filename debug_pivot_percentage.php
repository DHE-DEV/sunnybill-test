<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Models\SolarPlant;
use App\Models\SupplierContract;

echo "=== Debug: Pivot-Tabelle Percentage Problem ===" . PHP_EOL;

// Teste die spezifische Abrechnung aus der URL
$billingId = '0197d1f5-9e0b-72f4-8874-899319b69234';
$billing = SolarPlantBilling::find($billingId);

if (!$billing) {
    echo "Abrechnung nicht gefunden!" . PHP_EOL;
    exit;
}

echo "Abrechnung gefunden: " . $billing->id . PHP_EOL;
echo "Solaranlage: " . $billing->solarPlant->name . PHP_EOL;
echo "Kunde: " . ($billing->customer->name ?? 'Unbekannt') . PHP_EOL;

$solarPlant = $billing->solarPlant;
$activeContracts = $solarPlant->activeSupplierContracts()->get();

echo PHP_EOL . "Analysiere Verträge und Pivot-Daten:" . PHP_EOL;

foreach ($activeContracts as $contract) {
    echo "Vertrag: " . $contract->title . PHP_EOL;
    
    // Hole die Pivot-Daten
    $solarPlantPivot = $contract->solarPlants()
        ->where('solar_plant_id', $solarPlant->id)
        ->first();
    
    if ($solarPlantPivot) {
        echo "  Pivot gefunden" . PHP_EOL;
        echo "  Pivot-Daten: " . json_encode($solarPlantPivot->pivot->toArray()) . PHP_EOL;
        
        // Prüfe verschiedene Möglichkeiten für den Percentage-Wert
        if (isset($solarPlantPivot->pivot->percentage)) {
            echo "  Percentage (pivot->percentage): " . $solarPlantPivot->pivot->percentage . PHP_EOL;
        } else {
            echo "  ✗ pivot->percentage nicht gefunden" . PHP_EOL;
        }
        
        // Prüfe alternative Spaltennamen
        $pivotArray = $solarPlantPivot->pivot->toArray();
        foreach ($pivotArray as $key => $value) {
            if (stripos($key, 'percent') !== false || stripos($key, 'anteil') !== false) {
                echo "  Möglicher Percentage-Wert: $key = $value" . PHP_EOL;
            }
        }
    } else {
        echo "  ✗ Keine Pivot-Daten gefunden für diese Solaranlage" . PHP_EOL;
    }
    
    echo PHP_EOL;
}

echo "=== Prüfe Pivot-Tabelle direkt ===" . PHP_EOL;

// Prüfe die Pivot-Tabelle direkt
$pivotData = \DB::table('supplier_contract_solar_plants')
    ->where('solar_plant_id', $solarPlant->id)
    ->get();

echo "Direkte Pivot-Tabellen-Daten:" . PHP_EOL;
foreach ($pivotData as $pivot) {
    echo "  " . json_encode((array)$pivot) . PHP_EOL;
}

echo PHP_EOL . "=== Teste calculateCostsForCustomer mit Debug ===" . PHP_EOL;

try {
    $costs = SolarPlantBilling::calculateCostsForCustomer(
        $billing->solar_plant_id,
        $billing->customer_id,
        $billing->billing_year,
        $billing->billing_month
    );
    
    echo "Berechnung erfolgreich!" . PHP_EOL;
    echo "Kosten: " . $costs['total_costs'] . PHP_EOL;
    echo "Gutschriften: " . $costs['total_credits'] . PHP_EOL;
    
} catch (Exception $e) {
    echo "Fehler bei der Berechnung: " . $e->getMessage() . PHP_EOL;
    echo "Datei: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo "Stack Trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== Debug abgeschlossen ===" . PHP_EOL;