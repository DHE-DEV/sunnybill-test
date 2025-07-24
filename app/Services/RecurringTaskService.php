<?php

namespace App\Services;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecurringTaskService
{
    /**
     * Verarbeite alle wiederkehrenden Aufgaben
     */
    public function processRecurringTasks(): array
    {
        $processedTasks = [];
        $errors = [];

        // Hole alle wiederkehrenden Aufgaben
        $recurringTasks = Task::where('is_recurring', true)
            ->whereNull('deleted_at')
            ->get();

        foreach ($recurringTasks as $task) {
            try {
                $result = $this->processRecurringTask($task);
                $processedTasks[] = $result;
            } catch (\Exception $e) {
                $errors[] = [
                    'task_id' => $task->id,
                    'error' => $e->getMessage()
                ];
                Log::error("Fehler bei wiederkehrender Aufgabe {$task->id}: " . $e->getMessage());
            }
        }

        return [
            'processed' => $processedTasks,
            'errors' => $errors
        ];
    }

    /**
     * Verarbeite eine einzelne wiederkehrende Aufgabe
     */
    public function processRecurringTask(Task $task): array
    {
        // Validiere wiederkehrende Aufgabe
        if (!$task->is_recurring) {
            return [
                'task_id' => $task->id,
                'action' => 'skipped',
                'reason' => 'Nicht wiederkehrend'
            ];
        }

        // Prüfe ob recurring_pattern definiert ist
        if (empty($task->recurring_pattern)) {
            return $this->handleMissingPattern($task);
        }

        // Bestimme nächstes Fälligkeitsdatum
        $nextDueDate = $this->calculateNextDueDate($task);
        
        if (!$nextDueDate) {
            return [
                'task_id' => $task->id,
                'action' => 'error',
                'reason' => 'Konnte nächstes Fälligkeitsdatum nicht berechnen'
            ];
        }

        // Prüfe ob eine neue Instanz erstellt werden muss
        if ($this->needsNewInstance($task, $nextDueDate)) {
            return $this->createNewInstance($task, $nextDueDate);
        }

        return [
            'task_id' => $task->id,
            'action' => 'no_action_needed',
            'reason' => 'Aufgabe ist noch aktuell'
        ];
    }

    /**
     * Behandle Aufgaben ohne definiertes Wiederholungsmuster
     */
    private function handleMissingPattern(Task $task): array
    {
        // Für tägliche Aufgaben basierend auf dem Titel
        if (str_contains(strtolower($task->title), 'täglich')) {
            $task->update(['recurring_pattern' => 'daily']);
            
            return [
                'task_id' => $task->id,
                'action' => 'pattern_fixed',
                'reason' => 'Wiederholungsmuster auf "daily" gesetzt'
            ];
        }

        // Für wöchentliche Aufgaben
        if (str_contains(strtolower($task->title), 'wöchentlich')) {
            $task->update(['recurring_pattern' => 'weekly']);
            
            return [
                'task_id' => $task->id,
                'action' => 'pattern_fixed',
                'reason' => 'Wiederholungsmuster auf "weekly" gesetzt'
            ];
        }

        // Standard: Setze auf tägliche Wiederholung
        $task->update(['recurring_pattern' => 'daily']);
        
        return [
            'task_id' => $task->id,
            'action' => 'pattern_defaulted',
            'reason' => 'Wiederholungsmuster standardmäßig auf "daily" gesetzt'
        ];
    }

    /**
     * Berechne nächstes Fälligkeitsdatum basierend auf Pattern
     */
    private function calculateNextDueDate(Task $task): ?Carbon
    {
        $dueDate = $task->due_date ? Carbon::parse($task->due_date) : Carbon::today();
        
        return match($task->recurring_pattern) {
            'daily' => $dueDate->addDay(),
            'weekly' => $dueDate->addWeek(),
            'monthly' => $dueDate->addMonth(),
            'quarterly' => $dueDate->addMonths(3),
            'yearly' => $dueDate->addYear(),
            default => null
        };
    }

    /**
     * Prüfe ob eine neue Instanz erstellt werden muss
     */
    private function needsNewInstance(Task $task, Carbon $nextDueDate): bool
    {
        // Wenn die Aufgabe abgeschlossen ist, erstelle neue Instanz
        if ($task->status === 'completed') {
            return true;
        }

        // Wenn das Fälligkeitsdatum überschritten ist und die Aufgabe läuft
        if ($task->due_date && Carbon::parse($task->due_date)->isPast() && $task->status === 'in_progress') {
            return true;
        }

        return false;
    }

    /**
     * Erstelle neue Instanz einer wiederkehrenden Aufgabe
     */
    private function createNewInstance(Task $originalTask, Carbon $nextDueDate): array
    {
        // Schließe die ursprüngliche Aufgabe ab, wenn sie noch läuft
        if ($originalTask->status !== 'completed') {
            $originalTask->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        }

        // Erstelle neue Instanz
        $newTask = $originalTask->replicate([
            'task_number',
            'completed_at',
            'created_at',
            'updated_at'
        ]);

        $newTask->status = 'open';
        $newTask->due_date = $nextDueDate->toDateString();
        $newTask->completed_at = null;
        $newTask->created_by = $originalTask->created_by;
        $newTask->save();

        return [
            'task_id' => $originalTask->id,
            'new_task_id' => $newTask->id,
            'action' => 'new_instance_created',
            'next_due_date' => $nextDueDate->format('Y-m-d'),
            'reason' => 'Neue Instanz für wiederkehrende Aufgabe erstellt'
        ];
    }

    /**
     * Hole alle verfügbaren Wiederholungsmuster
     */
    public static function getAvailablePatterns(): array
    {
        return [
            'daily' => 'Täglich',
            'weekly' => 'Wöchentlich', 
            'monthly' => 'Monatlich',
            'quarterly' => 'Vierteljährlich',
            'yearly' => 'Jährlich'
        ];
    }

    /**
     * Setze Wiederholungsmuster für eine Aufgabe
     */
    public function setRecurringPattern(Task $task, string $pattern): bool
    {
        $availablePatterns = array_keys(self::getAvailablePatterns());
        
        if (!in_array($pattern, $availablePatterns)) {
            return false;
        }

        $task->update([
            'is_recurring' => true,
            'recurring_pattern' => $pattern
        ]);

        return true;
    }
}
