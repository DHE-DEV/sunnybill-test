<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Task $task;
    protected User $assignedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, User $assignedBy)
    {
        $this->task = $task;
        $this->assignedBy = $assignedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        
        // Sende E-Mail nur wenn der Benutzer eine E-Mail-Adresse hat
        // und es sich um eine gültige E-Mail-Adresse handelt
        if ($notifiable->email && filter_var($notifiable->email, FILTER_VALIDATE_EMAIL)) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $taskUrl = url("/admin/tasks/{$this->task->id}");
        
        return (new MailMessage)
            ->subject("Neue Aufgabe zugewiesen: {$this->task->title}")
            ->greeting("Hallo {$notifiable->name}!")
            ->line("Ihnen wurde eine neue Aufgabe zugewiesen.")
            ->line("**Aufgabe:** {$this->task->title}")
            ->when($this->task->description, function ($message) {
                return $message->line("**Beschreibung:** {$this->task->description}");
            })
            ->when($this->task->due_date, function ($message) {
                return $message->line("**Fälligkeitsdatum:** {$this->task->due_date->format('d.m.Y')}");
            })
            ->line("**Priorität:** " . $this->getPriorityLabel($this->task->priority))
            ->line("**Zugewiesen von:** {$this->assignedBy->name}")
            ->action('Aufgabe anzeigen', $taskUrl)
            ->line('Vielen Dank für Ihre Aufmerksamkeit!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'task_number' => $this->task->task_number,
            'assigned_by_id' => $this->assignedBy->id,
            'assigned_by_name' => $this->assignedBy->name,
            'priority' => $this->task->priority,
            'due_date' => $this->task->due_date?->toDateString(),
            'message' => "Neue Aufgabe zugewiesen: {$this->task->title}",
            'action_url' => url("/admin/tasks/{$this->task->id}"),
        ];
    }

    /**
     * Get priority label in German
     */
    private function getPriorityLabel(string $priority): string
    {
        return match($priority) {
            'low' => 'Niedrig',
            'medium' => 'Mittel',
            'high' => 'Hoch',
            'urgent' => 'Dringend',
            default => ucfirst($priority),
        };
    }
}