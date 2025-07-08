<?php

use Illuminate\Support\Facades\Route;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

// Legal Pages
Route::get('/datenschutz', function () {
    return view('datenschutz');
})->name('datenschutz');

Route::get('/nutzungsbedingungen', function () {
    return view('nutzungsbedingungen');
})->name('nutzungsbedingungen');

Route::get('/impressum', function () {
    return view('impressum');
})->name('impressum');

// Login Route (redirect to Filament)
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

// Email Verification Routes
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    // Finde den Benutzer basierend auf der ID
    $user = \App\Models\User::findOrFail($id);
    
    // Prüfe die Signatur
    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403, 'Invalid verification link.');
    }
    
    // Prüfe ob der Link noch gültig ist
    if (! $request->hasValidSignature()) {
        abort(403, 'Verification link expired.');
    }
    
    // Prüfe ob E-Mail bereits verifiziert ist
    $wasAlreadyVerified = $user->hasVerifiedEmail();
    
    // Markiere E-Mail als verifiziert
    if (! $wasAlreadyVerified) {
        $user->markEmailAsVerified();
        
        // Sende Account-Aktivierungs-E-Mail mit temporärem Passwort
        try {
            $temporaryPassword = $user->getTemporaryPasswordForEmail();
            $user->notify(new \App\Notifications\AccountActivatedNotification($temporaryPassword));
        } catch (\Exception $e) {
            // Log error but don't break the verification process
            \Log::error('Failed to send account activation notification: ' . $e->getMessage());
        }
    }
    
    // Prüfe ob der Benutzer ein temporäres Passwort hat
    if ($user->hasTemporaryPassword()) {
        // Generiere einen sicheren Token für die Passwort-Änderung
        $token = hash('sha256', $user->id . $user->email . $user->created_at);
        
        return redirect()->route('password.change.temporary', [
            'userId' => $user->id,
            'token' => $token
        ])->with('status', 'E-Mail-Adresse erfolgreich bestätigt! Bitte ändern Sie jetzt Ihr temporäres Passwort.');
    }
    
    return redirect('/admin/login')->with('status', 'E-Mail-Adresse erfolgreich bestätigt! Sie können sich jetzt anmelden.');
})->middleware(['signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    
    return back()->with('message', 'Bestätigungslink wurde erneut gesendet!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Password Change Routes
Route::middleware('auth')->group(function () {
    Route::get('/password/change', [App\Http\Controllers\PasswordChangeController::class, 'show'])
        ->name('password.change');
    Route::post('/password/change', [App\Http\Controllers\PasswordChangeController::class, 'update'])
        ->name('password.update');
});

// Password Change Routes for Temporary Passwords (without authentication)
Route::get('/password/change/{userId}/{token}', [App\Http\Controllers\PasswordChangeController::class, 'showForTemporaryPassword'])
    ->name('password.change.temporary');
Route::post('/password/change/{userId}/{token}', [App\Http\Controllers\PasswordChangeController::class, 'updateTemporaryPassword'])
    ->name('password.update.temporary');

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

// Gmail OAuth2 Routes
Route::prefix('admin/gmail/oauth')->group(function () {
    Route::get('/callback', [App\Http\Controllers\GmailOAuthController::class, 'callback'])
        ->name('gmail.oauth.callback');
    Route::get('/authorize', [App\Http\Controllers\GmailOAuthController::class, 'authorize'])
        ->name('gmail.oauth.authorize');
    Route::post('/revoke', [App\Http\Controllers\GmailOAuthController::class, 'revoke'])
        ->name('gmail.oauth.revoke');
    Route::get('/test', [App\Http\Controllers\GmailOAuthController::class, 'test'])
        ->name('gmail.oauth.test');
});
