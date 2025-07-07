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

echo "=== Test: Echte Filament User-Erstellung Problem ===\n\n";

try {
    // Lösche zuerst alle Test-User
    User::where('email', 'like', '%test-real%')->delete();
    
    echo "1. Teste das aktuelle Problem:\n";
    
    // Simuliere genau das, was passiert wenn Filament User::create() aufruft
    $formData = [
        'name' => 'Test User Real',
        'email' => 'test-real@example.com',
        'password' => 'TestPassword123',
        'temporary_password' => 'TestPassword123', // Das wird auch gehashed!
        'role' => 'user',
        'is_active' => true,
        'password_change_required' => true,
        'phone' => '123456789'
    ];
    
    echo "   Eingabe-Daten:\n";
    foreach ($formData as $key => $value) {
        echo "   - {$key}: {$value}\n";
    }
    
    // Das ist das Problem: User::create() hasht ALLE Felder mit "password" im Namen
    $user = User::create($formData);
    
    echo "\n2. User erstellt mit ID: {$user->id}\n";
    
    // Lade User neu aus Datenbank
    $savedUser = User::find($user->id);
    
    echo "\n3. Gespeicherte Werte in Datenbank:\n";
    echo "   - password: " . substr($savedUser->password, 0, 30) . "...\n";
    echo "   - temporary_password: " . substr($savedUser->temporary_password, 0, 30) . "...\n";
    
    // Prüfe ob temporary_password gehashed wurde
    if (strlen($savedUser->temporary_password) > 20 && str_starts_with($savedUser->temporary_password, '$2y$')) {
        echo "   ❌ PROBLEM BESTÄTIGT: temporary_password ist gehashed!\n";
        echo "   Länge: " . strlen($savedUser->temporary_password) . " Zeichen\n";
    } else {
        echo "   ✅ temporary_password ist im Klartext\n";
    }
    
    echo "\n4. Teste verschiedene Lösungsansätze:\n";
    
    // Ansatz 1: Direkte Zuweisung nach Erstellung
    echo "   Ansatz 1: Direkte Zuweisung nach create()\n";
    $savedUser->temporary_password = 'DirectAssignment123';
    $savedUser->save();
    
    $reloaded1 = User::find($user->id);
    echo "   - Nach direkter Zuweisung: {$reloaded1->temporary_password}\n";
    
    if ($reloaded1->temporary_password === 'DirectAssignment123') {
        echo "   ✅ Direkte Zuweisung funktioniert\n";
    } else {
        echo "   ❌ Direkte Zuweisung funktioniert nicht\n";
    }
    
    // Ansatz 2: Update mit updateQuietly (umgeht Mutators)
    echo "\n   Ansatz 2: updateQuietly (umgeht Events/Mutators)\n";
    $savedUser->updateQuietly(['temporary_password' => 'QuietUpdate456']);
    
    $reloaded2 = User::find($user->id);
    echo "   - Nach updateQuietly: {$reloaded2->temporary_password}\n";
    
    // Ansatz 3: Direkte DB-Abfrage
    echo "\n   Ansatz 3: Direkte DB-Abfrage\n";
    \DB::table('users')->where('id', $user->id)->update(['temporary_password' => 'DirectDB789']);
    
    $reloaded3 = User::find($user->id);
    echo "   - Nach direkter DB-Abfrage: {$reloaded3->temporary_password}\n";
    
    echo "\n5. Teste User::create() ohne temporary_password:\n";
    
    // Erstelle User ohne temporary_password im create()
    $formData2 = [
        'name' => 'Test User Real 2',
        'email' => 'test-real2@example.com',
        'password' => 'TestPassword456',
        'role' => 'user',
        'is_active' => true,
        'password_change_required' => true,
        'phone' => '123456789'
    ];
    
    $user2 = User::create($formData2);
    echo "   User 2 erstellt mit ID: {$user2->id}\n";
    
    // Setze temporary_password nach der Erstellung
    $user2->temporary_password = 'AfterCreate789';
    $user2->save();
    
    $savedUser2 = User::find($user2->id);
    echo "   - temporary_password nach Erstellung gesetzt: {$savedUser2->temporary_password}\n";
    
    if ($savedUser2->temporary_password === 'AfterCreate789') {
        echo "   ✅ Setzen nach create() funktioniert!\n";
    } else {
        echo "   ❌ Setzen nach create() funktioniert nicht\n";
    }
    
    // Cleanup
    echo "\n6. Cleanup...\n";
    $user->delete();
    $user2->delete();
    echo "   ✓ Test-User gelöscht\n";
    
    echo "\n=== FAZIT ===\n";
    echo "Das Problem liegt daran, dass Laravel automatisch ALLE Felder mit 'password'\n";
    echo "im Namen hasht, wenn sie in User::create() übergeben werden.\n";
    echo "Lösung: temporary_password NICHT in create() übergeben, sondern danach setzen.\n";
    
} catch (Exception $e) {
    echo "Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
