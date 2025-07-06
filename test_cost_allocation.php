<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Models\SolarPlant;
use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;

echo "=== Analyse der Kostenaufteilung Problem ===" . PHP_EOL;

$billingId = '0197d1f5-9e0b-72f4-8874-899319b69234';

// 1. Prüfe die spezifische Abrechnung
echo "1. Spezifische Solaranlagen-Abrechnung analysieren:" . PHP_EOL;
$billing = SolarPlantBilling::find($billingId);

if (!$billing) {
    echo "❌ Abrechnung nicht gefunden!" . PHP_EOL;
    exit(1);
}

$plant = $billing->solarPlant;
$customer = $billing->customer;

echo "Abrechnung ID: $billingId" . PHP_EOL;
echo "Solaranlage: " . $plant->name . " (ID: " . $plant->id . ")" . PHP_EOL;
echo "Kunde: " . ($customer ? $customer->name : 'Unbekannt') . PHP_EOL;
echo "Aktuelle Kosten: " . number_format($billing->total_costs, 2) . " €" . PHP_EOL;
echo "Aktuelle Gutschriften: " . number_format($billing->total_credits, 2) . " €" . PHP_EOL;
echo "Aktueller Nettobetrag: " . number_format($billing->net_amount, 2) . " €" . PHP_EOL;

// 2. Analysiere die Verträge dieser Solaranlage
echo PHP_EOL . "2. Verträge der Solaranlage analysieren:" . PHP_EOL;
$contracts = $plant->activeSupplierContracts()->get();

foreach ($contracts as $contract) {
    echo "Vertrag: " . $contract->title . PHP_EOL;
    echo "  Supplier: " . ($contract->supplier ? $contract->supplier->company_name : 'Unbekannt') . PHP_EOL;
    
    // Prüfe alle Solaranlagen dieses Vertrags
    $contractPlants = $contract->solarPlants()->get();
    echo "  Kostenträger (Solaranlagen): " . $contractPlants->count() . PHP_EOL;
    
    foreach ($contractPlants as $contractPlant) {
        $pivotData = $contractPlant->pivot;
        $percentage = $pivotData->percentage ?? 0;
        echo "    - " . $contractPlant->name . ": " . $percentage . "%" . PHP_EOL;
    }
    
    // Prüfe Abrechnungen für diesen Vertrag
    $contractBilling = $contract->billings()
        ->where('billing_year', $billing->billing_year)
        ->where('billing_month', $billing->billing_month)
        ->first();
    
    if ($contractBilling) {
        echo "  Vertragsabrechnung: " . number_format($contractBilling->total_amount, 2) . " €" . PHP_EOL;
        
        // Berechne was diese Solaranlage anteilig zahlen sollte
        $plantPercentage = 0;
        foreach ($contractPlants as $contractPlant) {
            if ($contractPlant->id === $plant->id) {
                $plantPercentage = $contractPlant->pivot->percentage ?? 0;
                break;
            }
        }
        
        $plantShare = ($contractBilling->total_amount * $plantPercentage) / 100;
        echo "  Anteil dieser Solaranlage ($plantPercentage%): " . number_format($plantShare, 2) . " €" . PHP_EOL;
    } else {
        echo "  ❌ Keine Vertragsabrechnung für " . $billing->billing_month . "/" . $billing->billing_year . PHP_EOL;
    }
    echo "---" . PHP_EOL;
}

// 3. Analysiere Kundenbeteiligungen
echo PHP_EOL . "3. Kundenbeteiligungen der Solaranlage:" . PHP_EOL;
$participations = $plant->participations()->get();

foreach ($participations as $participation) {
    $participationCustomer = $participation->customer;
    $customerName = $participationCustomer ? $participationCustomer->name : 'Unbekannt';
    echo "  - Kunde: $customerName" . PHP_EOL;
    echo "    Beteiligung: " . $participation->percentage . "%" . PHP_EOL;
}

// 4. Teste die aktuelle Kostenberechnung
echo PHP_EOL . "4. Aktuelle Kostenberechnung testen:" . PHP_EOL;
try {
    $costData = SolarPlantBilling::calculateCostsForCustomer(
        $plant->id,
        $customer->id,
        $billing->billing_year,
        $billing->billing_month
    );
    
    echo "Berechnete Kosten: " . number_format($costData['total_costs'], 2) . " €" . PHP_EOL;
    echo "Berechnete Gutschriften: " . number_format($costData['total_credits'], 2) . " €" . PHP_EOL;
    echo "Berechneter Nettobetrag: " . number_format($costData['net_amount'], 2) . " €" . PHP_EOL;
    
    echo PHP_EOL . "Kostenaufschlüsselung:" . PHP_EOL;
    foreach ($costData['cost_breakdown'] as $cost) {
        echo "  - " . $cost['contract_title'] . ": " . number_format($cost['customer_share'], 2) . " € (von " . number_format($cost['total_amount'], 2) . " €)" . PHP_EOL;
    }
    
    echo PHP_EOL . "Gutschriftenaufschlüsselung:" . PHP_EOL;
    foreach ($costData['credit_breakdown'] as $credit) {
        echo "  - " . $credit['contract_title'] . ": " . number_format($credit['customer_share'], 2) . " € (von " . number_format($credit['total_amount'], 2) . " €)" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Fehler bei Kostenberechnung: " . $e->getMessage() . PHP_EOL;
}

// 5. Zeige das Problem auf
echo PHP_EOL . "5. Problem-Analyse:" . PHP_EOL;
echo "Das Problem ist, dass die Kostenberechnung den gesamten Vertragsbetrag nimmt" . PHP_EOL;
echo "und nur mit der Kundenbeteiligung multipliziert, aber NICHT berücksichtigt," . PHP_EOL;
echo "dass der Vertrag möglicherweise mehrere Solaranlagen als Kostenträger hat." . PHP_EOL;
echo PHP_EOL;
echo "Korrekte Berechnung sollte sein:" . PHP_EOL;
echo "1. Vertragsbetrag * Solaranlagen-Anteil = Solaranlagen-Kosten" . PHP_EOL;
echo "2. Solaranlagen-Kosten * Kunden-Beteiligung = Kunden-Anteil" . PHP_EOL;

echo PHP_EOL . "=== Analyse abgeschlossen ===" . PHP_EOL;