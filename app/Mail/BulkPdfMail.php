<?php

namespace App\Mail;

use App\Models\CompanySetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class BulkPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $pdfFiles;
    public CompanySetting $settings;
    public int $totalCount;

    /**
     * Create a new message instance.
     */
    public function __construct(array $pdfFiles, int $totalCount)
    {
        $this->pdfFiles = $pdfFiles;
        $this->totalCount = $totalCount;
        $this->settings = CompanySetting::current();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = 'Solar-Abrechnungen PDF-Export (' . $this->totalCount . ' Dateien)';
        
        return new Envelope(
            subject: $subject,
            from: config('mail.from.address', 'noreply@voltmaster.cloud'),
            replyTo: config('mail.from.address', 'noreply@voltmaster.cloud'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.bulk-pdf-mail',
            with: [
                'totalCount' => $this->totalCount,
                'settings' => $this->settings,
                'companyName' => $this->settings->company_name ?: 'SunnyBill',
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
        $attachments = [];
        
        foreach ($this->pdfFiles as $file) {
            if (file_exists($file['path'])) {
                $attachments[] = Attachment::fromPath($file['path'])
                    ->as($file['name'])
                    ->withMime('application/pdf');
            }
        }
        
        return $attachments;
    }
}
