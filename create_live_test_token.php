<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Models\AppToken;
use App\Models\User;

echo "=== Erstelle neuen Live Test Token ===\n\n";

// Hole Administrator User
$user = User::where('email', 'admin@sunnybill.test')->first();

if (!$user) {
    echo "❌ Administrator nicht gefunden!\n";
    exit;
}

// Generiere neuen Token mit sb_ Präfix
$plainToken = 'sb_' . \Illuminate\Support\Str::random(64);

// Erstelle Token mit vollen Customer-Rechten
$token = AppToken::create([
    'user_id' => $user->id,
    'name' => 'Live API Test Token',
    'token' => hash('sha256', $plainToken), // Token wird gehasht gespeichert
    'abilities' => [
        'customers:read',
        'customers:create', 
        'customers:update',
        'customers:delete',
        'phone-numbers:read',
        'phone-numbers:create',
        'phone-numbers:update',
        'phone-numbers:delete'
    ],
    'expires_at' => now()->addYears(1),
    'is_active' => true,
    'created_by_ip' => '127.0.0.1',
    'app_type' => 'web_app',
    'notes' => 'Test Token für Live API Customer Daten',
]);

echo "✅ Neuer Token erstellt:\n";
echo "Token ID: {$token->id}\n";
echo "Plain Token (für API): $plainToken\n";
echo "Hash (in DB): {$token->token}\n";
echo "User: {$user->name}\n";
echo "Abilities: " . implode(', ', $token->abilities) . "\n";
echo "Expires: {$token->expires_at}\n\n";

echo "🔑 Verwende diesen Token in der API:\n";
echo "Authorization: Bearer $plainToken\n";
