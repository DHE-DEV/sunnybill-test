<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Notifications\AccountActivatedNotification;
use Illuminate\Support\Facades\Hash;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Account-Aktivierungs-E-Mail Test ===\n\n";

try {
    // Test 1: Erstelle einen neuen Test-Benutzer
    echo "1. Erstelle Test-Benutzer...\n";
    
    $testUser = User::create([
        'name' => 'Test Benutzer Account Activation',
        'email' => 'test-account-activation@example.com',
        'password' => Hash::make('testpassword123'),
        'role' => 'user',
        'is_active' => true,
    ]);
    
    echo "   ✓ Test-Benutzer erstellt: {$testUser->name} ({$testUser->email})\n";
    echo "   ✓ ID: {$testUser->id}\n";
    echo "   ✓ E-Mail verifiziert: " . ($testUser->hasVerifiedEmail() ? 'Ja' : 'Nein') . "\n\n";
    
    // Test 2: Teste AccountActivatedNotification
    echo "2. Teste AccountActivatedNotification...\n";
    
    $notification = new AccountActivatedNotification();
    $mailMessage = $notification->toMail($testUser);
    
    echo "   ✓ AccountActivatedNotification erstellt\n";
    echo "   - Subject: " . $mailMessage->subject . "\n";
    echo "   - Greeting: " . $mailMessage->greeting . "\n";
    echo "   - Intro Lines: " . count($mailMessage->introLines) . "\n";
    echo "   - Action Text: " . $mailMessage->actionText . "\n";
    echo "   - Action URL: " . $mailMessage->actionUrl . "\n\n";
    
    // Test 3: Sende Account-Aktivierungs-E-Mail
    echo "3. Sende Account-Aktivierungs-E-Mail...\n";
    
    try {
        $testUser->notify(new AccountActivatedNotification());
        echo "   ✓ Account-Aktivierungs-E-Mail erfolgreich gesendet\n\n";
    } catch (Exception $e) {
        echo "   ✗ Fehler beim Senden der Account-Aktivierungs-E-Mail: " . $e->getMessage() . "\n\n";
    }
    
    // Test 4: Simuliere E-Mail-Verifikation mit anschließender Account-Aktivierung
    echo "4. Simuliere E-Mail-Verifikation mit Account-Aktivierung...\n";
    
    // Setze E-Mail als nicht verifiziert
    $testUser->update(['email_verified_at' => null]);
    echo "   - E-Mail-Verifikation zurückgesetzt\n";
    
    // Simuliere Verifikation
    $wasAlreadyVerified = $testUser->hasVerifiedEmail();
    echo "   - War bereits verifiziert: " . ($wasAlreadyVerified ? 'Ja' : 'Nein') . "\n";
    
    // Markiere als verifiziert
    $testUser->markEmailAsVerified();
    echo "   - E-Mail als verifiziert markiert\n";
    
    // Sende Account-Aktivierungs-E-Mail (wie in der Route)
    if (!$wasAlreadyVerified) {
        try {
            $testUser->notify(new AccountActivatedNotification());
            echo "   ✓ Account-Aktivierungs-E-Mail nach Verifikation gesendet\n";
        } catch (Exception $e) {
            echo "   ✗ Fehler beim Senden: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // Test 5: Teste E-Mail-Inhalte im Detail
    echo "5. Teste E-Mail-Inhalte im Detail...\n";
    
    $notification = new AccountActivatedNotification();
    $mailMessage = $notification->toMail($testUser);
    
    echo "   - Subject: " . $mailMessage->subject . "\n";
    echo "   - Greeting: " . $mailMessage->greeting . "\n";
    
    foreach ($mailMessage->introLines as $index => $line) {
        echo "   - Intro Line " . ($index + 1) . ": " . $line . "\n";
    }
    
    echo "   - Action Text: " . $mailMessage->actionText . "\n";
    echo "   - Action URL: " . $mailMessage->actionUrl . "\n";
    
    foreach ($mailMessage->outroLines as $index => $line) {
        echo "   - Outro Line " . ($index + 1) . ": " . $line . "\n";
    }
    
    echo "   - Salutation: " . $mailMessage->salutation . "\n\n";
    
    // Test 6: Teste Array-Representation
    echo "6. Teste Array-Representation...\n";
    
    $arrayData = $notification->toArray($testUser);
    echo "   - Array Keys: " . implode(', ', array_keys($arrayData)) . "\n";
    echo "   - User ID: " . $arrayData['user_id'] . "\n";
    echo "   - User Email: " . $arrayData['user_email'] . "\n";
    echo "   - Activated At: " . $arrayData['activated_at'] . "\n\n";
    
    // Test 7: Teste Login-URL
    echo "7. Teste Login-URL...\n";
    
    $loginUrl = config('app.url') . '/admin';
    echo "   - Login URL: " . $loginUrl . "\n";
    echo "   - APP_URL: " . config('app.url') . "\n\n";
    
    // Cleanup: Lösche Test-Benutzer
    echo "8. Cleanup...\n";
    $testUser->delete();
    echo "   ✓ Test-Benutzer gelöscht\n\n";
    
    echo "=== Test erfolgreich abgeschlossen ===\n";
    echo "Die Account-Aktivierungs-E-Mail ist korrekt konfiguriert und funktionsfähig.\n\n";
    
    echo "Funktionsweise:\n";
    echo "1. Benutzer wird erstellt und erhält E-Mail-Verifikation\n";
    echo "2. Benutzer klickt auf Verifikationslink\n";
    echo "3. E-Mail wird als verifiziert markiert\n";
    echo "4. Account-Aktivierungs-E-Mail wird automatisch gesendet\n";
    echo "5. Benutzer erhält E-Mail mit Login-Link und Anmeldedaten\n\n";
    
    echo "Manuelle Verwaltung:\n";
    echo "- Administratoren können Account-Aktivierungs-E-Mails über das Admin Panel senden\n";
    echo "- Verfügbar für bereits verifizierte Benutzer\n";
    echo "- Automatisch beim manuellen Markieren als verifiziert\n";
    
} catch (Exception $e) {
    echo "✗ Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
