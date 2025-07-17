<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\SolarPlant;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Debug Fixed Solar Plant Statistics ===\n\n";

// Gesamtanzahl der Anlagen
$totalPlants = SolarPlant::count();
echo "Gesamt Solaranlagen: {$totalPlants}\n";

// Neue Logik testen
$planningPlants = SolarPlant::where('status', 'in_planning')->count();
$maintenancePlants = SolarPlant::where('status', 'maintenance')->count();

// Inaktive Anlagen (neue Logik)
$actuallyInactive = SolarPlant::where(function($query) {
    $query->where('status', 'inactive')
          ->orWhere('status', 'inaktiv')
          ->orWhere('is_active', false);
})->count();

// Aktive Anlagen (neue Logik)
$reallyActive = SolarPlant::where(function($query) {
    $query->where('status', 'active')
          ->orWhere('status', 'aktiv');
})->where('is_active', true)->count();

echo "\nKorrigierte Zählung:\n";
echo "  Aktive Anlagen: {$reallyActive}\n";
echo "  Inaktive Anlagen: {$actuallyInactive}\n";
echo "  In Planung: {$planningPlants}\n";
echo "  Wartung: {$maintenancePlants}\n";

// Summe prüfen
$sum = $reallyActive + $actuallyInactive + $planningPlants + $maintenancePlants;
echo "\nSumme: {$sum} (sollte {$totalPlants} sein)\n";

// Detail-Analyse der inaktiven Anlagen
echo "\nDetailed Analyse der inaktiven Anlagen:\n";
$inactiveDetails = SolarPlant::where(function($query) {
    $query->where('status', 'inactive')
          ->orWhere('status', 'inaktiv')
          ->orWhere('is_active', false);
})->select('id', 'name', 'status', 'is_active')->get();

foreach ($inactiveDetails as $plant) {
    $active = $plant->is_active ? 'true' : 'false';
    echo "  - {$plant->name} (Status: {$plant->status}, is_active: {$active})\n";
}

// Prüfe, ob es Anlagen gibt, die in keine Kategorie fallen
$uncategorized = SolarPlant::where(function($query) {
    $query->whereNotIn('status', ['active', 'inactive', 'in_planning', 'maintenance'])
          ->where('is_active', true);
})->count();

echo "\nAnlagen, die in keine Kategorie fallen: {$uncategorized}\n";

if ($uncategorized > 0) {
    echo "Diese Anlagen:\n";
    $uncategorizedPlants = SolarPlant::where(function($query) {
        $query->whereNotIn('status', ['active', 'inactive', 'in_planning', 'maintenance'])
              ->where('is_active', true);
    })->select('id', 'name', 'status', 'is_active')->get();
    
    foreach ($uncategorizedPlants as $plant) {
        $active = $plant->is_active ? 'true' : 'false';
        echo "  - {$plant->name} (Status: '{$plant->status}', is_active: {$active})\n";
    }
}

echo "\n=== Ende Debug ===\n";
