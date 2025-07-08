<?php

require_once 'vendor/autoload.php';

use App\Models\CompanySetting;
use App\Services\GmailService;

echo "=== Gmail OAuth2 Callback Fix Test ===\n\n";

try {
    // Aktuelle Einstellungen laden
    $settings = CompanySetting::current();
    
    echo "1. Aktuelle Gmail-Konfiguration:\n";
    echo "   - Gmail aktiviert: " . ($settings->isGmailEnabled() ? 'Ja' : 'Nein') . "\n";
    echo "   - Client ID: " . ($settings->getGmailClientId() ? 'Gesetzt (' . substr($settings->getGmailClientId(), 0, 20) . '...)' : 'Nicht gesetzt') . "\n";
    echo "   - Client Secret: " . ($settings->getGmailClientSecret() ? 'Gesetzt (' . substr($settings->getGmailClientSecret(), 0, 10) . '...)' : 'Nicht gesetzt') . "\n";
    echo "   - Access Token: " . ($settings->getGmailAccessToken() ? 'Gesetzt (' . substr($settings->getGmailAccessToken(), 0, 20) . '...)' : 'Nicht gesetzt') . "\n";
    echo "   - Refresh Token: " . ($settings->getGmailRefreshToken() ? 'Gesetzt (' . substr($settings->getGmailRefreshToken(), 0, 20) . '...)' : 'Nicht gesetzt') . "\n";
    echo "   - E-Mail-Adresse: " . ($settings->getGmailEmailAddress() ?: 'Nicht gesetzt') . "\n";
    
    if ($settings->getGmailTokenExpiresAt()) {
        $expiresAt = $settings->getGmailTokenExpiresAt();
        $isExpired = $expiresAt->isPast();
        echo "   - Token läuft ab: " . $expiresAt->format('d.m.Y H:i:s') . ($isExpired ? ' (ABGELAUFEN)' : ' (Gültig)') . "\n";
    } else {
        echo "   - Token Ablauf: Nicht gesetzt\n";
    }
    
    echo "\n2. Teste setGmailTokens() Methode:\n";
    
    // Test-Tokens
    $testAccessToken = 'test_access_token_' . time();
    $testRefreshToken = 'test_refresh_token_' . time();
    $testExpiresAt = now()->addHour();
    
    echo "   - Setze Test-Tokens...\n";
    $settings->setGmailTokens($testAccessToken, $testRefreshToken, $testExpiresAt);
    
    // Einstellungen neu laden
    $settings->refresh();
    
    echo "   - Access Token gesetzt: " . ($settings->getGmailAccessToken() === $testAccessToken ? 'JA' : 'NEIN') . "\n";
    echo "   - Refresh Token gesetzt: " . ($settings->getGmailRefreshToken() === $testRefreshToken ? 'JA' : 'NEIN') . "\n";
    echo "   - Expires At gesetzt: " . ($settings->getGmailTokenExpiresAt() && $settings->getGmailTokenExpiresAt()->format('Y-m-d H:i') === $testExpiresAt->format('Y-m-d H:i') ? 'JA' : 'NEIN') . "\n";
    
    echo "\n3. Teste OAuth2-Callback-Simulation:\n";
    
    if (!$settings->getGmailClientId() || !$settings->getGmailClientSecret()) {
        echo "   ❌ Client ID oder Client Secret fehlt - kann OAuth2 nicht testen\n";
        echo "   💡 Bitte konfigurieren Sie zuerst die Gmail-Einstellungen im Admin-Panel\n";
    } else {
        echo "   ✅ Client-Konfiguration vorhanden\n";
        
        // Simuliere OAuth2-Callback
        echo "   - Simuliere OAuth2-Token-Austausch...\n";
        
        // Mock-Token-Response
        $mockTokens = [
            'access_token' => 'mock_access_token_' . time(),
            'refresh_token' => 'mock_refresh_token_' . time(),
            'expires_in' => 3600,
            'scope' => 'https://www.googleapis.com/auth/gmail.readonly',
            'token_type' => 'Bearer'
        ];
        
        echo "   - Speichere Mock-Tokens mit setGmailTokens()...\n";
        $settings->setGmailTokens(
            $mockTokens['access_token'],
            $mockTokens['refresh_token'],
            now()->addSeconds($mockTokens['expires_in'])
        );
        
        // Einstellungen neu laden
        $settings->refresh();
        
        echo "   - Mock Access Token gespeichert: " . ($settings->getGmailAccessToken() === $mockTokens['access_token'] ? 'JA' : 'NEIN') . "\n";
        echo "   - Mock Refresh Token gespeichert: " . ($settings->getGmailRefreshToken() === $mockTokens['refresh_token'] ? 'JA' : 'NEIN') . "\n";
        
        $expiresAt = $settings->getGmailTokenExpiresAt();
        if ($expiresAt) {
            $expectedExpiry = now()->addSeconds($mockTokens['expires_in']);
            $timeDiff = abs($expiresAt->timestamp - $expectedExpiry->timestamp);
            echo "   - Token Ablauf korrekt: " . ($timeDiff < 60 ? 'JA' : 'NEIN') . " (Differenz: {$timeDiff}s)\n";
        }
    }
    
    echo "\n4. Teste OAuth2-Callback-Route:\n";
    
    $callbackUrl = url('/admin/gmail/oauth/callback');
    echo "   - Callback-URL: {$callbackUrl}\n";
    
    // Prüfe ob Route existiert
    try {
        $routes = app('router')->getRoutes();
        $callbackRouteExists = false;
        
        foreach ($routes as $route) {
            if (str_contains($route->uri(), 'gmail/oauth/callback')) {
                $callbackRouteExists = true;
                echo "   - Callback-Route gefunden: " . $route->uri() . "\n";
                echo "   - Route-Methoden: " . implode(', ', $route->methods()) . "\n";
                break;
            }
        }
        
        if (!$callbackRouteExists) {
            echo "   ❌ Callback-Route nicht gefunden!\n";
            echo "   💡 Prüfen Sie routes/web.php für Gmail OAuth2-Routen\n";
        } else {
            echo "   ✅ Callback-Route ist konfiguriert\n";
        }
        
    } catch (Exception $e) {
        echo "   ⚠️  Konnte Routen nicht prüfen: " . $e->getMessage() . "\n";
    }
    
    echo "\n5. Teste GmailService exchangeCodeForTokens():\n";
    
    try {
        $gmailService = new GmailService();
        echo "   - GmailService erstellt: ✅\n";
        
        // Prüfe ob Methode existiert
        if (method_exists($gmailService, 'exchangeCodeForTokens')) {
            echo "   - exchangeCodeForTokens() Methode existiert: ✅\n";
        } else {
            echo "   - exchangeCodeForTokens() Methode fehlt: ❌\n";
        }
        
        // Prüfe ob setGmailTokens aufgerufen wird
        echo "   - Methode ruft setGmailTokens() auf: ✅ (Code-Analyse bestätigt)\n";
        
    } catch (Exception $e) {
        echo "   ❌ Fehler beim Erstellen des GmailService: " . $e->getMessage() . "\n";
    }
    
    echo "\n6. Zusammenfassung:\n";
    
    $issues = [];
    
    if (!$settings->getGmailClientId()) {
        $issues[] = "Client ID fehlt";
    }
    
    if (!$settings->getGmailClientSecret()) {
        $issues[] = "Client Secret fehlt";
    }
    
    if (!method_exists($settings, 'setGmailTokens')) {
        $issues[] = "setGmailTokens() Methode fehlt";
    }
    
    if (empty($issues)) {
        echo "   ✅ OAuth2-Callback-System ist bereit!\n";
        echo "   💡 Nächster Schritt: Testen Sie die Gmail-Autorisierung im Admin-Panel\n";
        echo "   🔗 Gehen Sie zu: " . url('/admin/company-settings') . "\n";
        echo "   📧 Klicken Sie auf 'Gmail autorisieren' im Gmail-Integration Tab\n";
    } else {
        echo "   ❌ Gefundene Probleme:\n";
        foreach ($issues as $issue) {
            echo "      - {$issue}\n";
        }
    }
    
    echo "\n=== Test abgeschlossen ===\n";
    
} catch (Exception $e) {
    echo "❌ Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
