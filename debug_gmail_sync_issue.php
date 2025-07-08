<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\CompanySetting;
use App\Services\GmailService;
use Illuminate\Support\Facades\Log;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail Sync Debug ===\n\n";

try {
    // 1. Einstellungen prüfen
    echo "1. Gmail-Einstellungen prüfen:\n";
    $settings = CompanySetting::current();
    
    echo "   - Gmail aktiviert: " . ($settings->gmail_enabled ? 'Ja' : 'Nein') . "\n";
    echo "   - Client ID vorhanden: " . ($settings->gmail_client_id ? 'Ja' : 'Nein') . "\n";
    echo "   - Client Secret vorhanden: " . ($settings->gmail_client_secret ? 'Ja' : 'Nein') . "\n";
    echo "   - Access Token vorhanden: " . ($settings->gmail_access_token ? 'Ja' : 'Nein') . "\n";
    echo "   - Refresh Token vorhanden: " . ($settings->gmail_refresh_token ? 'Ja' : 'Nein') . "\n";
    echo "   - E-Mail-Adresse: " . ($settings->gmail_email_address ?: 'Nicht gesetzt') . "\n";
    echo "   - INBOX-Filter: " . ($settings->gmail_filter_inbox ? 'Ja' : 'Nein') . "\n";
    echo "   - Max Results: " . ($settings->gmail_max_results ?: 'Standard (100)') . "\n";
    echo "\n";
    
    // 2. Service initialisieren
    echo "2. GmailService initialisieren:\n";
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
        exit(1);
    }
    echo "   ✅ Verbindung erfolgreich: " . $connectionTest['email'] . "\n";
    
    // 4. Labels abrufen
    echo "\n4. Verfügbare Labels abrufen:\n";
    $labels = $gmailService->getLabels();
    echo "   Anzahl Labels: " . count($labels) . "\n";
    
    if (count($labels) > 0) {
        echo "   Erste 10 Labels:\n";
        foreach (array_slice($labels, 0, 10) as $label) {
            echo "     - " . $label['name'] . " (ID: " . $label['id'] . ")\n";
        }
    }
    
    // 5. E-Mails ohne Filter abrufen
    echo "\n5. E-Mails ohne Filter abrufen:\n";
    $messagesWithoutFilter = $gmailService->getMessages(['maxResults' => 5]);
    echo "   Gefundene E-Mails (ohne Filter): " . count($messagesWithoutFilter['messages'] ?? []) . "\n";
    echo "   Geschätzte Gesamtanzahl: " . ($messagesWithoutFilter['resultSizeEstimate'] ?? 0) . "\n";
    
    if (!empty($messagesWithoutFilter['messages'])) {
        echo "   Erste E-Mail IDs:\n";
        foreach (array_slice($messagesWithoutFilter['messages'], 0, 3) as $msg) {
            echo "     - " . $msg['id'] . "\n";
        }
    }
    
    // 6. E-Mails mit INBOX-Filter testen
    if ($settings->gmail_filter_inbox) {
        echo "\n6. E-Mails mit INBOX-Filter (-in:inbox) abrufen:\n";
        $messagesWithFilter = $gmailService->getMessages(['maxResults' => 5, 'q' => '-in:inbox']);
        echo "   Gefundene E-Mails (mit Filter): " . count($messagesWithFilter['messages'] ?? []) . "\n";
        echo "   Geschätzte Gesamtanzahl: " . ($messagesWithFilter['resultSizeEstimate'] ?? 0) . "\n";
    } else {
        echo "\n6. INBOX-Filter ist deaktiviert\n";
    }
    
    // 7. Verschiedene Abfragen testen
    echo "\n7. Verschiedene Abfragen testen:\n";
    
    $queries = [
        'Alle E-Mails' => '',
        'Nur INBOX' => 'in:inbox',
        'Nicht INBOX' => '-in:inbox',
        'Ungelesen' => 'is:unread',
        'Letzte 7 Tage' => 'newer_than:7d',
    ];
    
    foreach ($queries as $name => $query) {
        $options = ['maxResults' => 5];
        if ($query) {
            $options['q'] = $query;
        }
        
        $result = $gmailService->getMessages($options);
        $count = count($result['messages'] ?? []);
        $estimate = $result['resultSizeEstimate'] ?? 0;
        
        echo "   {$name}: {$count} E-Mails (geschätzt: {$estimate})\n";
    }
    
    // 8. Eine E-Mail im Detail abrufen
    if (!empty($messagesWithoutFilter['messages'])) {
        echo "\n8. Erste E-Mail im Detail abrufen:\n";
        $firstMessageId = $messagesWithoutFilter['messages'][0]['id'];
        $messageDetail = $gmailService->getMessage($firstMessageId);
        
        if ($messageDetail) {
            $payload = $messageDetail['payload'] ?? [];
            $headers = [];
            foreach ($payload['headers'] ?? [] as $header) {
                $headers[$header['name']] = $header['value'];
            }
            
            echo "   Gmail-ID: " . $messageDetail['id'] . "\n";
            echo "   Thread-ID: " . $messageDetail['threadId'] . "\n";
            echo "   Betreff: " . ($headers['Subject'] ?? 'Kein Betreff') . "\n";
            echo "   Von: " . ($headers['From'] ?? 'Unbekannt') . "\n";
            echo "   Datum: " . ($headers['Date'] ?? 'Unbekannt') . "\n";
            echo "   Labels: " . implode(', ', $messageDetail['labelIds'] ?? []) . "\n";
            echo "   Snippet: " . substr($messageDetail['snippet'] ?? '', 0, 100) . "...\n";
        } else {
            echo "   ❌ Konnte E-Mail-Details nicht abrufen\n";
        }
    }
    
    // 9. Sync-Optionen analysieren
    echo "\n9. Sync-Optionen analysieren:\n";
    $syncOptions = [];
    
    if ($settings->gmail_filter_inbox) {
        $syncOptions['q'] = '-in:inbox';
        echo "   Filter aktiv: -in:inbox\n";
    } else {
        echo "   Kein Filter aktiv\n";
    }
    
    $syncOptions['maxResults'] = $settings->gmail_max_results ?: 100;
    echo "   Max Results: " . $syncOptions['maxResults'] . "\n";
    
    // 10. Tatsächliche Sync-Abfrage testen
    echo "\n10. Tatsächliche Sync-Abfrage testen:\n";
    $syncMessages = $gmailService->getMessages($syncOptions);
    $syncCount = count($syncMessages['messages'] ?? []);
    $syncEstimate = $syncMessages['resultSizeEstimate'] ?? 0;
    
    echo "   Sync würde {$syncCount} E-Mails verarbeiten (geschätzt: {$syncEstimate})\n";
    
    if ($syncCount === 0) {
        echo "   ⚠️  PROBLEM: Sync-Abfrage findet keine E-Mails!\n";
        
        if ($settings->gmail_filter_inbox) {
            echo "   MÖGLICHE URSACHE: INBOX-Filter ist zu restriktiv\n";
            echo "   LÖSUNG: Filter deaktivieren oder andere Abfrage verwenden\n";
        } else {
            echo "   MÖGLICHE URSACHEN:\n";
            echo "   - Keine E-Mails im Gmail-Konto\n";
            echo "   - API-Berechtigungen unvollständig\n";
            echo "   - Gmail-Konto ist leer\n";
        }
    } else {
        echo "   ✅ Sync-Abfrage funktioniert korrekt\n";
    }
    
    echo "\n=== Debug abgeschlossen ===\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
