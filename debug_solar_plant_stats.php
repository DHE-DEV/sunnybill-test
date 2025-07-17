<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\SolarPlant;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Debug Solar Plant Statistics ===\n\n";

// Gesamtanzahl der Anlagen
$totalPlants = SolarPlant::count();
echo "Gesamt Solaranlagen: {$totalPlants}\n\n";

// Status-Verteilung
echo "Status-Verteilung:\n";
$statusDistribution = SolarPlant::selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->orderBy('count', 'desc')
    ->get();

foreach ($statusDistribution as $status) {
    echo "  {$status->status}: {$status->count}\n";
}

echo "\n";

// is_active Boolean-Feld Verteilung
echo "is_active Boolean-Feld Verteilung:\n";
$isActiveDistribution = SolarPlant::selectRaw('is_active, COUNT(*) as count')
    ->groupBy('is_active')
    ->orderBy('count', 'desc')
    ->get();

foreach ($isActiveDistribution as $active) {
    $label = $active->is_active ? 'true' : 'false';
    echo "  is_active = {$label}: {$active->count}\n";
}

echo "\n";

// Beispiel-Datensätze anzeigen
echo "Beispiel-Datensätze (erste 10):\n";
$examples = SolarPlant::select('id', 'name', 'status', 'is_active')
    ->limit(10)
    ->get();

foreach ($examples as $plant) {
    $active = $plant->is_active ? 'true' : 'false';
    echo "  ID: {$plant->id}, Name: {$plant->name}, Status: {$plant->status}, is_active: {$active}\n";
}

echo "\n";

// Soft-deleted Anlagen prüfen
$deletedPlants = SolarPlant::onlyTrashed()->count();
echo "Gelöschte Anlagen (Soft Deleted): {$deletedPlants}\n";

// Anlagen ohne Status
$plantsWithoutStatus = SolarPlant::whereNull('status')->count();
echo "Anlagen ohne Status: {$plantsWithoutStatus}\n";

// Anlagen mit leerem Status
$plantsWithEmptyStatus = SolarPlant::where('status', '')->count();
echo "Anlagen mit leerem Status: {$plantsWithEmptyStatus}\n";

echo "\n=== Ende Debug ===\n";
