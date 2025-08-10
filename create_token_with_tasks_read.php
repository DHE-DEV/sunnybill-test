<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\AppToken;
use App\Models\User;

// Laravel Environment laden
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Administrator User finden (ID 1)
    $user = User::find(1);
    
    if (!$user) {
        echo "❌ Fehler: Benutzer mit ID 1 nicht gefunden\n";
        exit(1);
    }
    
    echo "📍 Benutzer gefunden: {$user->name} (ID: {$user->id})\n";
    echo "📧 Email: {$user->email}\n\n";
    
    // Neuen Token generieren
    $plainToken = 'sb_' . Str::random(64);
    $hashedToken = hash('sha256', $plainToken);
    
    // Berechtigung mit tasks:read und customers:read
    $abilities = [
        'customers:read',
        'customers:create', 
        'customers:update',
        'customers:delete',
        'tasks:read', // Diese Berechtigung wird benötigt
        'tasks:create',
        'tasks:update',
        'tasks:delete'
    ];
    
    // Token in Datenbank speichern
    $token = AppToken::create([
        'user_id' => $user->id,
        'name' => 'Customer API Test Token mit Tasks Read',
        'token' => $hashedToken,
        'abilities' => $abilities,
        'expires_at' => now()->addDays(30),
        'last_used_at' => null,
    ]);
    
    echo "✅ Token erfolgreich erstellt!\n\n";
    echo "🔐 Token Details:\n";
    echo "   ID: {$token->id}\n";
    echo "   Name: {$token->name}\n";
    echo "   Benutzer: {$user->name}\n";
    echo "   Gültig bis: {$token->expires_at}\n";
    echo "   Berechtigungen: " . implode(', ', $token->abilities) . "\n\n";
    
    echo "🎯 VERWENDBARER TOKEN:\n";
    echo "   {$plainToken}\n\n";
    
    echo "📋 Curl Beispiel:\n";
    echo "curl -H 'Authorization: Bearer {$plainToken}' \\\n";
    echo "     -H 'Content-Type: application/json' \\\n";
    echo "     https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/customers\n\n";
    
    echo "🎉 Token bereit für Customer API Tests!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
