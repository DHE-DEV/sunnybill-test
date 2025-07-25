<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;

echo "=== Debug: Spezifische Abrechnung ===\n\n";

$billingId = '019840c8-5142-70cd-bb96-ac4a3f4a2828';
$billing = SolarPlantBilling::find($billingId);

if (!$billing) {
    echo "Abrechnung nicht gefunden: {$billingId}\n";
    exit;
}

echo "Solar Plant Billing ID: {$billing->id}\n";
echo "Solar Plant ID: {$billing->solar_plant_id}\n";
echo "Customer ID: {$billing->customer_id}\n";
echo "Customer Name: {$billing->customer->name}\n";
echo "Participation Percentage (from billing): {$billing->participation_percentage}%\n";

// Hole aktuelle Beteiligung
$participation = $billing->solarPlant->participations()
    ->where('customer_id', $billing->customer_id)
    ->first();

if ($participation) {
    echo "Current Participation Percentage: {$participation->percentage}%\n";
} else {
    echo "No current participation found\n";
}

echo "\n=== COST BREAKDOWN ANALYSIS ===\n";
if ($billing->cost_breakdown) {
    foreach ($billing->cost_breakdown as $index => $item) {
        echo "\nCost Item {$index}:\n";
        echo "  Contract Title: {$item['contract_title']}\n";
        echo "  Solar Plant Percentage: {$item['solar_plant_percentage']}%\n";
        echo "  Customer Percentage: {$item['customer_percentage']}%\n";
        echo "  Customer Share: {$item['customer_share']} €\n";
        echo "  Total Amount: {$item['total_amount']} €\n";
        
        // Berechne zur Überprüfung
        $expectedShare = $item['total_amount'] * ($item['solar_plant_percentage'] / 100) * ($item['customer_percentage'] / 100);
        echo "  Expected Share (calculated): {$expectedShare} €\n";
        echo "  Match: " . (abs($expectedShare - $item['customer_share']) < 0.01 ? 'YES' : 'NO') . "\n";
    }
}

echo "\n=== CREDIT BREAKDOWN ANALYSIS ===\n";
if ($billing->credit_breakdown) {
    foreach ($billing->credit_breakdown as $index => $item) {
        echo "\nCredit Item {$index}:\n";
        echo "  Contract Title: {$item['contract_title']}\n";
        echo "  Solar Plant Percentage: {$item['solar_plant_percentage']}%\n";
        echo "  Customer Percentage: {$item['customer_percentage']}%\n";
        echo "  Customer Share: {$item['customer_share']} €\n";
        echo "  Total Amount: {$item['total_amount']} €\n";
        
        // Berechne zur Überprüfung
        $expectedShare = abs($item['total_amount']) * ($item['solar_plant_percentage'] / 100) * ($item['customer_percentage'] / 100);
        echo "  Expected Share (calculated): {$expectedShare} €\n";
        echo "  Match: " . (abs($expectedShare - $item['customer_share']) < 0.01 ? 'YES' : 'NO') . "\n";
    }
}

echo "\n=== Ende Debug ===\n";
