<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SolarPlant;
use App\Models\PlantParticipation;

echo "=== Test der korrigierten Anzeige-Logik ===\n";

$plant = SolarPlant::where('name', 'Aurich 1')->first();
if (!$plant) {
    echo "❌ SolarPlant 'Aurich 1' nicht gefunden!\n";
    exit;
}

echo "✅ SolarPlant gefunden: {$plant->name}\n\n";

$participations = PlantParticipation::where('solar_plant_id', $plant->id)
    ->with('customer')
    ->get();

echo "=== Simulation der Filament RepeatableEntry ===\n";
foreach ($participations as $record) {
    $customer = $record->customer;
    
    echo "Beteiligung ID {$record->id}:\n";
    echo "  - customer.name Feld: '" . ($customer->name ?? 'NULL') . "'\n";
    
    // Simulation der formatStateUsing Funktion
    if (!$customer) {
        $displayName = 'Kunde nicht gefunden';
    } else {
        $displayName = $customer->customer_type === 'business'
            ? ($customer->company_name ?: $customer->name)
            : $customer->name;
    }
    
    echo "  - 🎯 formatStateUsing Ergebnis: '" . $displayName . "'\n";
    echo "  - Beteiligung: {$record->percentage}%\n\n";
}

echo "=== Erwartetes Ergebnis ===\n";
echo "Beteiligung 1: 'Bentaieb & Boukentar PV GbR' (business)\n";
echo "Beteiligung 2: 'Soumaya Boukentar ' (private)\n";