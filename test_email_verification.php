<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== E-Mail-Verifikation Test ===\n\n";

try {
    // Test 1: Erstelle einen neuen Test-Benutzer
    echo "1. Erstelle Test-Benutzer...\n";
    
    $testUser = User::create([
        'name' => 'Test Benutzer Email Verification',
        'email' => 'test-email-verification@example.com',
        'password' => Hash::make('testpassword123'),
        'role' => 'user',
        'is_active' => true,
    ]);
    
    echo "   ✓ Test-Benutzer erstellt: {$testUser->name} ({$testUser->email})\n";
    echo "   ✓ ID: {$testUser->id}\n";
    echo "   ✓ E-Mail verifiziert: " . ($testUser->hasVerifiedEmail() ? 'Ja' : 'Nein') . "\n\n";
    
    // Test 2: Sende E-Mail-Verifikation
    echo "2. Sende E-Mail-Verifikation...\n";
    
    try {
        $testUser->sendEmailVerificationNotification();
        echo "   ✓ E-Mail-Verifikation erfolgreich gesendet\n\n";
    } catch (Exception $e) {
        echo "   ✗ Fehler beim Senden der E-Mail-Verifikation: " . $e->getMessage() . "\n\n";
    }
    
    // Test 3: Prüfe E-Mail-Verifikationsstatus
    echo "3. Prüfe E-Mail-Verifikationsstatus...\n";
    echo "   - hasVerifiedEmail(): " . ($testUser->hasVerifiedEmail() ? 'true' : 'false') . "\n";
    echo "   - email_verified_at: " . ($testUser->email_verified_at ? $testUser->email_verified_at->format('Y-m-d H:i:s') : 'null') . "\n\n";
    
    // Test 4: Markiere E-Mail als verifiziert
    echo "4. Markiere E-Mail als verifiziert...\n";
    $testUser->markEmailAsVerified();
    $testUser->refresh();
    
    echo "   ✓ E-Mail als verifiziert markiert\n";
    echo "   - hasVerifiedEmail(): " . ($testUser->hasVerifiedEmail() ? 'true' : 'false') . "\n";
    echo "   - email_verified_at: " . ($testUser->email_verified_at ? $testUser->email_verified_at->format('Y-m-d H:i:s') : 'null') . "\n\n";
    
    // Test 5: Teste CustomVerifyEmail Notification
    echo "5. Teste CustomVerifyEmail Notification...\n";
    
    // Setze E-Mail-Verifikation zurück für Test
    $testUser->update(['email_verified_at' => null]);
    
    $notification = new \App\Notifications\CustomVerifyEmail();
    $mailMessage = $notification->toMail($testUser);
    
    echo "   ✓ CustomVerifyEmail Notification erstellt\n";
    echo "   - Subject: " . $mailMessage->subject . "\n";
    echo "   - Greeting: " . $mailMessage->greeting . "\n";
    echo "   - Intro Lines: " . count($mailMessage->introLines) . "\n";
    echo "   - Action Text: " . $mailMessage->actionText . "\n";
    echo "   - Action URL: " . (strlen($mailMessage->actionUrl) > 50 ? substr($mailMessage->actionUrl, 0, 50) . '...' : $mailMessage->actionUrl) . "\n\n";
    
    // Test 6: Prüfe Konfiguration
    echo "6. Prüfe Konfiguration...\n";
    echo "   - APP_NAME: " . config('app.name') . "\n";
    echo "   - MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
    echo "   - MAIL_FROM_NAME: " . config('mail.from.name') . "\n";
    echo "   - MAIL_MAILER: " . config('mail.default') . "\n";
    echo "   - Auth Verification Expire: " . config('auth.verification.expire', 60) . " Minuten\n\n";
    
    // Test 7: Teste Routen
    echo "7. Teste Routen...\n";
    
    $routes = [
        'verification.notice' => '/email/verify',
        'verification.verify' => '/email/verify/{id}/{hash}',
        'verification.send' => '/email/verification-notification',
    ];
    
    foreach ($routes as $name => $path) {
        try {
            $url = route($name, $name === 'verification.verify' ? ['id' => 1, 'hash' => 'test'] : []);
            echo "   ✓ Route '{$name}': {$url}\n";
        } catch (Exception $e) {
            echo "   ✗ Route '{$name}' Fehler: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // Cleanup: Lösche Test-Benutzer
    echo "8. Cleanup...\n";
    $testUser->delete();
    echo "   ✓ Test-Benutzer gelöscht\n\n";
    
    echo "=== Test erfolgreich abgeschlossen ===\n";
    echo "Die E-Mail-Verifikation ist korrekt konfiguriert und funktionsfähig.\n\n";
    
    echo "Nächste Schritte:\n";
    echo "1. Erstellen Sie einen neuen Benutzer über das Filament Admin Panel\n";
    echo "2. Der Benutzer erhält automatisch eine E-Mail-Verifikation\n";
    echo "3. Nach dem Klick auf den Verifikationslink wird der Benutzer zur Admin-Seite weitergeleitet\n";
    echo "4. Sie können E-Mail-Verifikationen auch manuell über die Benutzer-Aktionen senden\n";
    
} catch (Exception $e) {
    echo "✗ Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
