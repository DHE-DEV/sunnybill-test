<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\CompanySetting;
use App\Models\GmailLog;
use App\Services\GmailService;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail Logging Debug ===\n\n";

try {
    // 1. Logging-Einstellungen prüfen
    echo "1. Gmail-Logging-Einstellungen prüfen:\n";
    $settings = CompanySetting::current();
    
    echo "   - Gmail aktiviert: " . ($settings->gmail_enabled ? 'Ja' : 'Nein') . "\n";
    echo "   - Gmail Logging aktiviert: " . ($settings->gmail_logging_enabled ? 'Ja ✅' : 'Nein ❌') . "\n";
    echo "   - Access Token vorhanden: " . ($settings->gmail_access_token ? 'Ja' : 'Nein') . "\n";
    echo "   - Refresh Token vorhanden: " . ($settings->gmail_refresh_token ? 'Ja' : 'Nein') . "\n";
    echo "\n";
    
    if (!$settings->gmail_logging_enabled) {
        echo "❌ Gmail-Logging ist deaktiviert!\n";
        echo "💡 LÖSUNG: Aktiviere Gmail-Logging in den Firmeneinstellungen\n";
        echo "   1. Gehe zu Filament Admin → Firmeneinstellungen\n";
        echo "   2. Tab 'Gmail-Integration'\n";
        echo "   3. Aktiviere 'E-Mail Logging aktiviert'\n";
        echo "   4. Speichern\n\n";
    }
    
    // 2. Datenbank-Tabelle prüfen
    echo "2. Gmail-Logs Datenbank-Tabelle prüfen:\n";
    
    try {
        $logCount = GmailLog::count();
        echo "   ✅ gmail_logs Tabelle existiert\n";
        echo "   📊 Aktuelle Log-Einträge: {$logCount}\n";
        
        if ($logCount > 0) {
            $latestLog = GmailLog::latest()->first();
            echo "   📅 Neuester Log-Eintrag: " . $latestLog->created_at->format('d.m.Y H:i:s') . "\n";
            echo "   📧 Gmail-ID: " . $latestLog->gmail_id . "\n";
            echo "   📝 Betreff: " . ($latestLog->subject ?: 'Kein Betreff') . "\n";
        } else {
            echo "   ⚠️  Keine Log-Einträge vorhanden\n";
        }
        
    } catch (\Exception $e) {
        echo "   ❌ Fehler beim Zugriff auf gmail_logs Tabelle: " . $e->getMessage() . "\n";
        echo "   💡 LÖSUNG: Führe die Migration aus:\n";
        echo "      php artisan migrate\n";
    }
    echo "\n";
    
    // 3. Service testen
    echo "3. GmailService testen:\n";
    $gmailService = new GmailService();
    
    if (!$gmailService->isConfigured()) {
        echo "   ❌ Gmail ist nicht konfiguriert\n";
        exit(1);
    }
    echo "   ✅ Gmail ist konfiguriert\n";
    
    // 4. Verbindung testen
    echo "\n4. Verbindung testen:\n";
    $connectionTest = $gmailService->testConnection();
    
    if (!$connectionTest['success']) {
        echo "   ❌ Verbindung fehlgeschlagen: " . $connectionTest['error'] . "\n";
        exit(1);
    }
    
    echo "   ✅ Verbindung erfolgreich\n";
    echo "   📧 E-Mail: " . $connectionTest['email'] . "\n";
    
    // 5. Test-Sync mit Logging
    echo "\n5. Test-Synchronisation mit Logging:\n";
    
    if (!$settings->gmail_logging_enabled) {
        echo "   ⚠️  Logging ist deaktiviert - aktiviere es zuerst!\n";
    } else {
        echo "   🔄 Starte Test-Sync mit maximal 2 E-Mails...\n";
        
        $logCountBefore = GmailLog::count();
        echo "   📊 Log-Einträge vor Sync: {$logCountBefore}\n";
        
        try {
            $stats = $gmailService->syncEmails(['maxResults' => 2]);
            
            echo "   📈 Sync-Statistiken:\n";
            echo "      - Verarbeitet: " . $stats['processed'] . "\n";
            echo "      - Neu: " . $stats['new'] . "\n";
            echo "      - Aktualisiert: " . $stats['updated'] . "\n";
            echo "      - Fehler: " . $stats['errors'] . "\n";
            
            $logCountAfter = GmailLog::count();
            $newLogs = $logCountAfter - $logCountBefore;
            
            echo "   📊 Log-Einträge nach Sync: {$logCountAfter}\n";
            echo "   📝 Neue Log-Einträge: {$newLogs}\n";
            
            if ($newLogs === 0 && $stats['processed'] > 0) {
                echo "   ❌ PROBLEM: E-Mails wurden verarbeitet, aber keine Logs erstellt!\n";
                echo "   🔍 Mögliche Ursachen:\n";
                echo "      - createGmailLog() Methode wird nicht aufgerufen\n";
                echo "      - Fehler beim Speichern der Log-Einträge\n";
                echo "      - Logging-Bedingung nicht erfüllt\n";
            } elseif ($newLogs > 0) {
                echo "   ✅ Logging funktioniert korrekt!\n";
                
                // Neueste Logs anzeigen
                $recentLogs = GmailLog::latest()->take(3)->get();
                echo "   📋 Neueste Log-Einträge:\n";
                foreach ($recentLogs as $log) {
                    echo "      - " . $log->created_at->format('H:i:s') . " | " . 
                         $log->gmail_id . " | " . 
                         ($log->subject ?: 'Kein Betreff') . " | " .
                         "Labels: " . count($log->all_labels ?? []) . "\n";
                }
            }
            
        } catch (\Exception $e) {
            echo "   ❌ Fehler beim Test-Sync: " . $e->getMessage() . "\n";
        }
    }
    
    // 6. Manueller Log-Test
    echo "\n6. Manueller Log-Eintrag Test:\n";
    
    if ($settings->gmail_logging_enabled) {
        try {
            $testLogData = [
                'gmail_id' => 'test_' . time(),
                'subject' => 'Test Log Eintrag',
                'from_email' => 'test@example.com',
                'total_labels' => 2,
                'all_labels' => ['INBOX', 'UNREAD'],
                'system_labels' => ['INBOX', 'UNREAD'],
                'category_labels' => [],
                'user_labels' => [],
                'has_inbox' => true,
                'is_unread' => true,
                'is_important' => false,
                'is_starred' => false,
                'filter_active' => $settings->gmail_filter_inbox ?? false,
                'action' => 'test',
                'notes' => 'Manueller Test-Eintrag',
            ];
            
            $testLog = GmailLog::create($testLogData);
            echo "   ✅ Test-Log-Eintrag erfolgreich erstellt (ID: {$testLog->id})\n";
            
            // Test-Eintrag wieder löschen
            $testLog->delete();
            echo "   🗑️  Test-Eintrag wieder gelöscht\n";
            
        } catch (\Exception $e) {
            echo "   ❌ Fehler beim Erstellen des Test-Log-Eintrags: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️  Logging ist deaktiviert - Test übersprungen\n";
    }
    
    // 7. Empfehlungen
    echo "\n7. Empfehlungen:\n";
    
    if (!$settings->gmail_logging_enabled) {
        echo "   🔧 WICHTIG: Aktiviere Gmail-Logging in den Einstellungen\n";
    }
    
    if ($settings->gmail_logging_enabled) {
        echo "   ✅ Gmail-Logging ist aktiviert\n";
        echo "   💡 Führe eine vollständige E-Mail-Synchronisation durch\n";
        echo "   📊 Prüfe danach die Logs mit: php show_gmail_logs.php\n";
    }
    
    echo "\n=== Debug abgeschlossen ===\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
