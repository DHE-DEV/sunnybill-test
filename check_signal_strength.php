<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\SimCard;

echo "=== SIM Cards Signal Strength Check ===\n\n";

echo "Total SIM Cards: " . SimCard::count() . "\n";
echo "SIM Cards with signal_strength: " . SimCard::whereNotNull('signal_strength')->count() . "\n\n";

echo "Sample SIM Cards:\n";
SimCard::take(5)->get(['id', 'iccid', 'signal_strength', 'assigned_to'])->each(function($sim) {
    echo "ID: {$sim->id} - ICCID: {$sim->iccid} - Signal: " . ($sim->signal_strength ?? 'NULL') . " - Assigned: " . ($sim->assigned_to ?? 'N/A') . "\n";
});

echo "\n=== Adding Test Signal Strength Data ===\n";

// Update some SIM cards with test signal strength values
$testSignals = [-65, -72, -83, -91, -58]; // Different signal strengths for testing

SimCard::take(5)->get()->each(function($sim, $index) use ($testSignals) {
    $signal = $testSignals[$index % count($testSignals)];
    $sim->update(['signal_strength' => $signal]);
    echo "Updated SIM {$sim->id} with signal strength: {$signal} dBm\n";
});

echo "\n=== Updated Data ===\n";
SimCard::take(5)->get(['id', 'iccid', 'signal_strength', 'assigned_to'])->each(function($sim) {
    echo "ID: {$sim->id} - ICCID: {$sim->iccid} - Signal: " . ($sim->signal_strength ?? 'NULL') . " dBm - Assigned: " . ($sim->assigned_to ?? 'N/A') . "\n";
});

echo "\nDone!\n";
