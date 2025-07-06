<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SolarPlant;
use App\Models\PlantParticipation;

echo "=== Finale Logik-Validierung für Aurich 1 ===\n";

$plant = SolarPlant::where('name', 'Aurich 1')->first();
if (!$plant) {
    echo "❌ SolarPlant 'Aurich 1' nicht gefunden!\n";
    exit;
}

echo "✅ SolarPlant gefunden: {$plant->name}\n\n";

$participations = PlantParticipation::where('solar_plant_id', $plant->id)
    ->with('customer')
    ->get();

echo "=== Beteiligungen mit neuer Logik ===\n";
foreach ($participations as $participation) {
    $customer = $participation->customer;
    
    echo "Beteiligung ID {$participation->id}:\n";
    echo "  - Kunde: {$customer->id}\n";
    echo "  - Type: {$customer->customer_type}\n";
    echo "  - Name: '" . ($customer->name ?? 'NULL') . "'\n";
    echo "  - Company: '" . ($customer->company_name ?? 'NULL') . "'\n";
    
    // Neue Logik mit Fallback
    $displayName = $customer->customer_type === 'business'
        ? ($customer->company_name ?: $customer->name)
        : $customer->name;
    
    echo "  - 🎯 ANZEIGE: '" . ($displayName ?? 'LEER') . "'\n";
    echo "  - Beteiligung: {$participation->percentage}%\n\n";
}

echo "=== Test der Dropdown-Logik ===\n";
$allCustomers = \App\Models\Customer::all();
foreach ($allCustomers as $customer) {
    $displayName = $customer->customer_type === 'business'
        ? ($customer->company_name ?: $customer->name)
        : $customer->name;
    
    echo "Kunde {$customer->id} ({$customer->customer_type}): '{$displayName}'\n";
}