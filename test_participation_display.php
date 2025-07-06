<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\PlantParticipation;

echo "=== Test: Beteiligungen Firmenname-Anzeige ===\n\n";

// Teste die Logik für Firmenname-Anzeige
$participations = PlantParticipation::with('customer')->take(5)->get();

if ($participations->isEmpty()) {
    echo "❌ Keine Beteiligungen gefunden!\n";
    exit(1);
}

echo "Gefundene Beteiligungen:\n";
foreach ($participations as $participation) {
    $customer = $participation->customer;
    
    if (!$customer) {
        echo "- Beteiligung ID {$participation->id}: ❌ Kein Kunde zugeordnet\n";
        continue;
    }
    
    // Logik aus dem ParticipationsRelationManager (aktualisiert)
    $displayName = $customer->customer_type === 'business'
        ? $customer->company_name
        : $customer->name;
    
    echo "- Beteiligung ID {$participation->id}:\n";
    echo "  * Kunde ID: {$customer->id}\n";
    echo "  * Kundentyp: " . ($customer->customer_type ?? 'NULL') . "\n";
    echo "  * Name: " . ($customer->name ?? 'NULL') . "\n";
    echo "  * Firmenname: " . ($customer->company_name ?? 'NULL') . "\n";
    echo "  * Angezeigter Name: {$displayName}\n";
    echo "  * Beteiligung: " . number_format($participation->percentage, 2, ',', '.') . "%\n\n";
}

// Teste auch Geschäftskunden speziell
echo "\n=== Geschäftskunden mit Beteiligungen ===\n";
$businessCustomers = Customer::where('customer_type', 'business')
    ->whereHas('plantParticipations')
    ->with('plantParticipations')
    ->take(3)
    ->get();

if ($businessCustomers->isEmpty()) {
    echo "❌ Keine Geschäftskunden mit Beteiligungen gefunden!\n";
} else {
    foreach ($businessCustomers as $customer) {
        $displayName = $customer->customer_type === 'business'
            ? $customer->company_name
            : $customer->name;
            
        echo "- Geschäftskunde ID {$customer->id}:\n";
        echo "  * Name: " . ($customer->name ?? 'NULL') . "\n";
        echo "  * Firmenname: " . ($customer->company_name ?? 'NULL') . "\n";
        echo "  * Angezeigter Name: {$displayName}\n";
        echo "  * Anzahl Beteiligungen: " . $customer->plantParticipations->count() . "\n\n";
    }
}

echo "✅ Test abgeschlossen!\n";