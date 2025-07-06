<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SolarPlant;
use App\Models\SolarPlantBilling;
use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;

echo "=== DEBUG: Alle Solaranlagen und Abrechnungen ===\n\n";

// Liste alle Solaranlagen auf
$plants = SolarPlant::all();
echo "📋 Verfügbare Solaranlagen: " . $plants->count() . "\n\n";

foreach ($plants as $plant) {
    echo "🌞 Solaranlage: {$plant->name}\n";
    echo "   ID: {$plant->id}\n";
    echo "   Nummer: {$plant->plant_number}\n";
    echo "   Standort: {$plant->location}\n";
    
    // Prüfe aktive Verträge
    $contracts = $plant->activeSupplierContracts()->get();
    echo "   📋 Aktive Verträge: " . $contracts->count() . "\n";
    
    foreach ($contracts as $contract) {
        echo "     📄 {$contract->title} ({$contract->supplier->name})\n";
        $billings = $contract->billings()->get();
        echo "       💰 Abrechnungen: " . $billings->count() . "\n";
        
        foreach ($billings as $billing) {
            echo "         - {$billing->billing_year}-{$billing->billing_month}: {$billing->total_amount} EUR\n";
        }
    }
    
    // Prüfe Solaranlagen-Abrechnungen
    $solarBillings = $plant->billings()->get();
    echo "   🌞 Solaranlagen-Abrechnungen: " . $solarBillings->count() . "\n";
    
    foreach ($solarBillings as $billing) {
        echo "     - {$billing->billing_year}-{$billing->billing_month}: {$billing->total_costs} EUR (Kunde: {$billing->customer->name})\n";
    }
    
    echo "\n";
}

// Teste mit der ersten verfügbaren Solaranlage
$firstPlant = $plants->first();
if ($firstPlant) {
    echo "🔍 TESTE mit erster Solaranlage: {$firstPlant->name} (ID: {$firstPlant->id})\n\n";
    
    // Prüfe ob es Kunden gibt
    $customer = $firstPlant->participations()->first()?->customer;
    if ($customer) {
        echo "👤 Teste für Kunde: {$customer->name}\n";
        
        try {
            $result = \App\Models\SolarPlantBilling::calculateCostsForCustomer($firstPlant->id, $customer->id, 2024, 4);
            
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
            
            // Versuche eine neue Abrechnung zu erstellen
            echo "\n🔧 Versuche neue Abrechnung zu erstellen...\n";
            $billings = \App\Models\SolarPlantBilling::createBillingsForMonth($firstPlant->id, 2024, 4);
            echo "✅ Erfolgreich " . count($billings) . " Abrechnungen erstellt!\n";
            
            foreach ($billings as $billing) {
                echo "📊 Abrechnung für {$billing->customer->name}:\n";
                echo "   Kosten: {$billing->total_costs} EUR\n";
                echo "   Gutschriften: {$billing->total_credits} EUR\n";
                echo "   Nettobetrag: {$billing->net_amount} EUR\n";
                
                if ($billing->cost_breakdown) {
                    echo "   💸 Kostenaufschlüsselung:\n";
                    foreach ($billing->cost_breakdown as $item) {
                        echo "     - {$item['contract_title']}: {$item['customer_share']} EUR ({$item['percentage']}%)\n";
                    }
                }
                
                if ($billing->credit_breakdown) {
                    echo "   💰 Gutschriftenaufschlüsselung:\n";
                    foreach ($billing->credit_breakdown as $item) {
                        echo "     - {$item['contract_title']}: {$item['customer_share']} EUR ({$item['percentage']}%)\n";
                    }
                }
                echo "\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Fehler bei Berechnung: " . $e->getMessage() . "\n";
            echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
        }
    } else {
        echo "❌ Kein Kunde für diese Anlage gefunden\n";
    }
} else {
    echo "❌ Keine Solaranlagen gefunden\n";
}

echo "\n=== DEBUG ENDE ===\n";