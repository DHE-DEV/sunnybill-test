<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\SupplierContractBilling;
use App\Models\SupplierContractBillingArticle;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "=== Debug für Billing ID: 019841fc-ff60-7116-bd01-2c393b4c096d ===\n\n";

// Suche nach der Billing
$billing = SupplierContractBilling::find('019841fc-ff60-7116-bd01-2c393b4c096d');

if (!$billing) {
    echo "❌ Billing mit ID 019841fc-ff60-7116-bd01-2c393b4c096d nicht gefunden!\n";
    exit(1);
}

echo "✅ Billing gefunden:\n";
echo "ID: {$billing->id}\n";
echo "Period: {$billing->billing_period}\n";
echo "Status: {$billing->status}\n\n";

// Suche nach Artikeln mit "Einspeisung Marktwert" 
$articles = SupplierContractBillingArticle::where('supplier_contract_billing_id', $billing->id)
    ->whereRaw("LOWER(description) LIKE '%einspeisung%' AND LOWER(description) LIKE '%marktwert%'")
    ->get();

if ($articles->isEmpty()) {
    echo "❌ Keine Artikel mit 'Einspeisung Marktwert' in dieser Billing gefunden!\n";
    echo "\nAlle Artikel in dieser Billing:\n";
    
    $allArticles = SupplierContractBillingArticle::where('supplier_contract_billing_id', $billing->id)->get();
    foreach ($allArticles as $article) {
        echo "- ID: {$article->id}\n";
        echo "  Description: {$article->description}\n";
        echo "  Quantity: {$article->quantity}\n";
        echo "  Unit Price: {$article->unit_price}\n";
        echo "  Total Price: {$article->total_price}\n\n";
    }
} else {
    echo "✅ Einspeisung Marktwert Artikel gefunden:\n\n";
    foreach ($articles as $article) {
        echo "Artikel ID: {$article->id}\n";
        echo "Description: {$article->description}\n";
        echo "❗ Aktuelle Quantity: {$article->quantity}\n";
        echo "Unit Price: {$article->unit_price} €\n";
        echo "Total Price: {$article->total_price} €\n";
        
        // Berechne was die Quantity sein sollte basierend auf total_price / unit_price
        $calculatedQuantity = $article->unit_price != 0 ? $article->total_price / $article->unit_price : 0;
        echo "🔍 Berechnete Quantity (total_price / unit_price): {$calculatedQuantity}\n";
        echo "🎯 Gewünschte Quantity (laut Problem): 99226.825\n\n";
        
        // Berechne was der total_price sein sollte mit 99226.825
        $expectedTotalPrice = 99226.825 * $article->unit_price;
        echo "💰 Erwarteter Total Price mit 99226.825: {$expectedTotalPrice} €\n\n";
    }
}
