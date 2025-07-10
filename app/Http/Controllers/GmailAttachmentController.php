<?php

namespace App\Http\Controllers;

use App\Models\GmailEmail;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class GmailAttachmentController extends Controller
{
    protected $gmailService;

    public function __construct(GmailService $gmailService)
    {
        $this->gmailService = $gmailService;
    }

    /**
     * Download Gmail attachment
     */
    public function download(Request $request, string $emailUuid, string $attachmentId)
    {
        try {
            // Find the Gmail email by UUID
            $email = GmailEmail::where('uuid', $emailUuid)->firstOrFail();
            
            // Find the attachment in the email's attachments array
            $attachment = collect($email->attachments ?? [])->first(function ($att) use ($attachmentId) {
                return ($att['id'] ?? null) === $attachmentId ||
                       ($att['attachmentId'] ?? null) === $attachmentId;
            });
            
            if (!$attachment) {
                abort(404, 'Anhang nicht gefunden');
            }

            // Try to get the attachment from local storage first
            $localPath = $this->getLocalAttachmentPath($email, $attachment);
            
            if (Storage::exists($localPath)) {
                return $this->serveLocalFile($localPath, $attachment, true);
            }

            // If not found locally, download from Gmail API with retry logic
            $fileContent = $this->gmailService->downloadAttachmentWithRetry($email->gmail_id, $attachmentId, 3);
            
            if (!$fileContent) {
                abort(404, 'Anhang konnte nicht heruntergeladen werden');
            }

            // Save to local storage for future use
            Storage::put($localPath, $fileContent);

            // Serve the file
            return response($fileContent)
                ->header('Content-Type', $attachment['mimeType'] ?? 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="' . ($attachment['filename'] ?? 'attachment') . '"')
                ->header('Content-Length', strlen($fileContent));

        } catch (\Exception $e) {
            \Log::error('Gmail attachment download error: ' . $e->getMessage(), [
                'email_uuid' => $emailUuid,
                'attachment_id' => $attachmentId,
                'trace' => $e->getTraceAsString()
            ]);
            
            abort(500, 'Fehler beim Herunterladen des Anhangs: ' . $e->getMessage());
        }
    }

    /**
     * Preview Gmail attachment
     */
    public function preview(Request $request, string $emailUuid, string $attachmentId)
    {
        try {
            // Find the Gmail email by UUID
            $email = GmailEmail::where('uuid', $emailUuid)->firstOrFail();
            
            // Find the attachment in the email's attachments array
            $attachment = collect($email->attachments ?? [])->first(function ($att) use ($attachmentId) {
                return ($att['id'] ?? null) === $attachmentId ||
                       ($att['attachmentId'] ?? null) === $attachmentId;
            });
            
            if (!$attachment) {
                abort(404, 'Anhang nicht gefunden');
            }

            // Only allow preview for certain file types
            $mimeType = $attachment['mimeType'] ?? '';
            $allowedPreviewTypes = [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/gif',
                'text/plain',
                'text/html'
            ];

            if (!in_array($mimeType, $allowedPreviewTypes)) {
                // Redirect to download for unsupported types
                return redirect()->route('gmail.attachment.download', [
                    'email' => $emailUuid,
                    'attachment' => $attachmentId
                ]);
            }

            // Try to get the attachment from local storage first
            $localPath = $this->getLocalAttachmentPath($email, $attachment);
            
            if (Storage::exists($localPath)) {
                return $this->serveLocalFile($localPath, $attachment, false);
            }

            // If not found locally, download from Gmail API with retry logic
            $fileContent = $this->gmailService->downloadAttachmentWithRetry($email->gmail_id, $attachmentId, 3);
            
            if (!$fileContent) {
                abort(404, 'Anhang konnte nicht heruntergeladen werden');
            }

            // Save to local storage for future use
            Storage::put($localPath, $fileContent);

            // Serve the file for preview
            return response($fileContent)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . ($attachment['filename'] ?? 'attachment') . '"')
                ->header('Content-Length', strlen($fileContent));

        } catch (\Exception $e) {
            \Log::error('Gmail attachment preview error: ' . $e->getMessage(), [
                'email_uuid' => $emailUuid,
                'attachment_id' => $attachmentId,
                'trace' => $e->getTraceAsString()
            ]);
            
            abort(500, 'Fehler beim Anzeigen des Anhangs: ' . $e->getMessage());
        }
    }

    /**
     * Get local storage path for attachment
     */
    protected function getLocalAttachmentPath(GmailEmail $email, array $attachment): string
    {
        $settings = \App\Models\CompanySetting::current();
        $attachmentPath = $settings->gmail_attachment_path ?? 'gmail-attachments';
        
        // Create directory structure: gmail-attachments/YYYY/MM/DD/gmail_id/
        $datePath = $email->gmail_date ? $email->gmail_date->format('Y/m/d') : date('Y/m/d');
        $fullPath = "{$attachmentPath}/{$datePath}/{$email->gmail_id}";
        
        $filename = $attachment['filename'] ?? "attachment_{$attachment['id']}";
        
        return "{$fullPath}/{$filename}";
    }

    /**
     * Serve file from local storage
     */
    protected function serveLocalFile(string $path, array $attachment, bool $forceDownload = false): Response
    {
        $fileContent = Storage::get($path);
        $mimeType = $attachment['mimeType'] ?? 'application/octet-stream';
        $filename = $attachment['filename'] ?? 'attachment';
        
        $disposition = $forceDownload ? 'attachment' : 'inline';
        
        return response($fileContent)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "{$disposition}; filename=\"{$filename}\"")
            ->header('Content-Length', strlen($fileContent))
            ->header('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
    }
}