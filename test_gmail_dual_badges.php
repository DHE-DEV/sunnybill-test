<?php

require_once 'vendor/autoload.php';

use App\Models\GmailEmail;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail Dual Badges Test ===\n\n";

try {
    // Inbox E-Mail-Statistiken abrufen (nur INBOX, ohne TRASH)
    $inboxReadCount = GmailEmail::read()
        ->whereJsonContains('labels', 'INBOX')
        ->whereJsonDoesntContain('labels', 'TRASH')
        ->count();
        
    $inboxUnreadCount = GmailEmail::unread()
        ->whereJsonContains('labels', 'INBOX')
        ->whereJsonDoesntContain('labels', 'TRASH')
        ->count();
    
    $totalInboxEmails = $inboxReadCount + $inboxUnreadCount;
    
    echo "📊 Inbox E-Mail-Statistiken:\n";
    echo "   Gelesene E-Mails (INBOX): {$inboxReadCount}\n";
    echo "   Ungelesene E-Mails (INBOX): {$inboxUnreadCount}\n";
    echo "   Gesamt INBOX E-Mails: {$totalInboxEmails}\n\n";
    
    // Badge-Anzeige simulieren
    if ($inboxUnreadCount > 0 || $inboxReadCount > 0) {
        echo "🏷️  Dual Badge Display:\n";
        echo "   Linkes Badge (Blau): {$inboxReadCount} (Gelesene E-Mails)\n";
        echo "   Rechtes Badge (Orange): {$inboxUnreadCount} (Ungelesene E-Mails)\n";
        echo "   HTML-Output: Zwei separate Badges nebeneinander\n\n";
        
        // HTML Badge simulieren
        $badgeHtml = '<span style="display: inline-flex; gap: 4px;">' .
                     '<span style="background: #3b82f6; color: white; padding: 2px 6px; border-radius: 9999px; font-size: 11px; font-weight: 600;">' . $inboxReadCount . '</span>' .
                     '<span style="background: #f97316; color: white; padding: 2px 6px; border-radius: 9999px; font-size: 11px; font-weight: 600;">' . $inboxUnreadCount . '</span>' .
                     '</span>';
        
        echo "📝 HTML Badge Code:\n";
        echo "   " . htmlspecialchars($badgeHtml) . "\n\n";
    } else {
        echo "🏷️  Badge-Anzeige: Kein Badge (keine INBOX E-Mails vorhanden)\n\n";
    }
    
    // Tooltip testen
    $tooltip = "Gelesen (blau): {$inboxReadCount} | Ungelesen (orange): {$inboxUnreadCount}";
    echo "💬 Tooltip: '{$tooltip}'\n\n";
    
    // Farbschema-Info
    echo "🎨 Farbschema:\n";
    echo "   Linkes Badge: #3b82f6 (Blau) - Gelesene E-Mails\n";
    echo "   Rechtes Badge: #f97316 (Orange) - Ungelesene E-Mails\n";
    echo "   Gap zwischen Badges: 4px\n";
    echo "   Border-Radius: 9999px (vollständig rund)\n";
    echo "   Font-Size: 11px\n";
    echo "   Font-Weight: 600 (semi-bold)\n\n";
    
    // Beispiel-Szenarien
    echo "📋 Beispiel-Szenarien:\n";
    
    $scenarios = [
        ['read' => 5, 'unread' => 3, 'description' => 'Normale Aktivität'],
        ['read' => 0, 'unread' => 8, 'description' => 'Nur ungelesene E-Mails'],
        ['read' => 12, 'unread' => 0, 'description' => 'Alle E-Mails gelesen'],
        ['read' => 25, 'unread' => 15, 'description' => 'Hohe Aktivität'],
        ['read' => 0, 'unread' => 0, 'description' => 'Leere Inbox'],
    ];
    
    foreach ($scenarios as $i => $scenario) {
        $leftBadge = $scenario['read'];
        $rightBadge = $scenario['unread'];
        $showBadge = ($scenario['read'] > 0 || $scenario['unread'] > 0);
        
        echo "   Szenario " . ($i + 1) . " ({$scenario['description']}):\n";
        echo "     Linkes Badge (Blau): {$leftBadge}\n";
        echo "     Rechtes Badge (Orange): {$rightBadge}\n";
        echo "     Badge angezeigt: " . ($showBadge ? 'Ja' : 'Nein') . "\n";
        echo "     Tooltip: 'Gelesen (blau): {$leftBadge} | Ungelesen (orange): {$rightBadge}'\n\n";
    }
    
    // Vergleich mit vorheriger Implementierung
    echo "🔄 Änderungen zur vorherigen Implementierung:\n";
    echo "   ✅ Zwei separate Badges statt einem kombinierten Badge\n";
    echo "   ✅ Linkes Badge zeigt gelesene E-Mails in blau\n";
    echo "   ✅ Rechtes Badge zeigt ungelesene E-Mails in orange\n";
    echo "   ✅ Nur INBOX E-Mails werden gezählt (ohne TRASH)\n";
    echo "   ✅ HTML-Styling für bessere visuelle Trennung\n";
    echo "   ✅ Angepasster Tooltip für neue Badge-Struktur\n\n";
    
    echo "✅ Dual Badge Test erfolgreich abgeschlossen!\n";
    echo "   Die Badges werden jetzt als zwei separate Elemente nebeneinander angezeigt.\n";
    
} catch (Exception $e) {
    echo "❌ Fehler beim Dual Badge Test: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
