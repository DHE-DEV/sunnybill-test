<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SolarPlantBilling;

echo "=== Test der Billing-Beschreibungen ===" . PHP_EOL;

$billingId = '0198568d-2e48-71c9-8145-95e70e504bf3';
$billing = SolarPlantBilling::find($billingId);

if (!$billing) {
    echo "❌ Billing mit ID {$billingId} nicht gefunden!" . PHP_EOL;
    exit(1);
}

echo "✅ Billing gefunden: {$billing->id}" . PHP_EOL;
echo "Solar Plant: {$billing->solar_plant_id}" . PHP_EOL;
echo "Customer: {$billing->customer_id}" . PHP_EOL;
echo "Monat: {$billing->billing_month}/{$billing->billing_year}" . PHP_EOL;
echo PHP_EOL;

// Prüfe Credit Breakdown
echo "=== CREDIT BREAKDOWN ===" . PHP_EOL;
if (empty($billing->credit_breakdown)) {
    echo "❌ Keine Credit Breakdown Daten!" . PHP_EOL;
} else {
    foreach ($billing->credit_breakdown as $i => $credit) {
        echo "Credit #{$i}:" . PHP_EOL;
        echo "  - Supplier: " . ($credit['supplier_name'] ?? 'Unbekannt') . PHP_EOL;
        echo "  - Contract: " . ($credit['contract_title'] ?? $credit['contract_number'] ?? 'Unbekannt') . PHP_EOL;
        echo "  - Description: " . ($credit['billing_description'] ?? 'KEINE BESCHREIBUNG') . PHP_EOL;
        echo "  - Amount: " . ($credit['customer_share'] ?? 0) . " €" . PHP_EOL;
        echo PHP_EOL;
    }
}

// Prüfe Cost Breakdown
echo "=== COST BREAKDOWN ===" . PHP_EOL;
if (empty($billing->cost_breakdown)) {
    echo "❌ Keine Cost Breakdown Daten!" . PHP_EOL;
} else {
    foreach ($billing->cost_breakdown as $i => $cost) {
        echo "Cost #{$i}:" . PHP_EOL;
        echo "  - Supplier: " . ($cost['supplier_name'] ?? 'Unbekannt') . PHP_EOL;
        echo "  - Contract: " . ($cost['contract_title'] ?? $cost['contract_number'] ?? 'Unbekannt') . PHP_EOL;
        echo "  - Description: " . ($cost['billing_description'] ?? 'KEINE BESCHREIBUNG') . PHP_EOL;
        echo "  - Amount: " . ($cost['customer_share'] ?? 0) . " €" . PHP_EOL;
        echo PHP_EOL;
    }
}

// Aktualisiere die Daten neu
echo "=== DATEN NEU BERECHNEN ===" . PHP_EOL;
try {
    $costData = SolarPlantBilling::calculateCostsForCustomer(
        $billing->solar_plant_id, 
        $billing->customer_id, 
        $billing->billing_year, 
        $billing->billing_month, 
        $billing->participation_percentage
    );
    
    // Speichere die aktualisierten Daten
    $billing->cost_breakdown = $costData['cost_breakdown'];
    $billing->credit_breakdown = $costData['credit_breakdown'];
    $billing->total_costs = $costData['total_costs'];
    $billing->total_credits = $costData['total_credits'];
    $billing->total_costs_net = $costData['total_costs_net'] ?? 0;
    $billing->total_credits_net = $costData['total_credits_net'] ?? 0;
    $billing->total_vat_amount = $costData['total_vat_amount'] ?? 0;
    $billing->save();
    
    echo "✅ Daten erfolgreich aktualisiert!" . PHP_EOL;
    
    echo PHP_EOL . "=== AKTUALISIERTE CREDIT BREAKDOWN ===" . PHP_EOL;
    foreach ($billing->credit_breakdown as $i => $credit) {
        echo "Credit #{$i}:" . PHP_EOL;
        echo "  - Description: " . ($credit['billing_description'] ?? 'KEINE BESCHREIBUNG') . PHP_EOL;
        if (!empty($credit['billing_description']) && $credit['billing_description'] !== 'LEER') {
            echo "  ✅ Beschreibung ist vorhanden und nicht leer!" . PHP_EOL;
        } else {
            echo "  ❌ Beschreibung fehlt oder ist leer!" . PHP_EOL;
        }
        echo PHP_EOL;
    }
    
    echo "=== AKTUALISIERTE COST BREAKDOWN ===" . PHP_EOL;
    foreach ($billing->cost_breakdown as $i => $cost) {
        echo "Cost #{$i}:" . PHP_EOL;
        echo "  - Description: " . ($cost['billing_description'] ?? 'KEINE BESCHREIBUNG') . PHP_EOL;
        if (!empty($cost['billing_description']) && $cost['billing_description'] !== 'LEER') {
            echo "  ✅ Beschreibung ist vorhanden und nicht leer!" . PHP_EOL;
        } else {
            echo "  ❌ Beschreibung fehlt oder ist leer!" . PHP_EOL;
        }
        echo PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Fehler beim Berechnen der Daten: " . $e->getMessage() . PHP_EOL;
}

echo "=== Test beendet ===" . PHP_EOL;
