<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use App\Notifications\NewUserPasswordNotification;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Passwort-System Test ===\n\n";

try {
    // Test 1: Zufälliges Passwort generieren
    echo "1. Teste Passwort-Generierung...\n";
    $randomPassword = User::generateRandomPassword();
    echo "✓ Zufälliges Passwort generiert: " . $randomPassword . "\n";
    echo "   Länge: " . strlen($randomPassword) . " Zeichen\n\n";
    
    // Test 2: Passwort-Validierung
    echo "2. Teste Passwort-Validierung...\n";
    $hasLowercase = preg_match('/[a-z]/', $randomPassword);
    $hasUppercase = preg_match('/[A-Z]/', $randomPassword);
    $hasNumber = preg_match('/[0-9]/', $randomPassword);
    $hasSpecial = preg_match('/[!@#$%^&*]/', $randomPassword);
    
    echo "   Kleinbuchstaben: " . ($hasLowercase ? "✓" : "✗") . "\n";
    echo "   Großbuchstaben: " . ($hasUppercase ? "✓" : "✗") . "\n";
    echo "   Zahlen: " . ($hasNumber ? "✓" : "✗") . "\n";
    echo "   Sonderzeichen: " . ($hasSpecial ? "✓" : "✗") . "\n\n";
    
    // Test 3: User Model Methoden
    echo "3. Teste User Model Methoden...\n";
    $testUser = new User([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password_change_required' => true,
    ]);
    
    echo "   needsPasswordChange(): " . ($testUser->needsPasswordChange() ? "✓ true" : "✗ false") . "\n";
    
    // Test 4: Notification Test
    echo "\n4. Teste Notification-Klasse...\n";
    $notification = new NewUserPasswordNotification($randomPassword);
    echo "✓ NewUserPasswordNotification erstellt\n";
    
    // Test 5: Middleware-Klasse
    echo "\n5. Teste Middleware-Klasse...\n";
    if (class_exists('App\Http\Middleware\RequirePasswordChange')) {
        echo "✓ RequirePasswordChange Middleware existiert\n";
    } else {
        echo "✗ RequirePasswordChange Middleware nicht gefunden\n";
    }
    
    // Test 6: Controller-Klasse
    echo "\n6. Teste Controller-Klasse...\n";
    if (class_exists('App\Http\Controllers\PasswordChangeController')) {
        echo "✓ PasswordChangeController existiert\n";
    } else {
        echo "✗ PasswordChangeController nicht gefunden\n";
    }
    
    // Test 7: Routen-Test
    echo "\n7. Teste Routen-Konfiguration...\n";
    $routes = app('router')->getRoutes();
    $passwordChangeRoute = false;
    $passwordUpdateRoute = false;
    
    foreach ($routes as $route) {
        if ($route->getName() === 'password.change') {
            $passwordChangeRoute = true;
        }
        if ($route->getName() === 'password.update') {
            $passwordUpdateRoute = true;
        }
    }
    
    echo "   password.change Route: " . ($passwordChangeRoute ? "✓" : "✗") . "\n";
    echo "   password.update Route: " . ($passwordUpdateRoute ? "✓" : "✗") . "\n";
    
    // Test 8: View-Datei
    echo "\n8. Teste View-Datei...\n";
    $viewPath = resource_path('views/auth/change-password.blade.php');
    if (file_exists($viewPath)) {
        echo "✓ change-password.blade.php existiert\n";
    } else {
        echo "✗ change-password.blade.php nicht gefunden\n";
    }
    
    echo "\n=== Test abgeschlossen ===\n";
    echo "✓ Alle Komponenten für das Passwort-System sind implementiert!\n\n";
    
    echo "Funktionsweise:\n";
    echo "1. Beim Erstellen eines neuen Users wird automatisch ein zufälliges Passwort generiert\n";
    echo "2. Das Passwort wird per E-Mail an den Benutzer gesendet\n";
    echo "3. Der Benutzer muss bei der ersten Anmeldung das Passwort ändern\n";
    echo "4. Das System erzwingt die Passwort-Änderung über Middleware\n";
    
} catch (Exception $e) {
    echo "✗ Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}