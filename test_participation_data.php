<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PlantParticipation;

echo "=== Detailanalyse der Beteiligungen ===\n";
$participations = PlantParticipation::with('customer')->whereHas('customer')->take(5)->get();

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
    echo "  - Aktuelle Anzeige: '" . ($displayName ?? 'LEER') . "'\n\n";
}