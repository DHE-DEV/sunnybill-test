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

        $mailMessage = (new MailMessage)
            ->subject('Ihr Account wurde aktiviert - ' . $portalName)
            ->greeting('Hallo ' . $notifiable->name . '!')
            ->line('Herzlichen Glückwunsch! Ihr Account bei ' . $portalName . ' wurde erfolgreich aktiviert.')
            ->line('Ihre E-Mail-Adresse wurde bestätigt und Sie können sich nun in Ihr Konto einloggen.')
            ->line('**Ihre Anmeldedaten:**')
            ->line('E-Mail: ' . $notifiable->email)
            ->action('Jetzt anmelden', $portalUrl)
            ->line('Bei Fragen oder Problemen wenden Sie sich bitte an Ihren Administrator.')
            ->salutation('Mit freundlichen Grüßen,')
            ->salutation('Das ' . $portalName . ' Team');

        return $mailMessage;
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
