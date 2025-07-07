<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserPasswordNotification extends Notification
{
    use Queueable;

    protected string $temporaryPassword;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $temporaryPassword)
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
        $loginUrl = config('app.url') . '/admin';

        return (new MailMessage)
            ->subject('Ihr neues Benutzerkonto - ' . config('app.name'))
            ->greeting('Hallo ' . $notifiable->name . '!')
            ->line('Willkommen bei ' . config('app.name') . '!')
            ->line('Ihr Benutzerkonto wurde erfolgreich erstellt. Um Ihr Konto zu aktivieren, müssen Sie zunächst Ihre E-Mail-Adresse bestätigen.')
            ->line('**Ihre Anmeldedaten:**')
            ->line('E-Mail: ' . $notifiable->email)
            ->line('Temporäres Passwort: **' . $this->temporaryPassword . '**')
            ->line('⚠️ **Wichtiger Hinweis:** Aus Sicherheitsgründen müssen Sie bei Ihrer ersten Anmeldung ein neues Passwort festlegen.')
            ->line('Bitte bewahren Sie diese E-Mail sicher auf, bis Sie sich erfolgreich angemeldet und Ihr Passwort geändert haben.')
            ->action('E-Mail-Adresse bestätigen', $loginUrl . '/email/verify')
            ->line('Nach der E-Mail-Bestätigung können Sie sich mit den oben genannten Daten anmelden.')
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
            'password_sent_at' => now(),
        ];
    }
}