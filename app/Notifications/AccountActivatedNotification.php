<?php

namespace App\Notifications;

use App\Models\CompanySetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivatedNotification extends Notification
{
    use Queueable;

    protected ?string $temporaryPassword;

    /**
     * Create a new notification instance.
     */
    public function __construct(?string $temporaryPassword = null)
    {
        $this->temporaryPassword = $temporaryPassword;
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
        try {
            $companySettings = CompanySetting::current();
            $portalUrl = $companySettings->getPortalUrl();
            $portalName = $companySettings->getPortalName();
        } catch (\Exception $e) {
            // Fallback falls Portal-Spalten noch nicht existieren
            $portalUrl = rtrim(config('app.url'), '/') . '/admin';
            $portalName = config('app.name', 'SunnyBill');
        }

        // Lade das temporäre Passwort aus der tmp_p Spalte oder verwende das übergebene
        $temporaryPassword = $this->temporaryPassword ?? $notifiable->temporary_password ?? $notifiable->getTemporaryPasswordForEmail();
        
        $mailMessage = (new MailMessage)
            ->subject('Ihr Account wurde aktiviert - ' . $portalName)
            ->greeting('Hallo ' . $notifiable->name . '!')
            ->line('Herzlichen Glückwunsch! Ihr Account bei ' . $portalName . ' wurde erfolgreich aktiviert.')
            ->line('Ihre E-Mail-Adresse wurde bestätigt und Sie können sich nun in Ihr Konto einloggen.')
            ->line('**Ihre Anmeldedaten:**')
            ->line('E-Mail: ' . $notifiable->email);

        // Füge das temporäre Passwort hinzu, falls verfügbar
        if ($temporaryPassword) {
            $mailMessage->line('Temporäres Passwort: **' . $temporaryPassword . '**');
            
            // Erstelle einen sicheren Token für die direkte Passwort-Änderung
            $token = hash('sha256', $notifiable->id . $notifiable->email . $notifiable->created_at);
            $passwordChangeUrl = url('/password/change/' . $notifiable->id . '/' . $token);
            
            $mailMessage
                ->line('⚠️ **Wichtiger Hinweis:** Sie müssen Ihr temporäres Passwort aus Sicherheitsgründen ändern.')
                ->action('Passwort jetzt ändern', $passwordChangeUrl)
                ->line('Alternativ können Sie sich auch direkt anmelden und werden zur Passwort-Änderung weitergeleitet:')
                ->line('Portal-URL: ' . $portalUrl);
        } else {
            $mailMessage->line('Passwort: Das temporäre Passwort aus der ersten E-Mail');
            $mailMessage->action('Jetzt anmelden', $portalUrl);
        }

        return $mailMessage
            ->line('Falls Sie Ihr temporäres Passwort vergessen haben, wenden Sie sich bitte an Ihren Administrator.')
            ->line('Bei Fragen oder Problemen wenden Sie sich bitte an Ihren Administrator.')
            ->salutation('Mit freundlichen Grüßen,')
            ->salutation('Das ' . $portalName . ' Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $notifiable->id,
            'user_email' => $notifiable->email,
            'activated_at' => now(),
        ];
    }
}
