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

echo "=== Dashboard Design Test ===\n\n";

// 1. Prüfe Dashboard-Klasse
echo "1. Dashboard-Klasse prüfen...\n";
$dashboardClass = \App\Filament\Pages\Dashboard::class;
if (class_exists($dashboardClass)) {
    echo "   ✓ Dashboard-Klasse existiert: {$dashboardClass}\n";
    
    $dashboard = new $dashboardClass();
    $widgets = $dashboard->getWidgets();
    echo "   - Anzahl Widgets: " . count($widgets) . "\n";
    
    foreach ($widgets as $widget) {
        $widgetName = class_basename($widget);
        echo "   - Widget: {$widgetName}\n";
    }
} else {
    echo "   ✗ Dashboard-Klasse nicht gefunden!\n";
}

// 2. Prüfe Dashboard-View
echo "\n2. Dashboard-View prüfen...\n";
$viewPath = resource_path('views/filament/pages/dashboard.blade.php');
if (file_exists($viewPath)) {
    echo "   ✓ Dashboard-View existiert: {$viewPath}\n";
    
    $viewContent = file_get_contents($viewPath);
    $hasAlpineJs = strpos($viewContent, 'x-data') !== false;
    $hasCollapsibleSections = strpos($viewContent, 'x-show="open"') !== false;
    
    echo "   - Alpine.js verwendet: " . ($hasAlpineJs ? "Ja" : "Nein") . "\n";
    echo "   - Kollabierbare Bereiche: " . ($hasCollapsibleSections ? "Ja" : "Nein") . "\n";
    
    // Zähle Bereiche
    $sections = substr_count($viewContent, 'x-data="{ open: false }"');
    echo "   - Anzahl kollabierbare Bereiche: {$sections}\n";
} else {
    echo "   ✗ Dashboard-View nicht gefunden!\n";
}

// 3. Prüfe JavaScript-Integration
echo "\n3. JavaScript-Integration prüfen...\n";
$jsLayoutPath = resource_path('views/layouts/filament-notifications.blade.php');
if (file_exists($jsLayoutPath)) {
    echo "   ✓ JavaScript-Layout existiert: {$jsLayoutPath}\n";
    
    $jsContent = file_get_contents($jsLayoutPath);
    $hasConflictPrevention = strpos($jsContent, 'window.gmailNotifications') !== false;
    $hasDocumentReadyCheck = strpos($jsContent, 'document.readyState') !== false;
    
    echo "   - Konflikt-Prävention: " . ($hasConflictPrevention ? "Ja" : "Nein") . "\n";
    echo "   - Document-Ready-Check: " . ($hasDocumentReadyCheck ? "Ja" : "Nein") . "\n";
} else {
    echo "   ✗ JavaScript-Layout nicht gefunden!\n";
}

// 4. Prüfe AdminPanelProvider
echo "\n4. AdminPanelProvider prüfen...\n";
$providerPath = app_path('Providers/Filament/AdminPanelProvider.php');
if (file_exists($providerPath)) {
    echo "   ✓ AdminPanelProvider existiert: {$providerPath}\n";
    
    $providerContent = file_get_contents($providerPath);
    $hasRenderHook = strpos($providerContent, 'renderHook') !== false;
    $hasNotificationLayout = strpos($providerContent, 'filament-notifications') !== false;
    
    echo "   - RenderHook verwendet: " . ($hasRenderHook ? "Ja" : "Nein") . "\n";
    echo "   - Notification-Layout eingebunden: " . ($hasNotificationLayout ? "Ja" : "Nein") . "\n";
} else {
    echo "   ✗ AdminPanelProvider nicht gefunden!\n";
}

// 5. Prüfe Widget-Klassen
echo "\n5. Widget-Klassen prüfen...\n";
$widgetClasses = [
    'TasksTodayWidget',
    'TasksThisWeekWidget', 
    'TasksThisMonthWidget',
    'CustomerStatsWidget',
    'SupplierStatsWidget',
    'InvoiceStatsWidget',
    'SolarPlantStatsWidget',
    'ArticleStatsWidget',
    'InvoiceRevenueChartWidget',
    'SolarPlantCapacityChartWidget',
    'CustomerGrowthChartWidget'
];

$existingWidgets = 0;
foreach ($widgetClasses as $widgetClass) {
    $fullClass = "\\App\\Filament\\Widgets\\{$widgetClass}";
    if (class_exists($fullClass)) {
        $existingWidgets++;
        echo "   ✓ {$widgetClass}\n";
    } else {
        echo "   ✗ {$widgetClass} (fehlt)\n";
    }
}

echo "\n   Zusammenfassung: {$existingWidgets}/" . count($widgetClasses) . " Widgets verfügbar\n";

// 6. Mögliche Probleme identifizieren
echo "\n6. Mögliche Design-Probleme:\n";

$problems = [];

// JavaScript-Konflikte
if (file_exists($jsLayoutPath) && file_exists($viewPath)) {
    $jsContent = file_get_contents($jsLayoutPath);
    $viewContent = file_get_contents($viewPath);
    
    if (strpos($viewContent, 'x-data') !== false && strpos($jsContent, 'DOMContentLoaded') !== false) {
        $problems[] = "Möglicher Alpine.js/JavaScript-Konflikt";
    }
}

// Fehlende Widgets
if ($existingWidgets < count($widgetClasses)) {
    $problems[] = "Fehlende Widget-Klassen können Layout-Probleme verursachen";
}

// CSS-Probleme
if (file_exists($viewPath)) {
    $viewContent = file_get_contents($viewPath);
    if (strpos($viewContent, 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3') !== false) {
        $problems[] = "Responsive Grid könnte auf kleinen Bildschirmen Probleme haben";
    }
}

if (empty($problems)) {
    echo "   ✓ Keine offensichtlichen Probleme gefunden\n";
} else {
    foreach ($problems as $problem) {
        echo "   ⚠ {$problem}\n";
    }
}

// 7. Lösungsvorschläge
echo "\n7. Lösungsvorschläge:\n";
echo "   1. Browser-Konsole auf JavaScript-Fehler prüfen\n";
echo "   2. Alpine.js und Notification-JavaScript auf Konflikte testen\n";
echo "   3. Fehlende Widget-Klassen implementieren oder aus Dashboard entfernen\n";
echo "   4. CSS-Grid-Layout auf verschiedenen Bildschirmgrößen testen\n";
echo "   5. Filament-Cache leeren: php artisan filament:cache-components\n";

echo "\n=== Test abgeschlossen ===\n";
