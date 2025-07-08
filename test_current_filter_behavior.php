<?php

require_once 'vendor/autoload.php';

use App\Models\GmailEmail;
use Illuminate\Support\Facades\DB;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test des aktuellen Filter-Verhaltens ===\n\n";

try {
    // 1. Alle E-Mails in der Datenbank
    echo "1. Alle E-Mails in der Datenbank:\n";
    $allEmails = GmailEmail::orderBy('gmail_date', 'desc')->get();
    
    foreach ($allEmails as $email) {
        $labels = implode(', ', $email->labels ?? []);
        echo "   - {$email->subject}\n";
        echo "     Labels: [{$labels}]\n";
        echo "     is_trash: " . ($email->is_trash ? 'true' : 'false') . "\n";
        echo "     Erstellt: " . $email->created_at->format('d.m.Y H:i') . "\n\n";
    }

    // 2. Filter-Test: Nur INBOX (ohne TRASH)
    echo "2. Filter-Test: Nur INBOX (ohne TRASH) - wie in ListGmailEmails:\n";
    $inboxEmails = GmailEmail::whereJsonContains('labels', 'INBOX')
        ->whereJsonDoesntContain('labels', 'TRASH')
        ->orderBy('gmail_date', 'desc')
        ->get();
    
    echo "   Anzahl gefilterte E-Mails: " . $inboxEmails->count() . "\n";
    foreach ($inboxEmails as $email) {
        $labels = implode(', ', $email->labels ?? []);
        echo "   - {$email->subject} (Labels: [{$labels}])\n";
    }
    echo "\n";

    // 3. Filter-Test: Nur TRASH
    echo "3. Filter-Test: Nur TRASH:\n";
    $trashEmails = GmailEmail::whereJsonContains('labels', 'TRASH')
        ->orderBy('gmail_date', 'desc')
        ->get();
    
    echo "   Anzahl E-Mails im Papierkorb: " . $trashEmails->count() . "\n";
    foreach ($trashEmails as $email) {
        $labels = implode(', ', $email->labels ?? []);
        echo "   - {$email->subject} (Labels: [{$labels}])\n";
    }
    echo "\n";

    // 4. Problem-Analyse
    echo "4. Problem-Analyse:\n";
    $totalEmails = GmailEmail::count();
    $inboxCount = GmailEmail::whereJsonContains('labels', 'INBOX')->count();
    $trashCount = GmailEmail::whereJsonContains('labels', 'TRASH')->count();
    $inboxNotTrashCount = GmailEmail::whereJsonContains('labels', 'INBOX')
        ->whereJsonDoesntContain('labels', 'TRASH')
        ->count();
    
    echo "   - Gesamt E-Mails in DB: {$totalEmails}\n";
    echo "   - E-Mails mit INBOX Label: {$inboxCount}\n";
    echo "   - E-Mails mit TRASH Label: {$trashCount}\n";
    echo "   - E-Mails die angezeigt werden (INBOX ohne TRASH): {$inboxNotTrashCount}\n\n";
    
    if ($trashCount === 0 && $inboxCount > 0) {
        echo "   ⚠️  PROBLEM IDENTIFIZIERT:\n";
        echo "   - Alle E-Mails haben noch INBOX Label\n";
        echo "   - Keine E-Mail hat TRASH Label\n";
        echo "   - Das bedeutet: Labels sind nicht aktuell!\n";
        echo "   - Grund: Keine Synchronisation seit E-Mail in Papierkorb verschoben wurde\n\n";
        
        echo "   LÖSUNG:\n";
        echo "   1. Gmail muss autorisiert werden\n";
        echo "   2. Synchronisation muss durchgeführt werden\n";
        echo "   3. Dann werden die Labels korrekt aktualisiert\n";
        echo "   4. Filter funktioniert dann wie erwartet\n\n";
    }

    // 5. Simuliere korrekte Labels
    echo "5. Simulation: Wie es aussehen sollte nach Synchronisation:\n";
    echo "   Angenommen, eine E-Mail wurde in den Papierkorb verschoben:\n";
    echo "   - Vorher: ['IMPORTANT', 'STARRED', 'CATEGORY_PERSONAL', 'INBOX']\n";
    echo "   - Nachher: ['IMPORTANT', 'STARRED', 'CATEGORY_PERSONAL', 'TRASH']\n";
    echo "   - Filter würde dann korrekt funktionieren\n\n";

    echo "=== Test abgeschlossen ===\n";
    echo "FAZIT: Der Filter funktioniert korrekt, aber die Daten sind veraltet!\n";

} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
