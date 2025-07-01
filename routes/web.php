<?php

use Illuminate\Support\Facades\Route;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

Route::get('/', function () {
    return view('welcome');
});

// Document download route
Route::get('/documents/{document}/download', function (Document $document) {
    $disk = Storage::disk($document->disk);
    
    // Check if file exists
    if (!$disk->exists($document->path)) {
        abort(404, 'Datei nicht gefunden');
    }
    
    // For cloud storage, redirect to signed URL
    if ($document->disk === 's3') {
        $url = $disk->temporaryUrl($document->path, now()->addMinutes(5));
        return redirect($url);
    }
    
    // For local storage, serve file directly
    $fileContent = $disk->get($document->path);
    
    return response($fileContent)
        ->header('Content-Type', $document->mime_type)
        ->header('Content-Disposition', 'attachment; filename="' . $document->original_name . '"')
        ->header('Content-Length', $document->size);
})->name('documents.download');

// Document preview route
Route::get('/documents/{document}/preview', function (Document $document) {
    $disk = Storage::disk($document->disk);
    
    // Check if file exists
    if (!$disk->exists($document->path)) {
        abort(404, 'Datei nicht gefunden');
    }
    
    // For cloud storage, redirect to signed URL
    if ($document->disk === 's3') {
        $url = $disk->temporaryUrl($document->path, now()->addMinutes(5));
        return redirect($url);
    }
    
    // For local storage, serve file directly
    $fileContent = $disk->get($document->path);
    
    return response($fileContent)
        ->header('Content-Type', $document->mime_type)
        ->header('Content-Disposition', 'inline; filename="' . $document->original_name . '"')
        ->header('Content-Length', $document->size);
})->name('documents.preview');

// Document view route (alias for preview)
Route::get('/documents/{document}/view', function (Document $document) {
    $disk = Storage::disk($document->disk);
    
    // Check if file exists
    if (!$disk->exists($document->path)) {
        abort(404, 'Datei nicht gefunden');
    }
    
    // For cloud storage, redirect to signed URL
    if ($document->disk === 's3') {
        $url = $disk->temporaryUrl($document->path, now()->addMinutes(5));
        return redirect($url);
    }
    
    // For local storage, serve file directly
    $fileContent = $disk->get($document->path);
    
    return response($fileContent)
        ->header('Content-Type', $document->mime_type)
        ->header('Content-Disposition', 'inline; filename="' . $document->original_name . '"')
        ->header('Content-Length', $document->size);
})->name('documents.view');

// Document delete route
Route::delete('/admin/documents/{document}/delete', function (Document $document) {
    try {
        // Lösche die Datei aus dem Dateisystem
        $disk = Storage::disk($document->disk ?? 'documents');
        if ($disk->exists($document->path)) {
            $disk->delete($document->path);
        }
        
        // Lösche das Datenbankrecord
        $document->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Dokument erfolgreich gelöscht'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Fehler beim Löschen des Dokuments: ' . $e->getMessage()
        ], 500);
    }
})->name('documents.delete');
