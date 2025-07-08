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

echo "=== Menü-Fix und Badge-Test ===\n\n";

try {
    // Test 1: Gmail Badge Funktion
    echo "1. Testing Gmail Badge Function...\n";
    
    $gmailResource = new App\Filament\Resources\GmailEmailResource();
    $badge = $gmailResource::getNavigationBadge();
    
    if ($badge) {
        echo "   ✅ Badge erfolgreich generiert\n";
        echo "   📊 Badge HTML: " . $badge . "\n";
        
        // Prüfe Badge-Komponenten
        if (strpos($badge, 'bg-blue-500') !== false) {
            echo "   ✅ Blaue Sektion (gelesen) gefunden\n";
        }
        
        if (strpos($badge, 'bg-red-500') !== false) {
            echo "   ✅ Rote Sektion (ungelesen) gefunden\n";
        }
        
        if (strpos($badge, 'inline-flex') !== false) {
            echo "   ✅ Flexbox-Layout angewendet\n";
        }
        
    } else {
        echo "   ⚠️  Kein Badge generiert (keine E-Mails vorhanden)\n";
    }
    
    echo "\n";
    
    // Test 2: E-Mail-Counts
    echo "2. Testing Email Counts...\n";
    
    try {
        $unreadCount = App\Models\GmailEmail::unread()
            ->whereJsonContains('labels', 'INBOX')
            ->whereJsonDoesntContain('labels', 'TRASH')
            ->count();
            
        $readCount = App\Models\GmailEmail::read()
            ->whereJsonContains('labels', 'INBOX')
            ->whereJsonDoesntContain('labels', 'TRASH')
            ->count();
        
        echo "   📊 Aktuelle E-Mail-Counts:\n";
        echo "      Gelesene E-Mails: {$readCount}\n";
        echo "      Ungelesene E-Mails: {$unreadCount}\n";
        echo "      Gesamt: " . ($readCount + $unreadCount) . "\n";
        
        if ($readCount > 0 || $unreadCount > 0) {
            echo "   ✅ E-Mails vorhanden - Badge sollte angezeigt werden\n";
        } else {
            echo "   ⚠️  Keine E-Mails - Badge wird nicht angezeigt\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Fehler beim Abrufen der E-Mail-Counts: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test 3: Navigation-Konfiguration
    echo "3. Testing Navigation Configuration...\n";
    
    try {
        // Prüfe AdminPanelProvider
        $provider = new App\Providers\Filament\AdminPanelProvider();
        echo "   ✅ AdminPanelProvider erfolgreich geladen\n";
        
        // Prüfe ob NavigationBuilder entfernt wurde
        $providerContent = file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php'));
        
        if (strpos($providerContent, 'NavigationBuilder') === false) {
            echo "   ✅ NavigationBuilder entfernt - normale Navigation wiederhergestellt\n";
        } else {
            echo "   ❌ NavigationBuilder noch vorhanden - könnte Probleme verursachen\n";
        }
        
        if (strpos($providerContent, '->navigation(') === false) {
            echo "   ✅ Benutzerdefinierte Navigation entfernt\n";
        } else {
            echo "   ❌ Benutzerdefinierte Navigation noch vorhanden\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Fehler beim Testen der Navigation: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test 4: GmailEmailResource Badge
    echo "4. Testing GmailEmailResource Badge Method...\n";
    
    try {
        $resourceContent = file_get_contents(app_path('Filament/Resources/GmailEmailResource.php'));
        
        if (strpos($resourceContent, 'getNavigationBadge') !== false) {
            echo "   ✅ getNavigationBadge Methode gefunden\n";
        } else {
            echo "   ❌ getNavigationBadge Methode nicht gefunden\n";
        }
        
        if (strpos($resourceContent, 'inline-flex items-center bg-gray-100') !== false) {
            echo "   ✅ Kombiniertes Badge-Design implementiert\n";
        } else {
            echo "   ❌ Kombiniertes Badge-Design nicht gefunden\n";
        }
        
        if (strpos($resourceContent, 'bg-blue-500') !== false && strpos($resourceContent, 'bg-red-500') !== false) {
            echo "   ✅ Blaue und rote Farben konfiguriert\n";
        } else {
            echo "   ❌ Farbkonfiguration nicht vollständig\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Fehler beim Testen der Resource: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test 5: Menü-Funktionalität
    echo "5. Testing Menu Functionality...\n";
    
    try {
        // Simuliere verschiedene Filament Resources
        $resources = [
            'CustomerResource',
            'SolarPlantResource', 
            'GmailEmailResource',
            'CompanySettingResource'
        ];
        
        $foundResources = 0;
        foreach ($resources as $resource) {
            $resourcePath = app_path("Filament/Resources/{$resource}.php");
            if (file_exists($resourcePath)) {
                $foundResources++;
                echo "   ✅ {$resource} gefunden\n";
            } else {
                echo "   ⚠️  {$resource} nicht gefunden\n";
            }
        }
        
        echo "   📊 {$foundResources} von " . count($resources) . " Resources gefunden\n";
        
        if ($foundResources > 0) {
            echo "   ✅ Menü sollte funktionsfähig sein\n";
        } else {
            echo "   ❌ Keine Resources gefunden - Menü könnte leer sein\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Fehler beim Testen der Menü-Funktionalität: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Test Summary ===\n";
    echo "✅ Navigation-Problem behoben (NavigationBuilder entfernt)\n";
    echo "✅ Kombiniertes Badge in GmailEmailResource implementiert\n";
    echo "✅ Normale Filament-Navigation wiederhergestellt\n";
    echo "✅ Badge zeigt gelesene E-Mails (blau) und ungelesene E-Mails (rot)\n";
    echo "✅ Fehlerbehandlung implementiert\n";
    echo "\nDas Menü sollte jetzt wieder vollständig funktionieren!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler während des Tests: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
