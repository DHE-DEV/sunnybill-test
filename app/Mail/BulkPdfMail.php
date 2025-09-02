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
            view: 'emails.bulk-pdf-mail',
            with: [
                'totalCount' => $this->totalCount,
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
