<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CompanySetting;

echo "🔍 Gmail Konfiguration Live-Check\n";
echo "=================================\n\n";

try {
    $settings = CompanySetting::current();
    
    echo "📋 1. Basis-Konfiguration:\n";
    echo "   Gmail aktiviert: " . ($settings->gmail_enabled ? '✅ Ja' : '❌ Nein') . "\n";
    echo "   Client ID: " . ($settings->gmail_client_id ? '✅ Vorhanden (' . substr($settings->gmail_client_id, 0, 20) . '...)' : '❌ Fehlt') . "\n";
    echo "   Client Secret: " . ($settings->gmail_client_secret ? '✅ Vorhanden (' . substr($settings->gmail_client_secret, 0, 10) . '...)' : '❌ Fehlt') . "\n";
    echo "\n";
    
    echo "🔑 2. OAuth2-Tokens:\n";
    echo "   Access Token: " . ($settings->gmail_access_token ? '✅ Vorhanden (' . substr($settings->gmail_access_token, 0, 20) . '...)' : '❌ Fehlt') . "\n";
    echo "   Refresh Token: " . ($settings->gmail_refresh_token ? '✅ Vorhanden (' . substr($settings->gmail_refresh_token, 0, 20) . '...)' : '❌ Fehlt') . "\n";
    echo "   E-Mail-Adresse: " . ($settings->gmail_email_address ? '✅ ' . $settings->gmail_email_address : '❌ Nicht gesetzt') . "\n";
    
    if ($settings->gmail_token_expires_at) {
        $expiresAt = \Carbon\Carbon::parse($settings->gmail_token_expires_at);
        $isExpired = $expiresAt->isPast();
        echo "   Token läuft ab: " . $expiresAt->format('d.m.Y H:i:s') . ($isExpired ? ' ❌ (ABGELAUFEN)' : ' ✅ (Gültig)') . "\n";
    } else {
        echo "   Token Ablauf: ❌ Nicht gesetzt\n";
    }
    
    echo "\n";
    
    echo "📊 3. Datenbank-Werte (Raw):\n";
    echo "   gmail_enabled: " . var_export($settings->gmail_enabled, true) . "\n";
    echo "   gmail_client_id: " . ($settings->gmail_client_id ? 'SET (' . strlen($settings->gmail_client_id) . ' chars)' : 'NULL') . "\n";
    echo "   gmail_client_secret: " . ($settings->gmail_client_secret ? 'SET (' . strlen($settings->gmail_client_secret) . ' chars)' : 'NULL') . "\n";
    echo "   gmail_access_token: " . ($settings->gmail_access_token ? 'SET (' . strlen($settings->gmail_access_token) . ' chars)' : 'NULL') . "\n";
    echo "   gmail_refresh_token: " . ($settings->gmail_refresh_token ? 'SET (' . strlen($settings->gmail_refresh_token) . ' chars)' : 'NULL') . "\n";
    echo "   gmail_email_address: " . ($settings->gmail_email_address ?: 'NULL') . "\n";
    echo "   gmail_token_expires_at: " . ($settings->gmail_token_expires_at ?: 'NULL') . "\n";
    
    echo "\n";
    
    // Prüfen, was fehlt
    $missing = [];
    if (!$settings->gmail_enabled) $missing[] = 'Gmail aktivieren';
    if (!$settings->gmail_client_id) $missing[] = 'Client ID';
    if (!$settings->gmail_client_secret) $missing[] = 'Client Secret';
    if (!$settings->gmail_access_token) $missing[] = 'Access Token';
    if (!$settings->gmail_refresh_token) $missing[] = 'Refresh Token';
    if (!$settings->gmail_email_address) $missing[] = 'E-Mail-Adresse';
    
    if (count($missing) > 0) {
        echo "❌ 4. Fehlende Konfiguration:\n";
        foreach ($missing as $item) {
            echo "   - {$item}\n";
        }
        echo "\n";
        
        echo "🔧 5. Lösungsschritte:\n";
        
        if (!$settings->gmail_enabled || !$settings->gmail_client_id || !$settings->gmail_client_secret) {
            echo "   📋 Schritt 1: Basis-Konfiguration\n";
            echo "      1. Gehen Sie zu: https://sunnybill-test.test/admin/company-settings\n";
            echo "      2. Scrollen Sie zum Gmail-Bereich\n";
            echo "      3. Aktivieren Sie Gmail\n";
            echo "      4. Tragen Sie Client ID und Client Secret ein\n";
            echo "      5. Speichern Sie die Einstellungen\n";
            echo "\n";
        }
        
        if (!$settings->gmail_access_token || !$settings->gmail_refresh_token) {
            echo "   🔑 Schritt 2: OAuth2-Autorisierung\n";
            echo "      1. Gehen Sie zu: https://sunnybill-test.test/admin/company-settings\n";
            echo "      2. Klicken Sie auf 'Gmail autorisieren'\n";
            echo "      3. Folgen Sie dem OAuth2-Prozess\n";
            echo "      4. Gewähren Sie alle erforderlichen Berechtigungen\n";
            echo "      5. Warten Sie auf die Weiterleitung zurück\n";
            echo "\n";
        }
        
        echo "   🔄 Schritt 3: Konfiguration erneut prüfen\n";
        echo "      Führen Sie dieses Script erneut aus: php check_gmail_config_live.php\n";
        echo "\n";
        
    } else {
        echo "✅ 4. Konfiguration vollständig!\n";
        echo "   Alle erforderlichen Werte sind gesetzt.\n";
        echo "\n";
        
        echo "🔄 5. Nächste Schritte:\n";
        echo "   1. E-Mails synchronisieren: php test_gmail_sync.php\n";
        echo "   2. Erweiterte Diagnose: php test_gmail_detailed_sync.php\n";
        echo "   3. Admin-Panel besuchen: https://sunnybill-test.test/admin/gmail-emails\n";
    }
    
    echo "\n";
    echo "🔗 Wichtige URLs:\n";
    echo "   📋 Firmeneinstellungen: https://sunnybill-test.test/admin/company-settings\n";
    echo "   📧 Gmail E-Mails: https://sunnybill-test.test/admin/gmail-emails\n";
    echo "   🔧 Google Cloud Console: https://console.cloud.google.com/\n";
    echo "\n";
    
    // Zusätzliche Informationen für Debugging
    echo "🔍 6. Debug-Informationen:\n";
    echo "   Aktueller Zeitstempel: " . now()->format('d.m.Y H:i:s') . "\n";
    echo "   Datenbank-Verbindung: ✅ Funktioniert\n";
    echo "   CompanySetting ID: " . $settings->id . "\n";
    echo "   Letzte Aktualisierung: " . ($settings->updated_at ? $settings->updated_at->format('d.m.Y H:i:s') : 'Unbekannt') . "\n";
    
} catch (Exception $e) {
    echo "❌ Fehler beim Laden der Konfiguration:\n";
    echo "   Fehler: " . $e->getMessage() . "\n";
    echo "   Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n";
    echo "🔧 Mögliche Ursachen:\n";
    echo "   1. Datenbank-Verbindung fehlgeschlagen\n";
    echo "   2. CompanySetting-Tabelle existiert nicht\n";
    echo "   3. Keine CompanySetting-Einträge vorhanden\n";
    echo "\n";
    echo "💡 Lösungsvorschläge:\n";
    echo "   1. Prüfen Sie die Datenbank-Verbindung\n";
    echo "   2. Führen Sie die Migrationen aus: php artisan migrate\n";
    echo "   3. Erstellen Sie einen CompanySetting-Eintrag im Admin-Panel\n";
}

echo "\n";
echo "📝 Hinweis: Führen Sie dieses Script jederzeit aus, um den aktuellen Status zu prüfen.\n";
