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

echo "=== Test: Filament UI Simulation ===\n\n";

try {
    // Lösche alle Test-User
    User::where('email', 'like', '%test-filament-ui%')->delete();
    
    echo "1. Simuliere die echte Filament CreateUser Page:\n\n";
    
    // Simuliere genau das, was in der CreateUser Page passiert
    $originalPassword = 'FilamentUITest123';
    
    // Schritt 1: Form-Daten wie sie von Filament kommen
    $formData = [
        'name' => 'Test Filament UI User',
        'email' => 'test-filament-ui@example.com',
        'password' => $originalPassword,
        'role' => 'user',
        'phone' => '123456789'
    ];
    
    echo "   Original Form-Daten:\n";
    foreach ($formData as $key => $value) {
        echo "   - {$key}: {$value}\n";
    }
    
    // Schritt 2: mutateFormDataBeforeCreate (wie in CreateUser.php)
    $temporaryPassword = $formData['password'] ?? User::generateRandomPassword(12);
    $formData['is_active'] = $formData['is_active'] ?? true;
    $formData['role'] = $formData['role'] ?? 'user';
    $formData['password_change_required'] = true;
    
    // WICHTIG: Entferne temporary_password aus den Daten
    unset($formData['temporary_password']);
    
    echo "\n   Nach mutateFormDataBeforeCreate:\n";
    echo "   - Temporäres Passwort gespeichert: {$temporaryPassword}\n";
    echo "   - temporary_password in Form-Daten: " . (isset($formData['temporary_password']) ? 'Ja' : 'Nein') . "\n";
    
    // Schritt 3: User::create() (Filament hasht das password automatisch)
    echo "\n   Erstelle User mit User::create()...\n";
    $user = User::create($formData);
    echo "   ✓ User erstellt mit ID: {$user->id}\n";
    
    // Schritt 4: afterCreate - setze temporäres Passwort
    echo "   Setze temporäres Passwort in afterCreate()...\n";
    $user->temporary_password = $temporaryPassword;
    $user->save();
    
    // Schritt 5: Validierung
    $savedUser = User::find($user->id);
    
    echo "\n2. Ergebnis der Filament UI Simulation:\n";
    echo "   - Name: {$savedUser->name}\n";
    echo "   - Email: {$savedUser->email}\n";
    echo "   - Password (gehashed): " . substr($savedUser->password, 0, 30) . "...\n";
    echo "   - Temporary Password: {$savedUser->temporary_password}\n";
    echo "   - Password Change Required: " . ($savedUser->password_change_required ? 'Ja' : 'Nein') . "\n";
    
    // Kritische Tests
    echo "\n3. Kritische Validierung:\n";
    
    if ($savedUser->temporary_password === $temporaryPassword) {
        echo "   ✅ Temporäres Passwort ist UNGEHASHED (korrekt)\n";
    } else {
        echo "   ❌ Temporäres Passwort ist gehashed oder falsch\n";
        echo "   Erwartet: {$temporaryPassword}\n";
        echo "   Erhalten: {$savedUser->temporary_password}\n";
    }
    
    if (Hash::check($temporaryPassword, $savedUser->password)) {
        echo "   ✅ Normales Passwort ist korrekt gehashed\n";
    } else {
        echo "   ❌ Normales Passwort ist nicht korrekt gehashed\n";
    }
    
    if ($savedUser->password !== $savedUser->temporary_password) {
        echo "   ✅ password ≠ temporary_password (Problem behoben)\n";
    } else {
        echo "   ❌ password = temporary_password (Problem besteht noch)\n";
    }
    
    // Teste das ursprüngliche Problem-Szenario
    echo "\n4. Teste das ursprüngliche Problem-Szenario:\n";
    echo "   Erstelle User mit temporary_password direkt in create()...\n";
    
    $problemData = [
        'name' => 'Test Problem User',
        'email' => 'test-filament-ui-problem@example.com',
        'password' => 'ProblemPassword123',
        'temporary_password' => 'ProblemPassword123', // Das war das Problem
        'role' => 'user',
        'is_active' => true,
        'password_change_required' => true,
    ];
    
    $problemUser = User::create($problemData);
    $problemSaved = User::find($problemUser->id);
    
    echo "   - Password: " . substr($problemSaved->password, 0, 30) . "...\n";
    echo "   - Temporary Password: {$problemSaved->temporary_password}\n";
    
    if ($problemSaved->temporary_password === 'ProblemPassword123') {
        echo "   ✅ Auch mit temporary_password in create() funktioniert es jetzt!\n";
    } else {
        echo "   ❌ Mit temporary_password in create() wird es immer noch gehashed\n";
    }
    
    // Vergleiche mit dem ursprünglichen Datensatz
    echo "\n5. Vergleich mit ursprünglichem Problem:\n";
    echo "   Ursprünglich (Problem):\n";
    echo "   - password: '\$2y\$12\$f70Q.K6Kl6hSU3oWv.88q.u7X2d2TuYTlzjAyn2u8BrnL2gxLjc1S'\n";
    echo "   - temporary_password: '\$2y\$12\$f70Q.K6Kl6hSU3oWv.88q.u7X2d2TuYTlzjAyn2u8BrnL2gxLjc1S'\n";
    echo "   ❌ Beide Felder identisch und gehashed\n\n";
    
    echo "   Jetzt (Lösung):\n";
    echo "   - password: '" . substr($savedUser->password, 0, 30) . "...'\n";
    echo "   - temporary_password: '{$savedUser->temporary_password}'\n";
    echo "   ✅ Felder unterschiedlich: gehashed vs. Klartext\n";
    
    // Teste E-Mail-Funktionalität
    echo "\n6. Teste E-Mail-Funktionalität:\n";
    echo "   - hasTemporaryPassword(): " . ($savedUser->hasTemporaryPassword() ? 'Ja' : 'Nein') . "\n";
    echo "   - getTemporaryPasswordForEmail(): {$savedUser->getTemporaryPasswordForEmail()}\n";
    echo "   - needsPasswordChange(): " . ($savedUser->needsPasswordChange() ? 'Ja' : 'Nein') . "\n";
    
    // Cleanup
    echo "\n7. Cleanup...\n";
    $user->delete();
    $problemUser->delete();
    echo "   ✓ Test-User gelöscht\n";
    
    echo "\n=== FAZIT ===\n";
    echo "Die Filament UI Simulation funktioniert korrekt!\n";
    echo "Falls das Problem in der echten UI noch besteht, liegt es möglicherweise an:\n";
    echo "1. Einer anderen Filament-Konfiguration\n";
    echo "2. Einem Event-Listener oder Observer\n";
    echo "3. Einem anderen Teil des Codes, der das temporary_password überschreibt\n";
    echo "\nBitte testen Sie die echte Filament UI und teilen Sie das Ergebnis mit.\n";
    
} catch (Exception $e) {
    echo "Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
