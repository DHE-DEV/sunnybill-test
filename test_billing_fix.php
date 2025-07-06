<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlant;
use App\Models\SolarPlantBilling;
use App\Models\Customer;

echo "=== SOLAR PLANT BILLING FIX TEST ===" . PHP_EOL;

$plantId = '0197cf8d-f147-7021-8fa1-10ebadf1c58b';

echo "1. Prüfe aktuelle Situation..." . PHP_EOL;

// Prüfe alle Abrechnungen (auch gelöschte)
$allBillings = SolarPlantBilling::withTrashed()
    ->where('solar_plant_id', $plantId)
    ->where('billing_year', 2024)
    ->where('billing_month', 4)
    ->get();

echo "Gefundene Abrechnungen (inkl. gelöschte): " . $allBillings->count() . PHP_EOL;

foreach ($allBillings as $billing) {
    $customer = Customer::find($billing->customer_id);
    echo "  - Kunde: " . ($customer->name ?? 'Unbekannt') . 
         " | Status: " . $billing->status . 
         " | Gelöscht: " . ($billing->deleted_at ? 'JA (' . $billing->deleted_at . ')' : 'NEIN') . PHP_EOL;
}

echo PHP_EOL . "2. Lösche alle bestehenden Abrechnungen permanent..." . PHP_EOL;

// Lösche alle Abrechnungen permanent
$deletedCount = SolarPlantBilling::withTrashed()
    ->where('solar_plant_id', $plantId)
    ->where('billing_year', 2024)
    ->where('billing_month', 4)
    ->forceDelete();

echo "Permanent gelöscht: " . $deletedCount . " Abrechnungen" . PHP_EOL;

echo PHP_EOL . "3. Prüfe ob alle gelöscht wurden..." . PHP_EOL;

$remainingBillings = SolarPlantBilling::withTrashed()
    ->where('solar_plant_id', $plantId)
    ->where('billing_year', 2024)
    ->where('billing_month', 4)
    ->count();

echo "Verbleibende Abrechnungen: " . $remainingBillings . PHP_EOL;

echo PHP_EOL . "4. Erstelle neue Abrechnungen..." . PHP_EOL;

try {
    $billings = SolarPlantBilling::createBillingsForMonth($plantId, 2024, 4);
    echo "✅ Erfolgreich " . count($billings) . " Abrechnungen erstellt!" . PHP_EOL;
    
    foreach ($billings as $billing) {
        $customer = Customer::find($billing->customer_id);
        echo "  - Kunde: " . ($customer->name ?? 'Unbekannt') . PHP_EOL;
        echo "    Kosten: " . $billing->total_costs . " EUR" . PHP_EOL;
        echo "    Gutschriften: " . $billing->total_credits . " EUR" . PHP_EOL;
        echo "    Nettobetrag: " . $billing->net_amount . " EUR" . PHP_EOL;
        echo "    Status: " . $billing->status . PHP_EOL;
        echo "---" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Fehler beim Erstellen der Abrechnungen:" . PHP_EOL;
    echo "Nachricht: " . $e->getMessage() . PHP_EOL;
    echo "Datei: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    
    // Zusätzliche Debug-Informationen
    echo PHP_EOL . "Debug-Informationen:" . PHP_EOL;
    
    $plant = SolarPlant::find($plantId);
    if ($plant) {
        echo "Solaranlage: " . $plant->name . PHP_EOL;
        
        $participations = $plant->participations()->get();
        echo "Beteiligungen: " . $participations->count() . PHP_EOL;
        
        foreach ($participations as $participation) {
            $customer = Customer::find($participation->customer_id);
            echo "  - Kunde: " . ($customer->name ?? 'Unbekannt') . 
                 " | Prozentsatz: " . $participation->percentage . "%" . PHP_EOL;
        }
    }
}

echo PHP_EOL . "5. Test der Wiederholung (sollte keine neuen Abrechnungen erstellen)..." . PHP_EOL;

try {
    $billings2 = SolarPlantBilling::createBillingsForMonth($plantId, 2024, 4);
    echo "✅ Zweiter Aufruf erfolgreich: " . count($billings2) . " Abrechnungen (sollte 0 sein)" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Fehler beim zweiten Aufruf: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== TEST ABGESCHLOSSEN ===" . PHP_EOL;