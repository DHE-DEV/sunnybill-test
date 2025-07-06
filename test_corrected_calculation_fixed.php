<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Models\SolarPlant;

echo "=== Test der korrigierten Kostenaufteilung ===" . PHP_EOL;

// Hole eine existierende Solaranlage aus der Datenbank
$plant = SolarPlant::first();
if (!$plant) {
    echo "Keine Solaranlage gefunden!" . PHP_EOL;
    exit;
}

echo "1. Teste korrigierte Berechnung für: " . $plant->name . PHP_EOL;
echo "Solaranlage ID: " . $plant->id . PHP_EOL;

// Hole den ersten Kunden der Anlage
$participation = $plant->participations()->first();
if (!$participation) {
    echo "Keine Kundenbeteiligung gefunden!" . PHP_EOL;
    exit;
}

$customerId = $participation->customer_id;
echo "Kunde ID: " . $customerId . PHP_EOL;
echo "Kunde: " . $participation->customer->name . PHP_EOL;

try {
    $costs = SolarPlantBilling::calculateCostsForCustomer($plant->id, $customerId, 2024, 4);
    
    echo PHP_EOL . "Neue Berechnungsergebnisse:" . PHP_EOL;
    echo "  Gesamtkosten: " . number_format($costs['total_costs'], 2, ',', '.') . " €" . PHP_EOL;
    echo "  Gesamtgutschriften: " . number_format($costs['total_credits'], 2, ',', '.') . " €" . PHP_EOL;
    echo "  Nettobetrag: " . number_format($costs['net_amount'], 2, ',', '.') . " €" . PHP_EOL;
    
    echo PHP_EOL . "Detaillierte Kostenaufschlüsselung:" . PHP_EOL;
    foreach ($costs['cost_breakdown'] as $cost) {
        echo "  - " . $cost['contract_title'] . ":" . PHP_EOL;
        echo "    Vertragsbetrag: " . number_format($cost['total_amount'], 2, ',', '.') . " €" . PHP_EOL;
        echo "    Solaranlagen-Anteil: " . $cost['solar_plant_percentage'] . "%" . PHP_EOL;
        echo "    Kunden-Anteil: " . $cost['customer_percentage'] . "%" . PHP_EOL;
        echo "    Kunden-Kosten: " . number_format($cost['customer_share'], 2, ',', '.') . " €" . PHP_EOL;
        echo "    Berechnung: " . number_format($cost['total_amount'], 2, ',', '.') . " × " . $cost['solar_plant_percentage'] . "% × " . $cost['customer_percentage'] . "% = " . number_format($cost['customer_share'], 2, ',', '.') . " €" . PHP_EOL;
        echo PHP_EOL;
    }
    
    echo "Detaillierte Gutschriftenaufschlüsselung:" . PHP_EOL;
    foreach ($costs['credit_breakdown'] as $credit) {
        echo "  - " . $credit['contract_title'] . ":" . PHP_EOL;
        echo "    Vertragsbetrag: " . number_format($credit['total_amount'], 2, ',', '.') . " €" . PHP_EOL;
        echo "    Solaranlagen-Anteil: " . $credit['solar_plant_percentage'] . "%" . PHP_EOL;
        echo "    Kunden-Anteil: " . $credit['customer_percentage'] . "%" . PHP_EOL;
        echo "    Kunden-Gutschrift: " . number_format($credit['customer_share'], 2, ',', '.') . " €" . PHP_EOL;
        echo "    Berechnung: " . number_format($credit['total_amount'], 2, ',', '.') . " × " . $credit['solar_plant_percentage'] . "% × " . $credit['customer_percentage'] . "% = " . number_format($credit['customer_share'], 2, ',', '.') . " €" . PHP_EOL;
        echo PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Fehler bei der Berechnung: " . $e->getMessage() . PHP_EOL;
    echo "Stack Trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "2. Teste auch die Solaranlage aus der ursprünglichen Analyse:" . PHP_EOL;

// Teste auch die spezifische Solaranlage aus der URL
$specificPlantId = '0197cf8d-f14a-73d4-b7f3-4b35cfc9ed40'; // Aurich 2
$specificPlant = SolarPlant::find($specificPlantId);

if ($specificPlant) {
    echo "Gefunden: " . $specificPlant->name . PHP_EOL;
    
    $specificParticipation = $specificPlant->participations()->first();
    if ($specificParticipation) {
        echo "Kunde: " . $specificParticipation->customer->name . PHP_EOL;
        
        try {
            $specificCosts = SolarPlantBilling::calculateCostsForCustomer($specificPlantId, $specificParticipation->customer_id, 2024, 4);
            echo "Kosten: " . number_format($specificCosts['total_costs'], 2, ',', '.') . " €" . PHP_EOL;
            echo "Gutschriften: " . number_format($specificCosts['total_credits'], 2, ',', '.') . " €" . PHP_EOL;
            echo "Netto: " . number_format($specificCosts['net_amount'], 2, ',', '.') . " €" . PHP_EOL;
        } catch (Exception $e) {
            echo "Fehler: " . $e->getMessage() . PHP_EOL;
        }
    }
} else {
    echo "Spezifische Solaranlage nicht gefunden." . PHP_EOL;
}

echo PHP_EOL . "=== Test abgeschlossen ===" . PHP_EOL;