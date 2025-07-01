<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixDocumentMimeTypes extends Command
{
    protected $signature = 'documents:fix-mime-types';
    protected $description = 'Fix MIME types for existing documents';

    public function handle()
    {
        $this->info('Korrigiere MIME-Types für bestehende Dokumente...');
        
        $documents = Document::where('mime_type', 'application/octet-stream')
            ->orWhereNull('mime_type')
            ->get();
        
        $fixed = 0;
        
        foreach ($documents as $document) {
            $fullPath = storage_path('app/' . $document->path);
            
            if (file_exists($fullPath)) {
                // Try to get MIME type, with fallback based on file extension
                $mimeType = mime_content_type($fullPath);
                
                // If mime_content_type fails or returns generic type, use file extension
                if (!$mimeType || $mimeType === 'application/octet-stream') {
                    $extension = strtolower(pathinfo($document->path, PATHINFO_EXTENSION));
                    $mimeType = match($extension) {
                        'pdf' => 'application/pdf',
                        'jpg', 'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                        'doc' => 'application/msword',
                        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        default => $mimeType ?: 'application/octet-stream'
                    };
                }
                
                if ($mimeType !== $document->mime_type) {
                    $document->update(['mime_type' => $mimeType]);
                    $this->line("Dokument '{$document->name}': {$document->mime_type} → {$mimeType}");
                    $fixed++;
                }
            } else {
                $this->warn("Datei nicht gefunden: {$document->path}");
            }
        }
        
        $this->info("✅ {$fixed} Dokumente korrigiert.");
        
        return 0;
    }
}