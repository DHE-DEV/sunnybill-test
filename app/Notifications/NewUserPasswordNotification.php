<?php

namespace App\Notifications;

use App\Models\CompanySetting;
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
        try {
            $companySettings = CompanySetting::current();
            $portalUrl = $companySettings->getPortalUrl();
            $portalName = $companySettings->getPortalName();
        } catch (\Exception $e) {
            // Fallback falls Portal-Spalten noch nicht existieren
            $portalUrl = rtrim(config('app.url'), '/') . '/admin';
            $portalName = config('app.name', 'SunnyBill');
        }

        return (new MailMessage)
            ->subject('Ihr neues Benutzerkonto - ' . $portalName)
            ->greeting('Hallo ' . $notifiable->name . '!')
            ->line('Willkommen bei ' . $portalName . '!')
            ->line('Ihr Benutzerkonto wurde erfolgreich erstellt.')
            ->line('')
            ->line('**⚠️ WICHTIG: Sie müssen zunächst Ihre E-Mail-Adresse bestätigen, bevor Sie sich anmelden können!**')
            ->line('Sie erhalten eine separate E-Mail mit einem Bestätigungslink. Klicken Sie auf diesen Link, um Ihr Konto zu aktivieren.')
            ->line('')
            ->line('**Ihre Anmeldedaten:**')
            ->line('Benutzername: ' . $notifiable->name)
            ->line('E-Mail: ' . $notifiable->email)
            ->line('Temporäres Passwort: **' . $this->temporaryPassword . '**')
            ->line('')
            ->line('**Anmeldeprozess:**')
            ->line('1. Bestätigen Sie zunächst Ihre E-Mail-Adresse über den Bestätigungslink')
            ->line('2. Melden Sie sich dann mit den oben genannten Daten an')
            ->line('3. Bei der ersten Anmeldung müssen Sie aus Sicherheitsgründen ein neues Passwort festlegen')
            ->line('')
            ->action('Zum Portal', $portalUrl)
            ->line('Portal-URL: ' . $portalUrl)
            ->line('')
            ->line('⚠️ **Bitte bewahren Sie diese E-Mail sicher auf, bis Sie sich erfolgreich angemeldet und Ihr Passwort geändert haben.**')
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
            'password_sent_at' => now(),
        ];
    }
}