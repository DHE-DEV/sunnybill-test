<?php

require_once 'vendor/autoload.php';

use App\Models\GmailEmail;
use App\Services\GmailService;
use Illuminate\Support\Facades\DB;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail Posteingang Filter Test ===\n\n";

try {
    // 1. Aktuelle E-Mail-Statistiken anzeigen
    echo "1. Aktuelle E-Mail-Statistiken:\n";
    echo "   - Gesamt E-Mails: " . GmailEmail::count() . "\n";
    echo "   - E-Mails im Posteingang (INBOX): " . GmailEmail::whereJsonContains('labels', 'INBOX')->count() . "\n";
    echo "   - E-Mails im Papierkorb (TRASH): " . GmailEmail::whereJsonContains('labels', 'TRASH')->count() . "\n";
    echo "   - E-Mails im Spam: " . GmailEmail::whereJsonContains('labels', 'SPAM')->count() . "\n";
    echo "   - Gesendete E-Mails (SENT): " . GmailEmail::whereJsonContains('labels', 'SENT')->count() . "\n\n";

    // 2. E-Mails mit verschiedenen Labels anzeigen
    echo "2. E-Mails nach Labels:\n";
    
    $inboxEmails = GmailEmail::whereJsonContains('labels', 'INBOX')
        ->whereJsonDoesntContain('labels', 'TRASH')
        ->orderBy('gmail_date', 'desc')
        ->limit(5)
        ->get();
    
    echo "   Posteingang (ohne Papierkorb) - " . $inboxEmails->count() . " E-Mails:\n";
    foreach ($inboxEmails as $email) {
        $labels = implode(', ', $email->labels ?? []);
        echo "     - {$email->subject} (Labels: {$labels})\n";
    }
    echo "\n";
    
    $trashEmails = GmailEmail::whereJsonContains('labels', 'TRASH')
        ->orderBy('gmail_date', 'desc')
        ->limit(3)
        ->get();
    
    echo "   Papierkorb - " . $trashEmails->count() . " E-Mails:\n";
    foreach ($trashEmails as $email) {
        $labels = implode(', ', $email->labels ?? []);
        echo "     - {$email->subject} (Labels: {$labels})\n";
    }
    echo "\n";

    // 3. Test der neuen Filterlogik
    echo "3. Test der neuen Filterlogik:\n";
    
    // Simuliere die neue Query aus ListGmailEmails
    $filteredEmails = GmailEmail::whereJsonContains('labels', 'INBOX')
        ->whereJsonDoesntContain('labels', 'TRASH')
        ->count();
    
    echo "   - E-Mails die mit neuem Filter angezeigt werden: {$filteredEmails}\n";
    
    // Prüfe ob E-Mails aus Papierkorb ausgeschlossen werden
    $trashCount = GmailEmail::whereJsonContains('labels', 'TRASH')->count();
    echo "   - E-Mails im Papierkorb (werden ausgeschlossen): {$trashCount}\n\n";

    // 4. Test des GmailService mit INBOX-Filter
    echo "4. Test des GmailService:\n";
    $gmailService = new GmailService();
    
    if ($gmailService->isConfigured()) {
        echo "   - Gmail ist konfiguriert ✓\n";
        
        // Test der Connection
        $connectionTest = $gmailService->testConnection();
        if ($connectionTest['success']) {
            echo "   - Verbindung erfolgreich ✓\n";
            echo "   - Verbunden mit: {$connectionTest['email']}\n";
            
            // Teste die neue getMessages Methode (sollte standardmäßig nur INBOX abrufen)
            echo "   - Teste neue getMessages Methode...\n";
            
            // Ohne Parameter sollte nur INBOX abgerufen werden
            $messages = $gmailService->getMessages(['maxResults' => 5]);
            echo "   - Anzahl abgerufener Nachrichten: " . count($messages['messages'] ?? []) . "\n";
            
        } else {
            echo "   - Verbindung fehlgeschlagen: {$connectionTest['error']}\n";
        }
    } else {
        echo "   - Gmail ist nicht konfiguriert\n";
    }
    echo "\n";

    // 5. Zeige Beispiel-E-Mails mit verschiedenen Status
    echo "5. Beispiel-E-Mails nach Status:\n";
    
    $examples = [
        'Posteingang (sichtbar)' => GmailEmail::whereJsonContains('labels', 'INBOX')
            ->whereJsonDoesntContain('labels', 'TRASH')
            ->first(),
        'Papierkorb (versteckt)' => GmailEmail::whereJsonContains('labels', 'TRASH')
            ->first(),
        'Gesendet' => GmailEmail::whereJsonContains('labels', 'SENT')
            ->whereJsonDoesntContain('labels', 'TRASH')
            ->first(),
    ];
    
    foreach ($examples as $type => $email) {
        if ($email) {
            $labels = implode(', ', $email->labels ?? []);
            echo "   {$type}:\n";
            echo "     - Betreff: {$email->subject}\n";
            echo "     - Labels: {$labels}\n";
            echo "     - Datum: " . ($email->gmail_date ? $email->gmail_date->format('d.m.Y H:i') : 'Unbekannt') . "\n";
        } else {
            echo "   {$type}: Keine E-Mails gefunden\n";
        }
        echo "\n";
    }

    echo "=== Test abgeschlossen ===\n";
    echo "Die Änderungen sollten dafür sorgen, dass:\n";
    echo "1. Standardmäßig nur E-Mails aus dem Posteingang angezeigt werden\n";
    echo "2. E-Mails aus dem Papierkorb ausgeblendet werden\n";
    echo "3. Benutzer können über Filter andere Ordner auswählen\n";
    echo "4. Neue Synchronisationen holen standardmäßig nur INBOX-E-Mails\n\n";

} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
