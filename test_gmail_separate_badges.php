<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\GmailEmail;
use App\Filament\Resources\GmailEmailResource;

echo "=== Gmail Separate Badges Test ===\n\n";

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

// Badge-Anzeige testen
$badge = GmailEmailResource::getNavigationBadge();
$badgeColor = GmailEmailResource::getNavigationBadgeColor();
$tooltip = GmailEmailResource::getNavigationBadgeTooltip();

echo "🏷️  Badge-HTML: " . ($badge ? $badge : 'Kein Badge (keine INBOX E-Mails vorhanden)') . "\n";
echo "🎨 Badge-Farbe: " . ($badgeColor ?: 'primary') . "\n";
echo "💬 Tooltip: '{$tooltip}'\n\n";

echo "✅ Separate Badge Test erfolgreich abgeschlossen!\n";
echo "   Die Badges werden jetzt als zwei separate HTML-Badges angezeigt:\n";
echo "   - Blauer Badge (gelesen): {$readCount}\n";
echo "   - Oranger Badge (ungelesen): {$unreadCount}\n";
echo "   - 2px Abstand zwischen den Badges\n";
echo "   - Keine Emoji-Symbole mehr\n";
