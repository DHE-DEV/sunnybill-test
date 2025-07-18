<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';

// Vollständiges Laravel-Bootstrapping
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Token prüfen
$token = 'sb_JFMObIEN1Adf8UGBxMjW8SiPrsYnVAikZl4bX2sukTxc9HR5AJdP02R0FXFvw';

echo "Token Hash: " . hash('sha256', $token) . "\n";

try {
    $appToken = \App\Models\AppToken::findByToken($token);
    
    if ($appToken) {
        echo "✅ Token gefunden!\n";
        echo "Name: " . $appToken->name . "\n";
        echo "Status: " . $appToken->status_label . "\n";
        echo "Aktiv: " . ($appToken->is_active ? 'Ja' : 'Nein') . "\n";
        echo "Gültig bis: " . $appToken->expires_at . "\n";
        echo "Berechtigungen: " . implode(', ', $appToken->abilities) . "\n";
        echo "User: " . $appToken->user->name . "\n";
        echo "Letzter Zugriff: " . ($appToken->last_used_at ?? 'Nie') . "\n";
        echo "App-Typ: " . $appToken->app_type . "\n";
        
        // Validierung prüfen
        if ($appToken->isValid()) {
            echo "✅ Token ist gültig und einsatzbereit!\n";
        } else {
            echo "❌ Token ist ungültig!\n";
            echo "Gründe:\n";
            echo "- Aktiv: " . ($appToken->is_active ? 'Ja' : 'Nein') . "\n";
            echo "- Nicht abgelaufen: " . ($appToken->expires_at > now() ? 'Ja' : 'Nein') . "\n";
            echo "- User existiert: " . ($appToken->user ? 'Ja' : 'Nein') . "\n";
            echo "- User aktiv: " . ($appToken->user && $appToken->user->is_active ? 'Ja' : 'Nein') . "\n";
        }
    } else {
        echo "❌ Token nicht in der Datenbank gefunden!\n";
        echo "Mögliche Gründe:\n";
        echo "- Token wurde gelöscht\n";
        echo "- Token wurde nie erstellt\n";
        echo "- Token-Format ist falsch\n";
    }
} catch (Exception $e) {
    echo "❌ Fehler bei der Token-Überprüfung:\n";
    echo $e->getMessage() . "\n";
}
