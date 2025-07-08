<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\CompanySetting;
use App\Models\GmailLog;
use App\Services\GmailService;
use Illuminate\Support\Facades\DB;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail Logging Final Test ===\n\n";

try {
    // 1. Aktuelle Einstellungen prüfen
    echo "1. Aktuelle Gmail-Einstellungen:\n";
    $settings = CompanySetting::current();
    
    echo "   - Gmail aktiviert: " . ($settings->gmail_enabled ? 'Ja' : 'Nein') . "\n";
    echo "   - INBOX-Filter: " . ($settings->gmail_filter_inbox ? 'Ja' : 'Nein') . "\n";
    echo "   - Logging aktiviert: " . ($settings->gmail_logging_enabled ? 'Ja' : 'Nein') . "\n";
    echo "   - E-Mail-Adresse: " . ($settings->gmail_email_address ?: 'Nicht gesetzt') . "\n";
    echo "\n";
    
    // 2. Logging aktivieren falls nicht aktiv
    if (!$settings->gmail_logging_enabled) {
        echo "2. Aktiviere Gmail-Logging...\n";
        $settings->update(['gmail_logging_enabled' => true]);
        echo "   ✅ Gmail-Logging aktiviert\n\n";
    } else {
        echo "2. Gmail-Logging bereits aktiviert ✅\n\n";
    }
    
    // 3. Aktuelle Log-Statistiken
    echo "3. Aktuelle Log-Statistiken:\n";
    $totalLogs = GmailLog::count();
    $todayLogs = GmailLog::today()->count();
    $inboxLogs = GmailLog::withInbox()->count();
    $inboxDespiteFilter = GmailLog::inboxDespiteFilter()->count();
    
    echo "   - Gesamt Log-Einträge: {$totalLogs}\n";
    echo "   - Heute: {$todayLogs}\n";
    echo "   - Mit INBOX-Label: {$inboxLogs}\n";
    echo "   - INBOX trotz Filter: {$inboxDespiteFilter}\n";
    echo "\n";
    
    // 4. Gmail-Service testen
    echo "4. Gmail-Service Test:\n";
    $gmailService = new GmailService();
    
    if (!$gmailService->isConfigured()) {
        echo "   ❌ Gmail ist nicht konfiguriert\n";
        echo "   Bitte konfigurieren Sie Gmail in den Firmeneinstellungen\n\n";
    } else {
        echo "   ✅ Gmail ist konfiguriert\n";
        
        // Verbindung testen
        $connectionTest = $gmailService->testConnection();
        if ($connectionTest['success']) {
            echo "   ✅ Verbindung erfolgreich: " . $connectionTest['email'] . "\n";
            
            // 5. E-Mail-Synchronisation mit Logging
            echo "\n5. E-Mail-Synchronisation mit Logging:\n";
            
            $logCountBefore = GmailLog::count();
            echo "   - Log-Einträge vor Sync: {$logCountBefore}\n";
            
            try {
                $syncResult = $gmailService->syncEmails(['maxResults' => 5]);
                
                $logCountAfter = GmailLog::count();
                $newLogs = $logCountAfter - $logCountBefore;
                
                echo "   - Verarbeitete E-Mails: " . $syncResult['processed'] . "\n";
                echo "   - Neue E-Mails: " . $syncResult['new'] . "\n";
                echo "   - Aktualisierte E-Mails: " . $syncResult['updated'] . "\n";
                echo "   - Fehler: " . $syncResult['errors'] . "\n";
                echo "   - Neue Log-Einträge: {$newLogs}\n";
                
                // 6. Neueste Log-Einträge anzeigen
                if ($newLogs > 0) {
                    echo "\n6. Neueste Log-Einträge:\n";
                    $recentLogs = GmailLog::orderBy('created_at', 'desc')->limit(3)->get();
                    
                    foreach ($recentLogs as $log) {
                        echo "   ---\n";
                        echo "   Gmail-ID: " . $log->gmail_id . "\n";
                        echo "   Betreff: " . ($log->subject ?: 'Kein Betreff') . "\n";
                        echo "   Von: " . ($log->from_email ?: 'Unbekannt') . "\n";
                        echo "   Labels gesamt: " . $log->total_labels . "\n";
                        echo "   System-Labels: " . implode(', ', $log->system_labels ?: []) . "\n";
                        echo "   Hat INBOX: " . ($log->has_inbox ? 'Ja' : 'Nein') . "\n";
                        echo "   Ist ungelesen: " . ($log->is_unread ? 'Ja' : 'Nein') . "\n";
                        echo "   Filter aktiv: " . ($log->filter_active ? 'Ja' : 'Nein') . "\n";
                        echo "   Aktion: " . $log->action . "\n";
                        echo "   Erstellt: " . $log->created_at->format('d.m.Y H:i:s') . "\n";
                    }
                }
                
            } catch (Exception $e) {
                echo "   ❌ Sync-Fehler: " . $e->getMessage() . "\n";
            }
            
        } else {
            echo "   ❌ Verbindung fehlgeschlagen: " . $connectionTest['error'] . "\n";
        }
    }
    
    // 7. Log-Statistiken nach Kategorien
    echo "\n7. Detaillierte Log-Statistiken:\n";
    
    $stats = [
        'Gesamt' => GmailLog::count(),
        'Heute' => GmailLog::today()->count(),
        'Mit INBOX' => GmailLog::withInbox()->count(),
        'Ungelesen' => GmailLog::where('is_unread', true)->count(),
        'Wichtig' => GmailLog::where('is_important', true)->count(),
        'Mit Stern' => GmailLog::where('is_starred', true)->count(),
        'Erstellt' => GmailLog::byAction('created')->count(),
        'Aktualisiert' => GmailLog::byAction('updated')->count(),
        'Synchronisiert' => GmailLog::byAction('sync')->count(),
    ];
    
    foreach ($stats as $label => $count) {
        echo "   - {$label}: {$count}\n";
    }
    
    // 8. Label-Verteilung
    echo "\n8. Label-Verteilung (Top 10):\n";
    $labelStats = DB::table('gmail_logs')
        ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(all_labels, "$[*]")) as label')
        ->whereNotNull('all_labels')
        ->get()
        ->flatMap(function ($row) {
            return json_decode('[' . $row->label . ']', true) ?: [];
        })
        ->countBy()
        ->sortDesc()
        ->take(10);
    
    foreach ($labelStats as $label => $count) {
        echo "   - {$label}: {$count}\n";
    }
    
    // 9. INBOX-Filter Analyse
    if ($settings->gmail_filter_inbox) {
        echo "\n9. INBOX-Filter Analyse:\n";
        $inboxDespiteFilter = GmailLog::inboxDespiteFilter()->count();
        $totalWithFilter = GmailLog::where('filter_active', true)->count();
        
        echo "   - E-Mails mit aktivem Filter: {$totalWithFilter}\n";
        echo "   - INBOX trotz Filter: {$inboxDespiteFilter}\n";
        
        if ($inboxDespiteFilter > 0) {
            echo "   ⚠️  Warnung: {$inboxDespiteFilter} E-Mails haben INBOX-Label trotz aktivem Filter!\n";
            
            $problematicEmails = GmailLog::inboxDespiteFilter()
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get(['gmail_id', 'subject', 'from_email', 'all_labels']);
            
            echo "   Beispiele:\n";
            foreach ($problematicEmails as $email) {
                echo "     - {$email->gmail_id}: {$email->subject} (von: {$email->from_email})\n";
            }
        } else {
            echo "   ✅ Filter funktioniert korrekt\n";
        }
    }
    
    // 10. Empfehlungen
    echo "\n10. Empfehlungen:\n";
    
    if (!$settings->gmail_enabled) {
        echo "   - Aktivieren Sie Gmail in den Firmeneinstellungen\n";
    }
    
    if (!$settings->gmail_logging_enabled) {
        echo "   - Aktivieren Sie das Gmail-Logging für detaillierte Analyse\n";
    }
    
    if ($totalLogs === 0) {
        echo "   - Führen Sie eine Gmail-Synchronisation durch um Log-Daten zu sammeln\n";
    }
    
    if ($settings->gmail_filter_inbox && $inboxDespiteFilter > 0) {
        echo "   - Überprüfen Sie die INBOX-Filter-Konfiguration\n";
    }
    
    echo "   - Überwachen Sie die Log-Einträge regelmäßig für Anomalien\n";
    echo "   - Nutzen Sie die Filament-Oberfläche für detaillierte Log-Analyse\n";
    
    echo "\n=== Test abgeschlossen ===\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
