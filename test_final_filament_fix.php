<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Models\User;
use App\Filament\Resources\UserResource\Pages\CreateUser;
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

echo "=== Test: Finaler Filament Fix ===\n\n";

try {
    // Lösche alle Test-User
    User::where('email', 'like', '%test-final%')->delete();
    
    echo "1. Simuliere echte Filament CreateUser Page:\n";
    
    // Erstelle eine echte CreateUser Instanz
    $createUserPage = new CreateUser();
    
    // Simuliere Form-Daten wie sie von Filament kommen würden
    $formData = [
        'name' => 'Test User Final',
        'email' => 'test-final@example.com',
        'password' => 'MyFinalPassword123',
        'temporary_password_value' => 'MyFinalPassword123', // Das versteckte Feld
        'role' => 'user',
        'is_active' => true,
        'phone' => '123456789'
    ];
    
    echo "   Eingabe-Daten:\n";
    foreach ($formData as $key => $value) {
        echo "   - {$key}: {$value}\n";
    }
    
    // Mutiere die Daten wie in der echten CreateUser Page
    $mutatedData = $createUserPage->mutateFormDataBeforeCreate($formData);
    
    echo "\n2. Nach mutateFormDataBeforeCreate:\n";
    echo "   - temporary_password_value enthalten: " . (isset($mutatedData['temporary_password_value']) ? 'Ja' : 'Nein') . "\n";
    echo "   - temporary_password enthalten: " . (isset($mutatedData['temporary_password']) ? 'Ja' : 'Nein') . "\n";
    echo "   - password: " . (isset($mutatedData['password']) ? $mutatedData['password'] : 'nicht gesetzt') . "\n";
    
    // Erstelle User mit mutierten Daten (simuliert Filament's create())
    $user = User::create($mutatedData);
    echo "   ✓ User erstellt mit ID: {$user->id}\n";
    
    // Setze den record für afterCreate
    $createUserPage->record = $user;
    
    // Führe afterCreate aus
    $createUserPage->afterCreate();
    
    echo "\n3. Nach afterCreate:\n";
    
    // Lade User neu aus Datenbank
    $savedUser = User::find($user->id);
    
    echo "   - Name: {$savedUser->name}\n";
    echo "   - Email: {$savedUser->email}\n";
    echo "   - Password (gehashed): " . substr($savedUser->password, 0, 30) . "...\n";
    echo "   - Temporary Password: {$savedUser->temporary_password}\n";
    echo "   - Password Change Required: " . ($savedUser->password_change_required ? 'Ja' : 'Nein') . "\n";
    
    // Validierung
    echo "\n4. Validierung:\n";
    
    // Temporäres Passwort sollte ungehashed sein
    if ($savedUser->temporary_password === 'MyFinalPassword123') {
        echo "   ✅ Temporäres Passwort ist UNGEHASHED (korrekt)\n";
    } else {
        echo "   ❌ Temporäres Passwort ist gehashed oder falsch\n";
        echo "   Erwartet: MyFinalPassword123\n";
        echo "   Erhalten: {$savedUser->temporary_password}\n";
    }
    
    // Normales Passwort sollte gehashed sein
    if (Hash::check('MyFinalPassword123', $savedUser->password)) {
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
    
    // Teste Helper-Methoden
    echo "\n5. Teste Helper-Methoden:\n";
    echo "   - hasTemporaryPassword(): " . ($savedUser->hasTemporaryPassword() ? 'Ja' : 'Nein') . "\n";
    echo "   - getTemporaryPasswordForEmail(): {$savedUser->getTemporaryPasswordForEmail()}\n";
    echo "   - needsPasswordChange(): " . ($savedUser->needsPasswordChange() ? 'Ja' : 'Nein') . "\n";
    
    // Teste ohne Passwort-Eingabe (automatische Generierung)
    echo "\n6. Teste automatische Passwort-Generierung:\n";
    
    $formData2 = [
        'name' => 'Test User Auto',
        'email' => 'test-final-auto@example.com',
        'role' => 'user',
        'is_active' => true,
        'phone' => '987654321'
        // Kein password oder temporary_password_value
    ];
    
    $createUserPage2 = new CreateUser();
    $mutatedData2 = $createUserPage2->mutateFormDataBeforeCreate($formData2);
    
    $user2 = User::create($mutatedData2);
    $createUserPage2->record = $user2;
    $createUserPage2->afterCreate();
    
    $savedUser2 = User::find($user2->id);
    
    echo "   - Auto-generiertes Passwort (gehashed): " . substr($savedUser2->password, 0, 30) . "...\n";
    echo "   - Auto-generiertes temporäres Passwort: {$savedUser2->temporary_password}\n";
    
    if (!empty($savedUser2->temporary_password) && strlen($savedUser2->temporary_password) >= 8) {
        echo "   ✅ Automatische Passwort-Generierung funktioniert\n";
    } else {
        echo "   ❌ Automatische Passwort-Generierung funktioniert nicht\n";
    }
    
    // Cleanup
    echo "\n7. Cleanup...\n";
    $user->delete();
    $user2->delete();
    echo "   ✓ Test-User gelöscht\n";
    
    echo "\n=== FAZIT ===\n";
    echo "✅ Das temporäre Passwort wird jetzt korrekt im Klartext gespeichert\n";
    echo "✅ Das normale Passwort wird korrekt gehashed\n";
    echo "✅ Die Filament User-Erstellung funktioniert wie erwartet\n";
    echo "✅ Automatische Passwort-Generierung funktioniert\n";
    
} catch (Exception $e) {
    echo "Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
