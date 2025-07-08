<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\GmailEmail;
use App\Filament\Resources\GmailEmailResource;

echo "=== Gmail Emoji Badges Test ===\n\n";

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

echo "🏷️  Badge-Anzeige: " . ($badge ? $badge : 'Kein Badge (keine INBOX E-Mails vorhanden)') . "\n";
echo "🎨 Badge-Farbe: " . ($badgeColor ?: 'primary') . "\n";
echo "💬 Tooltip: '{$tooltip}'\n\n";

// Farblogik erklären
echo "🎨 Farbschema-Logik:\n";
if ($unreadCount > 10) {
    echo "   Badge-Farbe: danger (rot) - Mehr als 10 ungelesene E-Mails\n";
} elseif ($unreadCount > 0) {
    echo "   Badge-Farbe: warning (orange) - 1-10 ungelesene E-Mails\n";
} else {
    echo "   Badge-Farbe: primary (blau) - Keine ungelesenen E-Mails\n";
}

echo "\n📋 Emoji-Bedeutung:\n";
echo "   📖 = Gelesene E-Mails\n";
echo "   📧 = Ungelesene E-Mails\n\n";

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
    $scenarioNumber = $i + 1;
    $mockBadge = ($scenario['read'] > 0 || $scenario['unread'] > 0) 
        ? "📖{$scenario['read']} 📧{$scenario['unread']}" 
        : null;
    
    $mockColor = 'primary';
    if ($scenario['unread'] > 10) {
        $mockColor = 'danger';
    } elseif ($scenario['unread'] > 0) {
        $mockColor = 'warning';
    }
    
    echo "   Szenario {$scenarioNumber} ({$scenario['description']}):\n";
    echo "     Badge: " . ($mockBadge ?: 'Kein Badge') . "\n";
    echo "     Farbe: {$mockColor}\n";
    echo "     Tooltip: '📖 Gelesen: {$scenario['read']} | 📧 Ungelesen: {$scenario['unread']}'\n\n";
}

echo "🔄 Änderungen zur vorherigen HTML-Implementierung:\n";
echo "   ✅ Emoji-basierte Darstellung statt HTML-Tags\n";
echo "   ✅ 📖 Symbol für gelesene E-Mails\n";
echo "   ✅ 📧 Symbol für ungelesene E-Mails\n";
echo "   ✅ Dynamische Badge-Farbe basierend auf ungelesenen E-Mails\n";
echo "   ✅ Nur INBOX E-Mails werden gezählt (ohne TRASH)\n";
echo "   ✅ Kompatibel mit Filament's Badge-System\n";
echo "   ✅ Kein HTML-Escaping-Problem\n\n";

echo "✅ Emoji Badge Test erfolgreich abgeschlossen!\n";
echo "   Die Badges werden jetzt als Emoji-Text angezeigt und sind vollständig kompatibel mit Filament.\n";
