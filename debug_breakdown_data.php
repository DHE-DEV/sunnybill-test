<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Models\SupplierContract;

echo "=== Debug: Solar Plant Billing Breakdown Data ===\n\n";

// Hole eine Beispiel-Abrechnung mit breakdown Daten
$billing = SolarPlantBilling::whereNotNull('cost_breakdown')
    ->orWhereNotNull('credit_breakdown')
    ->first();

if (!$billing) {
    echo "Keine SolarPlantBilling mit breakdown Daten gefunden.\n";
    exit;
}

echo "Solar Plant Billing ID: {$billing->id}\n";
echo "Solar Plant ID: {$billing->solar_plant_id}\n";
echo "Customer ID: {$billing->customer_id}\n";
echo "Participation Percentage: {$billing->participation_percentage}%\n\n";

// Analysiere cost_breakdown
if ($billing->cost_breakdown) {
    echo "=== COST BREAKDOWN ===\n";
    foreach ($billing->cost_breakdown as $index => $item) {
        echo "Item {$index}:\n";
        foreach ($item as $key => $value) {
            if (is_array($value)) {
                echo "  {$key}: " . json_encode($value) . "\n";
            } else {
                echo "  {$key}: {$value}\n";
            }
        }
        
        // Versuche den Vertrag zu finden
        $contractId = $item['contract_id'] ?? null;
        if ($contractId) {
            $contract = SupplierContract::find($contractId);
            if ($contract) {
                echo "  Contract found: {$contract->contract_number}\n";
                
                // Prüfe Pivot-Daten für diese Solaranlage
                $solarPlantId = $billing->solar_plant_id;
                echo "  Looking for solar plant ID: {$solarPlantId}\n";
                
                $pivotData = $contract->solarPlants()
                    ->wherePivot('solar_plant_id', $solarPlantId)
                    ->first();
                
                if ($pivotData) {
                    echo "  Pivot data found!\n";
                    echo "  Participation percentage from pivot: {$pivotData->pivot->participation_percentage}%\n";
                    echo "  Percentage field from pivot: {$pivotData->pivot->percentage}%\n";
                } else {
                    echo "  No pivot data found for this solar plant\n";
                    
                    // Debug: zeige alle Pivot-Verbindungen für diesen Vertrag
                    $allPivots = $contract->solarPlants()->get();
                    echo "  All pivot connections for this contract:\n";
                    foreach ($allPivots as $plant) {
                        $participationPercentage = isset($plant->pivot->participation_percentage) ? $plant->pivot->participation_percentage : 'N/A';
                        $percentage = isset($plant->pivot->percentage) ? $plant->pivot->percentage : 'N/A';
                        echo "    Solar Plant ID: {$plant->id}, Participation: {$participationPercentage}%, Percentage: {$percentage}%\n";
                    }
                }
            } else {
                echo "  Contract not found!\n";
            }
        }
        echo "\n";
    }
}

// Analysiere credit_breakdown
if ($billing->credit_breakdown) {
    echo "=== CREDIT BREAKDOWN ===\n";
    foreach ($billing->credit_breakdown as $index => $item) {
        echo "Item {$index}:\n";
        foreach ($item as $key => $value) {
            echo "  {$key}: {$value}\n";
        }
        echo "\n";
    }
}

echo "=== Ende Debug ===\n";
