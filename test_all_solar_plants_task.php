<?php

require_once 'bootstrap/app.php';

use App\Models\Task;
use App\Models\TaskType;
use App\Models\SolarPlant;
use App\Models\User;

// Simuliere einen eingeloggten Benutzer
$user = User::first();
if (!$user) {
    echo "Kein Benutzer gefunden. Erstelle einen Testbenutzer.\n";
    $user = User::create([
        'name' => 'Test Admin',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
}

auth()->login($user);

// Hole einen Task Type
$taskType = TaskType::first();
if (!$taskType) {
    echo "Kein Task Type gefunden. Erstelle einen Test Task Type.\n";
    $taskType = TaskType::create([
        'name' => 'Test Task Type',
        'is_active' => true,
    ]);
}

// Prüfe, ob es Solaranlagen gibt
$solarPlantsCount = SolarPlant::count();
echo "Anzahl Solaranlagen in der Datenbank: {$solarPlantsCount}\n";

if ($solarPlantsCount === 0) {
    echo "Keine Solaranlagen gefunden. Erstelle Test-Solaranlagen.\n";
    for ($i = 1; $i <= 3; $i++) {
        SolarPlant::create([
            'name' => "Test Solaranlage {$i}",
            'customer_id' => null,
            'project_start_date' => now(),
            'planned_completion_date' => now()->addMonths(6),
            'status' => 'active',
        ]);
    }
    $solarPlantsCount = 3;
}

// Erstelle eine Test-Aufgabe für alle Solaranlagen
echo "\nErstelle eine Test-Aufgabe für alle Solaranlagen...\n";
$task = Task::create([
    'title' => 'Test Aufgabe für alle Solaranlagen',
    'description' => 'Diese Aufgabe soll bei allen Solaranlagen als erledigt markiert werden.',
    'task_type_id' => $taskType->id,
    'priority' => 'medium',
    'status' => 'open',
    'applies_to_all_solar_plants' => true,
    'solar_plant_id' => null,
    'assigned_to' => $user->id,
    'owner_id' => $user->id,
    'created_by' => $user->id,
]);

echo "Aufgabe erstellt: {$task->title} (ID: {$task->id})\n";
echo "Gilt für alle Solaranlagen: " . ($task->applies_to_all_solar_plants ? 'JA' : 'NEIN') . "\n";

// Markiere die Aufgabe als abgeschlossen
echo "\nMarkiere die Aufgabe als abgeschlossen...\n";
$task->markAsCompleted();

// Prüfe, ob für jede Solaranlage eine abgeschlossene Aufgabe erstellt wurde
$completedTasks = Task::where('title', $task->title)
    ->where('status', 'completed')
    ->whereNotNull('solar_plant_id')
    ->where('applies_to_all_solar_plants', false)
    ->with('solarPlant')
    ->get();

echo "Anzahl erstellter abgeschlossener Aufgaben: {$completedTasks->count()}\n";

if ($completedTasks->count() === $solarPlantsCount) {
    echo "✅ SUCCESS: Für jede Solaranlage wurde eine abgeschlossene Aufgabe erstellt!\n";
    
    foreach ($completedTasks as $completedTask) {
        echo "  - {$completedTask->solarPlant->name} (Task ID: {$completedTask->id})\n";
    }
} else {
    echo "❌ FEHLER: Nicht alle Solaranlagen haben eine abgeschlossene Aufgabe erhalten.\n";
}

// Originale Aufgabe prüfen
$originalTask = Task::find($task->id);
echo "\nOriginale Aufgabe Status: {$originalTask->status}\n";
echo "Gilt für alle Solaranlagen: " . ($originalTask->applies_to_all_solar_plants ? 'JA' : 'NEIN') . "\n";

echo "\nTest abgeschlossen!\n";
