<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\CompanySetting;
use App\Services\GmailService;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail Autorisierung prüfen ===\n\n";

try {
    // 1. Aktuelle Einstellungen prüfen
    echo "1. Gmail-Einstellungen nach Autorisierung:\n";
    $settings = CompanySetting::current();
    
    echo "   - Gmail aktiviert: " . ($settings->gmail_enabled ? 'Ja' : 'Nein') . "\n";
    echo "   - Client ID: " . ($settings->gmail_client_id ? 'Vorhanden' : 'Fehlt') . "\n";
    echo "   - Client Secret: " . ($settings->gmail_client_secret ? 'Vorhanden' : 'Fehlt') . "\n";
    echo "   - Access Token: " . ($settings->gmail_access_token ? 'Vorhanden ✅' : 'FEHLT ❌') . "\n";
    echo "   - Refresh Token: " . ($settings->gmail_refresh_token ? 'Vorhanden ✅' : 'FEHLT ❌') . "\n";
    echo "   - E-Mail-Adresse: " . ($settings->gmail_email_address ?: 'Nicht gesetzt') . "\n";
    echo "   - Token läuft ab: " . ($settings->gmail_token_expires_at ? $settings->gmail_token_expires_at->format('d.m.Y H:i:s') : 'Unbekannt') . "\n";
    echo "\n";
    
    // 2. Service testen
    echo "2. Gmail-Service testen:\n";
    $gmailService = new GmailService();
    
    if (!$gmailService->isConfigured()) {
        echo "   ❌ Gmail ist nicht konfiguriert\n";
        exit(1);
    }
    echo "   ✅ Gmail ist konfiguriert\n";
    
    // 3. Verbindung testen
    echo "\n3. Verbindung testen:\n";
    $connectionTest = $gmailService->testConnection();
    
    if (!$connectionTest['success']) {
        echo "   ❌ Verbindung fehlgeschlagen: " . $connectionTest['error'] . "\n";
        
        if (str_contains($connectionTest['error'], 'Keine Autorisierung')) {
            echo "\n   LÖSUNG: Führe die OAuth-Autorisierung durch:\n";
            echo "   php fix_gmail_authorization.php\n";
        }
        
        exit(1);
    }
    
    echo "   ✅ Verbindung erfolgreich!\n";
    echo "   📧 E-Mail: " . $connectionTest['email'] . "\n";
    echo "   👤 Name: " . ($connectionTest['name'] ?: 'Nicht verfügbar') . "\n";
    
    // 4. E-Mails abrufen testen
    echo "\n4. E-Mail-Abruf testen:\n";
    
    // Ohne Filter
    $messagesWithoutFilter = $gmailService->getMessages(['maxResults' => 5]);
    $countWithoutFilter = count($messagesWithoutFilter['messages'] ?? []);
    $estimateWithoutFilter = $messagesWithoutFilter['resultSizeEstimate'] ?? 0;
    
    echo "   Ohne Filter: {$countWithoutFilter} E-Mails (geschätzt: {$estimateWithoutFilter})\n";
    
    // Mit Filter (falls aktiviert)
    if ($settings->gmail_filter_inbox) {
        $messagesWithFilter = $gmailService->getMessages(['maxResults' => 5, 'q' => '-in:inbox']);
        $countWithFilter = count($messagesWithFilter['messages'] ?? []);
        $estimateWithFilter = $messagesWithFilter['resultSizeEstimate'] ?? 0;
        
        echo "   Mit Filter (-in:inbox): {$countWithFilter} E-Mails (geschätzt: {$estimateWithFilter})\n";
        
        if ($countWithFilter === 0 && $countWithoutFilter > 0) {
            echo "   ⚠️  WARNUNG: Filter ist zu restriktiv - keine E-Mails gefunden!\n";
            echo "   💡 TIPP: Deaktiviere den INBOX-Filter in den Einstellungen\n";
        }
    } else {
        echo "   INBOX-Filter ist deaktiviert\n";
    }
    
    // 5. Sync-Test
    echo "\n5. Synchronisation testen:\n";
    
    if ($countWithoutFilter > 0 || ($settings->gmail_filter_inbox && $countWithFilter > 0)) {
        echo "   ✅ E-Mails verfügbar für Synchronisation\n";
        echo "   🔄 Du kannst jetzt die E-Mail-Synchronisation starten\n";
        
        // Logging-Status prüfen
        if ($settings->gmail_logging_enabled) {
            echo "   📊 Gmail-Logging ist aktiviert - detaillierte Logs werden erstellt\n";
        } else {
            echo "   📊 Gmail-Logging ist deaktiviert\n";
        }
        
    } else {
        echo "   ⚠️  Keine E-Mails für Synchronisation verfügbar\n";
        
        if ($settings->gmail_filter_inbox) {
            echo "   💡 TIPP: Prüfe den INBOX-Filter oder deaktiviere ihn\n";
        } else {
            echo "   💡 TIPP: Prüfe ob das Gmail-Konto E-Mails enthält\n";
        }
    }
    
    // 6. Nächste Schritte
    echo "\n6. Nächste Schritte:\n";
    echo "   a) Gehe zu deiner Live-Anwendung\n";
    echo "   b) Navigiere zu Gmail-Integration\n";
    echo "   c) Klicke auf 'E-Mails synchronisieren'\n";
    echo "   d) Prüfe die Ergebnisse\n";
    
    if ($settings->gmail_logging_enabled) {
        echo "   e) Schaue dir die detaillierten Logs an:\n";
        echo "      php show_gmail_logs.php\n";
    }
    
    echo "\n=== Autorisierung erfolgreich! ===\n";
    echo "Gmail ist jetzt vollständig konfiguriert und einsatzbereit.\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
