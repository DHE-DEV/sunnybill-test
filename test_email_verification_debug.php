<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debug: E-Mail-Bestätigung Weiterleitung ===\n\n";

try {
    // 1. Erstelle einen Test-Benutzer mit temporärem Passwort
    echo "1. Erstelle Test-Benutzer mit temporärem Passwort...\n";
    
    $temporaryPassword = User::generateRandomPassword();
    
    $user = User::create([
        'name' => 'Debug Test Benutzer',
        'email' => 'debug-test@example.com',
        'password' => Hash::make('dummy'),
        'role' => 'user',
        'is_active' => true,
        'email_verified_at' => null, // Noch nicht verifiziert
    ]);
    
    // Setze temporäres Passwort
    $user->setTemporaryPassword($temporaryPassword);
    
    echo "✓ Benutzer erstellt: {$user->email}\n";
    echo "✓ Temporäres Passwort: {$temporaryPassword}\n";
    echo "✓ tmp_p Spalte: " . ($user->tmp_p ?? 'NULL') . "\n";
    echo "✓ temporary_password Spalte: " . ($user->temporary_password ?? 'NULL') . "\n";
    echo "✓ password_change_required: " . ($user->password_change_required ? 'true' : 'false') . "\n";
    echo "✓ hasTemporaryPassword(): " . ($user->hasTemporaryPassword() ? 'true' : 'false') . "\n";
    echo "✓ getTemporaryPasswordForEmail(): " . ($user->getTemporaryPasswordForEmail() ?? 'NULL') . "\n\n";
    
    // 2. Simuliere E-Mail-Bestätigung Schritt für Schritt
    echo "2. Simuliere E-Mail-Bestätigung...\n";
    
    // Prüfe vor der Bestätigung
    echo "Vor E-Mail-Bestätigung:\n";
    echo "✓ hasVerifiedEmail(): " . ($user->hasVerifiedEmail() ? 'true' : 'false') . "\n";
    echo "✓ email_verified_at: " . ($user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
    
    $wasAlreadyVerified = $user->hasVerifiedEmail();
    
    // Markiere E-Mail als verifiziert
    if (!$wasAlreadyVerified) {
        $user->markEmailAsVerified();
        echo "✓ E-Mail als verifiziert markiert\n";
        
        // Sende Account-Aktivierungs-E-Mail
        try {
            $temporaryPasswordForEmail = $user->getTemporaryPasswordForEmail();
            echo "✓ Temporäres Passwort für E-Mail: " . ($temporaryPasswordForEmail ?? 'NULL') . "\n";
            
            $user->notify(new \App\Notifications\AccountActivatedNotification($temporaryPasswordForEmail));
            echo "✓ AccountActivatedNotification gesendet\n";
        } catch (\Exception $e) {
            echo "❌ Fehler beim Senden der Notification: " . $e->getMessage() . "\n";
        }
    }
    
    // Prüfe nach der Bestätigung
    echo "\nNach E-Mail-Bestätigung:\n";
    echo "✓ hasVerifiedEmail(): " . ($user->hasVerifiedEmail() ? 'true' : 'false') . "\n";
    echo "✓ email_verified_at: " . ($user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "✓ hasTemporaryPassword(): " . ($user->hasTemporaryPassword() ? 'true' : 'false') . "\n";
    echo "✓ tmp_p Spalte: " . ($user->tmp_p ?? 'NULL') . "\n";
    
    // 3. Teste Weiterleitung-Logik
    echo "\n3. Teste Weiterleitung-Logik...\n";
    
    if ($user->hasTemporaryPassword()) {
        echo "✓ Benutzer hat temporäres Passwort - sollte zur Passwort-Änderung weitergeleitet werden\n";
        
        $token = hash('sha256', $user->id . $user->email . $user->created_at);
        $redirectUrl = route('password.change.temporary', [
            'userId' => $user->id,
            'token' => $token
        ]);
        
        echo "✓ Weiterleitung URL: {$redirectUrl}\n";
        echo "✓ Token: {$token}\n";
        
    } else {
        echo "❌ Benutzer hat KEIN temporäres Passwort - würde zur Login-Seite weitergeleitet werden\n";
        echo "❌ Das ist das Problem!\n";
    }
    
    // 4. Prüfe alle relevanten User-Eigenschaften
    echo "\n4. Alle relevanten User-Eigenschaften:\n";
    
    $user->refresh(); // Lade User neu aus der Datenbank
    
    echo "✓ ID: {$user->id}\n";
    echo "✓ Email: {$user->email}\n";
    echo "✓ Name: {$user->name}\n";
    echo "✓ tmp_p: " . ($user->tmp_p ?? 'NULL') . "\n";
    echo "✓ temporary_password: " . ($user->temporary_password ?? 'NULL') . "\n";
    echo "✓ password_change_required: " . ($user->password_change_required ? 'true' : 'false') . "\n";
    echo "✓ email_verified_at: " . ($user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "✓ created_at: " . $user->created_at->format('Y-m-d H:i:s') . "\n";
    
    // 5. Teste User-Methoden direkt
    echo "\n5. Teste User-Methoden direkt:\n";
    
    echo "✓ hasTemporaryPassword(): " . ($user->hasTemporaryPassword() ? 'true' : 'false') . "\n";
    echo "✓ needsPasswordChange(): " . ($user->needsPasswordChange() ? 'true' : 'false') . "\n";
    echo "✓ getTemporaryPasswordForEmail(): " . ($user->getTemporaryPasswordForEmail() ?? 'NULL') . "\n";
    echo "✓ hasVerifiedEmail(): " . ($user->hasVerifiedEmail() ? 'true' : 'false') . "\n";
    
    // 6. Cleanup
    echo "\n6. Cleanup...\n";
    $user->delete();
    echo "✓ Test-Benutzer gelöscht\n";
    
    echo "\n=== Debug abgeschlossen ===\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
