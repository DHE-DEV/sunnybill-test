<?php

require_once 'vendor/autoload.php';

use App\Models\SolarPlantBilling;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUGGING SOLAR PLANT BILLING ARTICLES ===\n\n";

// ID aus der URL: admin/solar-plant-billings/019840c8-5157-7316-aa18-adc39afa124e
$billingId = '019840c8-5157-7316-aa18-adc39afa124e';

$billing = SolarPlantBilling::with(['customer', 'solarPlant'])->find($billingId);

if (!$billing) {
    echo "Billing not found!\n";
    exit(1);
}

echo "Billing ID: {$billing->id}\n";
echo "Customer: {$billing->customer->name}\n";
echo "Solar Plant: {$billing->solarPlant->name}\n";
echo "Period: {$billing->billing_month}/{$billing->billing_year}\n\n";

echo "=== BREAKDOWN WITH ARTICLES ===\n";

echo "\n--- CREDIT BREAKDOWN ---\n";
if (!empty($billing->credit_breakdown)) {
    foreach ($billing->credit_breakdown as $index => $credit) {
        echo "Credit {$index}:\n";
        echo "  Supplier: {$credit['supplier_name']}\n";
        echo "  Contract: {$credit['contract_title']}\n";
        echo "  Customer Share: {$credit['customer_share']}\n";
        
        if (isset($credit['articles']) && !empty($credit['articles'])) {
            echo "  Articles:\n";
            foreach ($credit['articles'] as $article) {
                echo "    - {$article['article_name']}: {$article['quantity']} x {$article['unit_price']} €\n";
                echo "      Total: {$article['total_price_net']} € (net)\n";
                if (isset($article['description'])) {
                    echo "      Description: {$article['description']}\n";
                }
            }
        } else {
            echo "  No articles found\n";
        }
        echo "\n";
    }
} else {
    echo "No credit breakdown found\n";
}

echo "\n--- COST BREAKDOWN ---\n";
if (!empty($billing->cost_breakdown)) {
    foreach ($billing->cost_breakdown as $index => $cost) {
        echo "Cost {$index}:\n";
        echo "  Supplier: {$cost['supplier_name']}\n";
        echo "  Contract: {$cost['contract_title']}\n";
        echo "  Customer Share: {$cost['customer_share']}\n";
        
        if (isset($cost['articles']) && !empty($cost['articles'])) {
            echo "  Articles:\n";
            foreach ($cost['articles'] as $article) {
                echo "    - {$article['article_name']}: {$article['quantity']} x {$article['unit_price']} €\n";
                echo "      Total: {$article['total_price_net']} € (net)\n";
                if (isset($article['description'])) {
                    echo "      Description: {$article['description']}\n";
                }
            }
        } else {
            echo "  No articles found\n";
        }
        echo "\n";
    }
} else {
    echo "No cost breakdown found\n";
}
