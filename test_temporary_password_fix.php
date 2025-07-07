<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Models\User;
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

echo "=== Test: Temporäres Passwort Fix ===\n\n";

try {
    // Teste die User-Erstellung mit temporärem Passwort
    $testPassword = 'TestPassword123';
    
    echo "1. Erstelle Test-User mit temporärem Passwort...\n";
    
    $user = new User();
    $user->name = 'Test User Temp Password';
    $user->email = 'test-temp-password@example.com';
    $user->password = Hash::make($testPassword); // Gehashtes Passwort
    $user->temporary_password = $testPassword; // Sollte ungehashed bleiben
    $user->role = 'user';
    $user->is_active = true;
    $user->password_change_required = true;
    $user->save();
    
    echo "   ✓ User erstellt mit ID: {$user->id}\n";
    
    // Lade den User neu aus der Datenbank
    $savedUser = User::find($user->id);
    
    echo "\n2. Überprüfe gespeicherte Werte:\n";
    echo "   - Name: {$savedUser->name}\n";
    echo "   - Email: {$savedUser->email}\n";
    echo "   - Password (gehashed): " . substr($savedUser->password, 0, 20) . "...\n";
    echo "   - Temporary Password: {$savedUser->temporary_password}\n";
    echo "   - Password Change Required: " . ($savedUser->password_change_required ? 'Ja' : 'Nein') . "\n";
    
    // Teste ob das temporäre Passwort ungehashed ist
    echo "\n3. Teste temporäres Passwort:\n";
    if ($savedUser->temporary_password === $testPassword) {
        echo "   ✓ Temporäres Passwort ist UNGEHASHED gespeichert (korrekt)\n";
    } else {
        echo "   ✗ Temporäres Passwort ist gehashed oder verändert (Problem!)\n";
        echo "   Erwartet: {$testPassword}\n";
        echo "   Erhalten: {$savedUser->temporary_password}\n";
    }
    
    // Teste ob das normale Passwort gehashed ist
    echo "\n4. Teste normales Passwort:\n";
    if (Hash::check($testPassword, $savedUser->password)) {
        echo "   ✓ Normales Passwort ist korrekt gehashed\n";
    } else {
        echo "   ✗ Normales Passwort ist nicht korrekt gehashed\n";
    }
    
    // Teste die Mutator/Accessor Methoden
    echo "\n5. Teste Mutator/Accessor:\n";
    $savedUser->temporary_password = 'NewTempPassword456';
    $savedUser->save();
    
    $reloadedUser = User::find($user->id);
    if ($reloadedUser->temporary_password === 'NewTempPassword456') {
        echo "   ✓ Mutator/Accessor funktioniert korrekt\n";
    } else {
        echo "   ✗ Mutator/Accessor funktioniert nicht korrekt\n";
        echo "   Erwartet: NewTempPassword456\n";
        echo "   Erhalten: {$reloadedUser->temporary_password}\n";
    }
    
    // Teste die Helper-Methoden
    echo "\n6. Teste Helper-Methoden:\n";
    echo "   - hasTemporaryPassword(): " . ($reloadedUser->hasTemporaryPassword() ? 'Ja' : 'Nein') . "\n";
    echo "   - getTemporaryPasswordForEmail(): {$reloadedUser->getTemporaryPasswordForEmail()}\n";
    echo "   - needsPasswordChange(): " . ($reloadedUser->needsPasswordChange() ? 'Ja' : 'Nein') . "\n";
    
    // Cleanup
    echo "\n7. Cleanup...\n";
    $user->delete();
    echo "   ✓ Test-User gelöscht\n";
    
    echo "\n=== Test erfolgreich abgeschlossen ===\n";
    
} catch (Exception $e) {
    echo "Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
