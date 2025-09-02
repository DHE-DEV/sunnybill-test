<?php

namespace App\Mail;

use App\Models\CompanySetting;
use App\Models\SolarPlantBilling;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class SingleBillingMail extends Mailable
{
    use Queueable, SerializesModels;

    public SolarPlantBilling $billing;
    public CompanySetting $settings;
    public string $customMessage;
    public string $pdfPath;
    public string $pdfFileName;

    /**
     * Create a new message instance.
     */
    public function __construct(SolarPlantBilling $billing, ?string $customMessage, string $pdfPath, string $pdfFileName)
    {
        $this->billing = $billing;
        $this->customMessage = $customMessage ?? '';
        $this->pdfPath = $pdfPath;
        $this->pdfFileName = $pdfFileName;
        $this->settings = CompanySetting::current();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $customer = $this->billing->customer;
        $customerName = $customer->customer_type === 'business' && $customer->company_name 
            ? $customer->company_name 
            : $customer->name;

        $subject = sprintf(
            'Ihre Abrechnung %04d-%02d %s',
            $this->billing->billing_year,
            $this->billing->billing_month,
            ""
        );
        
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
        // Logo als base64 für E-Mail konvertieren
        $logoBase64 = null;
        if ($this->settings->hasLogo()) {
            try {
                $logoPath = storage_path('app/public/' . $this->settings->logo_path);
                if (file_exists($logoPath)) {
                    $logoContent = file_get_contents($logoPath);
                    $mimeType = mime_content_type($logoPath);
                    $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoContent);
                }
            } catch (\Exception $e) {
                // Logo konnte nicht geladen werden - wird ignoriert
            }
        }

        return new Content(
            view: 'emails.single-billing-mail',
            with: [
                'billing' => $this->billing,
                'customMessage' => $this->customMessage,
                'settings' => $this->settings,
                'companyName' => $this->settings->company_name ?: 'SunnyBill',
                'logoBase64' => $logoBase64,
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
        
        if (file_exists($this->pdfPath)) {
            $attachments[] = Attachment::fromPath($this->pdfPath)
                ->as($this->pdfFileName)
                ->withMime('application/pdf');
        }
        
        return $attachments;
    }
}
