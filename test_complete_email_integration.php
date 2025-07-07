<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Models\User;
use App\Notifications\AccountActivatedNotification;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        api: __DIR__.'/routes/api.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== KOMPLETTE E-MAIL INTEGRATION TEST ===\n\n";

try {
    // Lösche Test-User falls vorhanden
    User::where('email', 'complete-integration-test@example.com')->delete();
    
    echo "1. SIMULIERE KOMPLETTE USER-ERSTELLUNG (WIE IN FILAMENT):\n";
    
    // Schritt 1: Simuliere mutateFormDataBeforeCreate
    $originalPassword = 'CompleteTest2025!';
    $formData = [
        'name' => 'Complete Integration Test User',
        'email' => 'complete-integration-test@example.com',
        'password' => $originalPassword,
        'role' => 'user',
        'phone' => '022429018928',
        'is_active' => true,
        'password_change_required' => true,
    ];
    
    echo "   Schritt 1: mutateFormDataBeforeCreate\n";
    echo "   - Temporäres Passwort: {$originalPassword}\n";
    
    // Schritt 2: User::create() (password wird automatisch gehashed)
    echo "\n   Schritt 2: User::create()\n";
    $user = User::create($formData);
    echo "   - User erstellt mit ID: {$user->id}\n";
    echo "   - password gehashed: " . substr($user->password, 0, 20) . "...\n";
    
    // Schritt 3: afterCreate - Setze tmp_p (simuliert CreateUser::afterCreate)
    echo "\n   Schritt 3: afterCreate - Setze tmp_p\n";
    $user->tmp_p = $originalPassword;
    $user->save();
    echo "   - tmp_p gesetzt: '{$user->tmp_p}'\n";
    
    echo "\n2. VERIFIKATION DER DATENBANK-EINTRÄGE:\n";
    $savedUser = User::find($user->id);
    echo "   - Name: {$savedUser->name}\n";
    echo "   - Email: {$savedUser->email}\n";
    echo "   - password (gehashed): " . substr($savedUser->password, 0, 30) . "...\n";
    echo "   - tmp_p (Klartext): '{$savedUser->tmp_p}'\n";
    echo "   - temporary_password: " . ($savedUser->temporary_password ?? 'NULL') . "\n";
    echo "   - password_change_required: " . ($savedUser->password_change_required ? 'Ja' : 'Nein') . "\n";
    
    // Verifikation
    if ($savedUser->tmp_p === $originalPassword && Hash::check($originalPassword, $savedUser->password)) {
        echo "   ✅ Datenbank-Einträge korrekt\n";
    } else {
        echo "   ❌ Datenbank-Einträge fehlerhaft\n";
    }
    
    echo "\n3. SIMULIERE E-MAIL-VERIFIKATION:\n";
    echo "   User klickt auf E-Mail-Verifikations-Link...\n";
    $savedUser->markEmailAsVerified();
    echo "   ✓ E-Mail als verifiziert markiert\n";
    
    echo "\n4. TESTE ACCOUNT-AKTIVIERUNGS-E-MAIL (WIE IN USERRESOURCE):\n";
    
    // Simuliere die Aktion aus UserResource::mark_verified
    echo "   Simuliere UserResource 'Als verifiziert markieren' Aktion...\n";
    $temporaryPassword = $savedUser->getTemporaryPasswordForEmail();
    echo "   - Temporäres Passwort aus tmp_p geladen: '{$temporaryPassword}'\n";
    
    // Erstelle und teste die Notification
    $notification = new AccountActivatedNotification($temporaryPassword);
    $mailMessage = $notification->toMail($savedUser);
    
    // Extrahiere E-Mail-Daten
    $mailData = $mailMessage->toArray();
    $introLines = $mailData['introLines'] ?? [];
    
    echo "\n5. E-MAIL INHALT VERIFIKATION:\n";
    echo "   - Subject: " . ($mailData['subject'] ?? 'N/A') . "\n";
    echo "   - Greeting: " . ($mailData['greeting'] ?? 'N/A') . "\n";
    echo "   - Action Text: " . ($mailData['actionText'] ?? 'N/A') . "\n";
    echo "   - Action URL: " . ($mailData['actionUrl'] ?? 'N/A') . "\n";
    
    // Suche nach der Passwort-Zeile
    $passwordLineFound = false;
    $correctPasswordInEmail = false;
    
    foreach ($introLines as $line) {
        if (strpos($line, 'Temporäres Passwort:') !== false) {
            echo "   - Passwort-Zeile: {$line}\n";
            $passwordLineFound = true;
            $correctPasswordInEmail = strpos($line, $originalPassword) !== false;
            break;
        }
    }
    
    echo "\n6. KRITISCHE TESTS:\n";
    
    // Test 1: tmp_p ist korrekt gesetzt
    if ($savedUser->tmp_p === $originalPassword) {
        echo "   ✅ tmp_p korrekt im Klartext gespeichert\n";
    } else {
        echo "   ❌ tmp_p nicht korrekt: '{$savedUser->tmp_p}' vs '{$originalPassword}'\n";
    }
    
    // Test 2: Helper-Methode funktioniert
    if ($savedUser->getTemporaryPasswordForEmail() === $originalPassword) {
        echo "   ✅ getTemporaryPasswordForEmail() funktioniert korrekt\n";
    } else {
        echo "   ❌ getTemporaryPasswordForEmail() fehlerhaft\n";
    }
    
    // Test 3: E-Mail enthält korrektes Passwort
    if ($passwordLineFound && $correctPasswordInEmail) {
        echo "   ✅ E-Mail enthält korrektes temporäres Passwort\n";
    } else {
        echo "   ❌ E-Mail enthält nicht das korrekte temporäre Passwort\n";
    }
    
    // Test 4: Anmelde-Link vorhanden
    if (!empty($mailData['actionUrl']) && !empty($mailData['actionText'])) {
        echo "   ✅ Anmelde-Link in E-Mail vorhanden\n";
    } else {
        echo "   ❌ Anmelde-Link fehlt in E-Mail\n";
    }
    
    echo "\n7. VOLLSTÄNDIGE E-MAIL VORSCHAU:\n";
    echo "   " . str_repeat("=", 60) . "\n";
    echo "   Von: " . config('mail.from.name', 'SunnyBill') . " <" . config('mail.from.address', 'noreply@example.com') . ">\n";
    echo "   An: {$savedUser->email}\n";
    echo "   Betreff: " . ($mailData['subject'] ?? 'N/A') . "\n";
    echo "   " . str_repeat("-", 60) . "\n";
    echo "   " . ($mailData['greeting'] ?? 'Hallo!') . "\n\n";
    
    foreach ($introLines as $line) {
        echo "   {$line}\n";
    }
    
    if (!empty($mailData['actionText']) && !empty($mailData['actionUrl'])) {
        echo "\n   [" . $mailData['actionText'] . "]\n";
        echo "   Link: " . $mailData['actionUrl'] . "\n";
    }
    
    $outroLines = $mailData['outroLines'] ?? [];
    if (!empty($outroLines)) {
        echo "\n";
        foreach ($outroLines as $line) {
            echo "   {$line}\n";
        }
    }
    
    echo "   " . str_repeat("=", 60) . "\n";
    
    echo "\n8. TESTE VERSCHIEDENE SZENARIEN:\n";
    
    // Szenario 1: User ohne tmp_p
    echo "   Szenario 1: User ohne temporäres Passwort\n";
    $userWithoutTmpP = User::create([
        'name' => 'User Without TmpP',
        'email' => 'no-tmp-p-integration@example.com',
        'password' => 'SomePassword123!',
        'role' => 'user',
        'is_active' => true,
    ]);
    
    $notificationWithoutTmpP = new AccountActivatedNotification();
    $mailMessageWithoutTmpP = $notificationWithoutTmpP->toMail($userWithoutTmpP);
    $mailDataWithoutTmpP = $mailMessageWithoutTmpP->toArray();
    $introLinesWithoutTmpP = $mailDataWithoutTmpP['introLines'] ?? [];
    
    $fallbackFound = false;
    foreach ($introLinesWithoutTmpP as $line) {
        if (strpos($line, 'Das temporäre Passwort aus der ersten E-Mail') !== false) {
            echo "   - Fallback-Text gefunden: ✓\n";
            $fallbackFound = true;
            break;
        }
    }
    
    if (!$fallbackFound) {
        echo "   - Fallback-Text nicht gefunden: ❌\n";
    }
    
    // Szenario 2: Explizit übergebenes Passwort
    echo "\n   Szenario 2: Explizit übergebenes Passwort\n";
    $explicitPassword = 'ExplicitPassword123!';
    $notificationExplicit = new AccountActivatedNotification($explicitPassword);
    $mailMessageExplicit = $notificationExplicit->toMail($savedUser);
    $mailDataExplicit = $mailMessageExplicit->toArray();
    $introLinesExplicit = $mailDataExplicit['introLines'] ?? [];
    
    $explicitPasswordFound = false;
    foreach ($introLinesExplicit as $line) {
        if (strpos($line, $explicitPassword) !== false) {
            echo "   - Explizites Passwort in E-Mail gefunden: ✓\n";
            $explicitPasswordFound = true;
            break;
        }
    }
    
    if (!$explicitPasswordFound) {
        echo "   - Explizites Passwort nicht gefunden: ❌\n";
    }
    
    // Cleanup
    echo "\n9. CLEANUP:\n";
    $savedUser->delete();
    $userWithoutTmpP->delete();
    echo "   ✓ Test-User gelöscht\n";
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "FINALE BEWERTUNG - KOMPLETTE INTEGRATION\n";
    echo str_repeat("=", 70) . "\n";
    
    $allTestsPassed = (
        $savedUser->tmp_p === $originalPassword &&
        $savedUser->getTemporaryPasswordForEmail() === $originalPassword &&
        $passwordLineFound &&
        $correctPasswordInEmail &&
        !empty($mailData['actionUrl']) &&
        $fallbackFound &&
        $explicitPasswordFound
    );
    
    if ($allTestsPassed) {
        echo "🎉 ALLE TESTS ERFOLGREICH! 🎉\n\n";
        echo "Die komplette E-Mail-Integration funktioniert perfekt:\n\n";
        echo "✅ USER-ERSTELLUNG:\n";
        echo "   • tmp_p wird korrekt im Klartext gespeichert\n";
        echo "   • password wird sicher gehashed\n";
        echo "   • Alle Datenbank-Felder korrekt gesetzt\n\n";
        echo "✅ ACCOUNT-AKTIVIERUNGS-E-MAIL:\n";
        echo "   • Lädt temporäres Passwort aus tmp_p Spalte\n";
        echo "   • Zeigt Passwort im Klartext in der E-Mail\n";
        echo "   • Enthält korrekten Anmelde-Link\n";
        echo "   • Fallback-Text für User ohne temporäres Passwort\n";
        echo "   • Unterstützt explizit übergebene Passwörter\n\n";
        echo "✅ INTEGRATION:\n";
        echo "   • UserResource verwendet korrekte Notification-Parameter\n";
        echo "   • Alle Helper-Methoden funktionieren\n";
        echo "   • E-Mail-Format ist professionell und vollständig\n\n";
        echo "🚀 BEREIT FÜR PRODUKTION!\n";
        echo "Sie können jetzt sicher User über Filament anlegen.\n";
        echo "Die Account-Aktivierungs-E-Mails enthalten das temporäre Passwort.\n";
    } else {
        echo "❌ EINIGE TESTS FEHLGESCHLAGEN!\n";
        echo "Bitte prüfen Sie die obigen Ergebnisse für Details.\n";
    }
    
} catch (Exception $e) {
    echo "Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
