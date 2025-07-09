<?php

namespace App\Console\Commands;

use App\Models\GmailEmail;
use Illuminate\Console\Command;

class DebugGmailPdfAttachments extends Command
{
    protected $signature = 'debug:gmail-pdf {email_id}';
    protected $description = 'Debug Gmail PDF attachments for a specific email';

    public function handle()
    {
        $emailId = $this->argument('email_id');
        
        // Versuche zuerst nach UUID zu suchen, dann nach ID
        $email = GmailEmail::where('uuid', $emailId)->first() ?? GmailEmail::find($emailId);

        if (!$email) {
            $this->error("E-Mail mit ID/UUID {$emailId} nicht gefunden");
            return 1;
        }

        $this->info("=== E-Mail Debug Info ===");
        $this->info("ID: {$email->id}");
        $this->info("Subject: {$email->subject}");
        $this->info("Hat Anhänge: " . ($email->has_attachments ? 'Ja' : 'Nein'));
        $this->info("Anzahl Anhänge: {$email->attachment_count}");
        
        $this->info("\n=== Alle Anhänge ===");
        if ($email->attachments) {
            foreach ($email->attachments as $index => $attachment) {
                $this->info("Anhang {$index}:");
                $this->info("  - Filename: " . ($attachment['filename'] ?? 'N/A'));
                $this->info("  - MIME Type: " . ($attachment['mimeType'] ?? 'N/A'));
                $this->info("  - Size: " . ($attachment['size'] ?? 'N/A'));
            }
        } else {
            $this->info("Keine Anhänge gefunden");
        }

        $this->info("\n=== PDF-Anhänge Test ===");
        $this->info("Hat PDF-Anhänge: " . ($email->hasPdfAttachments() ? 'Ja' : 'Nein'));
        
        $pdfAttachments = $email->getPdfAttachments();
        $this->info("Anzahl PDF-Anhänge: " . count($pdfAttachments));
        
        if (!empty($pdfAttachments)) {
            foreach ($pdfAttachments as $index => $attachment) {
                $this->info("PDF-Anhang {$index}:");
                $this->info("  - Filename: " . ($attachment['filename'] ?? 'N/A'));
                $this->info("  - MIME Type: " . ($attachment['mimeType'] ?? 'N/A'));
            }
        }

        return 0;
    }
}