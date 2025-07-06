<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlant;
use App\Models\SolarPlantBilling;

echo "=== Verfügbare Solaranlagen und Test ===" . PHP_EOL;

// 1. Liste alle verfügbaren Solaranlagen auf
echo "1. Verfügbare Solaranlagen:" . PHP_EOL;
$plants = SolarPlant::all();

if ($plants->count() == 0) {
    echo "❌ Keine Solaranlagen gefunden!" . PHP_EOL;
    exit(1);
}

foreach ($plants as $plant) {
    echo "  - ID: " . $plant->id . PHP_EOL;
    echo "    Name: " . $plant->name . PHP_EOL;
    echo "    Aktive Verträge: " . $plant->activeSupplierContracts()->count() . PHP_EOL;
    echo "    Kundenbeteiligungen: " . $plant->participations()->count() . PHP_EOL;
    echo "    Bestehende Abrechnungen: " . $plant->billings()->count() . PHP_EOL;
    echo "---" . PHP_EOL;
}

// 2. Teste mit der ersten verfügbaren Solaranlage
$testPlant = $plants->first();
$plantId = $testPlant->id;
$year = 2024;
$month = 4;

echo PHP_EOL . "2. Teste mit Solaranlage: " . $testPlant->name . PHP_EOL;
echo "ID: $plantId" . PHP_EOL;

// 3. Prüfe Verträge und deren Abrechnungen
echo PHP_EOL . "3. Verträge und Abrechnungen:" . PHP_EOL;
$contracts = $testPlant->activeSupplierContracts()->get();

if ($contracts->count() == 0) {
    echo "❌ Keine aktiven Verträge gefunden!" . PHP_EOL;
} else {
    foreach ($contracts as $contract) {
        echo "Vertrag: " . $contract->title . PHP_EOL;
        $billing = $contract->billings()
            ->where('billing_year', $year)
            ->where('billing_month', $month)
            ->first();
        
        if ($billing) {
            echo "  ✅ Abrechnung vorhanden: " . number_format($billing->total_amount, 2) . " €" . PHP_EOL;
        } else {
            echo "  ❌ Keine Abrechnung für $month/$year" . PHP_EOL;
        }
    }
}

// 4. Prüfe Kundenbeteiligungen
echo PHP_EOL . "4. Kundenbeteiligungen:" . PHP_EOL;
$participations = $testPlant->participations()->get();

if ($participations->count() == 0) {
    echo "❌ Keine Kundenbeteiligungen gefunden!" . PHP_EOL;
} else {
    foreach ($participations as $participation) {
        $customerName = $participation->customer ? $participation->customer->name : 'Unbekannt';
        echo "  - Kunde: $customerName" . PHP_EOL;
        echo "    Beteiligung: " . $participation->percentage . "%" . PHP_EOL;
    }
}

// 5. Teste Kostenberechnung
if ($participations->count() > 0) {
    echo PHP_EOL . "5. Teste Kostenberechnung:" . PHP_EOL;
    $firstParticipation = $participations->first();
    $customerId = $firstParticipation->customer_id;
    $customerName = $firstParticipation->customer ? $firstParticipation->customer->name : 'Unbekannt';
    
    echo "Teste für Kunde: $customerName" . PHP_EOL;
    
    try {
        $costData = SolarPlantBilling::calculateCostsForCustomer($plantId, $customerId, $year, $month);
        echo "✅ Kostenberechnung erfolgreich:" . PHP_EOL;
        echo "  Kosten: " . number_format($costData['total_costs'], 2) . " €" . PHP_EOL;
        echo "  Gutschriften: " . number_format($costData['total_credits'], 2) . " €" . PHP_EOL;
        echo "  Nettobetrag: " . number_format($costData['net_amount'], 2) . " €" . PHP_EOL;
        echo "  Kostenpositionen: " . count($costData['cost_breakdown']) . PHP_EOL;
        echo "  Gutschriftenpositionen: " . count($costData['credit_breakdown']) . PHP_EOL;
        
        // Zeige Details der Kostenpositionen
        if (!empty($costData['cost_breakdown'])) {
            echo "  Kostendetails:" . PHP_EOL;
            foreach ($costData['cost_breakdown'] as $cost) {
                echo "    - " . $cost['contract_title'] . ": " . number_format($cost['customer_share'], 2) . " €" . PHP_EOL;
            }
        }
        
        if (!empty($costData['credit_breakdown'])) {
            echo "  Gutschriftendetails:" . PHP_EOL;
            foreach ($costData['credit_breakdown'] as $credit) {
                echo "    - " . $credit['contract_title'] . ": " . number_format($credit['customer_share'], 2) . " €" . PHP_EOL;
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Fehler bei Kostenberechnung: " . $e->getMessage() . PHP_EOL;
    }
}

// 6. Versuche Abrechnungen zu erstellen
echo PHP_EOL . "6. Erstelle Solaranlagen-Abrechnungen:" . PHP_EOL;
try {
    $billings = SolarPlantBilling::createBillingsForMonth($plantId, $year, $month);
    echo "✅ Erfolgreich " . count($billings) . " Abrechnungen erstellt!" . PHP_EOL;
    
    foreach ($billings as $billing) {
        $customerName = $billing->customer ? $billing->customer->name : 'Unbekannt';
        echo "  - Kunde: $customerName" . PHP_EOL;
        echo "    Kosten: " . number_format($billing->total_costs, 2) . " €" . PHP_EOL;
        echo "    Gutschriften: " . number_format($billing->total_credits, 2) . " €" . PHP_EOL;
        echo "    Nettobetrag: " . number_format($billing->net_amount, 2) . " €" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Fehler beim Erstellen: " . $e->getMessage() . PHP_EOL;
    echo "Details: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}

// 7. Teste Löschen und Wiederherstellen
echo PHP_EOL . "7. Teste Löschen und Wiederherstellen:" . PHP_EOL;
$existingBilling = SolarPlantBilling::where('solar_plant_id', $plantId)
    ->where('billing_year', $year)
    ->where('billing_month', $month)
    ->first();

if ($existingBilling) {
    $customerName = $existingBilling->customer ? $existingBilling->customer->name : 'Unbekannt';
    echo "Lösche Abrechnung für: $customerName" . PHP_EOL;
    $existingBilling->delete();
    
    echo "Versuche erneut zu erstellen..." . PHP_EOL;
    try {
        $newBillings = SolarPlantBilling::createBillingsForMonth($plantId, $year, $month);
        echo "✅ Erfolgreich " . count($newBillings) . " Abrechnungen nach Löschung erstellt!" . PHP_EOL;
    } catch (Exception $e) {
        echo "❌ Fehler beim erneuten Erstellen: " . $e->getMessage() . PHP_EOL;
    }
} else {
    echo "Keine Abrechnung zum Testen gefunden" . PHP_EOL;
}

echo PHP_EOL . "=== Test abgeschlossen ===" . PHP_EOL;