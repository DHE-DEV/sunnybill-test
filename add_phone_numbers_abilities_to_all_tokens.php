<?php

require_once 'bootstrap/app.php';

use App\Models\AppToken;
use Illuminate\Support\Facades\DB;

/**
 * Script zum Hinzufügen der Phone Numbers Berechtigungen zu allen existierenden Tokens
 * 
 * Dieses Script fügt allen App-Tokens in der Datenbank die vollständigen 
 * phone-numbers Berechtigungen hinzu, damit sie die Telefonnummern-API verwenden können.
 */

echo "=== PHONE NUMBERS BERECHTIGUNGEN FÜR ALLE TOKENS HINZUFÜGEN ===\n\n";

// Neue Phone Numbers Berechtigungen
$phoneNumbersAbilities = [
    'phone-numbers:read',
    'phone-numbers:create', 
    'phone-numbers:update',
    'phone-numbers:delete',
];

try {
    // Alle App-Tokens abrufen
    $tokens = AppToken::all();
    
    if ($tokens->isEmpty()) {
        echo "ℹ️  Keine App-Tokens in der Datenbank gefunden.\n";
        echo "   Erstelle zuerst App-Tokens bevor du dieses Script ausführst.\n";
        exit(0);
    }
    
    echo "📱 {$tokens->count()} App-Tokens gefunden\n\n";
    
    $updatedTokens = 0;
    $alreadyUpdatedTokens = 0;
    
    foreach ($tokens as $token) {
        echo "Token: {$token->name} (ID: {$token->id})\n";
        echo "User: {$token->user->name ?? 'Unbekannt'}\n";
        
        // Aktuelle Berechtigungen abrufen
        $currentAbilities = $token->abilities ?? [];
        echo "Aktuelle Berechtigungen: " . count($currentAbilities) . "\n";
        
        // Prüfen, ob Phone Numbers Berechtigungen bereits vorhanden sind
        $hasPhoneNumbersAbilities = true;
        $missingAbilities = [];
        
        foreach ($phoneNumbersAbilities as $ability) {
            if (!in_array($ability, $currentAbilities)) {
                $hasPhoneNumbersAbilities = false;
                $missingAbilities[] = $ability;
            }
        }
        
        if ($hasPhoneNumbersAbilities) {
            echo "✅ Token hat bereits alle Phone Numbers Berechtigungen\n";
            $alreadyUpdatedTokens++;
        } else {
            echo "📝 Füge fehlende Berechtigungen hinzu: " . implode(', ', $missingAbilities) . "\n";
            
            // Neue Berechtigungen hinzufügen
            $newAbilities = array_unique(array_merge($currentAbilities, $phoneNumbersAbilities));
            
            // Token aktualisieren
            $token->update([
                'abilities' => $newAbilities
            ]);
            
            echo "✅ Token erfolgreich aktualisiert!\n";
            echo "   Neue Anzahl Berechtigungen: " . count($newAbilities) . "\n";
            echo "   Hinzugefügte Berechtigungen: " . implode(', ', $missingAbilities) . "\n";
            
            $updatedTokens++;
        }
        
        echo "\n";
    }
    
    // Zusammenfassung
    echo "=== ZUSAMMENFASSUNG ===\n";
    echo "Verarbeitete Tokens: {$tokens->count()}\n";
    echo "Aktualisierte Tokens: {$updatedTokens}\n";
    echo "Bereits aktuell: {$alreadyUpdatedTokens}\n\n";
    
    // Zeige alle Tokens mit ihren Phone Numbers Berechtigungen
    echo "=== AKTUELLE PHONE NUMBERS BERECHTIGUNGEN ALLER TOKENS ===\n\n";
    
    foreach ($tokens->fresh() as $token) {
        echo "Token: {$token->name}\n";
        echo "User: {$token->user->name ?? 'Unbekannt'}\n";
        
        $phoneAbilities = array_filter($token->abilities ?? [], function($ability) {
            return strpos($ability, 'phone-numbers:') === 0;
        });
        
        if (!empty($phoneAbilities)) {
            echo "Phone Numbers Berechtigungen:\n";
            foreach ($phoneAbilities as $ability) {
                echo "  ✓ {$ability}\n";
            }
        } else {
            echo "❌ Keine Phone Numbers Berechtigungen\n";
        }
        
        echo "\n";
    }
    
    echo "✅ Alle Tokens haben jetzt Vollzugriff auf die Phone Numbers API!\n\n";
    
    // Zusätzliche Informationen
    echo "=== VERWENDUNG ===\n";
    echo "Die Tokens können jetzt folgende Phone Numbers API-Endpunkte verwenden:\n";
    echo "- GET    /api/app/phone-numbers                           (phone-numbers:read)\n";
    echo "- POST   /api/app/phone-numbers                           (phone-numbers:create)\n";
    echo "- GET    /api/app/phone-numbers/{id}                      (phone-numbers:read)\n";
    echo "- PUT    /api/app/phone-numbers/{id}                      (phone-numbers:update)\n";
    echo "- DELETE /api/app/phone-numbers/{id}                      (phone-numbers:delete)\n";
    echo "- PATCH  /api/app/phone-numbers/{id}/make-primary         (phone-numbers:update)\n";
    echo "- GET    /api/app/owners/{type}/{id}/phone-numbers        (phone-numbers:read)\n\n";
    
    echo "Beispiel API-Aufruf:\n";
    echo "curl -X GET 'http://localhost/api/app/phone-numbers' \\\n";
    echo "     -H 'Authorization: Bearer TOKEN_HIER' \\\n";
    echo "     -H 'Accept: application/json'\n\n";
    
} catch (Exception $e) {
    echo "❌ Fehler beim Aktualisieren der Tokens:\n";
    echo "   {$e->getMessage()}\n";
    echo "   Datei: {$e->getFile()}:{$e->getLine()}\n";
    
    if ($e->getPrevious()) {
        echo "   Ursprünglicher Fehler: {$e->getPrevious()->getMessage()}\n";
    }
    
    exit(1);
}

