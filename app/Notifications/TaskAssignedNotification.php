<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Services\GmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

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
        // Sende E-Mail über Gmail-Service
        try {
            $gmailService = new GmailService();
            
            $subject = "Neue Aufgabe zugewiesen: {$this->task->title}";
            $taskUrl = url("/admin/tasks/{$this->task->id}");
            
            // Erstelle HTML-E-Mail-Body
            $body = "<h2>Hallo {$notifiable->name}!</h2>";
            $body .= "<p>Ihnen wurde eine neue Aufgabe zugewiesen.</p>";
            $body .= "<p><strong>Aufgabe:</strong> {$this->task->title}</p>";
            
            if ($this->task->description) {
                $body .= "<p><strong>Beschreibung:</strong> {$this->task->description}</p>";
            }
            
            if ($this->task->due_date) {
                $body .= "<p><strong>Fälligkeitsdatum:</strong> {$this->task->due_date->format('d.m.Y')}</p>";
            }
            
            $body .= "<p><strong>Priorität:</strong> " . $this->getPriorityLabel($this->task->priority) . "</p>";
            $body .= "<p><strong>Zugewiesen von:</strong> {$this->assignedBy->name}</p>";
            $body .= "<p><a href='{$taskUrl}' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Aufgabe anzeigen</a></p>";
            $body .= "<p>Vielen Dank für Ihre Aufmerksamkeit!</p>";
            $body .= "<p>Ihr SunnyBill Team</p>";
            
            $result = $gmailService->sendEmail($notifiable->email, $subject, $body, ['html' => true]);
            
            if ($result['success']) {
                Log::info('TaskAssignedNotification: E-Mail erfolgreich gesendet', [
                    'to' => $notifiable->email,
                    'task_id' => $this->task->id,
                    'task_title' => $this->task->title,
                    'message_id' => $result['message_id'] ?? null
                ]);
            } else {
                Log::error('TaskAssignedNotification: E-Mail-Versendung fehlgeschlagen', [
                    'to' => $notifiable->email,
                    'task_id' => $this->task->id,
                    'error' => $result['error'] ?? 'Unbekannter Fehler'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('TaskAssignedNotification: Exception beim E-Mail-Versand', [
                'to' => $notifiable->email,
                'task_id' => $this->task->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        
        // Fallback: Gib trotzdem eine MailMessage zurück für Kompatibilität
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