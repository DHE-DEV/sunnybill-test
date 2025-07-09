<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\User;
use Filament\Notifications\Notification;

class TaskObserver
{
    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        // Prüfe ob assigned_to geändert wurde
        if ($task->isDirty('assigned_to')) {
            $this->handleAssignedToChange($task);
        }

        // Prüfe ob owner_id geändert wurde
        if ($task->isDirty('owner_id')) {
            $this->handleOwnerChange($task);
        }
    }

    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        // Benachrichtigung bei Erstellung mit Zuweisung
        if ($task->assigned_to) {
            $this->sendAssignmentNotification($task, $task->assigned_to, 'zugewiesen');
        }

        // Benachrichtigung bei Erstellung mit Inhaber
        if ($task->owner_id && $task->owner_id !== $task->assigned_to) {
            $this->sendOwnerNotification($task, $task->owner_id, 'als Inhaber gesetzt');
        }
    }

    /**
     * Behandelt Änderungen bei assigned_to
     */
    private function handleAssignedToChange(Task $task): void
    {
        $oldAssignedTo = $task->getOriginal('assigned_to');
        $newAssignedTo = $task->assigned_to;

        // Benachrichtigung an neuen zugewiesenen Benutzer
        if ($newAssignedTo && $newAssignedTo !== $oldAssignedTo) {
            $this->sendAssignmentNotification($task, $newAssignedTo, 'zugewiesen');
        }

        // Optional: Benachrichtigung an vorherigen Benutzer über Entfernung der Zuweisung
        if ($oldAssignedTo && $oldAssignedTo !== $newAssignedTo) {
            $this->sendAssignmentRemovedNotification($task, $oldAssignedTo);
        }
    }

    /**
     * Behandelt Änderungen bei owner_id
     */
    private function handleOwnerChange(Task $task): void
    {
        $oldOwnerId = $task->getOriginal('owner_id');
        $newOwnerId = $task->owner_id;

        // Benachrichtigung an neuen Inhaber
        if ($newOwnerId && $newOwnerId !== $oldOwnerId) {
            $this->sendOwnerNotification($task, $newOwnerId, 'als Inhaber gesetzt');
        }

        // Optional: Benachrichtigung an vorherigen Inhaber über Entfernung
        if ($oldOwnerId && $oldOwnerId !== $newOwnerId) {
            $this->sendOwnerRemovedNotification($task, $oldOwnerId);
        }
    }

    /**
     * Sendet Benachrichtigung für Aufgaben-Zuweisung
     */
    private function sendAssignmentNotification(Task $task, int $userId, string $action): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            return;
        }

        Notification::make()
            ->title('Neue Aufgabe ' . $action)
            ->body("Ihnen wurde die Aufgabe \"{$task->title}\" {$action}.")
            ->icon('heroicon-o-clipboard-document-list')
            ->iconColor('info')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Aufgabe anzeigen')
                    ->url(route('filament.admin.resources.tasks.view', $task))
                    ->button(),
            ])
            ->sendToDatabase($user);
    }

    /**
     * Sendet Benachrichtigung für Inhaber-Zuweisung
     */
    private function sendOwnerNotification(Task $task, int $userId, string $action): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            return;
        }

        Notification::make()
            ->title('Aufgaben-Inhaberschaft')
            ->body("Sie wurden für die Aufgabe \"{$task->title}\" {$action}.")
            ->icon('heroicon-o-user-circle')
            ->iconColor('warning')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Aufgabe anzeigen')
                    ->url(route('filament.admin.resources.tasks.view', $task))
                    ->button(),
            ])
            ->sendToDatabase($user);
    }

    /**
     * Sendet Benachrichtigung über entfernte Zuweisung
     */
    private function sendAssignmentRemovedNotification(Task $task, int $userId): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            return;
        }

        Notification::make()
            ->title('Aufgaben-Zuweisung entfernt')
            ->body("Die Zuweisung für die Aufgabe \"{$task->title}\" wurde entfernt.")
            ->icon('heroicon-o-x-circle')
            ->iconColor('gray')
            ->sendToDatabase($user);
    }

    /**
     * Sendet Benachrichtigung über entfernte Inhaberschaft
     */
    private function sendOwnerRemovedNotification(Task $task, int $userId): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            return;
        }

        Notification::make()
            ->title('Aufgaben-Inhaberschaft entfernt')
            ->body("Die Inhaberschaft für die Aufgabe \"{$task->title}\" wurde entfernt.")
            ->icon('heroicon-o-x-circle')
            ->iconColor('gray')
            ->sendToDatabase($user);
    }
}