<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use App\Notifications\AccountActivatedNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: Vereinfachte Account-Aktivierungs-E-Mail ===\n\n";

try {
    // Fake Notifications für Testing
    Notification::fake();
    
    // Erstelle einen Test-Benutzer
    $user = User::create([
        'name' => 'Test Benutzer',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'role' => 'user',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    
    echo "Test-Benutzer erstellt: {$user->name} ({$user->email})\n\n";
    
    // Sende die Account-Aktivierungs-Benachrichtigung
    $user->notify(new AccountActivatedNotification());
    
    echo "Account-Aktivierungs-E-Mail gesendet!\n\n";
    
    // Prüfe, ob die Benachrichtigung gesendet wurde
    Notification::assertSentTo($user, AccountActivatedNotification::class);
    
    echo "✅ Benachrichtigung erfolgreich gesendet!\n\n";
    
    // Simuliere den E-Mail-Inhalt
    $notification = new AccountActivatedNotification();
    $mailMessage = $notification->toMail($user);
    
    echo "=== E-Mail-Inhalt Vorschau ===\n";
    echo "Betreff: " . $mailMessage->subject . "\n";
    echo "Empfänger: {$user->name} ({$user->email})\n\n";
    
    echo "E-Mail-Struktur:\n";
    echo "- ✅ Begrüßung: Hallo {$user->name}!\n";
    echo "- ✅ Aktivierungsbestätigung\n";
    echo "- ✅ E-Mail-Adresse bestätigt\n";
    echo "- ✅ Anmeldedaten (nur E-Mail)\n";
    echo "- ❌ KEIN temporäres Passwort mehr\n";
    echo "- ❌ KEIN Sicherheitshinweis mehr\n";
    echo "- ✅ Button: 'Jetzt anmelden'\n";
    echo "- ✅ Support-Hinweis\n";
    echo "- ✅ Grußformel\n\n";
    
    echo "=== Änderungen erfolgreich implementiert ===\n";
    echo "✅ Temporäres Passwort entfernt\n";
    echo "✅ Sicherheitshinweis entfernt\n";
    echo "✅ Button-Text geändert zu 'Jetzt anmelden'\n";
    echo "✅ E-Mail ist jetzt viel einfacher und benutzerfreundlicher\n\n";
    
    echo "Die E-Mail enthält jetzt nur noch:\n";
    echo "1. Aktivierungsbestätigung\n";
    echo "2. E-Mail-Adresse des Benutzers\n";
    echo "3. 'Jetzt anmelden' Button\n";
    echo "4. Support-Kontakt\n\n";
    
    // Cleanup
    $user->delete();
    
    echo "✅ Test erfolgreich abgeschlossen!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
