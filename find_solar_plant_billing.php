<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\SolarPlantBilling;
use App\Models\SupplierContractBilling;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Finde alle Solaranlagen-Abrechnungen
$billings = SolarPlantBilling::with(['solarPlant', 'customer'])
    ->whereNotNull('credit_breakdown')
    ->where(function($q) {
        $q->whereJsonLength('credit_breakdown', '>', 0);
    })
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($billings->isEmpty()) {
    echo "Keine Solaranlagen-Abrechnungen mit Gutschriften gefunden.\n";
    
    // Zeige alle vorhandenen Abrechnungen
    $allBillings = SolarPlantBilling::with(['solarPlant', 'customer'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    if ($allBillings->isEmpty()) {
        echo "Überhaupt keine Solaranlagen-Abrechnungen gefunden.\n";
    } else {
        echo "\nVorhandene Abrechnungen (ohne Gutschriften):\n";
        foreach ($allBillings as $billing) {
            echo "- ID: {$billing->id}\n";
            echo "  Rechnungsnr: {$billing->invoice_number}\n";
            echo "  Kunde: " . ($billing->customer->name ?? 'Unbekannt') . "\n";
            echo "  Solaranlage: " . ($billing->solarPlant->name ?? 'Unbekannt') . "\n";
            echo "  Periode: {$billing->billing_month}/{$billing->billing_year}\n";
            echo "  Gutschriften: " . number_format($billing->total_credits, 2, ',', '.') . " €\n";
            echo "  Hat credit_breakdown: " . (is_array($billing->credit_breakdown) && count($billing->credit_breakdown) > 0 ? 'Ja' : 'Nein') . "\n";
            echo "\n";
        }
    }
} else {
    echo "Gefundene Solaranlagen-Abrechnungen mit Gutschriften:\n\n";
    
    foreach ($billings as $billing) {
        echo "ID: {$billing->id}\n";
        echo "Rechnungsnr: {$billing->invoice_number}\n";
        echo "Kunde: " . ($billing->customer->name ?? 'Unbekannt') . "\n";
        echo "Solaranlage: " . ($billing->solarPlant->name ?? 'Unbekannt') . "\n";
        echo "Periode: {$billing->billing_month}/{$billing->billing_year}\n";
        echo "Gutschriften: " . number_format($billing->total_credits, 2, ',', '.') . " €\n";
        
        // Zeige Details aus credit_breakdown
        if (is_array($billing->credit_breakdown)) {
            echo "Anzahl Gutschriften-Positionen: " . count($billing->credit_breakdown) . "\n";
            
            foreach ($billing->credit_breakdown as $index => $credit) {
                echo "  - Position " . ($index + 1) . ": ";
                echo ($credit['supplier_name'] ?? 'Unbekannt') . " - ";
                echo number_format($credit['customer_share'] ?? 0, 2, ',', '.') . " €";
                
                if (isset($credit['contract_billing_id'])) {
                    $contractBilling = SupplierContractBilling::find($credit['contract_billing_id']);
                    if ($contractBilling) {
                        $articlesCount = $contractBilling->articles()->count();
                        echo " ({$articlesCount} Artikel)";
                    }
                }
                echo "\n";
            }
        }
        
        echo str_repeat("-", 50) . "\n\n";
    }
    
    echo "\nVerwenden Sie eine der oben genannten IDs für weitere Tests.\n";
}
