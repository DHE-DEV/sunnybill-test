<?php

namespace App\Mail;

use App\Models\User;
use App\Models\CompanySetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GmailNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public array $notificationData;
    public CompanySetting $settings;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, array $notificationData)
    {
        $this->user = $user;
        $this->notificationData = $notificationData;
        $this->settings = CompanySetting::current();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = 'Gmail Benachrichtigung: ' . ($this->notificationData['subject'] ?? 'Neue E-Mail');
        
        return new Envelope(
            subject: $subject,
            from: config('mail.from.address', 'noreply@sunnybill.de'),
            replyTo: config('mail.from.address', 'noreply@sunnybill.de'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.gmail-notification',
            with: [
                'user' => $this->user,
                'notificationData' => $this->notificationData,
                'settings' => $this->settings,
                'companyName' => $this->settings->company_name ?: 'SunnyBill',
                'emailUrl' => $this->notificationData['url'] ?? null,
                'sender' => $this->notificationData['sender'] ?? 'Unbekannt',
                'subject' => $this->notificationData['subject'] ?? 'Kein Betreff',
                'snippet' => $this->notificationData['snippet'] ?? '',
                'receivedAt' => $this->notificationData['received_at'] ?? now()->toISOString(),
                'hasAttachments' => $this->notificationData['has_attachments'] ?? false,
                'isImportant' => $this->notificationData['is_important'] ?? false,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $mail = $this->subject('Gmail Benachrichtigung: ' . ($this->notificationData['subject'] ?? 'Neue E-Mail'))
                     ->view('emails.gmail-notification')
                     ->with([
                         'user' => $this->user,
                         'notificationData' => $this->notificationData,
                         'settings' => $this->settings,
                         'companyName' => $this->settings->company_name ?: 'SunnyBill',
                         'emailUrl' => $this->notificationData['url'] ?? null,
                         'sender' => $this->notificationData['sender'] ?? 'Unbekannt',
                         'subject' => $this->notificationData['subject'] ?? 'Kein Betreff',
                         'snippet' => $this->notificationData['snippet'] ?? '',
                         'receivedAt' => $this->notificationData['received_at'] ?? now()->toISOString(),
                         'hasAttachments' => $this->notificationData['has_attachments'] ?? false,
                         'isImportant' => $this->notificationData['is_important'] ?? false,
                     ]);

        // Setze Priorität für wichtige E-Mails
        if ($this->notificationData['is_important'] ?? false) {
            $mail->priority(1); // Hohe Priorität
        }

        return $mail;
    }
}
