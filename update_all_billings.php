<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;

echo "=== Aktualisierung aller Solaranlagen-Abrechnungen ===" . PHP_EOL;

// Hole alle bestehenden Solaranlagen-Abrechnungen
$billings = SolarPlantBilling::all();

echo "Gefunden: " . $billings->count() . " Abrechnungen" . PHP_EOL;
echo "Aktualisiere mit korrigierter Kostenaufteilung..." . PHP_EOL . PHP_EOL;

$updated = 0;
$errors = 0;

foreach ($billings as $billing) {
    try {
        // Lade Beziehungen explizit
        $billing->load(['customer', 'solarPlant']);
        
        $customerName = $billing->customer ? $billing->customer->name : 'Unbekannt';
        $plantName = $billing->solarPlant ? $billing->solarPlant->name : 'Unbekannt';
        
        echo "Aktualisiere: $customerName ($plantName)" . PHP_EOL;
        echo "  Abrechnung ID: " . $billing->id . PHP_EOL;
        echo "  Jahr/Monat: " . $billing->billing_year . "/" . $billing->billing_month . PHP_EOL;
        
        // Prüfe ob alle notwendigen Daten vorhanden sind
        if (!$billing->solar_plant_id || !$billing->customer_id) {
            echo "  ✗ Überspringe: Fehlende Solaranlagen- oder Kunden-ID" . PHP_EOL . PHP_EOL;
            $errors++;
            continue;
        }
        
        // Berechne die Kosten neu mit korrigierter Methode
        $costs = SolarPlantBilling::calculateCostsForCustomer(
            $billing->solar_plant_id,
            $billing->customer_id,
            $billing->billing_year,
            $billing->billing_month
        );
        
        // Zeige Vergleich
        echo "  Vorher: Kosten=" . number_format($billing->total_costs, 2, ',', '.') . " €, " .
             "Gutschriften=" . number_format($billing->total_credits, 2, ',', '.') . " €, " .
             "Netto=" . number_format($billing->net_amount, 2, ',', '.') . " €" . PHP_EOL;
        
        echo "  Nachher: Kosten=" . number_format($costs['total_costs'], 2, ',', '.') . " €, " .
             "Gutschriften=" . number_format($costs['total_credits'], 2, ',', '.') . " €, " .
             "Netto=" . number_format($costs['net_amount'], 2, ',', '.') . " €" . PHP_EOL;
        
        // Aktualisiere die Abrechnung
        $billing->update([
            'total_costs' => $costs['total_costs'],
            'total_credits' => $costs['total_credits'],
            'net_amount' => $costs['net_amount'],
            'cost_breakdown' => $costs['cost_breakdown'],
            'credit_breakdown' => $costs['credit_breakdown']
        ]);
        
        $updated++;
        echo "  ✓ Erfolgreich aktualisiert" . PHP_EOL . PHP_EOL;
        
    } catch (Exception $e) {
        $errors++;
        echo "  ✗ Fehler: " . $e->getMessage() . PHP_EOL;
        echo "  Stack Trace: " . $e->getTraceAsString() . PHP_EOL . PHP_EOL;
    }
}

echo "=== Aktualisierung abgeschlossen ===" . PHP_EOL;
echo "Erfolgreich aktualisiert: $updated" . PHP_EOL;
echo "Fehler: $errors" . PHP_EOL;

if ($updated > 0) {
    echo PHP_EOL . "Alle Solaranlagen-Abrechnungen wurden mit der korrigierten Kostenaufteilung aktualisiert!" . PHP_EOL;
    echo "Die doppelte Aufteilung (Vertrag → Solaranlage → Kunde) wird jetzt korrekt berücksichtigt." . PHP_EOL;
}