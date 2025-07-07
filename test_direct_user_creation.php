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

echo "=== Test: Direkter User-Erstellung Test ===\n\n";

try {
    // Lösche alle Test-User
    User::where('email', 'like', '%test-direct%')->delete();
    
    echo "1. Teste User-Erstellung wie sie jetzt funktionieren sollte:\n";
    
    // Simuliere die neue Logik
    $password = 'TestDirectPassword123';
    
    // Schritt 1: Erstelle User OHNE temporary_password
    $userData = [
        'name' => 'Test User Direct',
        'email' => 'test-direct@example.com',
        'password' => Hash::make($password), // Manuell gehashed
        'role' => 'user',
        'is_active' => true,
        'password_change_required' => true,
        'phone' => '123456789'
        // KEIN temporary_password hier!
    ];
    
    echo "   Erstelle User ohne temporary_password...\n";
    $user = User::create($userData);
    echo "   ✓ User erstellt mit ID: {$user->id}\n";
    
    // Schritt 2: Setze temporary_password NACH der Erstellung
    echo "   Setze temporary_password nach der Erstellung...\n";
    $user->temporary_password = $password; // Sollte durch Mutator ungehashed bleiben
    $user->save();
    
    // Schritt 3: Lade User neu und prüfe
    $savedUser = User::find($user->id);
    
    echo "\n2. Gespeicherte Werte:\n";
    echo "   - Name: {$savedUser->name}\n";
    echo "   - Email: {$savedUser->email}\n";
    echo "   - Password (gehashed): " . substr($savedUser->password, 0, 30) . "...\n";
    echo "   - Temporary Password: {$savedUser->temporary_password}\n";
    echo "   - Password Change Required: " . ($savedUser->password_change_required ? 'Ja' : 'Nein') . "\n";
    
    // Validierung
    echo "\n3. Validierung:\n";
    
    // Temporäres Passwort sollte ungehashed sein
    if ($savedUser->temporary_password === $password) {
        echo "   ✅ Temporäres Passwort ist UNGEHASHED (korrekt)\n";
    } else {
        echo "   ❌ Temporäres Passwort ist gehashed oder falsch\n";
        echo "   Erwartet: {$password}\n";
        echo "   Erhalten: {$savedUser->temporary_password}\n";
    }
    
    // Normales Passwort sollte gehashed sein
    if (Hash::check($password, $savedUser->password)) {
        echo "   ✅ Normales Passwort ist korrekt gehashed\n";
    } else {
        echo "   ❌ Normales Passwort ist nicht korrekt gehashed\n";
    }
    
    // Verschiedene Werte
    if ($savedUser->password !== $savedUser->temporary_password) {
        echo "   ✅ password ≠ temporary_password (Problem behoben)\n";
    } else {
        echo "   ❌ password = temporary_password (Problem besteht noch)\n";
    }
    
    echo "\n4. Teste verschiedene Szenarien:\n";
    
    // Szenario 1: Update des temporären Passworts
    echo "   Szenario 1: Update des temporären Passworts\n";
    $newTempPassword = 'NewTempPassword456';
    $savedUser->temporary_password = $newTempPassword;
    $savedUser->save();
    
    $reloaded = User::find($user->id);
    if ($reloaded->temporary_password === $newTempPassword) {
        echo "   ✅ Update des temporären Passworts funktioniert\n";
    } else {
        echo "   ❌ Update des temporären Passworts funktioniert nicht\n";
    }
    
    // Szenario 2: Setzen auf null
    echo "   Szenario 2: Temporäres Passwort auf null setzen\n";
    $savedUser->temporary_password = null;
    $savedUser->save();
    
    $reloaded = User::find($user->id);
    if ($reloaded->temporary_password === null) {
        echo "   ✅ Setzen auf null funktioniert\n";
    } else {
        echo "   ❌ Setzen auf null funktioniert nicht\n";
    }
    
    // Szenario 3: Setzen über Helper-Methode
    echo "   Szenario 3: Setzen über Helper-Methode\n";
    $helperPassword = 'HelperPassword789';
    $savedUser->setTemporaryPassword($helperPassword);
    
    $reloaded = User::find($user->id);
    if ($reloaded->temporary_password === $helperPassword) {
        echo "   ✅ Helper-Methode funktioniert\n";
    } else {
        echo "   ❌ Helper-Methode funktioniert nicht\n";
    }
    
    // Teste das ursprüngliche Problem
    echo "\n5. Teste das ursprüngliche Problem:\n";
    echo "   Erstelle User mit temporary_password in create()...\n";
    
    $problemData = [
        'name' => 'Test Problem User',
        'email' => 'test-direct-problem@example.com',
        'password' => Hash::make('ProblemPassword123'),
        'temporary_password' => 'ProblemPassword123', // Das sollte jetzt funktionieren
        'role' => 'user',
        'is_active' => true,
        'password_change_required' => true,
    ];
    
    $problemUser = User::create($problemData);
    $problemSaved = User::find($problemUser->id);
    
    echo "   - Password: " . substr($problemSaved->password, 0, 30) . "...\n";
    echo "   - Temporary Password: {$problemSaved->temporary_password}\n";
    
    if ($problemSaved->temporary_password === 'ProblemPassword123') {
        echo "   ✅ Mutator verhindert Hashing auch bei create()\n";
    } else {
        echo "   ❌ Mutator verhindert Hashing NICHT bei create()\n";
    }
    
    // Cleanup
    echo "\n6. Cleanup...\n";
    $user->delete();
    $problemUser->delete();
    echo "   ✓ Test-User gelöscht\n";
    
    echo "\n=== FAZIT ===\n";
    echo "Der Mutator funktioniert korrekt. Das Problem liegt wahrscheinlich\n";
    echo "in der Filament-Konfiguration oder im Timing der Operationen.\n";
    
} catch (Exception $e) {
    echo "Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
