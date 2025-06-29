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
    // Check if file exists
    if (!Storage::disk($document->disk)->exists($document->path)) {
        abort(404, 'Datei nicht gefunden');
    }
    
    // Get file content
    $fileContent = Storage::disk($document->disk)->get($document->path);
    
    // Return file download response
    return response($fileContent)
        ->header('Content-Type', $document->mime_type)
        ->header('Content-Disposition', 'attachment; filename="' . $document->original_name . '"')
        ->header('Content-Length', $document->size);
})->name('documents.download');

// Document preview route
Route::get('/documents/{document}/preview', function (Document $document) {
    // Check if file exists
    if (!Storage::disk($document->disk)->exists($document->path)) {
        abort(404, 'Datei nicht gefunden');
    }
    
    // Get file content
    $fileContent = Storage::disk($document->disk)->get($document->path);
    
    // Return file for inline viewing (preview)
    return response($fileContent)
        ->header('Content-Type', $document->mime_type)
        ->header('Content-Disposition', 'inline; filename="' . $document->original_name . '"')
        ->header('Content-Length', $document->size);
})->name('documents.preview');
