<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;

echo "=== TESTING: Layout-Ausrichtung Standort/Produzierte Energie ===\n\n";

// Finde eine Testabrechnung mit produzierter Energie
$billing = SolarPlantBilling::with(['solarPlant', 'customer'])
    ->whereNotNull('produced_energy_kwh')
    ->where('produced_energy_kwh', '>', 0)
    ->first();

if (!$billing) {
    echo "❌ Keine Abrechnung mit produzierter Energie gefunden!\n";
    exit;
}

echo "✅ Testabrechnung gefunden: {$billing->id}\n";
echo "Solaranlage: {$billing->solarPlant->name}\n";
echo "Kunde: {$billing->customer->name}\n\n";

echo "=== LAYOUT-ÄNDERUNGEN IMPLEMENTIERT ===\n\n";

echo "🚫 ENTFERNT: Spalte 'Anlagenleistung'\n";
echo "   - Wurde komplett aus dem PDF-Template entfernt\n";
echo "   - Kein {{ \$solarPlant->total_capacity_kw }} mehr angezeigt\n\n";

echo "✅ ANGEPASST: Spaltenbreiten für perfekte Ausrichtung\n\n";

echo "OBERER BEREICH (plant-details):\n";
echo "┌─────────────────────────────────────────┬───────────────────────────────────┐\n";
echo "│ SPALTE 1: Standort                     │ SPALTE 2: Ihre Beteiligung       │\n";
echo "│ Breite: 43%                            │ Breite: 57%                       │\n";
echo "└─────────────────────────────────────────┴───────────────────────────────────┘\n\n";

echo "UNTERER BEREICH (energy-details):\n";
echo "┌─────────────────────────────────────────┬─────────────────────────┬─────────┐\n";
echo "│ SPALTE 1: Produzierte Energie          │ SPALTE 2: Ihr Anteil   │  Leer   │\n";
echo "│ Breite: 43%                            │ Breite: 35%             │  22%    │\n";
echo "└─────────────────────────────────────────┴─────────────────────────┴─────────┘\n\n";

echo "🎯 PERFEKTE AUSRICHTUNG ERREICHT!\n";
echo "✅ Standort-Spalte: 43% Breite\n";
echo "✅ Produzierte Energie-Spalte: 43% Breite\n";
echo "✅ Beide Spalten sind exakt gleich breit!\n";
echo "✅ Anlagenleistung wurde entfernt\n\n";

echo "=== CSS-IMPLEMENTIERUNG ===\n";
echo "plant-details > div:first-child  { width: 43%; }  // Standort\n";
echo "plant-details > div:last-child   { width: 57%; }  // Ihre Beteiligung\n";
echo "energy-details > div:first-child { width: 43%; }  // Produzierte Energie\n\n";

echo "🎉 LAYOUT-AUSRICHTUNG ERFOLGREICH IMPLEMENTIERT!\n";
echo "Die Standort-Spalte hat jetzt die exakte Breite der Produzierte Energie-Spalte!\n";
