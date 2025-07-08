<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\CompanySetting;
use App\Services\GmailService;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail Autorisierung reparieren ===\n\n";

try {
    // 1. Aktuelle Einstellungen prüfen
    echo "1. Aktuelle Gmail-Einstellungen:\n";
    $settings = CompanySetting::current();
    
    echo "   - Gmail aktiviert: " . ($settings->gmail_enabled ? 'Ja' : 'Nein') . "\n";
    echo "   - Client ID: " . ($settings->gmail_client_id ? 'Vorhanden' : 'Fehlt') . "\n";
    echo "   - Client Secret: " . ($settings->gmail_client_secret ? 'Vorhanden' : 'Fehlt') . "\n";
    echo "   - Access Token: " . ($settings->gmail_access_token ? 'Vorhanden' : 'FEHLT') . "\n";
    echo "   - Refresh Token: " . ($settings->gmail_refresh_token ? 'Vorhanden' : 'FEHLT') . "\n";
    echo "   - E-Mail-Adresse: " . ($settings->gmail_email_address ?: 'Nicht gesetzt') . "\n";
    echo "\n";
    
    if (!$settings->gmail_client_id || !$settings->gmail_client_secret) {
        echo "❌ Client ID oder Client Secret fehlen. Bitte zuerst in den Einstellungen konfigurieren.\n";
        exit(1);
    }
    
    if (!$settings->gmail_enabled) {
        echo "⚠️  Gmail ist deaktiviert. Aktiviere Gmail in den Einstellungen.\n";
        exit(1);
    }
    
    // 2. OAuth-URL generieren
    echo "2. OAuth-Autorisierungs-URL generieren:\n";
    
    $gmailService = new GmailService();
    $redirectUri = url('/gmail/oauth/callback');
    $authUrl = $gmailService->getAuthorizationUrl($redirectUri);
    
    if (!$authUrl) {
        echo "❌ Konnte OAuth-URL nicht generieren.\n";
        exit(1);
    }
    
    echo "   ✅ OAuth-URL generiert\n";
    echo "\n";
    
    // 3. Anweisungen anzeigen
    echo "3. NÄCHSTE SCHRITTE:\n";
    echo "   a) Öffne diese URL in deinem Browser:\n";
    echo "      " . $authUrl . "\n\n";
    echo "   b) Melde dich mit deinem Gmail-Konto an\n";
    echo "   c) Erlaube der Anwendung Zugriff auf Gmail\n";
    echo "   d) Du wirst zu einer Callback-URL weitergeleitet\n";
    echo "   e) Die Tokens werden automatisch gespeichert\n\n";
    
    // 4. Alternative: Direkte Token-Eingabe
    echo "4. ALTERNATIVE - Manuelle Token-Eingabe:\n";
    echo "   Falls der automatische Callback nicht funktioniert,\n";
    echo "   kannst du die Tokens auch manuell eingeben:\n\n";
    
    echo "   Führe nach der Autorisierung dieses Skript aus:\n";
    echo "   php check_gmail_authorization.php\n\n";
    
    // 5. Callback-URL prüfen
    echo "5. Callback-URL-Konfiguration:\n";
    $redirectUri = url('/gmail/oauth/callback');
    echo "   Konfigurierte Redirect URI: " . $redirectUri . "\n";
    echo "   Diese muss in der Google Cloud Console eingetragen sein!\n\n";
    
    // 6. Troubleshooting
    echo "6. TROUBLESHOOTING:\n";
    echo "   - Stelle sicher, dass die Redirect URI in Google Cloud Console stimmt\n";
    echo "   - Prüfe, dass Gmail API aktiviert ist\n";
    echo "   - Verwende den korrekten Google Account\n";
    echo "   - Bei Fehlern: Lösche alte Autorisierungen in Google Account\n\n";
    
    echo "=== Autorisierung starten ===\n";
    echo "Öffne die obige URL in deinem Browser!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
