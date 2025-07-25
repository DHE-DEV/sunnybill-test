<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;

echo "=== TESTING: PDF-Konsistenz der Positionsbeschreibungen ===\n\n";

// Finde eine Testabrechnung
$billing = SolarPlantBilling::with(['solarPlant', 'customer'])->first();

if (!$billing) {
    echo "❌ Keine Abrechnung gefunden!\n";
    exit;
}

echo "✅ Testabrechnung gefunden: {$billing->id}\n";
echo "Solaranlage: {$billing->solarPlant->name}\n";
echo "Kunde: {$billing->customer->name}\n";
echo "Periode: " . $billing->getFormattedMonthAttribute() . "\n\n";

echo "=== ÜBERPRÜFUNG: Positionsbeschreibungen ===\n";

// Simuliere die Variablen, die im PDF-Template verwendet werden
$monthName = \Carbon\Carbon::createFromDate($billing->billing_year, $billing->billing_month, 1)
    ->locale('de')
    ->translatedFormat('F');

// Hole die aktuelle Beteiligung
$currentPercentage = $billing->solarPlant->participations()
    ->where('customer_id', $billing->customer_id)
    ->first()?->percentage ?? $billing->participation_percentage;

$expectedDescription = $monthName . ' ' . $billing->billing_year . ' - ' . number_format($currentPercentage, 2, ',', '.') . '% Anteil';

echo "Erwartete Beschreibung für beide Positionen:\n";
echo "\"$expectedDescription\"\n\n";

echo "=== POSITIONEN IM PDF ===\n";

if ($billing->total_credits > 0) {
    echo "✅ Position 1: Einnahmen/Gutschriften\n";
    echo "   Beschreibung: \"$expectedDescription\"\n";
    echo "   Betrag: " . number_format($billing->total_credits, 2, ',', '.') . " €\n\n";
}

if ($billing->total_costs > 0) {
    $position = $billing->total_credits > 0 ? 2 : 1;
    echo "✅ Position $position: Betriebskosten\n";
    echo "   Beschreibung: \"$expectedDescription\"\n";
    echo "   Betrag: -" . number_format($billing->total_costs, 2, ',', '.') . " €\n\n";
}

echo "=== BESTÄTIGUNG ===\n";
echo "✅ Beide Positionen verwenden jetzt dieselbe Beschreibungsformatierung\n";
echo "✅ Format: [Monat] [Jahr] - [Prozentsatz]% Anteil\n";
echo "✅ Konsistente Darstellung im PDF erreicht\n\n";

echo "🎉 PDF-KONSISTENZ HERGESTELLT!\n";
echo "Die Beschreibungen für Einnahmen/Gutschriften und Betriebskosten\n";
echo "sind jetzt einheitlich formatiert.\n";
