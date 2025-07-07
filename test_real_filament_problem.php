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

echo "=== Test: Echtes Filament Problem Reproduktion ===\n\n";

try {
    // Lösche alle Test-User
    User::where('email', 'like', '%test-real-problem%')->delete();
    
    echo "1. Reproduziere das echte Problem:\n";
    echo "   Simuliere genau das, was Filament macht...\n\n";
    
    // Das ist genau das, was passiert, wenn Filament User::create() aufruft
    // mit temporary_password im Array
    $filamentData = [
        'name' => 'Daniel DH Henninger',
        'email' => 'test-real-problem@gmail.com',
        'password' => 'TestPassword123',
        'temporary_password' => 'TestPassword123', // Das wird auch gehashed!
        'role' => 'user',
        'is_active' => 1,
        'password_change_required' => 1,
        'phone' => '022429018928'
    ];
    
    echo "2. Daten die an User::create() übergeben werden:\n";
    foreach ($filamentData as $key => $value) {
        echo "   - {$key}: {$value}\n";
    }
    
    echo "\n3. Teste verschiedene Ansätze:\n\n";
    
    // Ansatz 1: Direkte Übergabe (das Problem)
    echo "   Ansatz 1: Direkte Übergabe an User::create() (Problem)\n";
    $user1 = User::create($filamentData);
    $saved1 = User::find($user1->id);
    
    echo "   - password: " . substr($saved1->password, 0, 30) . "...\n";
    echo "   - temporary_password: " . substr($saved1->temporary_password, 0, 30) . "...\n";
    
    if (strlen($saved1->temporary_password) > 20 && str_starts_with($saved1->temporary_password, '$2y$')) {
        echo "   ❌ PROBLEM: temporary_password ist gehashed!\n";
    } else {
        echo "   ✅ temporary_password ist im Klartext\n";
    }
    
    // Ansatz 2: Ohne temporary_password in create(), dann setzen
    echo "\n   Ansatz 2: Ohne temporary_password in create(), dann setzen\n";
    $data2 = $filamentData;
    $data2['email'] = 'test-real-problem-2@gmail.com'; // Andere E-Mail
    $tempPassword = $data2['temporary_password'];
    unset($data2['temporary_password']);
    
    $user2 = User::create($data2);
    $user2->temporary_password = $tempPassword;
    $user2->save();
    
    $saved2 = User::find($user2->id);
    echo "   - password: " . substr($saved2->password, 0, 30) . "...\n";
    echo "   - temporary_password: {$saved2->temporary_password}\n";
    
    if ($saved2->temporary_password === $tempPassword) {
        echo "   ✅ LÖSUNG: temporary_password ist im Klartext!\n";
    } else {
        echo "   ❌ temporary_password ist immer noch gehashed\n";
    }
    
    // Ansatz 3: Teste den Mutator direkt
    echo "\n   Ansatz 3: Teste Mutator direkt\n";
    $user3 = new User();
    $user3->name = 'Test Mutator';
    $user3->email = 'test-real-problem-mutator@gmail.com';
    $user3->password = Hash::make('TestPassword123');
    $user3->temporary_password = 'TestPassword123'; // Sollte durch Mutator ungehashed bleiben
    $user3->role = 'user';
    $user3->is_active = true;
    $user3->password_change_required = true;
    $user3->save();
    
    $saved3 = User::find($user3->id);
    echo "   - password: " . substr($saved3->password, 0, 30) . "...\n";
    echo "   - temporary_password: {$saved3->temporary_password}\n";
    
    if ($saved3->temporary_password === 'TestPassword123') {
        echo "   ✅ Mutator funktioniert korrekt!\n";
    } else {
        echo "   ❌ Mutator funktioniert nicht\n";
    }
    
    // Ansatz 4: Teste updateQuietly
    echo "\n   Ansatz 4: Teste updateQuietly (umgeht alle Events)\n";
    $user4 = User::create([
        'name' => 'Test Quiet',
        'email' => 'test-real-problem-quiet@gmail.com',
        'password' => Hash::make('TestPassword123'),
        'role' => 'user',
        'is_active' => true,
        'password_change_required' => true,
    ]);
    
    $user4->updateQuietly(['temporary_password' => 'TestPassword123']);
    
    $saved4 = User::find($user4->id);
    echo "   - password: " . substr($saved4->password, 0, 30) . "...\n";
    echo "   - temporary_password: {$saved4->temporary_password}\n";
    
    if ($saved4->temporary_password === 'TestPassword123') {
        echo "   ✅ updateQuietly funktioniert!\n";
    } else {
        echo "   ❌ updateQuietly funktioniert nicht\n";
    }
    
    echo "\n4. Analyse der Mutator-Aufrufe:\n";
    
    // Teste ob der Mutator überhaupt aufgerufen wird
    $testUser = new User();
    echo "   Teste setTemporaryPasswordAttribute direkt...\n";
    $testUser->setTemporaryPasswordAttribute('DirectTest123');
    echo "   - Direkte Zuweisung: " . ($testUser->attributes['temporary_password'] ?? 'NULL') . "\n";
    
    // Teste über Property-Zugriff
    $testUser2 = new User();
    echo "   Teste über Property-Zugriff...\n";
    $testUser2->temporary_password = 'PropertyTest456';
    echo "   - Property-Zugriff: " . ($testUser2->attributes['temporary_password'] ?? 'NULL') . "\n";
    
    echo "\n5. Prüfe Laravel-Version und Casting:\n";
    echo "   - Laravel Version: " . app()->version() . "\n";
    echo "   - User Casts: " . json_encode((new User())->getCasts()) . "\n";
    
    // Prüfe ob temporary_password in den Casts ist
    $casts = (new User())->getCasts();
    if (isset($casts['temporary_password'])) {
        echo "   ❌ PROBLEM GEFUNDEN: temporary_password ist in den Casts!\n";
        echo "   - Cast Type: {$casts['temporary_password']}\n";
    } else {
        echo "   ✅ temporary_password ist nicht in den Casts\n";
    }
    
    // Cleanup
    echo "\n6. Cleanup...\n";
    $user1->delete();
    $user2->delete();
    $user3->delete();
    $user4->delete();
    echo "   ✓ Test-User gelöscht\n";
    
    echo "\n=== DIAGNOSE ===\n";
    echo "Wenn Ansatz 2 funktioniert, aber Ansatz 1 nicht, dann liegt das Problem\n";
    echo "daran, dass Laravel beim User::create() alle Felder mit 'password' im Namen\n";
    echo "automatisch hasht, bevor die Mutators aufgerufen werden.\n";
    
} catch (Exception $e) {
    echo "Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
