<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\AppToken;
use Illuminate\Support\Str;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Erstelle vollständige API-Tokens für Postman ===\n\n";

try {
    // Hole alle Benutzer
    $users = User::all();
    
    if ($users->isEmpty()) {
        echo "❌ Keine Benutzer gefunden!\n";
        exit;
    }
    
    // Alle verfügbaren Berechtigungen holen
    $allAbilities = array_keys(AppToken::getAvailableAbilities());
    
    echo "📋 Verfügbare Berechtigungen (" . count($allAbilities) . "):\n";
    foreach ($allAbilities as $ability) {
        echo "   - {$ability}\n";
    }
    echo "\n";
    
    $createdTokens = [];
    
    foreach ($users as $user) {
        echo "👤 Erstelle Token für: {$user->name} ({$user->email})\n";
        
        // Prüfe ob bereits ein Full-Access Token existiert
        $existingToken = AppToken::where('user_id', $user->id)
            ->where('name', 'Full Access - Postman')
            ->first();
            
        if ($existingToken) {
            echo "   ⚠️  Token existiert bereits - wird deaktiviert und neu erstellt\n";
            $existingToken->disable();
        }
        
        // Generiere plain text Token (BEVOR er gehasht wird)
        $plainToken = 'sb_' . Str::random(64);
        
        // Erstelle neuen Token (nur mit verfügbaren Feldern)
        $appToken = new AppToken([
            'user_id' => $user->id,
            'name' => 'Full Access - Postman',
            'token' => hash('sha256', $plainToken), // Speichere Hash in DB
            'abilities' => $allAbilities, // Alle Berechtigungen
            'expires_at' => now()->addYears(5), // 5 Jahre gültig
        ]);
        
        $appToken->save();
        
        $createdTokens[] = [
            'user' => $user,
            'token' => $plainToken, // Plain text für Postman
            'app_token' => $appToken
        ];
        
        echo "   ✅ Token erstellt (ID: {$appToken->id})\n";
    }
    
    // Übersicht der erstellten Tokens
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "🔑 API-TOKENS FÜR POSTMAN\n";
    echo str_repeat('=', 80) . "\n\n";
    
    foreach ($createdTokens as $tokenData) {
        $user = $tokenData['user'];
        $token = $tokenData['token'];
        $appToken = $tokenData['app_token'];
        
        echo "👤 BENUTZER: {$user->name}\n";
        echo "📧 Email: {$user->email}\n";
        echo "🆔 User ID: {$user->id}\n";
        echo "🔐 Token: {$token}\n";
        echo "📅 Gültig bis: {$appToken->expires_at->format('d.m.Y H:i:s')}\n";
        echo "🎯 Berechtigungen: " . count($appToken->abilities) . " (Vollzugriff)\n";
        echo "\n" . str_repeat('-', 80) . "\n\n";
    }
    
    // Postman Anleitung
    echo "📖 VERWENDUNG IN POSTMAN:\n";
    echo str_repeat('-', 40) . "\n";
    echo "1. Erstelle eine neue Collection oder Request\n";
    echo "2. Gehe zu Authorization Tab\n";
    echo "3. Wähle Type: Bearer Token\n";
    echo "4. Füge einen der obigen Tokens ein\n";
    echo "5. Oder verwende Header: Authorization: Bearer {TOKEN}\n\n";
    
    echo "🔗 BEISPIEL API ENDPOINTS:\n";
    echo str_repeat('-', 40) . "\n";
    echo "GET    /api/tasks              - Alle Aufgaben\n";
    echo "POST   /api/tasks              - Neue Aufgabe erstellen\n";
    echo "GET    /api/customers          - Alle Kunden\n";
    echo "GET    /api/solar-plants       - Alle Solaranlagen\n";
    echo "GET    /api/suppliers          - Alle Lieferanten\n";
    echo "GET    /api/projects           - Alle Projekte\n";
    echo "GET    /api/phone-numbers      - Alle Telefonnummern\n\n";
    
    echo "✅ Erfolgreich " . count($createdTokens) . " API-Tokens erstellt!\n";
    echo "💡 Die Tokens sind 5 Jahre gültig und haben Vollzugriff auf alle API-Funktionen.\n";

} catch (Exception $e) {
    echo "❌ Fehler beim Erstellen der API-Tokens: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
