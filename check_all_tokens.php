<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';

// Vollständiges Laravel-Bootstrapping
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Alle App-Tokens in der Datenbank:\n";
echo "================================\n\n";

try {
    $tokens = \App\Models\AppToken::all();
    
    if ($tokens->count() > 0) {
        foreach ($tokens as $token) {
            echo "Token ID: " . $token->id . "\n";
            echo "Name: " . $token->name . "\n";
            echo "Hash (erste 20 Zeichen): " . substr($token->token, 0, 20) . "...\n";
            echo "Status: " . $token->status_label . "\n";
            echo "Aktiv: " . ($token->is_active ? 'Ja' : 'Nein') . "\n";
            echo "Gültig bis: " . $token->expires_at . "\n";
            echo "Berechtigungen: " . implode(', ', $token->abilities) . "\n";
            echo "User: " . $token->user->name . "\n";
            echo "App-Typ: " . $token->app_type . "\n";
            echo "Erstellt: " . $token->created_at . "\n";
            echo "Letzter Zugriff: " . ($token->last_used_at ?? 'Nie') . "\n";
            echo "Gültig: " . ($token->isValid() ? 'Ja' : 'Nein') . "\n";
            echo "---\n";
        }
    } else {
        echo "Keine App-Tokens in der Datenbank gefunden.\n";
    }

    echo "\nPrüfe spezifischen Token:\n";
    echo "========================\n";
    $searchToken = 'sb_JFMObIEN1Adf8UGBxMjW8SiPrsYnVAikZl4bX2sukTxc9HR5AJdP02R0FXFvw';
    $searchHash = hash('sha256', $searchToken);
    echo "Suchtoken: " . $searchToken . "\n";
    echo "Hash: " . $searchHash . "\n";
    
    $found = \App\Models\AppToken::where('token', $searchHash)->first();
    if ($found) {
        echo "✅ Token gefunden!\n";
    } else {
        echo "❌ Token nicht gefunden!\n";
    }

} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}
