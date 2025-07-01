<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Display the specified document (for viewing/preview)
     */
    public function view(Document $document): Response
    {
        // Check if file exists
        if (!Storage::disk($document->disk)->exists($document->path)) {
            abort(404, 'Datei nicht gefunden.');
        }

        // Get file content
        $fileContent = Storage::disk($document->disk)->get($document->path);
        
        // Return response with appropriate headers for inline viewing
        return response($fileContent, 200, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="' . $document->original_name . '"',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Download the specified document
     */
    public function download(Document $document): StreamedResponse
    {
        // Check if file exists
        if (!Storage::disk($document->disk)->exists($document->path)) {
            abort(404, 'Datei nicht gefunden.');
        }

        // Return download response
        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type,
            ]
        );
    }

    /**
     * Get document thumbnail (for future use)
     */
    public function thumbnail(Document $document): Response
    {
        // For now, just return the original file for images
        // In the future, this could generate actual thumbnails
        if (in_array($document->mime_type, ['image/jpeg', 'image/png'])) {
            return $this->view($document);
        }

        // For non-images, return a placeholder or icon
        abort(404, 'Thumbnail nicht verfügbar.');
    }
}