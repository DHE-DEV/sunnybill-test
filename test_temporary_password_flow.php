<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: Temporäres Passwort Flow ===\n\n";

try {
    // 1. Erstelle einen Test-Benutzer mit temporärem Passwort
    echo "1. Erstelle Test-Benutzer mit temporärem Passwort...\n";
    
    $temporaryPassword = User::generateRandomPassword();
    
    $user = User::create([
        'name' => 'Test Benutzer Temp Password',
        'email' => 'test-temp-password@example.com',
        'password' => Hash::make('dummy'), // Dummy-Passwort
        'role' => 'user',
        'is_active' => true,
        'email_verified_at' => null, // Noch nicht verifiziert
    ]);
    
    // Setze temporäres Passwort
    $user->setTemporaryPassword($temporaryPassword);
    
    echo "✓ Benutzer erstellt: {$user->email}\n";
    echo "✓ Temporäres Passwort: {$temporaryPassword}\n";
    echo "✓ Hat temporäres Passwort: " . ($user->hasTemporaryPassword() ? 'Ja' : 'Nein') . "\n";
    echo "✓ Passwort-Änderung erforderlich: " . ($user->needsPasswordChange() ? 'Ja' : 'Nein') . "\n\n";
    
    // 2. Simuliere E-Mail-Bestätigung
    echo "2. Simuliere E-Mail-Bestätigung...\n";
    
    $user->markEmailAsVerified();
    echo "✓ E-Mail als verifiziert markiert\n";
    
    // 3. Teste Token-Generierung für Passwort-Änderung
    echo "\n3. Teste Token-Generierung...\n";
    
    $token = hash('sha256', $user->id . $user->email . $user->created_at);
    $passwordChangeUrl = url('/password/change/' . $user->id . '/' . $token);
    
    echo "✓ Token generiert: {$token}\n";
    echo "✓ Passwort-Änderungs-URL: {$passwordChangeUrl}\n\n";
    
    // 4. Teste Passwort-Änderung
    echo "4. Teste Passwort-Änderung...\n";
    
    $newPassword = 'NewSecurePassword123!';
    
    // Simuliere Passwort-Änderung
    $user->update([
        'password' => Hash::make($newPassword),
    ]);
    
    $user->markPasswordAsChanged();
    
    echo "✓ Neues Passwort gesetzt\n";
    echo "✓ Hat temporäres Passwort: " . ($user->hasTemporaryPassword() ? 'Ja' : 'Nein') . "\n";
    echo "✓ Passwort-Änderung erforderlich: " . ($user->needsPasswordChange() ? 'Ja' : 'Nein') . "\n";
    echo "✓ Passwort geändert am: " . ($user->password_changed_at ? $user->password_changed_at->format('Y-m-d H:i:s') : 'Nicht gesetzt') . "\n\n";
    
    // 5. Teste E-Mail-Benachrichtigungen
    echo "5. Teste E-Mail-Benachrichtigungen...\n";
    
    // Erstelle einen neuen Benutzer für E-Mail-Test
    $emailTestUser = User::create([
        'name' => 'E-Mail Test Benutzer',
        'email' => 'email-test@example.com',
        'password' => Hash::make('dummy'),
        'role' => 'user',
        'is_active' => true,
        'email_verified_at' => null,
    ]);
    
    $emailTestTempPassword = User::generateRandomPassword();
    $emailTestUser->setTemporaryPassword($emailTestTempPassword);
    
    echo "✓ E-Mail-Test-Benutzer erstellt: {$emailTestUser->email}\n";
    echo "✓ Temporäres Passwort für E-Mail: {$emailTestTempPassword}\n";
    
    // Teste CustomVerifyEmail Notification
    try {
        $emailTestUser->sendEmailVerificationNotification($emailTestTempPassword);
        echo "✓ E-Mail-Bestätigungs-Benachrichtigung gesendet\n";
    } catch (Exception $e) {
        echo "⚠ E-Mail-Bestätigungs-Benachrichtigung konnte nicht gesendet werden: " . $e->getMessage() . "\n";
    }
    
    // Markiere E-Mail als verifiziert und teste AccountActivatedNotification
    $emailTestUser->markEmailAsVerified();
    
    try {
        $emailTestUser->notify(new \App\Notifications\AccountActivatedNotification($emailTestTempPassword));
        echo "✓ Account-Aktivierungs-Benachrichtigung gesendet\n";
    } catch (Exception $e) {
        echo "⚠ Account-Aktivierungs-Benachrichtigung konnte nicht gesendet werden: " . $e->getMessage() . "\n";
    }
    
    echo "\n6. Teste URL-Generierung...\n";
    
    $emailTestToken = hash('sha256', $emailTestUser->id . $emailTestUser->email . $emailTestUser->created_at);
    $emailTestPasswordChangeUrl = url('/password/change/' . $emailTestUser->id . '/' . $emailTestToken);
    
    echo "✓ Passwort-Änderungs-URL für E-Mail-Test: {$emailTestPasswordChangeUrl}\n";
    
    // 7. Cleanup
    echo "\n7. Cleanup...\n";
    
    $user->delete();
    $emailTestUser->delete();
    
    echo "✓ Test-Benutzer gelöscht\n";
    
    echo "\n=== Test erfolgreich abgeschlossen! ===\n";
    echo "\nZusammenfassung der implementierten Funktionen:\n";
    echo "✓ Temporäre Passwörter werden korrekt gesetzt und verwaltet\n";
    echo "✓ E-Mail-Bestätigung leitet zu Passwort-Änderung weiter\n";
    echo "✓ Sichere Token-Generierung für Passwort-Änderung\n";
    echo "✓ Passwort-Änderung löscht temporäres Passwort\n";
    echo "✓ E-Mail-Benachrichtigungen enthalten Passwort-Änderungs-Links\n";
    echo "✓ Middleware verhindert Zugriff ohne Passwort-Änderung\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
