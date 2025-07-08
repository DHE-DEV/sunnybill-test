<?php

require_once 'vendor/autoload.php';

use App\Models\GmailEmail;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail Badge Read Count Test ===\n\n";

try {
    // Aktuelle E-Mail-Statistiken abrufen
    $totalEmails = GmailEmail::where('is_trash', false)->count();
    $readCount = GmailEmail::read()->where('is_trash', false)->count();
    $unreadCount = GmailEmail::unread()->where('is_trash', false)->count();
    
    echo "📊 E-Mail-Statistiken:\n";
    echo "   Gesamt E-Mails (ohne Papierkorb): {$totalEmails}\n";
    echo "   Gelesene E-Mails: {$readCount}\n";
    echo "   Ungelesene E-Mails: {$unreadCount}\n\n";
    
    // Badge-Format testen
    if ($unreadCount > 0 || $readCount > 0) {
        $badgeText = $readCount . '/' . $unreadCount;
        echo "🏷️  Badge-Anzeige: '{$badgeText}'\n";
        echo "   Format: [Gelesen]/[Ungelesen]\n";
        echo "   Links: {$readCount} (Gelesene E-Mails)\n";
        echo "   Rechts: {$unreadCount} (Ungelesene E-Mails)\n\n";
    } else {
        echo "🏷️  Badge-Anzeige: Kein Badge (keine E-Mails vorhanden)\n\n";
    }
    
    // Badge-Farbe testen
    if ($unreadCount > 10) {
        $badgeColor = 'danger';
        echo "🔴 Badge-Farbe: {$badgeColor} (mehr als 10 ungelesene E-Mails)\n";
    } elseif ($unreadCount > 0) {
        $badgeColor = 'warning';
        echo "🟡 Badge-Farbe: {$badgeColor} (ungelesene E-Mails vorhanden)\n";
    } else {
        $badgeColor = 'primary';
        echo "🔵 Badge-Farbe: {$badgeColor} (nur gelesene E-Mails)\n";
    }
    
    // Tooltip testen
    $tooltip = "Gelesen: {$readCount} | Ungelesen: {$unreadCount}";
    echo "💬 Tooltip: '{$tooltip}'\n\n";
    
    // Beispiel-Szenarien
    echo "📋 Beispiel-Szenarien:\n";
    
    $scenarios = [
        ['read' => 5, 'unread' => 3],
        ['read' => 0, 'unread' => 8],
        ['read' => 12, 'unread' => 0],
        ['read' => 25, 'unread' => 15],
    ];
    
    foreach ($scenarios as $i => $scenario) {
        $badge = $scenario['read'] . '/' . $scenario['unread'];
        $color = $scenario['unread'] > 10 ? 'danger' : ($scenario['unread'] > 0 ? 'warning' : 'primary');
        $tooltip = "Gelesen: {$scenario['read']} | Ungelesen: {$scenario['unread']}";
        
        echo "   Szenario " . ($i + 1) . ": Badge '{$badge}' ({$color}) - {$tooltip}\n";
    }
    
    echo "\n✅ Badge-Test erfolgreich abgeschlossen!\n";
    echo "   Die linke Zahl zeigt jetzt die Anzahl der gelesenen E-Mails an.\n";
    echo "   Die rechte Zahl zeigt die Anzahl der ungelesenen E-Mails an.\n";
    
} catch (Exception $e) {
    echo "❌ Fehler beim Badge-Test: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
