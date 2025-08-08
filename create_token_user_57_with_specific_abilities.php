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
    // User mit ID 57 finden
    $user = User::find(57);
    
    if (!$user) {
        echo "User mit ID 57 nicht gefunden!\n";
        exit(1);
    }

    echo "User gefunden: {$user->name} (ID: {$user->id})\n";

    // Alle verfügbaren Berechtigungen abrufen
    $allAbilities = array_keys(AppToken::getAvailableAbilities());
    
    echo "Verfügbare Berechtigungen:\n";
    foreach ($allAbilities as $ability) {
        echo "  - $ability\n";
    }

    // Neuen App Token mit ALLEN spezifischen Berechtigungen erstellen
    $plainTextToken = AppToken::generateToken();
    $appToken = AppToken::create([
        'user_id' => $user->id,
        'name' => 'Vollzugriff Token für User 57 (Spezifische Berechtigungen)',
        'token' => hash('sha256', $plainTextToken),
        'abilities' => $allAbilities, // Alle spezifischen Berechtigungen statt "*"
        'expires_at' => now()->addYears(10), // Token läuft in 10 Jahren ab
        'last_used_at' => null,
    ]);

    echo "\n=== TOKEN ERFOLGREICH ERSTELLT ===\n";
    echo "Token ID: {$appToken->id}\n";
    echo "User ID: {$user->id}\n";
    echo "User Name: {$user->name}\n";
    echo "Token Name: {$appToken->name}\n";
    echo "Anzahl Berechtigungen: " . count($appToken->abilities) . "\n";
    echo "Plain Text Token: {$plainTextToken}\n";
    echo "\n=== WICHTIG ===\n";
    echo "Der Plain Text Token wird NUR EINMAL angezeigt!\n";
    echo "Speichere ihn sicher ab: {$plainTextToken}\n";
    echo "\nFür API-Requests verwende:\n";
    echo "Authorization: Bearer {$plainTextToken}\n";
    echo "=====================================\n";

    // Teste explizit die tasks:read Berechtigung
    echo "\n=== BERECHTIGUNGSTEST ===\n";
    echo "Hat 'tasks:read' Berechtigung: " . ($appToken->hasAbility('tasks:read') ? 'JA' : 'NEIN') . "\n";
    echo "Hat 'tasks:create' Berechtigung: " . ($appToken->hasAbility('tasks:create') ? 'JA' : 'NEIN') . "\n";
    echo "Hat 'customers:read' Berechtigung: " . ($appToken->hasAbility('customers:read') ? 'JA' : 'NEIN') . "\n";

    // Token in Datenbank verifizieren
    $tokenFromDb = AppToken::where('user_id', 57)->latest()->first();
    if ($tokenFromDb) {
        echo "\nToken erfolgreich in Datenbank gespeichert!\n";
        echo "Database Token Hash: " . substr($tokenFromDb->token, 0, 10) . "...\n";
        echo "Abilities Count: " . count($tokenFromDb->abilities) . "\n";
        echo "Erste 5 Abilities: " . implode(', ', array_slice($tokenFromDb->abilities, 0, 5)) . "\n";
    }

} catch (Exception $e) {
    echo "FEHLER beim Erstellen des Tokens:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}
