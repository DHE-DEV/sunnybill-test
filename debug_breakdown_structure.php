<?php

require_once 'vendor/autoload.php';

use App\Models\SolarPlantBilling;
use Illuminate\Support\Facades\DB;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Debug: Breakdown-Struktur Problem ===" . PHP_EOL;

// Hole die spezifische Abrechnung
$billingId = '0197d1f5-9e0b-72f4-8874-899319b69234';
$billing = SolarPlantBilling::find($billingId);

if (!$billing) {
    echo "Abrechnung nicht gefunden!" . PHP_EOL;
    exit;
}

echo "Abrechnung gefunden: {$billing->id}" . PHP_EOL;
echo "Solaranlage: {$billing->solarPlant->name}" . PHP_EOL;
echo "Kunde: {$billing->customer->name}" . PHP_EOL;
echo PHP_EOL;

echo "=== Prüfe cost_breakdown Struktur ===" . PHP_EOL;
if ($billing->cost_breakdown) {
    echo "Cost Breakdown vorhanden: " . count($billing->cost_breakdown) . " Einträge" . PHP_EOL;
    foreach ($billing->cost_breakdown as $index => $item) {
        echo "Eintrag {$index}:" . PHP_EOL;
        echo "  Typ: " . gettype($item) . PHP_EOL;
        if (is_array($item)) {
            echo "  Schlüssel: " . implode(', ', array_keys($item)) . PHP_EOL;
            echo "  Vollständige Daten: " . json_encode($item, JSON_PRETTY_PRINT) . PHP_EOL;
            
            // Prüfe spezifisch nach percentage
            if (array_key_exists('percentage', $item)) {
                echo "  ✓ percentage vorhanden: " . $item['percentage'] . PHP_EOL;
            } else {
                echo "  ✗ percentage FEHLT!" . PHP_EOL;
                echo "  Verfügbare Schlüssel: " . implode(', ', array_keys($item)) . PHP_EOL;
            }
        }
        echo "---" . PHP_EOL;
    }
} else {
    echo "Keine cost_breakdown vorhanden" . PHP_EOL;
}

echo PHP_EOL;
echo "=== Prüfe credit_breakdown Struktur ===" . PHP_EOL;
if ($billing->credit_breakdown) {
    echo "Credit Breakdown vorhanden: " . count($billing->credit_breakdown) . " Einträge" . PHP_EOL;
    foreach ($billing->credit_breakdown as $index => $item) {
        echo "Eintrag {$index}:" . PHP_EOL;
        echo "  Typ: " . gettype($item) . PHP_EOL;
        if (is_array($item)) {
            echo "  Schlüssel: " . implode(', ', array_keys($item)) . PHP_EOL;
            echo "  Vollständige Daten: " . json_encode($item, JSON_PRETTY_PRINT) . PHP_EOL;
            
            // Prüfe spezifisch nach percentage
            if (array_key_exists('percentage', $item)) {
                echo "  ✓ percentage vorhanden: " . $item['percentage'] . PHP_EOL;
            } else {
                echo "  ✗ percentage FEHLT!" . PHP_EOL;
                echo "  Verfügbare Schlüssel: " . implode(', ', array_keys($item)) . PHP_EOL;
            }
        }
        echo "---" . PHP_EOL;
    }
} else {
    echo "Keine credit_breakdown vorhanden" . PHP_EOL;
}

echo PHP_EOL;
echo "=== Prüfe Rohdaten aus Datenbank ===" . PHP_EOL;
$rawData = DB::table('solar_plant_billings')
    ->where('id', $billingId)
    ->first();

if ($rawData) {
    echo "Raw cost_breakdown: " . $rawData->cost_breakdown . PHP_EOL;
    echo "Raw credit_breakdown: " . $rawData->credit_breakdown . PHP_EOL;
    
    // Versuche JSON zu dekodieren
    if ($rawData->cost_breakdown) {
        $decodedCost = json_decode($rawData->cost_breakdown, true);
        echo "Dekodierte cost_breakdown: " . json_encode($decodedCost, JSON_PRETTY_PRINT) . PHP_EOL;
    }
    
    if ($rawData->credit_breakdown) {
        $decodedCredit = json_decode($rawData->credit_breakdown, true);
        echo "Dekodierte credit_breakdown: " . json_encode($decodedCredit, JSON_PRETTY_PRINT) . PHP_EOL;
    }
}

echo PHP_EOL;
echo "=== Teste Neuberechnung ===" . PHP_EOL;
try {
    $costs = SolarPlantBilling::calculateCostsForCustomer(
        $billing->solar_plant_id,
        $billing->customer_id,
        $billing->billing_year,
        $billing->billing_month
    );
    
    echo "Neuberechnung erfolgreich!" . PHP_EOL;
    echo "Neue cost_breakdown Struktur:" . PHP_EOL;
    if (isset($costs['cost_breakdown'])) {
        foreach ($costs['cost_breakdown'] as $index => $item) {
            echo "  Eintrag {$index}: " . json_encode($item, JSON_PRETTY_PRINT) . PHP_EOL;
        }
    }
    
    echo "Neue credit_breakdown Struktur:" . PHP_EOL;
    if (isset($costs['credit_breakdown'])) {
        foreach ($costs['credit_breakdown'] as $index => $item) {
            echo "  Eintrag {$index}: " . json_encode($item, JSON_PRETTY_PRINT) . PHP_EOL;
        }
    }
    
} catch (Exception $e) {
    echo "Fehler bei Neuberechnung: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;
echo "=== Debug abgeschlossen ===" . PHP_EOL;