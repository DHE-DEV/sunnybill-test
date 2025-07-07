<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends VerifyEmail
{
    /**
     * The temporary password for the user.
     */
    protected $temporaryPassword;

    /**
     * Create a new notification instance.
     */
    public function __construct($temporaryPassword = null)
    {
        $this->temporaryPassword = $temporaryPassword;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        $mailMessage = (new MailMessage)
            ->subject('E-Mail-Adresse bestätigen - ' . config('app.name'))
            ->greeting('Hallo ' . $notifiable->name . '!')
            ->line('Willkommen bei ' . config('app.name') . '!')
            ->line('Ihr Benutzerkonto wurde erfolgreich erstellt. Um Ihr Konto zu aktivieren, müssen Sie Ihre E-Mail-Adresse bestätigen.')
            ->line('')
            ->line('**Ihre Anmeldedaten:**')
            ->line('Benutzername: ' . $notifiable->email);

        // Temporäres Passwort hinzufügen, falls vorhanden
        if ($this->temporaryPassword) {
            $mailMessage->line('Temporäres Passwort: ' . $this->temporaryPassword);
        }

        $mailMessage
            ->line('')
            ->line('**Wichtiger Hinweis:** Ein Login ist erst nach der Bestätigung Ihrer E-Mail-Adresse möglich.')
            ->line('')
            ->action('E-Mail-Adresse bestätigen', $verificationUrl)
            ->line('Dieser Bestätigungslink läuft in 60 Minuten ab.')
            ->line('Falls Sie dieses Konto nicht erstellt haben, können Sie diese E-Mail ignorieren.')
            ->salutation('Mit freundlichen Grüßen,')
            ->salutation('Das ' . config('app.name') . ' Team');

        return $mailMessage;
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
