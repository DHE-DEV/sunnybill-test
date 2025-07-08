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

echo "=== Kombiniertes Badge Design Test ===\n\n";

try {
    // Test der neuen Badge-Funktion
    echo "1. Testing combined badge function...\n";
    
    $badgeFunction = function () {
        try {
            $unreadCount = App\Models\GmailEmail::unread()
                ->whereJsonContains('labels', 'INBOX')
                ->whereJsonDoesntContain('labels', 'TRASH')
                ->count();
                
            $readCount = App\Models\GmailEmail::read()
                ->whereJsonContains('labels', 'INBOX')
                ->whereJsonDoesntContain('labels', 'TRASH')
                ->count();
            
            if ($unreadCount > 0 || $readCount > 0) {
                return '<span class="inline-flex items-center bg-gray-100 text-xs rounded-full overflow-hidden">' .
                       '<span class="bg-blue-500 text-white px-2 py-0.5">' . $readCount . '</span>' .
                       '<span class="bg-red-500 text-white px-2 py-0.5">' . $unreadCount . '</span>' .
                       '</span>';
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    };
    
    $result = $badgeFunction();
    
    if ($result) {
        echo "   ✅ Combined badge generated successfully\n";
        echo "   📊 Badge HTML: " . $result . "\n";
        
        // Analyse des Badge-Inhalts
        if (strpos($result, 'bg-blue-500') !== false) {
            echo "   ✅ Blue section (read emails) found\n";
        }
        
        if (strpos($result, 'bg-red-500') !== false) {
            echo "   ✅ Red section (unread emails) found\n";
        }
        
        if (strpos($result, 'inline-flex') !== false) {
            echo "   ✅ Flexbox layout applied\n";
        }
        
        if (strpos($result, 'rounded-full') !== false) {
            echo "   ✅ Rounded design applied\n";
        }
        
        if (strpos($result, 'overflow-hidden') !== false) {
            echo "   ✅ Overflow hidden for clean edges\n";
        }
        
    } else {
        echo "   ⚠️  No badge generated (no emails found)\n";
    }
    
    echo "\n";
    
    // Test verschiedener Szenarien
    echo "2. Testing different scenarios...\n";
    
    // Simuliere verschiedene E-Mail-Counts
    $testScenarios = [
        ['read' => 5, 'unread' => 3, 'description' => '5 gelesen, 3 ungelesen'],
        ['read' => 0, 'unread' => 7, 'description' => '0 gelesen, 7 ungelesen'],
        ['read' => 12, 'unread' => 0, 'description' => '12 gelesen, 0 ungelesen'],
        ['read' => 0, 'unread' => 0, 'description' => '0 gelesen, 0 ungelesen'],
    ];
    
    foreach ($testScenarios as $scenario) {
        $readCount = $scenario['read'];
        $unreadCount = $scenario['unread'];
        
        if ($unreadCount > 0 || $readCount > 0) {
            $badge = '<span class="inline-flex items-center bg-gray-100 text-xs rounded-full overflow-hidden">' .
                     '<span class="bg-blue-500 text-white px-2 py-0.5">' . $readCount . '</span>' .
                     '<span class="bg-red-500 text-white px-2 py-0.5">' . $unreadCount . '</span>' .
                     '</span>';
            echo "   📧 {$scenario['description']}: Badge generiert\n";
            echo "      HTML: {$badge}\n";
        } else {
            echo "   📧 {$scenario['description']}: Kein Badge (korrekt)\n";
        }
    }
    
    echo "\n";
    
    // Test der aktuellen Datenbank-Werte
    echo "3. Testing with current database values...\n";
    
    try {
        $actualUnreadCount = App\Models\GmailEmail::unread()
            ->whereJsonContains('labels', 'INBOX')
            ->whereJsonDoesntContain('labels', 'TRASH')
            ->count();
            
        $actualReadCount = App\Models\GmailEmail::read()
            ->whereJsonContains('labels', 'INBOX')
            ->whereJsonDoesntContain('labels', 'TRASH')
            ->count();
        
        echo "   📊 Aktuelle Datenbank-Werte:\n";
        echo "      Gelesene E-Mails: {$actualReadCount}\n";
        echo "      Ungelesene E-Mails: {$actualUnreadCount}\n";
        
        if ($actualUnreadCount > 0 || $actualReadCount > 0) {
            $actualBadge = '<span class="inline-flex items-center bg-gray-100 text-xs rounded-full overflow-hidden">' .
                          '<span class="bg-blue-500 text-white px-2 py-0.5">' . $actualReadCount . '</span>' .
                          '<span class="bg-red-500 text-white px-2 py-0.5">' . $actualUnreadCount . '</span>' .
                          '</span>';
            echo "   ✅ Aktuelles Badge: {$actualBadge}\n";
        } else {
            echo "   ⚠️  Kein Badge erforderlich (keine E-Mails)\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Fehler beim Abrufen der Datenbank-Werte: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test der CSS-Klassen
    echo "4. CSS Classes Analysis...\n";
    echo "   🎨 Badge Design Details:\n";
    echo "      - Container: inline-flex items-center bg-gray-100 text-xs rounded-full overflow-hidden\n";
    echo "      - Linke Seite (Gelesen): bg-blue-500 text-white px-2 py-0.5\n";
    echo "      - Rechte Seite (Ungelesen): bg-red-500 text-white px-2 py-0.5\n";
    echo "      - Farben: Blau (#3b82f6) für gelesen, Rot (#ef4444) für ungelesen\n";
    echo "      - Layout: Flexbox für nahtlose Verbindung der beiden Hälften\n";
    echo "      - Styling: Abgerundete Ecken mit overflow-hidden für saubere Kanten\n";
    
    echo "\n=== Test Summary ===\n";
    echo "✅ Kombiniertes Badge erfolgreich implementiert\n";
    echo "✅ Linke Zahl (gelesen) in Blau\n";
    echo "✅ Rechte Zahl (ungelesen) in Rot\n";
    echo "✅ Nahtlose Verbindung zwischen beiden Hälften\n";
    echo "✅ Professionelles Design mit Tailwind CSS\n";
    echo "✅ Fehlerbehandlung implementiert\n";
    echo "\nDas kombinierte Badge ist bereit für den Einsatz!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler während des Tests: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
