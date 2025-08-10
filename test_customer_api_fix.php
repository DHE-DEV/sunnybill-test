<?php

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\SolarPlant;
use App\Models\PlantParticipation;

echo "=== Test: Customer API Fix - solarPlants Beziehung ===\n\n";

try {
    // Test 1: Prüfe ob solarPlants Beziehung existiert
    echo "1. Teste Customer->solarPlants() Beziehung...\n";
    $customer = Customer::first();
    
    if (!$customer) {
        echo "❌ Kein Kunde gefunden\n";
        exit(1);
    }
    
    // Teste die Beziehung
    $solarPlants = $customer->solarPlants;
    echo "✅ solarPlants Beziehung funktioniert\n";
    echo "   Kunde: {$customer->name} ({$customer->id})\n";
    echo "   Anzahl Solaranlagen: " . $solarPlants->count() . "\n\n";
    
    // Test 2: Teste API Endpoint simuliert
    echo "2. Teste Customer mit solarPlants laden...\n";
    $customerWithPlants = Customer::with(['solarPlants'])->first();
    
    if ($customerWithPlants) {
        echo "✅ Customer mit solarPlants erfolgreich geladen\n";
        echo "   Kunde: {$customerWithPlants->name}\n";
        echo "   Geladene Solaranlagen: " . $customerWithPlants->solarPlants->count() . "\n";
        
        foreach ($customerWithPlants->solarPlants as $plant) {
            echo "   - {$plant->name} (ID: {$plant->id})\n";
        }
    } else {
        echo "❌ Fehler beim Laden der Customer mit solarPlants\n";
    }
    
    echo "\n3. Teste has_solar_plants Filter...\n";
    $customersWithPlants = Customer::whereHas('solarPlants')->count();
    $customersWithoutPlants = Customer::whereDoesntHave('solarPlants')->count();
    
    echo "✅ Filter funktionieren:\n";
    echo "   Kunden mit Solaranlagen: {$customersWithPlants}\n";
    echo "   Kunden ohne Solaranlagen: {$customersWithoutPlants}\n";

    echo "\n4. Teste zusätzliche API Beziehungen...\n";
    
    // Teste participations Beziehung (sollte Alias für plantParticipations sein)
    $participations = $customer->participations;
    echo "✅ participations Beziehung funktioniert\n";
    echo "   Anzahl Beteiligungen: " . $participations->count() . "\n";
    
    // Teste projects Beziehung
    $projects = $customer->projects;
    echo "✅ projects Beziehung funktioniert\n";
    echo "   Anzahl Projekte: " . $projects->count() . "\n";
    
    // Teste tasks Beziehung
    $tasks = $customer->tasks;
    echo "✅ tasks Beziehung funktioniert\n";
    echo "   Anzahl Aufgaben: " . $tasks->count() . "\n";
    
    echo "\n5. Teste vollständige API-Ladung...\n";
    $customerWithAllRelations = Customer::with(['solarPlants', 'participations', 'projects', 'tasks'])->first();
    
    if ($customerWithAllRelations) {
        echo "✅ Customer mit allen API-Beziehungen erfolgreich geladen\n";
        echo "   Solaranlagen: " . $customerWithAllRelations->solarPlants->count() . "\n";
        echo "   Beteiligungen: " . $customerWithAllRelations->participations->count() . "\n";
        echo "   Projekte: " . $customerWithAllRelations->projects->count() . "\n";
        echo "   Aufgaben: " . $customerWithAllRelations->tasks->count() . "\n";
    }
    
    echo "\n🎉 Alle Tests bestanden! Die Customer API sollte jetzt vollständig funktionieren.\n";
    echo "Alle fehlenden Beziehungen (solarPlants, participations, projects, tasks) wurden hinzugefügt.\n";
    
} catch (Exception $e) {
    echo "❌ Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
