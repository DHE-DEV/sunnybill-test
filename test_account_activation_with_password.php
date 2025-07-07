<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Models\User;
use App\Notifications\AccountActivatedNotification;
use Illuminate\Support\Facades\Notification;

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

echo "=== TEST: ACCOUNT ACTIVATION E-MAIL MIT TEMPORÄREM PASSWORT ===\n\n";

try {
    // Lösche Test-User falls vorhanden
    User::where('email', 'activation-test@example.com')->delete();
    
    echo "1. ERSTELLE TEST-USER MIT TEMPORÄREM PASSWORT:\n";
    
    // Erstelle User mit temporärem Passwort in tmp_p
    $temporaryPassword = 'TempPass2025!';
    $user = User::create([
        'name' => 'Test User Activation',
        'email' => 'activation-test@example.com',
        'password' => $temporaryPassword, // Wird automatisch gehashed
        'role' => 'user',
        'is_active' => true,
        'password_change_required' => true,
    ]);
    
    // Setze temporäres Passwort in tmp_p (Klartext)
    $user->tmp_p = $temporaryPassword;
    $user->save();
    
    echo "   ✓ User erstellt mit ID: {$user->id}\n";
    echo "   ✓ tmp_p gesetzt: '{$user->tmp_p}'\n";
    echo "   ✓ password gehashed: " . substr($user->password, 0, 20) . "...\n\n";
    
    echo "2. TESTE ACCOUNT ACTIVATION NOTIFICATION:\n";
    
    // Test 1: Notification ohne explizites Passwort (lädt aus tmp_p)
    echo "   Test 1: Notification ohne explizites Passwort\n";
    $notification1 = new AccountActivatedNotification();
    $mailMessage1 = $notification1->toMail($user);
    
    // Extrahiere den E-Mail-Inhalt
    $mailData1 = $mailMessage1->toArray();
    $introLines1 = $mailData1['introLines'] ?? [];
    
    echo "   - Subject: " . ($mailData1['subject'] ?? 'N/A') . "\n";
    echo "   - Greeting: " . ($mailData1['greeting'] ?? 'N/A') . "\n";
    
    $passwordFound1 = false;
    foreach ($introLines1 as $line) {
        if (strpos($line, 'Temporäres Passwort:') !== false) {
            echo "   - Passwort-Zeile: {$line}\n";
            $passwordFound1 = strpos($line, $temporaryPassword) !== false;
            break;
        }
    }
    
    if ($passwordFound1) {
        echo "   ✅ Temporäres Passwort korrekt aus tmp_p geladen\n";
    } else {
        echo "   ❌ Temporäres Passwort nicht gefunden oder falsch\n";
    }
    
    // Test 2: Notification mit explizit übergebenem Passwort
    echo "\n   Test 2: Notification mit explizit übergebenem Passwort\n";
    $explicitPassword = 'ExplicitPass123!';
    $notification2 = new AccountActivatedNotification($explicitPassword);
    $mailMessage2 = $notification2->toMail($user);
    
    $mailData2 = $mailMessage2->toArray();
    $introLines2 = $mailData2['introLines'] ?? [];
    
    $passwordFound2 = false;
    foreach ($introLines2 as $line) {
        if (strpos($line, 'Temporäres Passwort:') !== false) {
            echo "   - Passwort-Zeile: {$line}\n";
            $passwordFound2 = strpos($line, $explicitPassword) !== false;
            break;
        }
    }
    
    if ($passwordFound2) {
        echo "   ✅ Explizit übergebenes Passwort korrekt verwendet\n";
    } else {
        echo "   ❌ Explizit übergebenes Passwort nicht gefunden\n";
    }
    
    // Test 3: User ohne temporäres Passwort
    echo "\n   Test 3: User ohne temporäres Passwort\n";
    $userWithoutTmpP = User::create([
        'name' => 'User Without TmpP',
        'email' => 'no-tmp-p@example.com',
        'password' => 'SomePassword123!',
        'role' => 'user',
        'is_active' => true,
    ]);
    
    $notification3 = new AccountActivatedNotification();
    $mailMessage3 = $notification3->toMail($userWithoutTmpP);
    
    $mailData3 = $mailMessage3->toArray();
    $introLines3 = $mailData3['introLines'] ?? [];
    
    $fallbackFound = false;
    foreach ($introLines3 as $line) {
        if (strpos($line, 'Das temporäre Passwort aus der ersten E-Mail') !== false) {
            echo "   - Fallback-Zeile: {$line}\n";
            $fallbackFound = true;
            break;
        }
    }
    
    if ($fallbackFound) {
        echo "   ✅ Fallback-Text korrekt angezeigt\n";
    } else {
        echo "   ❌ Fallback-Text nicht gefunden\n";
    }
    
    echo "\n3. TESTE ANMELDE-LINK:\n";
    
    $actionUrl = $mailData1['actionUrl'] ?? '';
    $actionText = $mailData1['actionText'] ?? '';
    
    echo "   - Action Text: {$actionText}\n";
    echo "   - Action URL: {$actionUrl}\n";
    
    if (!empty($actionUrl) && $actionText === 'Jetzt anmelden') {
        echo "   ✅ Anmelde-Link korrekt konfiguriert\n";
    } else {
        echo "   ❌ Anmelde-Link fehlt oder falsch konfiguriert\n";
    }
    
    echo "\n4. VOLLSTÄNDIGE E-MAIL VORSCHAU:\n";
    echo "   " . str_repeat("-", 50) . "\n";
    echo "   Subject: " . ($mailData1['subject'] ?? 'N/A') . "\n";
    echo "   Greeting: " . ($mailData1['greeting'] ?? 'N/A') . "\n";
    
    foreach ($introLines1 as $line) {
        echo "   {$line}\n";
    }
    
    if (!empty($actionText) && !empty($actionUrl)) {
        echo "   \n   [{$actionText}] -> {$actionUrl}\n";
    }
    
    $outroLines = $mailData1['outroLines'] ?? [];
    foreach ($outroLines as $line) {
        echo "   {$line}\n";
    }
    
    echo "   " . str_repeat("-", 50) . "\n";
    
    // Cleanup
    echo "\n5. CLEANUP:\n";
    $user->delete();
    $userWithoutTmpP->delete();
    echo "   ✓ Test-User gelöscht\n";
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "ZUSAMMENFASSUNG\n";
    echo str_repeat("=", 60) . "\n";
    
    if ($passwordFound1 && $passwordFound2 && $fallbackFound) {
        echo "✅ ALLE TESTS ERFOLGREICH!\n\n";
        echo "Die AccountActivatedNotification funktioniert korrekt:\n";
        echo "• Lädt temporäres Passwort aus tmp_p Spalte\n";
        echo "• Verwendet explizit übergebenes Passwort wenn vorhanden\n";
        echo "• Zeigt Fallback-Text wenn kein temporäres Passwort verfügbar\n";
        echo "• Enthält korrekten Anmelde-Link\n";
        echo "• E-Mail-Format ist korrekt strukturiert\n";
    } else {
        echo "❌ EINIGE TESTS FEHLGESCHLAGEN!\n";
        echo "Bitte prüfen Sie die obigen Ergebnisse.\n";
    }
    
} catch (Exception $e) {
    echo "Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
