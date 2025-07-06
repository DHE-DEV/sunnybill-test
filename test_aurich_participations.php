<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SolarPlant;
use App\Models\PlantParticipation;

echo "=== SolarPlant 'Aurich 1' Beteiligungen ===\n";
$plant = SolarPlant::where('name', 'Aurich 1')->first();
if ($plant) {
    echo "SolarPlant gefunden: {$plant->name} (ID: {$plant->id})\n\n";
    
    $participations = PlantParticipation::where('solar_plant_id', $plant->id)
        ->with('customer')
        ->get();
    
    foreach ($participations as $participation) {
        $customer = $participation->customer;
        echo "Beteiligung ID {$participation->id}:\n";
        echo "  - Kunde ID: {$customer->id}\n";
        echo "  - customer_type: '" . ($customer->customer_type ?? 'NULL') . "'\n";
        echo "  - name: '" . ($customer->name ?? 'NULL') . "'\n";
        echo "  - company_name: '" . ($customer->company_name ?? 'NULL') . "'\n";
        echo "  - email: '" . ($customer->email ?? 'NULL') . "'\n";
        echo "  - Beteiligung: " . $participation->percentage . "%\n";
        
        // Test der aktuellen Logik
        $displayName = $customer->customer_type === 'business' ? $customer->company_name : $customer->name;
        echo "  - Aktuelle Anzeige: '" . ($displayName ?? 'LEER') . "'\n";
        
        // Test mit Fallback-Logik
        $fallbackName = $customer->customer_type === 'business' 
            ? ($customer->company_name ?: $customer->name) 
            : $customer->name;
        echo "  - Mit Fallback: '" . ($fallbackName ?? 'LEER') . "'\n\n";
    }
} else {
    echo "SolarPlant 'Aurich 1' nicht gefunden!\n";
}