// Zeige auch eine Übersicht aller verfügbaren Berechtigungen
echo "=== ALLE VERFÜGBAREN BERECHTIGUNGEN ===\n";
echo "Nach diesem Update haben alle Tokens Zugriff auf:\n\n";

$allAbilitiesExample = [
    'tasks:read', 'tasks:create', 'tasks:update', 'tasks:delete', 'tasks:status', 'tasks:assign', 'tasks:time',
    'solar-plants:read', 'solar-plants:create', 'solar-plants:update', 'solar-plants:delete',
    'customers:read', 'customers:create', 'customers:update', 'customers:delete', 'customers:status',
    'suppliers:read', 'suppliers:create', 'suppliers:update', 'suppliers:delete', 'suppliers:status',
    'projects:read', 'projects:create', 'projects:update', 'projects:delete', 'projects:status',
    'milestones:read', 'milestones:create', 'milestones:update', 'milestones:delete', 'milestones:status',
    'appointments:read', 'appointments:create', 'appointments:update', 'appointments:delete', 'appointments:status',
    'costs:read', 'costs:create', 'costs:reports',
    'phone-numbers:read', 'phone-numbers:create', 'phone-numbers:update', 'phone-numbers:delete',
];

$categories = [
    'tasks' => 'Aufgaben-Management',
    'solar-plants' => 'Solaranlagen-Management',
    'customers' => 'Kunden-Management',
    'suppliers' => 'Lieferanten-Management',
    'projects' => 'Projekt-Management',
    'milestones' => 'Meilenstein-Management',
    'appointments' => 'Termin-Management',
    'costs' => 'Kosten-Management',
    'phone-numbers' => '📱 Telefonnummern-Management (NEU)',
];

foreach ($categories as $prefix => $name) {
    echo "• {$name}:\n";
    $relevantAbilities = array_filter($allAbilitiesExample, function($ability) use ($prefix) {
        return strpos($ability, $prefix . ':') === 0;
    });
    
    foreach ($relevantAbilities as $ability) {
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

echo "🎯 Vollzugriff erfolgreich für alle Tokens konfiguriert!\n";
