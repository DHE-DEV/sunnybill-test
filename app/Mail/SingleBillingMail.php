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
        // Logo als base64 für E-Mail konvertieren (mit 50% Größenreduzierung)
        $logoBase64 = null;
        if ($this->settings->hasLogo()) {
            try {
                $logoPath = storage_path('app/public/' . $this->settings->logo_path);
                if (file_exists($logoPath)) {
                    // Logo laden und um 50% verkleinern
                    $originalImage = $this->createImageFromFile($logoPath);
                    if ($originalImage) {
                        $originalWidth = imagesx($originalImage);
                        $originalHeight = imagesy($originalImage);
                        
                        // Neue Dimensionen (50% kleiner)
                        $newWidth = intval($originalWidth * 0.5);
                        $newHeight = intval($originalHeight * 0.5);
                        
                        // Neues verkleinertes Bild erstellen
                        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                        
                        // Transparenz für PNG unterstützen
                        imagealphablending($resizedImage, false);
                        imagesavealpha($resizedImage, true);
                        $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                        imagefill($resizedImage, 0, 0, $transparent);
                        
                        // Bild verkleinern
                        imagecopyresampled($resizedImage, $originalImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
                        
                        // Als Base64 konvertieren
                        ob_start();
                        $mimeType = mime_content_type($logoPath);
                        if (strpos($mimeType, 'png') !== false) {
                            imagepng($resizedImage);
                        } else {
                            imagejpeg($resizedImage, null, 90);
                            $mimeType = 'image/jpeg';
                        }
                        $imageData = ob_get_contents();
                        ob_end_clean();
                        
                        $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                        
                        // Speicher freigeben
                        imagedestroy($originalImage);
                        imagedestroy($resizedImage);
                    }
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
     * Erstellt ein GD-Image-Resource aus einer Datei
     */
    private function createImageFromFile(string $filePath)
    {
        $mimeType = mime_content_type($filePath);
        
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                return imagecreatefrompng($filePath);
            case 'image/gif':
                return imagecreatefromgif($filePath);
            case 'image/webp':
                return imagecreatefromwebp($filePath);
            default:
                return false;
        }
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
