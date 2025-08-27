<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$billing = \App\Models\SolarPlantBilling::find('0198e794-08d4-71f5-839b-59e8b1556839');

if ($billing) {
    echo "=== COST BREAKDOWN DATA ===\n";
    echo json_encode($billing->cost_breakdown, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "=== POSITION 4 ANALYSIS ===\n";
    if (!empty($billing->cost_breakdown)) {
        foreach ($billing->cost_breakdown as $index => $cost) {
            if ($cost['supplier_name'] === 'Prosoltec Anlagenbetreiber GmbH') {
                echo "Position: " . ($index + 4) . "\n";
                echo "Supplier: " . $cost['supplier_name'] . "\n";
                echo "Contract Title: " . ($cost['contract_title'] ?? 'N/A') . "\n";
                echo "Customer Share Net: " . ($cost['customer_share_net'] ?? 'N/A') . "\n";
                echo "Customer Share: " . ($cost['customer_share'] ?? 'N/A') . "\n";
                echo "VAT Rate: " . ($cost['vat_rate'] ?? 'N/A') . "\n";
                echo "Tax Calculation: " . (($cost['customer_share'] ?? 0) - ($cost['customer_share_net'] ?? 0)) . "\n";
                break;
            }
        }
    }
} else {
    echo "Billing not found!\n";
}

?>
