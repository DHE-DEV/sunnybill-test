<?php

namespace App\Notifications;

use App\Models\Router;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RouterOfflineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Router $router;
    protected string $statusChange;

    /**
     * Create a new notification instance.
     */
    public function __construct(Router $router, string $statusChange = '')
    {
        $this->router = $router;
        $this->statusChange = $statusChange;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $minutesOffline = $this->router->last_seen_at ? 
            round($this->router->last_seen_at->diffInMinutes(now())) : 'unbekannt';
        
        $isBackOnline = str_contains($this->statusChange, '→ online');
        
        if ($isBackOnline) {
            return (new MailMessage)
                ->subject("✅ Router {$this->router->name} ist wieder online")
                ->greeting('Router Status Update')
                ->line("Der Router **{$this->router->name}** ist wieder online und sendet Daten.")
                ->line("Status-Änderung: {$this->statusChange}")
                ->line("Zuletzt gesehen: " . ($this->router->last_seen_at ? $this->router->last_seen_at->format('d.m.Y H:i:s') : 'Nie'))
                ->action('Router Details anzeigen', url("/admin/routers/{$this->router->id}/edit"))
                ->line('Das System überwacht weiterhin alle Router-Verbindungen.')
                ->salutation('VoltMaster Monitoring System');
        }
        
        return (new MailMessage)
            ->subject("🚨 WARNUNG: Router {$this->router->name} ist offline")
            ->greeting('Router Offline Warnung')
            ->line("Der Router **{$this->router->name}** ist offline und sendet keine Daten mehr.")
            ->line("Status-Änderung: {$this->statusChange}")
            ->line("Zuletzt gesehen: " . ($this->router->last_seen_at ? $this->router->last_seen_at->format('d.m.Y H:i:s') : 'Nie'))
            ->line("Offline seit: {$minutesOffline} Minuten")
            ->line('')
            ->line('**Router-Details:**')
            ->line("- Name: {$this->router->name}")
            ->line("- Modell: {$this->router->model}")
            ->line("- Standort: " . ($this->router->location ?? 'Nicht angegeben'))
            ->line("- IP-Adresse: " . ($this->router->ip_address ?? 'Nicht konfiguriert'))
            ->line("- Netzbetreiber: " . ($this->router->operator ?? 'Unbekannt'))
            ->action('Router Details anzeigen', url("/admin/routers/{$this->router->id}/edit"))
            ->line('Bitte überprüfen Sie die Router-Verbindung und kontaktieren Sie bei anhaltenden Problemen den Support.')
            ->salutation('VoltMaster Monitoring System');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'router_id' => $this->router->id,
            'router_name' => $this->router->name,
            'status_change' => $this->statusChange,
            'last_seen_at' => $this->router->last_seen_at,
            'offline_since_minutes' => $this->router->last_seen_at ? 
                round($this->router->last_seen_at->diffInMinutes(now())) : null
        ];
    }
}