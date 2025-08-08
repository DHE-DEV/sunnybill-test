<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use App\Models\AppToken;

// Laravel App laden
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // User mit ID 57 finden oder erstellen
    $user = User::find(57);
    
    if (!$user) {
        echo "User mit ID 57 nicht gefunden. Erstelle neuen User...\n";
        
        $user = User::create([
            'id' => 57,
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        
        echo "User erstellt: {$user->name} (ID: {$user->id})\n";
    } else {
        echo "User gefunden: {$user->name} (ID: {$user->id})\n";
    }

    // Neuen App Token erstellen mit vollen Berechtigungen
    $appToken = AppToken::create([
        'user_id' => $user->id,
        'name' => 'Vollzugriff Token für User 57',
        'token' => hash('sha256', $plainTextToken = \Illuminate\Support\Str::random(40)),
        'abilities' => ['*'], // Volle Berechtigungen für alles
        'expires_at' => now()->addYears(10), // Token läuft in 10 Jahren ab
        'last_used_at' => null,
    ]);

    echo "\n=== TOKEN ERFOLGREICH ERSTELLT ===\n";
    echo "Token ID: {$appToken->id}\n";
    echo "User ID: {$user->id}\n";
    echo "User Name: {$user->name}\n";
    echo "Token Name: {$appToken->name}\n";
    echo "Berechtigungen: " . json_encode($appToken->abilities) . "\n";
    echo "Plain Text Token: {$plainTextToken}\n";
    echo "\n=== WICHTIG ===\n";
    echo "Der Plain Text Token wird NUR EINMAL angezeigt!\n";
    echo "Speichere ihn sicher ab: {$plainTextToken}\n";
    echo "\nFür API-Requests verwende:\n";
    echo "Authorization: Bearer {$plainTextToken}\n";
    echo "=====================================\n";

    // Token in Datenbank verifizieren
    $tokenFromDb = AppToken::where('user_id', 57)->latest()->first();
    if ($tokenFromDb) {
        echo "\nToken erfolgreich in Datenbank gespeichert!\n";
        echo "Database Token Hash: " . substr($tokenFromDb->token, 0, 10) . "...\n";
        echo "Abilities: " . implode(', ', $tokenFromDb->abilities) . "\n";
    }

} catch (Exception $e) {
    echo "FEHLER beim Erstellen des Tokens:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}
