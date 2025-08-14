<?php

require_once 'vendor/autoload.php';

// Laravel bootstrapping
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Models\SolarPlant;
use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;

echo "=== Debug: calculateCostsForCustomer Methode ===\n\n";

// Finde eine bestehende Abrechnung
$billing = SolarPlantBilling::first();
if (!$billing) {
    echo "Keine Abrechnung gefunden.\n";
    exit;
}

$solarPlantId = $billing->solar_plant_id;
$customerId = $billing->customer_id;
$year = $billing->billing_year;
$month = $billing->billing_month;
$percentage = $billing->participation_percentage;

echo "Debug für:\n";
echo "- Solaranlage: {$solarPlantId}\n";
echo "- Kunde: {$customerId}\n";
echo "- Jahr: {$year}, Monat: {$month}\n";
echo "- Anteil: {$percentage}%\n\n";

// Schritt 1: Hole die Solaranlage
$solarPlant = SolarPlant::find($solarPlantId);
if (!$solarPlant) {
    echo "Solaranlage nicht gefunden.\n";
    exit;
}

echo "1. Solaranlage gefunden: {$solarPlant->name}\n";

// Schritt 2: Hole aktive Verträge
$activeContracts = $solarPlant->activeSupplierContracts()->get();
echo "2. Aktive Verträge: " . $activeContracts->count() . "\n";

foreach ($activeContracts as $contract) {
    echo "   - Vertrag: {$contract->title} (ID: {$contract->id})\n";
    echo "     Lieferant: " . ($contract->supplier->company_name ?? $contract->supplier->name ?? 'Unbekannt') . "\n";
    
    // Schritt 3: Hole Abrechnungen für diesen Vertrag
    $contractBillings = $contract->billings()
        ->where('billing_year', $year)
        ->where('billing_month', $month)
        ->get();
    
    echo "     Abrechnungen für {$year}-{$month}: " . $contractBillings->count() . "\n";
    
    foreach ($contractBillings as $contractBilling) {
        echo "       * Abrechnung: {$contractBilling->billing_number} (Typ: " . ($contractBilling->billing_type ?? 'nicht gesetzt') . ")\n";
        echo "         Gesamtbetrag: {$contractBilling->total_amount}\n";
        echo "         Nettobetrag: " . ($contractBilling->net_amount ?? 'nicht gesetzt') . "\n";
        
        // Schritt 4: Hole die Artikel für diese Abrechnung
        $articles = $contractBilling->articles()->get();
        echo "         Artikel: " . $articles->count() . "\n";
        
        foreach ($articles as $article) {
            echo "           - Artikel: " . ($article->description ?? 'Keine Beschreibung') . "\n";
            echo "             article_id: " . ($article->article_id ?? 'NICHT GESETZT') . "\n";
            echo "             quantity: {$article->quantity}\n";
            echo "             unit_price: {$article->unit_price}\n";
            echo "             total_price: {$article->total_price}\n";
            
            if ($article->article_id) {
                $articleModel = \App\Models\Article::find($article->article_id);
                if ($articleModel) {
                    echo "             Artikel-Model: {$articleModel->name}\n";
                    echo "             decimal_places: " . ($articleModel->decimal_places ?? 'null') . "\n";
                    echo "             total_decimal_places: " . ($articleModel->total_decimal_places ?? 'null') . "\n";
                } else {
                    echo "             Artikel-Model: NICHT GEFUNDEN\n";
                }
            }
            echo "\n";
        }
        echo "\n";
    }
    echo "\n";
}

echo "\n=== Debug abgeschlossen ===\n";
