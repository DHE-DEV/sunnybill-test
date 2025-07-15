<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Facades\Log;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        // Sende Benachrichtigung wenn eine neue Task mit assigned_to erstellt wird
        if ($task->assigned_to && $task->assigned_to !== $task->created_by) {
            $this->sendTaskAssignedNotification($task, $task->assigned_to, $task->created_by);
        }
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        // Prüfe ob assigned_to geändert wurde
        if ($task->isDirty('assigned_to')) {
            $oldAssignedTo = $task->getOriginal('assigned_to');
            $newAssignedTo = $task->assigned_to;
            
            // Sende Benachrichtigung nur wenn:
            // 1. Eine neue Person zugewiesen wurde (nicht null)
            // 2. Es sich um eine andere Person handelt
            // 3. Die Person sich nicht selbst zuweist
            if ($newAssignedTo && 
                $newAssignedTo !== $oldAssignedTo && 
                $newAssignedTo !== auth()->id()) {
                
                $assignedBy = auth()->user() ?? $task->creator;
                $this->sendTaskAssignedNotification($task, $newAssignedTo, $assignedBy->id);
            }
        }
    }

    /**
     * Sende Task-Zuweisungs-Benachrichtigung
     */
    private function sendTaskAssignedNotification(Task $task, int $assignedToUserId, int $assignedByUserId): void
    {
        try {
            $assignedToUser = User::find($assignedToUserId);
            $assignedByUser = User::find($assignedByUserId);
            
            if (!$assignedToUser || !$assignedByUser) {
                Log::warning('TaskObserver: Benutzer nicht gefunden', [
                    'assigned_to' => $assignedToUserId,
                    'assigned_by' => $assignedByUserId,
                    'task_id' => $task->id
                ]);
                return;
            }
            
            // Prüfe ob der Benutzer eine gültige E-Mail-Adresse hat
            if (!$assignedToUser->email || !filter_var($assignedToUser->email, FILTER_VALIDATE_EMAIL)) {
                Log::info('TaskObserver: Keine gültige E-Mail-Adresse für Benutzer', [
                    'user_id' => $assignedToUser->id,
                    'user_name' => $assignedToUser->name,
                    'email' => $assignedToUser->email,
                    'task_id' => $task->id
                ]);
                return;
            }
            
            // Sende Benachrichtigung
            $assignedToUser->notify(new TaskAssignedNotification($task, $assignedByUser));
            
            Log::info('TaskObserver: Task-Zuweisungs-Benachrichtigung gesendet', [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'assigned_to' => $assignedToUser->name,
                'assigned_to_email' => $assignedToUser->email,
                'assigned_by' => $assignedByUser->name
            ]);
            
        } catch (\Exception $e) {
            Log::error('TaskObserver: Fehler beim Senden der Task-Benachrichtigung', [
                'task_id' => $task->id,
                'assigned_to' => $assignedToUserId,
                'assigned_by' => $assignedByUserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        // Hier könnten wir in Zukunft Benachrichtigungen für gelöschte Tasks hinzufügen
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        // Hier könnten wir in Zukunft Benachrichtigungen für wiederhergestellte Tasks hinzufügen
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        // Hier könnten wir in Zukunft Benachrichtigungen für endgültig gelöschte Tasks hinzufügen
    }
}