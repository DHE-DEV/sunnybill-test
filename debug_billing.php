<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SolarPlant;
use App\Models\SolarPlantBilling;
use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;

echo "=== DEBUG: Solaranlagen-Abrechnung Problem ===\n\n";

// Prüfe die spezifische Solaranlage aus der URL
$plantId = '0197d1dd-7669-7084-8a1c-01134f48ed16';
$plant = SolarPlant::find($plantId);

if (!$plant) {
    echo "❌ Solaranlage mit ID {$plantId} nicht gefunden!\n";
    exit;
}

echo "✅ Solaranlage gefunden: {$plant->name}\n";
echo "📍 Standort: {$plant->location}\n";
echo "🔢 Anlagen-Nr: {$plant->plant_number}\n\n";

// Prüfe aktive Verträge
$contracts = $plant->activeSupplierContracts()->get();
echo "📋 Aktive Verträge: " . $contracts->count() . "\n\n";

foreach ($contracts as $contract) {
    echo "📄 Vertrag: {$contract->title}\n";
    echo "   Lieferant: {$contract->supplier->name}\n";
    
    $billings = $contract->billings()->get();
    echo "   Abrechnungen: " . $billings->count() . "\n";
    
    foreach ($billings as $billing) {
        echo "   💰 Abrechnung {$billing->billing_year}-{$billing->billing_month}: {$billing->total_amount} EUR\n";
        echo "       Status: {$billing->status}\n";
        echo "       Beschreibung: {$billing->description}\n";
    }
    
    // Prüfe Pivot-Daten für Prozentsätze
    $pivot = $plant->supplierContracts()->where('supplier_contract_id', $contract->id)->first();
    if ($pivot) {
        echo "   📊 Prozentsatz: {$pivot->pivot->percentage}%\n";
    }
    echo "\n";
}

// Prüfe existierende Solaranlagen-Abrechnungen
$solarBillings = $plant->billings()->get();
echo "🌞 Solaranlagen-Abrechnungen: " . $solarBillings->count() . "\n\n";

foreach ($solarBillings as $billing) {
    echo "📊 Abrechnung {$billing->billing_year}-{$billing->billing_month}:\n";
    echo "   Kunde: {$billing->customer->name}\n";
    echo "   Kosten: {$billing->total_costs} EUR\n";
    echo "   Gutschriften: {$billing->total_credits} EUR\n";
    echo "   Nettobetrag: {$billing->net_amount} EUR\n";
    echo "   Status: {$billing->status}\n";
    
    if ($billing->cost_breakdown) {
        echo "   💸 Kostenaufschlüsselung:\n";
        foreach ($billing->cost_breakdown as $item) {
            echo "     - {$item['contract_title']}: {$item['customer_share']} EUR ({$item['percentage']}%)\n";
        }
    } else {
        echo "   ❌ Keine Kostenaufschlüsselung vorhanden\n";
    }
    
    if ($billing->credit_breakdown) {
        echo "   💰 Gutschriftenaufschlüsselung:\n";
        foreach ($billing->credit_breakdown as $item) {
            echo "     - {$item['contract_title']}: {$item['customer_share']} EUR ({$item['percentage']}%)\n";
        }
    } else {
        echo "   ❌ Keine Gutschriftenaufschlüsselung vorhanden\n";
    }
    echo "\n";
}

// Teste die calculateCostsForCustomer Methode direkt
echo "🔍 TESTE calculateCostsForCustomer Methode:\n\n";

// Hole den ersten Kunden der Anlage
$customer = $plant->participations()->first()?->customer;
if ($customer) {
    echo "👤 Teste für Kunde: {$customer->name}\n";
    
    try {
        $result = \App\Models\SolarPlantBilling::calculateCostsForCustomer($plantId, $customer->id, 2024, 4);
        
        echo "✅ Berechnung erfolgreich:\n";
        echo "   Gesamtkosten: {$result['total_costs']} EUR\n";
        echo "   Gesamtgutschriften: {$result['total_credits']} EUR\n";
        echo "   Nettobetrag: {$result['net_amount']} EUR\n";
        
        if (!empty($result['cost_breakdown'])) {
            echo "   💸 Kostenaufschlüsselung:\n";
            foreach ($result['cost_breakdown'] as $item) {
                echo "     - {$item['contract_title']}: {$item['customer_share']} EUR (von {$item['total_amount']} EUR, {$item['percentage']}%)\n";
            }
        } else {
            echo "   ❌ Keine Kostenaufschlüsselung in Berechnung\n";
        }
        
        if (!empty($result['credit_breakdown'])) {
            echo "   💰 Gutschriftenaufschlüsselung:\n";
            foreach ($result['credit_breakdown'] as $item) {
                echo "     - {$item['contract_title']}: {$item['customer_share']} EUR (von {$item['total_amount']} EUR, {$item['percentage']}%)\n";
            }
        } else {
            echo "   ❌ Keine Gutschriftenaufschlüsselung in Berechnung\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Fehler bei Berechnung: " . $e->getMessage() . "\n";
        echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "❌ Kein Kunde für diese Anlage gefunden\n";
}

echo "\n=== DEBUG ENDE ===\n";