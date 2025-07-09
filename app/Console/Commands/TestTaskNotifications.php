<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use Illuminate\Console\Command;

class TestTaskNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:task-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testet die Task-Benachrichtigungen';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Teste Task-Benachrichtigungen...');

        // Hole zwei Benutzer für den Test
        $users = User::take(2)->get();
        
        if ($users->count() < 2) {
            $this->error('Mindestens 2 Benutzer erforderlich für den Test');
            return 1;
        }

        $user1 = $users->first();
        $user2 = $users->last();

        $this->info("Benutzer 1: {$user1->name} (ID: {$user1->id})");
        $this->info("Benutzer 2: {$user2->name} (ID: {$user2->id})");

        // Test 1: Neue Aufgabe mit Zuweisung erstellen
        $this->info("\n--- Test 1: Neue Aufgabe mit Zuweisung erstellen ---");
        
        $task = Task::create([
            'title' => 'Test Aufgabe für Benachrichtigungen',
            'description' => 'Diese Aufgabe testet die Benachrichtigungsfunktion',
            'priority' => 'medium',
            'status' => 'open',
            'task_type_id' => 1, // Annahme: TaskType mit ID 1 existiert
            'assigned_to' => $user1->id,
            'owner_id' => $user2->id,
            'created_by' => auth()->id() ?? $user2->id,
        ]);

        $this->info("Aufgabe erstellt: {$task->title} (ID: {$task->id})");
        $this->info("Zugewiesen an: {$user1->name}");
        $this->info("Inhaber: {$user2->name}");

        // Prüfe Benachrichtigungen
        $user1Notifications = $user1->unreadNotifications()->count();
        $user2Notifications = $user2->unreadNotifications()->count();

        $this->info("Ungelesene Benachrichtigungen für {$user1->name}: {$user1Notifications}");
        $this->info("Ungelesene Benachrichtigungen für {$user2->name}: {$user2Notifications}");

        // Test 2: Zuweisung ändern
        $this->info("\n--- Test 2: Zuweisung ändern ---");
        
        $task->update([
            'assigned_to' => $user2->id,
            'owner_id' => $user1->id,
        ]);

        $this->info("Aufgabe aktualisiert:");
        $this->info("Neue Zuweisung an: {$user2->name}");
        $this->info("Neuer Inhaber: {$user1->name}");

        // Prüfe Benachrichtigungen erneut
        $user1->refresh();
        $user2->refresh();
        
        $user1NotificationsAfter = $user1->unreadNotifications()->count();
        $user2NotificationsAfter = $user2->unreadNotifications()->count();

        $this->info("Ungelesene Benachrichtigungen für {$user1->name}: {$user1NotificationsAfter}");
        $this->info("Ungelesene Benachrichtigungen für {$user2->name}: {$user2NotificationsAfter}");

        // Zeige die letzten Benachrichtigungen
        $this->info("\n--- Letzte Benachrichtigungen ---");
        
        $latestNotifications = \Illuminate\Notifications\DatabaseNotification::latest()->take(5)->get();
        
        foreach ($latestNotifications as $notification) {
            $user = User::find($notification->notifiable_id);
            $data = $notification->data;
            $this->info("Benutzer: {$user->name} | Titel: {$data['title']} | Nachricht: {$data['body']}");
        }

        // Aufräumen
        $task->delete();
        $this->info("\nTest-Aufgabe gelöscht.");

        $this->info("\nTest abgeschlossen!");
        return 0;
    }
}