<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Models\AppToken;

echo "=== Vollständige Token mit customers:read Berechtigung ===\n\n";

$tokens = AppToken::where('is_active', true)
    ->where('expires_at', '>', now())
    ->whereJsonContains('abilities', 'customers:read')
    ->get();

foreach ($tokens as $token) {
    echo "Token Name: {$token->name}\n";
    echo "User: {$token->user->name}\n";
    echo "Full Token: {$token->token}\n";
    echo "Abilities: " . implode(', ', $token->abilities) . "\n";
    echo "Expires: {$token->expires_at}\n";
    echo str_repeat('-', 60) . "\n\n";
}

echo "✅ Insgesamt " . $tokens->count() . " gültige Tokens gefunden.\n";
