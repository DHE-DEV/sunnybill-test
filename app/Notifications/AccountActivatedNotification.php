<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
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
        $loginUrl = config('app.url') . '/admin';

        return (new MailMessage)
            ->subject('Ihr Account wurde aktiviert - ' . config('app.name'))
            ->greeting('Hallo ' . $notifiable->name . '!')
            ->line('Herzlichen Glückwunsch! Ihr Account bei ' . config('app.name') . ' wurde erfolgreich aktiviert.')
            ->line('Ihre E-Mail-Adresse wurde bestätigt und Sie können sich nun in Ihr Konto einloggen.')
            ->line('**Ihre Anmeldedaten:**')
            ->line('E-Mail: ' . $notifiable->email)
            ->line('Passwort: Das temporäre Passwort aus der ersten E-Mail')
            ->action('Jetzt anmelden', $loginUrl)
            ->line('⚠️ **Wichtiger Hinweis:** Bei Ihrer ersten Anmeldung müssen Sie aus Sicherheitsgründen ein neues Passwort festlegen.')
            ->line('Falls Sie Ihr temporäres Passwort vergessen haben, wenden Sie sich bitte an Ihren Administrator.')
            ->line('Bei Fragen oder Problemen wenden Sie sich bitte an Ihren Administrator.')
            ->salutation('Mit freundlichen Grüßen,')
            ->salutation('Das ' . config('app.name') . ' Team');
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
