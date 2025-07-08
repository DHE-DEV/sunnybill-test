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

use App\Services\GmailService;
use App\Models\CompanySetting;
use App\Models\GmailEmail;

echo "🔍 Gmail Detaillierte Synchronisation & Diagnose\n";
echo "===============================================\n\n";

try {
    $gmailService = new GmailService();
    $settings = CompanySetting::current();
    
    // 1. Basis-Konfiguration prüfen
    echo "📋 1. Gmail Basis-Konfiguration...\n";
    echo "   Gmail aktiviert: " . ($settings->isGmailEnabled() ? '✅ Ja' : '❌ Nein') . "\n";
    echo "   Client ID: " . ($settings->getGmailClientId() ? '✅ Vorhanden' : '❌ Fehlt') . "\n";
    echo "   Client Secret: " . ($settings->getGmailClientSecret() ? '✅ Vorhanden' : '❌ Fehlt') . "\n";
    echo "   Access Token: " . ($settings->getGmailAccessToken() ? '✅ Vorhanden' : '❌ Fehlt') . "\n";
    echo "   Refresh Token: " . ($settings->getGmailRefreshToken() ? '✅ Vorhanden' : '❌ Fehlt') . "\n";
    echo "   E-Mail-Adresse: " . ($settings->gmail_email_address ?? '❌ Nicht gesetzt') . "\n";
    
    if (!$settings->isGmailEnabled() || !$settings->getGmailClientId() || !$settings->getGmailRefreshToken()) {
        echo "\n❌ Gmail ist nicht vollständig konfiguriert!\n";
        echo "🔧 Bitte konfigurieren Sie Gmail in den Firmeneinstellungen.\n";
        exit(1);
    }
    
    echo "\n";
    
    // 2. Token-Status prüfen
    echo "🔑 2. Token-Status prüfen...\n";
    
    if ($settings->getGmailTokenExpiresAt()) {
        $expiresAt = $settings->getGmailTokenExpiresAt();
        $isExpired = $expiresAt->isPast();
        $timeLeft = $expiresAt->diffForHumans();
        
        echo "   Token läuft ab: " . $expiresAt->format('d.m.Y H:i:s') . "\n";
        echo "   Status: " . ($isExpired ? '❌ ABGELAUFEN' : '✅ Gültig') . "\n";
        echo "   Zeit bis Ablauf: {$timeLeft}\n";
        
        if ($isExpired) {
            echo "   🔄 Versuche Token zu erneuern...\n";
            try {
                $newToken = $gmailService->refreshAccessToken();
                echo "   ✅ Token erfolgreich erneuert!\n";
            } catch (\Exception $e) {
                echo "   ❌ Token-Erneuerung fehlgeschlagen: " . $e->getMessage() . "\n";
                exit(1);
            }
        }
    }
    
    echo "\n";
    
    // 3. API-Verbindung testen
    echo "🔗 3. Gmail API-Verbindung testen...\n";
    
    $connectionTest = $gmailService->testConnection();
    
    if ($connectionTest['success']) {
        echo "   ✅ Verbindung erfolgreich!\n";
        echo "   📧 E-Mail: " . $connectionTest['email'] . "\n";
        if (isset($connectionTest['name'])) {
            echo "   👤 Name: " . $connectionTest['name'] . "\n";
        }
    } else {
        echo "   ❌ Verbindung fehlgeschlagen: " . $connectionTest['error'] . "\n";
        exit(1);
    }
    
    echo "\n";
    
    // 4. Gmail-Postfach analysieren
    echo "📊 4. Gmail-Postfach analysieren...\n";
    
    // Verschiedene Abfragen testen
    $queries = [
        'Alle E-Mails' => [],
        'Nur Posteingang' => ['labelIds' => ['INBOX']],
        'Nur ungelesene' => ['q' => 'is:unread'],
        'Letzte 7 Tage' => ['q' => 'newer_than:7d'],
        'Letzte 30 Tage' => ['q' => 'newer_than:30d'],
        'Mit Anhängen' => ['q' => 'has:attachment'],
        'Gesendete E-Mails' => ['labelIds' => ['SENT']],
    ];
    
    foreach ($queries as $queryName => $options) {
        echo "   🔍 {$queryName}:\n";
        
        try {
            $options['maxResults'] = 10; // Nur wenige für Test
            $result = $gmailService->getMessages($options);
            
            $count = $result['resultSizeEstimate'] ?? 0;
            $messages = $result['messages'] ?? [];
            
            echo "      📊 Geschätzte Anzahl: {$count}\n";
            echo "      📧 Zurückgegebene E-Mails: " . count($messages) . "\n";
            
            if (count($messages) > 0) {
                echo "      📝 Erste E-Mail ID: " . $messages[0]['id'] . "\n";
                
                // Erste E-Mail im Detail abrufen
                $firstMessage = $gmailService->getMessage($messages[0]['id']);
                if ($firstMessage) {
                    $headers = [];
                    if (isset($firstMessage['payload']['headers'])) {
                        foreach ($firstMessage['payload']['headers'] as $header) {
                            $headers[$header['name']] = $header['value'];
                        }
                    }
                    
                    echo "      📧 Betreff: " . ($headers['Subject'] ?? 'Kein Betreff') . "\n";
                    echo "      👤 Von: " . ($headers['From'] ?? 'Unbekannt') . "\n";
                    echo "      📅 Datum: " . ($headers['Date'] ?? 'Unbekannt') . "\n";
                    echo "      🏷️  Labels: " . implode(', ', $firstMessage['labelIds'] ?? []) . "\n";
                }
            }
            
        } catch (\Exception $e) {
            echo "      ❌ Fehler: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    // 5. Labels abrufen
    echo "🏷️  5. Verfügbare Gmail Labels...\n";
    
    try {
        $labels = $gmailService->getLabels();
        echo "   📊 Anzahl Labels: " . count($labels) . "\n";
        
        if (count($labels) > 0) {
            echo "   📋 Labels:\n";
            foreach ($labels as $label) {
                $type = $label['type'] ?? 'user';
                $messagesTotal = $label['messagesTotal'] ?? 0;
                $messagesUnread = $label['messagesUnread'] ?? 0;
                
                echo "      - {$label['name']} (ID: {$label['id']}, Typ: {$type})\n";
                echo "        📧 Gesamt: {$messagesTotal}, 📬 Ungelesen: {$messagesUnread}\n";
            }
        }
    } catch (\Exception $e) {
        echo "   ❌ Fehler beim Abrufen der Labels: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // 6. Verschiedene Sync-Strategien testen
    echo "🔄 6. Verschiedene Sync-Strategien testen...\n";
    
    $syncStrategies = [
        'Standard (50 E-Mails)' => ['maxResults' => 50],
        'Nur Posteingang (20 E-Mails)' => ['maxResults' => 20, 'labelIds' => ['INBOX']],
        'Nur ungelesene (10 E-Mails)' => ['maxResults' => 10, 'q' => 'is:unread'],
        'Letzte 7 Tage (30 E-Mails)' => ['maxResults' => 30, 'q' => 'newer_than:7d'],
        'Alle verfügbaren (100 E-Mails)' => ['maxResults' => 100],
    ];
    
    foreach ($syncStrategies as $strategyName => $options) {
        echo "   🔄 {$strategyName}:\n";
        
        try {
            $stats = $gmailService->syncEmails($options);
            
            echo "      ✅ Verarbeitet: {$stats['processed']}\n";
            echo "      ✅ Neue E-Mails: {$stats['new']}\n";
            echo "      ✅ Aktualisierte E-Mails: {$stats['updated']}\n";
            echo "      ✅ Fehler: {$stats['errors']}\n";
            
            if ($stats['new'] > 0) {
                echo "      🎉 Erfolgreich! {$stats['new']} neue E-Mails importiert.\n";
                break; // Stoppe bei erfolgreichem Import
            }
            
        } catch (\Exception $e) {
            echo "      ❌ Fehler: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    // 7. Finale Statistiken
    echo "📊 7. Finale Statistiken...\n";
    
    $totalEmails = GmailEmail::count();
    $unreadEmails = GmailEmail::unread()->count();
    $todayEmails = GmailEmail::whereDate('created_at', today())->count();
    $thisWeekEmails = GmailEmail::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
    
    echo "   📧 Gesamt E-Mails in DB: {$totalEmails}\n";
    echo "   📬 Ungelesene E-Mails: {$unreadEmails}\n";
    echo "   📅 Heute importiert: {$todayEmails}\n";
    echo "   📆 Diese Woche importiert: {$thisWeekEmails}\n";
    
    if ($settings->getGmailLastSync()) {
        echo "   🕐 Letzte Synchronisation: " . $settings->getGmailLastSync()->format('d.m.Y H:i:s') . "\n";
    }
    
    if ($settings->getGmailLastError()) {
        echo "   ⚠️  Letzter Fehler: " . $settings->getGmailLastError() . "\n";
    }
    
    echo "\n";
    
    // 8. Neueste E-Mails anzeigen
    if ($totalEmails > 0) {
        echo "📬 8. Neueste E-Mails (Top 5)...\n";
        
        $latestEmails = GmailEmail::latest('gmail_date')->take(5)->get();
        
        foreach ($latestEmails as $index => $email) {
            $fromEmail = is_array($email->from) && count($email->from) > 0 
                ? $email->from[0]['email'] ?? 'Unbekannt'
                : 'Unbekannt';
            
            $fromName = is_array($email->from) && count($email->from) > 0 
                ? $email->from[0]['name'] ?? ''
                : '';
            
            $from = $fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail;
            
            echo "   " . ($index + 1) . ". {$email->subject}\n";
            echo "      Von: {$from}\n";
            echo "      Datum: " . ($email->gmail_date ? $email->gmail_date->format('d.m.Y H:i') : 'Unbekannt') . "\n";
            echo "      Status: " . ($email->is_read ? 'Gelesen' : 'Ungelesen') . "\n";
            echo "      Labels: " . (is_array($email->labels) ? implode(', ', $email->labels) : 'Keine') . "\n";
            echo "      Snippet: " . substr($email->snippet ?? '', 0, 100) . "...\n";
            echo "\n";
        }
    } else {
        echo "📭 8. Keine E-Mails in der Datenbank gefunden.\n\n";
        
        echo "🔍 Mögliche Ursachen:\n";
        echo "   1. Das Gmail-Konto ist leer\n";
        echo "   2. Die Gmail API-Berechtigung ist zu restriktiv\n";
        echo "   3. Es gibt ein Problem mit der Gmail API-Abfrage\n";
        echo "   4. Die E-Mails sind in Labels, die nicht abgefragt werden\n";
        echo "\n";
        
        echo "🔧 Empfohlene Lösungen:\n";
        echo "   1. Prüfen Sie das Gmail-Konto manuell auf E-Mails\n";
        echo "   2. Erweitern Sie die Gmail API-Berechtigung\n";
        echo "   3. Testen Sie verschiedene Abfrage-Parameter\n";
        echo "   4. Prüfen Sie die Gmail API-Logs\n";
    }
    
    echo "\n";
    echo "✅ Detaillierte Gmail-Diagnose abgeschlossen!\n";
    echo "\n";
    echo "🔗 Nächste Schritte:\n";
    echo "   1. Besuchen Sie: https://sunnybill-test.test/admin/gmail-emails\n";
    echo "   2. Testen Sie den Sync-Button im Admin-Panel\n";
    echo "   3. Prüfen Sie die Gmail API-Berechtigung\n";
    echo "   4. Kontrollieren Sie das Gmail-Konto manuell\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "📍 Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    if ($e->getPrevious()) {
        echo "🔗 Ursprünglicher Fehler: " . $e->getPrevious()->getMessage() . "\n";
    }
    
    echo "\n";
    echo "🔧 Debug-Informationen:\n";
    echo "   Stack Trace:\n";
    echo $e->getTraceAsString();
    
    exit(1);
}
