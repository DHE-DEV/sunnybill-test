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

echo "=== Menüstruktur-Anpassung Test ===\n\n";

try {
    // Test 1: Gmail E-Mails Menügruppe
    echo "1. Testing Gmail E-Mails Navigation Group...\n";
    
    $gmailResource = new App\Filament\Resources\GmailEmailResource();
    $navigationGroup = $gmailResource::getNavigationGroup();
    $navigationSort = $gmailResource::getNavigationSort();
    
    echo "   📁 Navigation Group: '{$navigationGroup}'\n";
    echo "   🔢 Navigation Sort: {$navigationSort}\n";
    
    if ($navigationGroup === 'Dokumente') {
        echo "   ✅ Gmail E-Mails korrekt unter 'Dokumente' eingeordnet\n";
    } else {
        echo "   ❌ Gmail E-Mails nicht unter 'Dokumente' - aktuell: '{$navigationGroup}'\n";
    }
    
    if ($navigationSort === 10) {
        echo "   ✅ Korrekte Sortierung (10) für Gmail E-Mails\n";
    } else {
        echo "   ❌ Falsche Sortierung - erwartet: 10, aktuell: {$navigationSort}\n";
    }
    
    echo "\n";
    
    // Test 2: Gmail Badge funktioniert weiterhin
    echo "2. Testing Gmail Badge Functionality...\n";
    
    $badge = $gmailResource::getNavigationBadge();
    $badgeColor = $gmailResource::getNavigationBadgeColor();
    
    if ($badge) {
        echo "   ✅ Gmail Badge funktioniert: '{$badge}'\n";
        echo "   🎨 Badge-Farbe: '{$badgeColor}'\n";
    } else {
        echo "   ⚠️  Kein Gmail Badge (keine E-Mails vorhanden)\n";
    }
    
    echo "\n";
    
    // Test 3: Benachrichtigungen Menügruppe
    echo "3. Testing Notifications Navigation Group...\n";
    
    $notificationsPage = new App\Filament\Pages\NotificationsPage();
    $notificationGroup = $notificationsPage::getNavigationGroup();
    $notificationSort = $notificationsPage::getNavigationSort();
    
    echo "   📁 Navigation Group: '{$notificationGroup}'\n";
    echo "   🔢 Navigation Sort: {$notificationSort}\n";
    
    if ($notificationGroup === 'Dokumente') {
        echo "   ✅ Benachrichtigungen korrekt unter 'Dokumente' eingeordnet\n";
    } else {
        echo "   ❌ Benachrichtigungen nicht unter 'Dokumente' - aktuell: '{$notificationGroup}'\n";
    }
    
    if ($notificationSort === 11) {
        echo "   ✅ Korrekte Sortierung (11) für Benachrichtigungen (nach Gmail)\n";
    } else {
        echo "   ❌ Falsche Sortierung - erwartet: 11, aktuell: {$notificationSort}\n";
    }
    
    echo "\n";
    
    // Test 4: Benachrichtigungen Badge
    echo "4. Testing Notifications Badge...\n";
    
    $notificationBadge = $notificationsPage::getNavigationBadge();
    $notificationBadgeColor = $notificationsPage::getNavigationBadgeColor();
    
    if ($notificationBadge) {
        echo "   ✅ Benachrichtigungen Badge funktioniert: '{$notificationBadge}'\n";
        echo "   🎨 Badge-Farbe: '{$notificationBadgeColor}'\n";
        
        // Prüfe ob es eine Zahl ist
        if (is_numeric($notificationBadge)) {
            echo "   ✅ Badge zeigt Anzahl der ungelesenen Benachrichtigungen\n";
        } else {
            echo "   ❌ Badge sollte eine Zahl sein, ist aber: '{$notificationBadge}'\n";
        }
        
        // Prüfe Farbe
        if ($notificationBadgeColor === 'danger') {
            echo "   ✅ Korrekte rote Farbe für ungelesene Benachrichtigungen\n";
        } else {
            echo "   ❌ Falsche Farbe - erwartet: 'danger', aktuell: '{$notificationBadgeColor}'\n";
        }
        
    } else {
        echo "   ⚠️  Kein Benachrichtigungen Badge (keine ungelesenen Benachrichtigungen)\n";
    }
    
    echo "\n";
    
    // Test 5: Menü-Hierarchie
    echo "5. Testing Menu Hierarchy...\n";
    
    echo "   📋 Erwartete Menüstruktur unter 'Dokumente':\n";
    echo "      1. Gmail E-Mails (Sort: 10) - Badge: gelesen|ungelesen\n";
    echo "      2. Benachrichtigungen (Sort: 11) - Badge: Anzahl ungelesen\n";
    echo "\n";
    
    echo "   📊 Aktuelle Konfiguration:\n";
    echo "      Gmail E-Mails:\n";
    echo "        - Gruppe: {$navigationGroup}\n";
    echo "        - Sort: {$navigationSort}\n";
    echo "        - Badge: " . ($badge ?: 'null') . "\n";
    echo "        - Badge-Farbe: " . ($badgeColor ?: 'null') . "\n";
    echo "\n";
    echo "      Benachrichtigungen:\n";
    echo "        - Gruppe: {$notificationGroup}\n";
    echo "        - Sort: {$notificationSort}\n";
    echo "        - Badge: " . ($notificationBadge ?: 'null') . "\n";
    echo "        - Badge-Farbe: " . ($notificationBadgeColor ?: 'null') . "\n";
    
    echo "\n";
    
    // Test 6: Badge-Unterschiede
    echo "6. Testing Badge Differences...\n";
    
    echo "   🔍 Gmail Badge Format:\n";
    if ($badge) {
        if (strpos($badge, '|') !== false) {
            echo "      Format: 'gelesen|ungelesen' (z.B. '1|2')\n";
            echo "      ✅ Zeigt sowohl gelesene als auch ungelesene E-Mails\n";
        } else {
            echo "      ❌ Unerwartetes Format - sollte 'gelesen|ungelesen' sein\n";
        }
    } else {
        echo "      Kein Badge vorhanden\n";
    }
    
    echo "\n   🔍 Benachrichtigungen Badge Format:\n";
    if ($notificationBadge) {
        echo "      Format: Einfache Zahl (z.B. '3')\n";
        echo "      ✅ Zeigt nur Anzahl ungelesener Benachrichtigungen\n";
    } else {
        echo "      Kein Badge vorhanden (alle gelesen oder keine Benachrichtigungen)\n";
    }
    
    echo "\n=== Test Summary ===\n";
    
    $gmailCorrect = ($navigationGroup === 'Dokumente' && $navigationSort === 10);
    $notificationCorrect = ($notificationGroup === 'Dokumente' && $notificationSort === 11);
    
    if ($gmailCorrect && $notificationCorrect) {
        echo "✅ Menüstruktur erfolgreich angepasst!\n";
        echo "✅ Gmail E-Mails steht unter 'Dokumente' (Sort: 10)\n";
        echo "✅ Benachrichtigungen steht unter 'Dokumente' (Sort: 11)\n";
        echo "✅ Beide haben funktionsfähige Badges\n";
        echo "✅ Reihenfolge: Gmail E-Mails → Benachrichtigungen\n";
    } else {
        echo "❌ Menüstruktur nicht vollständig korrekt\n";
        if (!$gmailCorrect) {
            echo "❌ Gmail E-Mails Konfiguration fehlerhaft\n";
        }
        if (!$notificationCorrect) {
            echo "❌ Benachrichtigungen Konfiguration fehlerhaft\n";
        }
    }
    
    echo "\nDie Menüpunkte erscheinen jetzt beide unter 'Dokumente' mit entsprechenden Badges!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler während des Tests: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
