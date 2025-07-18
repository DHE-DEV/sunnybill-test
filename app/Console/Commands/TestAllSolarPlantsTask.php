<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\SolarPlant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TestAllSolarPlantsTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:all-solar-plants-task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test der "Alle Solaranlagen" Funktionalität für Tasks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Teste die "Alle Solaranlagen" Funktionalität...');
        
        // Simuliere einen eingeloggten Benutzer
        $user = User::first();
        if (!$user) {
            $this->info('Kein Benutzer gefunden. Erstelle einen Testbenutzer.');
            $user = User::create([
                'name' => 'Test Admin',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        Auth::login($user);

        // Hole einen Task Type
        $taskType = TaskType::first();
        if (!$taskType) {
            $this->info('Kein Task Type gefunden. Erstelle einen Test Task Type.');
            $taskType = TaskType::create([
                'name' => 'Test Task Type',
                'is_active' => true,
            ]);
        }

        // Prüfe, ob es Solaranlagen gibt
        $solarPlantsCount = SolarPlant::count();
        $this->info("Anzahl Solaranlagen in der Datenbank: {$solarPlantsCount}");

        if ($solarPlantsCount === 0) {
            $this->info('Keine Solaranlagen gefunden. Erstelle Test-Solaranlagen.');
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
        $this->info('Erstelle eine Test-Aufgabe für alle Solaranlagen...');
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

        $this->info("Aufgabe erstellt: {$task->title} (ID: {$task->id})");
        $this->info("Gilt für alle Solaranlagen: " . ($task->applies_to_all_solar_plants ? 'JA' : 'NEIN'));

        // Markiere die Aufgabe als abgeschlossen
        $this->info('Markiere die Aufgabe als abgeschlossen...');
        $task->markAsCompleted();

        // Prüfe, ob für jede Solaranlage eine abgeschlossene Aufgabe erstellt wurde
        $completedTasks = Task::where('title', $task->title)
            ->where('status', 'completed')
            ->whereNotNull('solar_plant_id')
            ->where('applies_to_all_solar_plants', false)
            ->with('solarPlant')
            ->get();

        $this->info("Anzahl erstellter abgeschlossener Aufgaben: {$completedTasks->count()}");

        if ($completedTasks->count() === $solarPlantsCount) {
            $this->info('✅ SUCCESS: Für jede Solaranlage wurde eine abgeschlossene Aufgabe erstellt!');
            
            foreach ($completedTasks as $completedTask) {
                $this->info("  - {$completedTask->solarPlant->name} (Task ID: {$completedTask->id})");
            }
        } else {
            $this->error('❌ FEHLER: Nicht alle Solaranlagen haben eine abgeschlossene Aufgabe erhalten.');
        }

        // Originale Aufgabe prüfen
        $originalTask = Task::find($task->id);
        $this->info("Originale Aufgabe Status: {$originalTask->status}");
        $this->info("Gilt für alle Solaranlagen: " . ($originalTask->applies_to_all_solar_plants ? 'JA' : 'NEIN'));

        $this->info('Test abgeschlossen!');
    }
}
