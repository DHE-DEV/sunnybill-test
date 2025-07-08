<?php

require_once 'vendor/autoload.php';

use App\Models\GmailEmail;
use App\Services\GmailService;
use Illuminate\Support\Facades\DB;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail Label Synchronisation Test ===\n\n";

try {
    // 1. Aktuelle Labels in der Datenbank anzeigen
    echo "1. Aktuelle Labels in der Datenbank:\n";
    $emails = GmailEmail::orderBy('gmail_date', 'desc')->limit(5)->get();
    
    foreach ($emails as $email) {
        $labels = implode(', ', $email->labels ?? []);
        echo "   - {$email->subject} (Labels: {$labels})\n";
        echo "     is_trash: " . ($email->is_trash ? 'true' : 'false') . "\n";
    }
    echo "\n";

    // 2. Gmail Service initialisieren
    $gmailService = new GmailService();
    
    if (!$gmailService->isConfigured()) {
        echo "Gmail ist nicht konfiguriert!\n";
        exit(1);
    }

    $connectionTest = $gmailService->testConnection();
    if (!$connectionTest['success']) {
        echo "Gmail Verbindung fehlgeschlagen: {$connectionTest['error']}\n";
        exit(1);
    }

    echo "2. Gmail Verbindung erfolgreich: {$connectionTest['email']}\n\n";

    // 3. Synchronisation durchführen
    echo "3. Starte Synchronisation...\n";
    $stats = $gmailService->syncEmails(['maxResults' => 10]);
    
    echo "   - Verarbeitet: {$stats['processed']}\n";
    echo "   - Neu: {$stats['new']}\n";
    echo "   - Aktualisiert: {$stats['updated']}\n";
    echo "   - Fehler: {$stats['errors']}\n\n";

    // 4. Labels nach Synchronisation anzeigen
    echo "4. Labels nach Synchronisation:\n";
    $emails = GmailEmail::orderBy('gmail_date', 'desc')->limit(5)->get();
    
    foreach ($emails as $email) {
        $labels = implode(', ', $email->labels ?? []);
        echo "   - {$email->subject} (Labels: {$labels})\n";
        echo "     is_trash: " . ($email->is_trash ? 'true' : 'false') . "\n";
    }
    echo "\n";

    // 5. Statistiken anzeigen
    echo "5. Aktuelle Statistiken:\n";
    echo "   - Gesamt E-Mails: " . GmailEmail::count() . "\n";
    echo "   - E-Mails im Posteingang: " . GmailEmail::whereJsonContains('labels', 'INBOX')->count() . "\n";
    echo "   - E-Mails im Papierkorb: " . GmailEmail::whereJsonContains('labels', 'TRASH')->count() . "\n";
    echo "   - E-Mails mit is_trash=true: " . GmailEmail::where('is_trash', true)->count() . "\n\n";

    // 6. Teste spezifische E-Mail-IDs aus Gmail
    echo "6. Teste direkte Gmail API Abfrage für erste E-Mail:\n";
    $firstEmail = GmailEmail::first();
    if ($firstEmail) {
        echo "   - Lokale E-Mail ID: {$firstEmail->gmail_id}\n";
        echo "   - Lokale Labels: " . implode(', ', $firstEmail->labels ?? []) . "\n";
        
        // Direkte API-Abfrage
        $gmailData = $gmailService->getMessage($firstEmail->gmail_id);
        if ($gmailData) {
            $apiLabels = $gmailData['labelIds'] ?? [];
            echo "   - Gmail API Labels: " . implode(', ', $apiLabels) . "\n";
            
            if ($firstEmail->labels !== $apiLabels) {
                echo "   ⚠️  LABELS SIND UNTERSCHIEDLICH!\n";
                echo "   - Datenbank: " . json_encode($firstEmail->labels) . "\n";
                echo "   - Gmail API: " . json_encode($apiLabels) . "\n";
            } else {
                echo "   ✓ Labels sind synchron\n";
            }
        }
    }

    echo "\n=== Test abgeschlossen ===\n";

} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
