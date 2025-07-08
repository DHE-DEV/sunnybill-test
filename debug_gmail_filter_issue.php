<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\CompanySetting;
use App\Services\GmailService;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail Filter Debug ===\n\n";

try {
    $settings = CompanySetting::current();
    $gmailService = new GmailService();
    
    echo "1. Filter-Einstellungen:\n";
    echo "   - INBOX-Filter aktiviert: " . ($settings->gmail_filter_inbox ? 'Ja ❌' : 'Nein ✅') . "\n";
    echo "\n";
    
    echo "2. E-Mail-Abruf ohne Filter:\n";
    $messagesWithoutFilter = $gmailService->getMessages(['maxResults' => 10]);
    $countWithoutFilter = count($messagesWithoutFilter['messages'] ?? []);
    $estimateWithoutFilter = $messagesWithoutFilter['resultSizeEstimate'] ?? 0;
    
    echo "   📧 Gefundene E-Mails: {$countWithoutFilter}\n";
    echo "   📊 Geschätzte Gesamtzahl: {$estimateWithoutFilter}\n";
    
    if ($countWithoutFilter > 0) {
        echo "   ✅ E-Mails sind verfügbar!\n";
        
        // Erste E-Mail Details anzeigen
        $firstMessage = $messagesWithoutFilter['messages'][0];
        $messageDetails = $gmailService->getMessage($firstMessage['id']);
        
        if ($messageDetails) {
            $labels = $messageDetails['labelIds'] ?? [];
            $headers = [];
            if (isset($messageDetails['payload']['headers'])) {
                foreach ($messageDetails['payload']['headers'] as $header) {
                    $headers[$header['name']] = $header['value'];
                }
            }
            
            echo "\n   📋 Beispiel E-Mail:\n";
            echo "      - Gmail-ID: " . $firstMessage['id'] . "\n";
            echo "      - Betreff: " . ($headers['Subject'] ?? 'Kein Betreff') . "\n";
            echo "      - Von: " . ($headers['From'] ?? 'Unbekannt') . "\n";
            echo "      - Labels: " . implode(', ', $labels) . "\n";
            echo "      - Hat INBOX: " . (in_array('INBOX', $labels) ? 'Ja' : 'Nein') . "\n";
            echo "      - Ist UNREAD: " . (in_array('UNREAD', $labels) ? 'Ja' : 'Nein') . "\n";
        }
    } else {
        echo "   ❌ Keine E-Mails gefunden!\n";
        echo "   💡 Mögliche Ursachen:\n";
        echo "      - Gmail-Konto ist leer\n";
        echo "      - Alle E-Mails sind archiviert\n";
        echo "      - Berechtigung reicht nicht aus\n";
    }
    
    echo "\n3. E-Mail-Abruf mit Filter (-in:inbox):\n";
    if ($settings->gmail_filter_inbox) {
        $messagesWithFilter = $gmailService->getMessages(['maxResults' => 10, 'q' => '-in:inbox']);
        $countWithFilter = count($messagesWithFilter['messages'] ?? []);
        $estimateWithFilter = $messagesWithFilter['resultSizeEstimate'] ?? 0;
        
        echo "   📧 Gefundene E-Mails: {$countWithFilter}\n";
        echo "   📊 Geschätzte Gesamtzahl: {$estimateWithFilter}\n";
        
        if ($countWithFilter === 0 && $countWithoutFilter > 0) {
            echo "   ❌ PROBLEM: Filter ist zu restriktiv!\n";
            echo "   💡 LÖSUNG: Deaktiviere den INBOX-Filter\n";
        } elseif ($countWithFilter > 0) {
            echo "   ✅ Filter funktioniert korrekt\n";
        }
    } else {
        echo "   ⚠️  INBOX-Filter ist deaktiviert - wird übersprungen\n";
    }
    
    echo "\n4. Alternative Filter testen:\n";
    
    // Test verschiedene Filter
    $filters = [
        'Alle E-Mails' => '',
        'Nur INBOX' => 'in:inbox',
        'Nur SENT' => 'in:sent',
        'Nur UNREAD' => 'is:unread',
        'Nur mit Anhängen' => 'has:attachment',
        'Letzte 7 Tage' => 'newer_than:7d',
    ];
    
    foreach ($filters as $name => $query) {
        $options = ['maxResults' => 5];
        if ($query) {
            $options['q'] = $query;
        }
        
        $messages = $gmailService->getMessages($options);
        $count = count($messages['messages'] ?? []);
        $estimate = $messages['resultSizeEstimate'] ?? 0;
        
        echo "   {$name}: {$count} E-Mails (geschätzt: {$estimate})\n";
    }
    
    echo "\n5. Empfehlungen:\n";
    
    if ($countWithoutFilter === 0) {
        echo "   ❌ Keine E-Mails im Gmail-Konto gefunden\n";
        echo "   💡 Prüfe:\n";
        echo "      - Ist das richtige Gmail-Konto verbunden?\n";
        echo "      - Hat das Konto E-Mails?\n";
        echo "      - Sind die Berechtigungen korrekt?\n";
    } elseif ($settings->gmail_filter_inbox && $countWithoutFilter > 0) {
        echo "   🔧 INBOX-Filter deaktivieren:\n";
        echo "      1. Gehe zu Filament Admin → Firmeneinstellungen\n";
        echo "      2. Tab 'Gmail-Integration'\n";
        echo "      3. Deaktiviere 'INBOX E-Mails filtern'\n";
        echo "      4. Speichern\n";
        echo "      5. E-Mail-Sync erneut versuchen\n";
    } else {
        echo "   ✅ Konfiguration sieht gut aus\n";
        echo "   🔄 Versuche eine vollständige Synchronisation\n";
    }
    
    echo "\n=== Debug abgeschlossen ===\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
