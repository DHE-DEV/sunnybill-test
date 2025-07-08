<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\CompanySetting;
use App\Services\GmailService;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail INBOX-Filter deaktivieren ===\n\n";

try {
    // 1. Aktuelle Einstellungen anzeigen
    echo "1. Aktuelle Filter-Einstellungen:\n";
    $settings = CompanySetting::current();
    
    echo "   - INBOX-Filter aktiviert: " . ($settings->gmail_filter_inbox ? 'Ja ❌' : 'Nein ✅') . "\n";
    echo "\n";
    
    if (!$settings->gmail_filter_inbox) {
        echo "✅ INBOX-Filter ist bereits deaktiviert!\n";
        echo "Das Problem liegt woanders. Führe aus:\n";
        echo "php debug_gmail_logging_issue.php\n";
        exit(0);
    }
    
    // 2. Filter deaktivieren
    echo "2. INBOX-Filter deaktivieren:\n";
    
    $settings->gmail_filter_inbox = false;
    $settings->save();
    
    echo "   ✅ INBOX-Filter wurde deaktiviert\n";
    echo "\n";
    
    // 3. Test nach Deaktivierung
    echo "3. Test nach Deaktivierung:\n";
    
    $gmailService = new GmailService();
    
    // E-Mails ohne Filter abrufen (sollte jetzt funktionieren)
    $messages = $gmailService->getMessages(['maxResults' => 5]);
    $count = count($messages['messages'] ?? []);
    $estimate = $messages['resultSizeEstimate'] ?? 0;
    
    echo "   📧 Verfügbare E-Mails: {$count}\n";
    echo "   📊 Geschätzte Gesamtzahl: {$estimate}\n";
    
    if ($count > 0) {
        echo "   ✅ E-Mails sind jetzt verfügbar für Synchronisation!\n";
        
        // Erste E-Mail Details
        $firstMessage = $messages['messages'][0];
        $messageDetails = $gmailService->getMessage($firstMessage['id']);
        
        if ($messageDetails) {
            $labels = $messageDetails['labelIds'] ?? [];
            $headers = [];
            if (isset($messageDetails['payload']['headers'])) {
                foreach ($messageDetails['payload']['headers'] as $header) {
                    $headers[$header['name']] = $header['value'];
                }
            }
            
            echo "\n   📋 Beispiel E-Mail (wird jetzt synchronisiert):\n";
            echo "      - Gmail-ID: " . $firstMessage['id'] . "\n";
            echo "      - Betreff: " . ($headers['Subject'] ?? 'Kein Betreff') . "\n";
            echo "      - Von: " . ($headers['From'] ?? 'Unbekannt') . "\n";
            echo "      - Labels: " . implode(', ', $labels) . "\n";
        }
    } else {
        echo "   ❌ Immer noch keine E-Mails verfügbar\n";
        echo "   💡 Das Problem liegt woanders - prüfe die Autorisierung\n";
    }
    
    // 4. Test-Synchronisation
    echo "\n4. Test-Synchronisation durchführen:\n";
    
    if ($count > 0) {
        echo "   🔄 Starte Test-Sync mit maximal 2 E-Mails...\n";
        
        try {
            $stats = $gmailService->syncEmails(['maxResults' => 2]);
            
            echo "   📈 Sync-Statistiken:\n";
            echo "      - Verarbeitet: " . $stats['processed'] . "\n";
            echo "      - Neu: " . $stats['new'] . "\n";
            echo "      - Aktualisiert: " . $stats['updated'] . "\n";
            echo "      - Fehler: " . $stats['errors'] . "\n";
            
            if ($stats['processed'] > 0) {
                echo "   ✅ Synchronisation funktioniert jetzt!\n";
                
                // Prüfe ob Logs erstellt wurden
                if ($settings->gmail_logging_enabled) {
                    $logCount = \App\Models\GmailLog::count();
                    echo "   📊 Gmail-Log-Einträge: {$logCount}\n";
                    
                    if ($logCount > 0) {
                        echo "   ✅ Logging funktioniert auch!\n";
                    } else {
                        echo "   ⚠️  Keine Log-Einträge erstellt - prüfe Logging-Konfiguration\n";
                    }
                }
            } else {
                echo "   ⚠️  Keine E-Mails verarbeitet - weitere Diagnose nötig\n";
            }
            
        } catch (\Exception $e) {
            echo "   ❌ Fehler beim Test-Sync: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️  Keine E-Mails verfügbar - Test-Sync übersprungen\n";
    }
    
    // 5. Nächste Schritte
    echo "\n5. Nächste Schritte:\n";
    
    if ($count > 0) {
        echo "   ✅ INBOX-Filter erfolgreich deaktiviert!\n";
        echo "   🔄 Gehe zu deiner Live-Anwendung und starte die E-Mail-Synchronisation\n";
        echo "   📊 Prüfe die Ergebnisse und Logs\n";
        
        if ($settings->gmail_logging_enabled) {
            echo "   📋 Logs anzeigen: php show_gmail_logs.php\n";
        }
        
        echo "\n   💡 TIPP: Du kannst den INBOX-Filter später wieder aktivieren,\n";
        echo "      wenn du nur E-Mails außerhalb der INBOX synchronisieren möchtest.\n";
    } else {
        echo "   ❌ Problem nicht gelöst - weitere Diagnose erforderlich\n";
        echo "   🔍 Führe aus: php debug_gmail_sync_issue.php\n";
    }
    
    echo "\n=== Filter-Fix abgeschlossen ===\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
