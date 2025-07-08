<?php

require_once 'vendor/autoload.php';

use App\Models\CompanySetting;
use App\Services\GmailService;

echo "=== Gmail OAuth2 Debug Test ===\n\n";

try {
    // 1. Firmeneinstellungen prüfen
    echo "1. Firmeneinstellungen prüfen...\n";
    $settings = CompanySetting::current();
    
    echo "   Gmail aktiviert: " . ($settings->isGmailEnabled() ? "✅ Ja" : "❌ Nein") . "\n";
    echo "   Client ID vorhanden: " . ($settings->getGmailClientId() ? "✅ Ja" : "❌ Nein") . "\n";
    echo "   Client Secret vorhanden: " . ($settings->getGmailClientSecret() ? "✅ Ja" : "❌ Nein") . "\n";
    
    if ($settings->getGmailClientId()) {
        $clientId = $settings->getGmailClientId();
        echo "   Client ID: " . substr($clientId, 0, 20) . "..." . substr($clientId, -10) . "\n";
    }
    
    echo "\n";
    
    // 2. GmailService-Konfiguration prüfen
    echo "2. GmailService-Konfiguration prüfen...\n";
    $gmailService = new GmailService();
    $isConfigured = $gmailService->isConfigured();
    echo "   Service konfiguriert: " . ($isConfigured ? "✅ Ja" : "❌ Nein") . "\n";
    
    if (!$isConfigured) {
        echo "   ❌ Gmail-Service ist nicht vollständig konfiguriert!\n";
        echo "   Bitte prüfen Sie Client ID und Client Secret in den Firmeneinstellungen.\n\n";
        exit;
    }
    
    echo "\n";
    
    // 3. OAuth2-URL generieren
    echo "3. OAuth2-Autorisierungs-URL generieren...\n";
    $redirectUri = url('/admin/gmail/oauth/callback');
    echo "   Redirect URI: $redirectUri\n";
    
    try {
        $authUrl = $gmailService->getAuthorizationUrl($redirectUri);
        echo "   ✅ OAuth2-URL erfolgreich generiert\n";
        echo "   URL-Länge: " . strlen($authUrl) . " Zeichen\n";
        
        // URL-Parameter analysieren
        $parsedUrl = parse_url($authUrl);
        parse_str($parsedUrl['query'], $params);
        
        echo "   OAuth2-Parameter:\n";
        echo "     - client_id: " . (isset($params['client_id']) ? "✅ Vorhanden" : "❌ Fehlt") . "\n";
        echo "     - redirect_uri: " . (isset($params['redirect_uri']) ? "✅ Vorhanden" : "❌ Fehlt") . "\n";
        echo "     - scope: " . (isset($params['scope']) ? "✅ Vorhanden" : "❌ Fehlt") . "\n";
        echo "     - response_type: " . (isset($params['response_type']) ? "✅ Vorhanden" : "❌ Fehlt") . "\n";
        echo "     - access_type: " . (isset($params['access_type']) ? "✅ Vorhanden" : "❌ Fehlt") . "\n";
        echo "     - state: " . (isset($params['state']) ? "✅ Vorhanden" : "❌ Fehlt") . "\n";
        
        if (isset($params['redirect_uri'])) {
            echo "   Redirect URI in URL: " . $params['redirect_uri'] . "\n";
            
            // Prüfen ob Redirect URI korrekt ist
            $expectedUri = 'https://sunnybill-test.chargedata.eu/admin/gmail/oauth/callback';
            if ($params['redirect_uri'] === $expectedUri) {
                echo "   ✅ Redirect URI ist korrekt\n";
            } else {
                echo "   ❌ Redirect URI ist FALSCH!\n";
                echo "   Erwartet: $expectedUri\n";
                echo "   Tatsächlich: " . $params['redirect_uri'] . "\n";
            }
        }
        
        if (isset($params['scope'])) {
            echo "   Scopes: " . $params['scope'] . "\n";
            $requiredScopes = [
                'https://www.googleapis.com/auth/gmail.readonly',
                'https://www.googleapis.com/auth/gmail.modify',
                'https://www.googleapis.com/auth/userinfo.email'
            ];
            
            foreach ($requiredScopes as $scope) {
                if (strpos($params['scope'], $scope) !== false) {
                    echo "     ✅ $scope\n";
                } else {
                    echo "     ❌ $scope (FEHLT!)\n";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "   ❌ Fehler beim Generieren der OAuth2-URL: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // 4. Token-Status prüfen
    echo "4. Token-Status prüfen...\n";
    $accessToken = $settings->getGmailAccessToken();
    $refreshToken = $settings->getGmailRefreshToken();
    $expiresAt = $settings->getGmailTokenExpiresAt();
    
    echo "   Access Token vorhanden: " . ($accessToken ? "✅ Ja" : "❌ Nein") . "\n";
    echo "   Refresh Token vorhanden: " . ($refreshToken ? "✅ Ja" : "❌ Nein") . "\n";
    
    if ($expiresAt) {
        echo "   Token läuft ab: " . $expiresAt->format('Y-m-d H:i:s') . "\n";
        echo "   Token gültig: " . ($expiresAt->gt(now()) ? "✅ Ja" : "❌ Abgelaufen") . "\n";
    } else {
        echo "   Token-Ablaufzeit: ❌ Nicht gesetzt\n";
    }
    
    if ($settings->gmail_email_address) {
        echo "   Verbundene E-Mail: " . $settings->gmail_email_address . "\n";
    }
    
    echo "\n";
    
    // 5. Verbindungstest (nur wenn Tokens vorhanden)
    if ($refreshToken) {
        echo "5. Verbindungstest durchführen...\n";
        try {
            $result = $gmailService->testConnection();
            
            if ($result['success']) {
                echo "   ✅ Verbindung erfolgreich!\n";
                echo "   E-Mail: " . $result['email'] . "\n";
                if (isset($result['name'])) {
                    echo "   Name: " . $result['name'] . "\n";
                }
            } else {
                echo "   ❌ Verbindung fehlgeschlagen: " . $result['error'] . "\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Verbindungstest-Fehler: " . $e->getMessage() . "\n";
        }
    } else {
        echo "5. Verbindungstest übersprungen (keine Tokens vorhanden)\n";
    }
    
    echo "\n";
    
    // 6. Zusammenfassung und nächste Schritte
    echo "=== ZUSAMMENFASSUNG ===\n";
    
    if (!$settings->isGmailEnabled()) {
        echo "❌ Gmail-Integration ist nicht aktiviert\n";
        echo "   → Aktivieren Sie Gmail in den Firmeneinstellungen\n";
    }
    
    if (!$settings->getGmailClientId() || !$settings->getGmailClientSecret()) {
        echo "❌ Client ID oder Client Secret fehlen\n";
        echo "   → Tragen Sie die Google OAuth2-Credentials ein\n";
    }
    
    if (!$refreshToken) {
        echo "❌ Keine Autorisierung vorhanden\n";
        echo "   → Führen Sie die Gmail-Autorisierung durch\n";
        echo "   → URL: https://sunnybill-test.chargedata.eu/admin/company-settings\n";
    }
    
    echo "\n=== GOOGLE CLOUD CONSOLE CHECKLISTE ===\n";
    echo "Prüfen Sie folgende Punkte in der Google Cloud Console:\n";
    echo "1. ✅ Redirect URI: https://sunnybill-test.chargedata.eu/admin/gmail/oauth/callback\n";
    echo "2. ✅ OAuth Consent Screen vollständig ausgefüllt\n";
    echo "3. ✅ Gmail API aktiviert\n";
    echo "4. ✅ Scopes hinzugefügt (gmail.readonly, gmail.modify, userinfo.email)\n";
    echo "5. ✅ Test Users hinzugefügt (falls im Testing-Modus)\n";
    echo "6. ✅ 5-10 Minuten nach Änderungen gewartet\n";
    
    echo "\n=== NÄCHSTE SCHRITTE ===\n";
    if ($isConfigured && $refreshToken) {
        echo "✅ Gmail-Integration ist vollständig konfiguriert!\n";
        echo "   Sie können jetzt E-Mails synchronisieren.\n";
    } else {
        echo "📋 Folgende Schritte sind noch erforderlich:\n";
        
        if (!$settings->isGmailEnabled()) {
            echo "1. Gmail-Integration in Firmeneinstellungen aktivieren\n";
        }
        
        if (!$settings->getGmailClientId() || !$settings->getGmailClientSecret()) {
            echo "2. Client ID und Client Secret in Firmeneinstellungen eintragen\n";
        }
        
        if (!$refreshToken) {
            echo "3. Gmail-Autorisierung durchführen:\n";
            echo "   → https://sunnybill-test.chargedata.eu/admin/company-settings\n";
            echo "   → Gmail-Tab öffnen\n";
            echo "   → 'Gmail autorisieren' klicken\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ FEHLER: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST BEENDET ===\n";
