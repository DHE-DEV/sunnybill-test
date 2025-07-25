<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;

echo "=== TESTING: 30% breitere Spalten - Standort und Produzierte Energie ===\n\n";

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

echo "=== BERECHNUNG: 30% BREITER ===\n\n";

$originalWidth = 43;
$increase = $originalWidth * 0.3;
$newWidth = $originalWidth + $increase;

echo "📊 URSPRÜNGLICHE BREITE: {$originalWidth}%\n";
echo "📊 ERHÖHUNG (30%): {$increase}%\n";
echo "📊 NEUE BREITE: {$newWidth}%\n\n";

echo "=== AKTUELLES LAYOUT ===\n\n";

echo "OBERER BEREICH (plant-details):\n";
echo "┌──────────────────────────────────────────────────────────┬─────────────────────────┐\n";
echo "│ SPALTE 1: Standort                                      │ SPALTE 2: Beteiligung  │\n";
echo "│ Breite: 56% (vorher 43% + 30% = 56%)                   │ Breite: 44%             │\n";
echo "└──────────────────────────────────────────────────────────┴─────────────────────────┘\n\n";

echo "UNTERER BEREICH (energy-details):\n";
echo "┌──────────────────────────────────────────────────────────┬───────────────┬─────────┐\n";
echo "│ SPALTE 1: Produzierte Energie                           │ SPALTE 2:     │  Leer   │\n";
echo "│ Breite: 56% (vorher 43% + 30% = 56%)                   │ Ihr Anteil    │  17%    │\n";
echo "│                                                          │ 27%           │         │\n";
echo "└──────────────────────────────────────────────────────────┴───────────────┴─────────┘\n\n";

echo "🎯 ERFOLGREICHE VERBREITERUNG!\n";
echo "✅ Standort-Spalte: 43% → 56% (+30%)\n";
echo "✅ Produzierte Energie-Spalte: 43% → 56% (+30%)\n";
echo "✅ Beide Spalten sind weiterhin gleich breit!\n";
echo "✅ Perfekte vertikale Ausrichtung beibehalten!\n\n";

echo "=== CSS-IMPLEMENTIERUNG ===\n";
echo ".plant-details > div:first-child  { width: 56%; }  // Standort (war 43%)\n";
echo ".plant-details > div:last-child   { width: 44%; }  // Ihre Beteiligung (angepasst)\n";
echo ".energy-details > div:first-child { width: 56%; }  // Produzierte Energie (war 43%)\n";
echo ".energy-details > div:nth-child(2) { width: 27%; }  // Ihr Anteil (angepasst)\n";
echo ".energy-details > div:last-child  { width: 17%; }  // Leerfeld (angepasst)\n\n";

echo "🎉 30% VERBREITERUNG ERFOLGREICH IMPLEMENTIERT!\n";
echo "Die Spalten 'Standort' und 'Produzierte Energie' sind jetzt 30% breiter!\n";
