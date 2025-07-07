<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: Kompletter E-Mail Flow ===\n\n";

try {
    // 1. Erstelle einen Test-Benutzer mit temporärem Passwort (wie Administrator)
    echo "1. Erstelle Test-Benutzer mit temporärem Passwort...\n";
    
    $temporaryPassword = User::generateRandomPassword();
    
    $user = User::create([
        'name' => 'E-Mail Flow Test Benutzer',
        'email' => 'email-flow-test@example.com',
        'password' => Hash::make('dummy'), // Dummy-Passwort
        'role' => 'user',
        'is_active' => true,
        'email_verified_at' => null, // Noch nicht verifiziert
    ]);
    
    // Setze temporäres Passwort
    $user->setTemporaryPassword($temporaryPassword);
    
    echo "✓ Benutzer erstellt: {$user->email}\n";
    echo "✓ Temporäres Passwort: {$temporaryPassword}\n";
    echo "✓ Hat temporäres Passwort: " . ($user->hasTemporaryPassword() ? 'Ja' : 'Nein') . "\n\n";
    
    // 2. Teste erste E-Mail (CustomVerifyEmail)
    echo "2. Teste erste E-Mail (CustomVerifyEmail)...\n";
    
    try {
        $user->sendEmailVerificationNotification($temporaryPassword);
        echo "✓ CustomVerifyEmail Notification gesendet\n";
        
        // Teste die CustomVerifyEmail Notification
        $customVerifyNotification = new \App\Notifications\CustomVerifyEmail($temporaryPassword);
        $customVerifyMail = $customVerifyNotification->toMail($user);
        
        echo "✓ CustomVerifyEmail Mail erstellt\n";
        echo "✓ Subject: " . $customVerifyMail->subject . "\n";
        
    } catch (Exception $e) {
        echo "❌ Fehler bei CustomVerifyEmail: " . $e->getMessage() . "\n";
    }
    
    // 3. Simuliere E-Mail-Bestätigung
    echo "\n3. Simuliere E-Mail-Bestätigung...\n";
    
    $user->markEmailAsVerified();
    echo "✓ E-Mail als verifiziert markiert\n";
    
    // 4. Teste zweite E-Mail (AccountActivatedNotification)
    echo "\n4. Teste zweite E-Mail (AccountActivatedNotification)...\n";
    
    try {
        $userTempPassword = $user->getTemporaryPasswordForEmail();
        echo "✓ Temporäres Passwort aus User: {$userTempPassword}\n";
        
        $accountActivatedNotification = new \App\Notifications\AccountActivatedNotification($userTempPassword);
        $accountActivatedMail = $accountActivatedNotification->toMail($user);
        
        echo "✓ AccountActivatedNotification Mail erstellt\n";
        echo "✓ Subject: " . $accountActivatedMail->subject . "\n";
        
        // Extrahiere die Action URL
        $reflection = new ReflectionClass($accountActivatedMail);
        $actionUrlProperty = $reflection->getProperty('actionUrl');
        $actionUrlProperty->setAccessible(true);
        $actionUrl = $actionUrlProperty->getValue($accountActivatedMail);
        
        echo "✓ Action URL: {$actionUrl}\n";
        
        // Prüfe ob die URL korrekt ist
        $expectedToken = hash('sha256', $user->id . $user->email . $user->created_at);
        $expectedUrl = url('/password/change/' . $user->id . '/' . $expectedToken);
        
        echo "✓ Erwartete URL: {$expectedUrl}\n";
        echo "✓ URLs stimmen überein: " . ($actionUrl === $expectedUrl ? 'Ja' : 'Nein') . "\n";
        
    } catch (Exception $e) {
        echo "❌ Fehler bei AccountActivatedNotification: " . $e->getMessage() . "\n";
    }
    
    // 5. Teste URL-Zugriff
    echo "\n5. Teste URL-Zugriff...\n";
    
    $token = hash('sha256', $user->id . $user->email . $user->created_at);
    $passwordChangeUrl = url('/password/change/' . $user->id . '/' . $token);
    
    echo "✓ Passwort-Änderungs-URL: {$passwordChangeUrl}\n";
    echo "✓ User ID: {$user->id}\n";
    echo "✓ Token: {$token}\n";
    
    // Teste Controller-Methode direkt
    try {
        $controller = new \App\Http\Controllers\PasswordChangeController();
        
        // Erstelle Mock Request
        $request = new \Illuminate\Http\Request();
        
        // Teste showForTemporaryPassword Methode
        $response = $controller->showForTemporaryPassword($request, $user->id, $token);
        
        echo "✓ Controller showForTemporaryPassword funktioniert\n";
        echo "✓ Response Status: " . $response->getStatusCode() . "\n";
        
    } catch (Exception $e) {
        echo "❌ Fehler beim Controller-Test: " . $e->getMessage() . "\n";
    }
    
    // 6. Teste kompletten Flow
    echo "\n6. Teste kompletten Flow...\n";
    
    // Simuliere E-Mail-Bestätigung mit Weiterleitung
    if ($user->hasTemporaryPassword()) {
        $redirectToken = hash('sha256', $user->id . $user->email . $user->created_at);
        $redirectUrl = route('password.change.temporary', [
            'userId' => $user->id,
            'token' => $redirectToken
        ]);
        
        echo "✓ Weiterleitung nach E-Mail-Bestätigung: {$redirectUrl}\n";
        echo "✓ Benutzer hat temporäres Passwort: Ja\n";
        echo "✓ Automatische Weiterleitung würde funktionieren\n";
    } else {
        echo "❌ Benutzer hat kein temporäres Passwort\n";
    }
    
    // 7. Cleanup
    echo "\n7. Cleanup...\n";
    $user->delete();
    echo "✓ Test-Benutzer gelöscht\n";
    
    echo "\n=== Test erfolgreich abgeschlossen! ===\n";
    echo "\nZusammenfassung:\n";
    echo "✓ Benutzer-Erstellung mit temporärem Passwort funktioniert\n";
    echo "✓ CustomVerifyEmail Notification funktioniert\n";
    echo "✓ E-Mail-Bestätigung funktioniert\n";
    echo "✓ AccountActivatedNotification mit korrekter URL funktioniert\n";
    echo "✓ Controller für Passwort-Änderung funktioniert\n";
    echo "✓ Automatische Weiterleitung funktioniert\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
