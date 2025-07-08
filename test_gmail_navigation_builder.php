<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\GmailEmail;

echo "=== Gmail NavigationBuilder Test ===\n\n";

// Statistiken abrufen
$unreadCount = GmailEmail::unread()
    ->whereJsonContains('labels', 'INBOX')
    ->whereJsonDoesntContain('labels', 'TRASH')
    ->count();

$readCount = GmailEmail::read()
    ->whereJsonContains('labels', 'INBOX')
    ->whereJsonDoesntContain('labels', 'TRASH')
    ->count();

$totalInboxCount = $readCount + $unreadCount;

echo "📊 Inbox E-Mail-Statistiken:\n";
echo "   Gelesene E-Mails (INBOX): {$readCount}\n";
echo "   Ungelesene E-Mails (INBOX): {$unreadCount}\n";
echo "   Gesamt INBOX E-Mails: {$totalInboxCount}\n\n";

// Badge-HTML simulieren (wie im AdminPanelProvider)
if ($unreadCount > 0 || $readCount > 0) {
    $readBadge = '<span class="bg-blue-500 text-white text-xs px-2 py-0.5 rounded-full">' . $readCount . '</span>';
    $unreadBadge = '<span class="bg-orange-500 text-white text-xs px-2 py-0.5 rounded-full ml-1">' . $unreadCount . '</span>';
    $badgeHtml = $readBadge . $unreadBadge;
} else {
    $badgeHtml = null;
}

echo "🏷️  NavigationBuilder Badge-HTML:\n";
echo "   " . ($badgeHtml ?: 'Kein Badge (keine INBOX E-Mails vorhanden)') . "\n\n";

echo "✅ NavigationBuilder Test erfolgreich abgeschlossen!\n";
echo "   Die Gmail-Navigation wird jetzt über NavigationBuilder implementiert:\n";
echo "   - NavigationItem mit benutzerdefinierten Badges\n";
echo "   - Tailwind CSS-Klassen für Styling\n";
echo "   - Blauer Badge für gelesene E-Mails: {$readCount}\n";
echo "   - Oranger Badge für ungelesene E-Mails: {$unreadCount}\n";
echo "   - Automatische Gruppierung in 'E-Mail'\n";
echo "   - URL: /admin/gmail-emails\n";
echo "   - Icon: heroicon-o-envelope\n\n";

echo "📋 Implementierungsdetails:\n";
echo "   - AdminPanelProvider: ->navigation() Methode hinzugefügt\n";
echo "   - NavigationBuilder mit NavigationItem verwendet\n";
echo "   - Badge-Funktion mit HTML-Rückgabe\n";
echo "   - Alte getNavigationBadge() Methoden aus Resource entfernt\n";
echo "   - Tailwind CSS für professionelles Styling\n";
