<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use App\Models\AppToken;
use Illuminate\Support\Str;

// Laravel App laden
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/**
 * Script zum Erstellen eines neuen App-Tokens mit allen verfügbaren Berechtigungen
 * einschließlich der neuen Phone Numbers CRUD-Funktionen
 */

echo "=== VOLLZUGRIFF-TOKEN MIT PHONE NUMBERS CRUD ERSTELLEN ===\n\n";

try {
    // Ersten verfügbaren User finden oder erstellen
    $user = User::first();
    
    if (!$user) {
        echo "ℹ️  Kein User gefunden. Erstelle Test-User...\n";
        $user = User::create([
            'id' => Str::uuid(),
            'name' => 'API Test User',
            'email' => 'api-test@sunnybill.de',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        echo "✅ Test-User erstellt: {$user->name} ({$user->email})\n\n";
    }
    
    echo "👤 Verwende User: {$user->name} (ID: {$user->id})\n\n";
    
    // Alle verfügbaren Berechtigungen definieren
    $allAbilities = [
        // Aufgaben-Management
        'tasks:read',
        'tasks:create',
        'tasks:update',
        'tasks:delete',
        'tasks:status',
        'tasks:assign',
        'tasks:time',
        
        // Solaranlagen-Management
        'solar-plants:read',
        'solar-plants:create',
        'solar-plants:update',
        'solar-plants:delete',
        
        // Kunden-Management
        'customers:read',
        'customers:create',
        'customers:update',
        'customers:delete',
        'customers:status',
        
        // Lieferanten-Management
        'suppliers:read',
        'suppliers:create',
        'suppliers:update',
        'suppliers:delete',
        'suppliers:status',
        
        // Projekt-Management
        'projects:read',
        'projects:create',
        'projects:update',
        'projects:delete',
        'projects:status',
        
        // Meilenstein-Management
        'milestones:read',
        'milestones:create',
        'milestones:update',
        'milestones:delete',
        'milestones:status',
        
        // Termin-Management
        'appointments:read',
        'appointments:create',
        'appointments:update',
        'appointments:delete',
        'appointments:status',
        
        // Kosten-Management
        'costs:read',
        'costs:create',
        'costs:reports',
        
        // 📱 Telefonnummern-Management (NEU)
        'phone-numbers:read',
        'phone-numbers:create',
        'phone-numbers:update',
        'phone-numbers:delete',
    ];
    
    // Token-Name mit Zeitstempel
    $tokenName = 'Full Access Token mit Phone Numbers - ' . now()->format('Y-m-d H:i:s');
    
    // Neuen Token mit der richtigen Methode erstellen
    $plainTextToken = AppToken::generateToken();
    
    $token = AppToken::create([
        'id' => Str::uuid(),
        'user_id' => $user->id,
        'name' => $tokenName,
        'token' => hash('sha256', $plainTextToken),
        'abilities' => $allAbilities,
        'expires_at' => now()->addYear(), // Token läuft nach 1 Jahr ab
        'last_used_at' => null,
    ]);
    
    echo "🎯 VOLLZUGRIFF-TOKEN ERFOLGREICH ERSTELLT!\n\n";
    
    echo "=== TOKEN INFORMATIONEN ===\n";
    echo "Token ID: {$token->id}\n";
    echo "Token Name: {$token->name}\n";
    echo "User: {$user->name} ({$user->email})\n";
    echo "Erstellt am: {$token->created_at}\n";
    echo "Läuft ab am: {$token->expires_at}\n";
    echo "Anzahl Berechtigungen: " . count($allAbilities) . "\n\n";
    
    echo "🔑 PLAIN TEXT TOKEN (WICHTIG - NUR EINMAL SICHTBAR!):\n";
    echo "================================================================================\n";
    echo $plainTextToken . "\n";
    echo "================================================================================\n\n";
    
    echo "⚠️  WICHTIG: Speichere diesen Token sicher! Er wird nicht wieder angezeigt.\n\n";
    
    // Alle Berechtigungen nach Kategorie anzeigen
    echo "=== ALLE BERECHTIGUNGEN DIESES TOKENS ===\n\n";
    
    $categories = [
        'tasks' => '📋 Aufgaben-Management',
        'solar-plants' => '☀️ Solaranlagen-Management',
        'customers' => '👥 Kunden-Management',
        'suppliers' => '🏪 Lieferanten-Management', 
        'projects' => '📊 Projekt-Management',
        'milestones' => '🎯 Meilenstein-Management',
        'appointments' => '📅 Termin-Management',
        'costs' => '💰 Kosten-Management',
        'phone-numbers' => '📱 Telefonnummern-Management',
    ];
    
    foreach ($categories as $prefix => $categoryName) {
        $categoryAbilities = array_filter($allAbilities, function($ability) use ($prefix) {
            return strpos($ability, $prefix . ':') === 0;
        });
        
        if (!empty($categoryAbilities)) {
            echo "{$categoryName}:\n";
            foreach ($categoryAbilities as $ability) {
                $action = explode(':', $ability)[1];
                $actionName = match($action) {
                    'read' => 'Lesen/Anzeigen',
                    'create' => 'Erstellen',
                    'update' => 'Bearbeiten',
                    'delete' => 'Löschen',
                    'status' => 'Status ändern',
                    'assign' => 'Zuweisen',
                    'time' => 'Zeiten verwalten',
                    'reports' => 'Berichte generieren',
                    default => ucfirst($action)
                };
                
                echo "  ✓ {$ability} ({$actionName})\n";
            }
            echo "\n";
        }
    }
    
    // Beispiel API-Aufrufe
    echo "=== BEISPIEL API-AUFRUFE ===\n\n";
    
    echo "1. Alle Telefonnummern abrufen:\n";
    echo "curl -X GET 'http://localhost/api/app/phone-numbers' \\\n";
    echo "     -H 'Authorization: Bearer {$plainTextToken}' \\\n";
    echo "     -H 'Accept: application/json'\n\n";
    
    echo "2. Neue Telefonnummer erstellen:\n";
    echo "curl -X POST 'http://localhost/api/app/phone-numbers' \\\n";
    echo "     -H 'Authorization: Bearer {$plainTextToken}' \\\n";
    echo "     -H 'Accept: application/json' \\\n";
    echo "     -H 'Content-Type: application/json' \\\n";
    echo "     -d '{\n";
    echo "       \"phoneable_id\": \"{$user->id}\",\n";
    echo "       \"phoneable_type\": \"App\\\\Models\\\\User\",\n";
    echo "       \"phone_number\": \"+49 30 123456789\",\n";
    echo "       \"type\": \"business\",\n";
    echo "       \"label\": \"Büro Berlin\",\n";
    echo "       \"is_primary\": true\n";
    echo "     }'\n\n";
    
    echo "3. Alle Tasks abrufen:\n";
    echo "curl -X GET 'http://localhost/api/app/tasks' \\\n";
    echo "     -H 'Authorization: Bearer {$plainTextToken}' \\\n";
    echo "     -H 'Accept: application/json'\n\n";
    
    echo "4. Kunden abrufen:\n";
    echo "curl -X GET 'http://localhost/api/app/customers' \\\n";
    echo "     -H 'Authorization: Bearer {$plainTextToken}' \\\n";
    echo "     -H 'Accept: application/json'\n\n";
    
    // Test-Script Anpassung
    echo "=== TEST-SCRIPT ANPASSUNG ===\n\n";
    echo "Um das Test-Script zu verwenden, ändere in 'test_phone_numbers_api.php':\n";
    echo "\$token = 'your-test-token-here';\n";
    echo "zu:\n";
    echo "\$token = '{$plainTextToken}';\n\n";
    
    echo "Dann führe aus: php test_phone_numbers_api.php\n\n";
    
    // Token-Übersicht
    echo "=== ALLE TOKENS IN DER DATENBANK ===\n\n";
    $allTokens = AppToken::with('user')->get();
    
    foreach ($allTokens as $existingToken) {
        echo "Token: {$existingToken->name}\n";
        echo "User: " . ($existingToken->user->name ?? 'Unbekannt') . "\n";
        echo "Erstellt: {$existingToken->created_at}\n";
        echo "Läuft ab: " . ($existingToken->expires_at ? $existingToken->expires_at : 'Nie') . "\n";
        echo "Berechtigungen: " . count($existingToken->abilities ?? []) . "\n";
        
        // Prüfe Phone Numbers Berechtigungen
        $hasPhoneNumbers = !empty(array_filter($existingToken->abilities ?? [], function($ability) {
            return strpos($ability, 'phone-numbers:') === 0;
        }));
        
        echo "Phone Numbers: " . ($hasPhoneNumbers ? '✅ Ja' : '❌ Nein') . "\n";
        echo "\n";
    }
    
    echo "🚀 TOKEN BEREIT FÜR VOLLZUGRIFF AUF ALLE APIs!\n";
    echo "   - Alle CRUD-Operationen für Telefonnummern\n";
    echo "   - Vollzugriff auf alle anderen API-Bereiche\n";
    echo "   - 1 Jahr gültig\n";
    echo "   - Sofort einsatzbereit\n\n";
    
} catch (Exception $e) {
    echo "❌ Fehler beim Erstellen des Tokens:\n";
    echo "   {$e->getMessage()}\n";
    echo "   Datei: {$e->getFile()}:{$e->getLine()}\n";
    
    if ($e->getPrevious()) {
        echo "   Ursprünglicher Fehler: {$e->getPrevious()->getMessage()}\n";
    }
    
    exit(1);
}

echo "✅ VOLLZUGRIFF-TOKEN MIT PHONE NUMBERS CRUD ERFOLGREICH ERSTELLT!\n";
