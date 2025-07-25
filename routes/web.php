<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes for PDF cleanup and downloads
Route::middleware(['auth'])->group(function () {
    Route::post('/admin/cleanup-temp-pdfs', function () {
        $batchId = request('batch');
        if (!$batchId) {
            return response()->json(['error' => 'Batch ID required'], 400);
        }
        
        try {
            // Lösche alle Dateien in diesem Batch-Ordner
            $batchPath = "temp/bulk-pdfs/{$batchId}";
            
            if (Storage::disk('public')->exists($batchPath)) {
                // Lösche alle Dateien im Batch-Ordner
                $files = Storage::disk('public')->files($batchPath);
                foreach ($files as $file) {
                    Storage::disk('public')->delete($file);
                }
                
                // Lösche den leeren Ordner
                Storage::disk('public')->deleteDirectory($batchPath);
                
                return response()->json(['success' => true, 'message' => 'Temporäre Dateien bereinigt']);
            }
            
            return response()->json(['success' => true, 'message' => 'Keine Dateien zum Bereinigen gefunden']);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Fehler beim Bereinigen: ' . $e->getMessage()], 500);
        }
    })->name('admin.cleanup-temp-pdfs');
    
    Route::get('/admin/download-bulk-pdfs', function () {
        $downloads = session('bulk_pdf_downloads');
        $batchId = session('bulk_pdf_batch_id');
        $successCount = session('bulk_pdf_success_count', 0);
        $errorCount = session('bulk_pdf_error_count', 0);
        
        if (!$downloads || !$batchId) {
            return redirect('/admin/solar-plant-billings')->with('error', 'Keine Downloads verfügbar');
        }
        
        return view('admin.bulk-pdf-download', compact('downloads', 'batchId', 'successCount', 'errorCount'));
    })->name('admin.download-bulk-pdfs');
});

// Document routes
Route::middleware(['auth'])->group(function () {
    Route::get('/documents/{document}/preview', [App\Http\Controllers\DocumentController::class, 'view'])
        ->name('documents.preview');
    Route::get('/documents/{document}/download', [App\Http\Controllers\DocumentController::class, 'download'])
        ->name('documents.download');
    Route::get('/documents/{document}/thumbnail', [App\Http\Controllers\DocumentController::class, 'thumbnail'])
        ->name('documents.thumbnail');
});

require __DIR__.'/auth.php';
