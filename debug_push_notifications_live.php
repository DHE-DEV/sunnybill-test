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

echo "=== Push-Benachrichtigungen Live-Debug ===\n\n";

// 1. API-Endpunkt testen
echo "1. API-Endpunkt /api/notifications/count testen...\n";
try {
    $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('api.notifications.count');
    if ($route) {
        echo "   ✓ Route existiert: " . $route->uri() . "\n";
    } else {
        echo "   ✗ Route nicht gefunden!\n";
    }
} catch (Exception $e) {
    echo "   ✗ Fehler beim Route-Check: " . $e->getMessage() . "\n";
}

// 2. Benachrichtigungen in der Datenbank prüfen
echo "\n2. Benachrichtigungen in der Datenbank...\n";
try {
    $notifications = \App\Models\Notification::where('read_at', null)->count();
    echo "   - Ungelesene Benachrichtigungen: {$notifications}\n";
    
    $totalNotifications = \App\Models\Notification::count();
    echo "   - Gesamt Benachrichtigungen: {$totalNotifications}\n";
    
    if ($totalNotifications > 0) {
        $latest = \App\Models\Notification::latest()->first();
        echo "   - Neueste Benachrichtigung: " . $latest->title . " (" . $latest->created_at . ")\n";
    }
} catch (Exception $e) {
    echo "   ✗ Fehler beim Benachrichtigungs-Check: " . $e->getMessage() . "\n";
}

// 3. Gmail-E-Mails prüfen
echo "\n3. Gmail-E-Mails prüfen...\n";
try {
    $gmailEmails = \App\Models\GmailEmail::count();
    echo "   - Gesamt Gmail-E-Mails: {$gmailEmails}\n";
    
    if ($gmailEmails > 0) {
        $latestEmail = \App\Models\GmailEmail::latest()->first();
        echo "   - Neueste E-Mail: " . $latestEmail->subject . " (" . $latestEmail->created_at . ")\n";
    }
} catch (Exception $e) {
    echo "   ✗ Fehler beim Gmail-Check: " . $e->getMessage() . "\n";
}

// 4. Company Settings prüfen
echo "\n4. Company Settings für Benachrichtigungen...\n";
try {
    $company = \App\Models\CompanySetting::first();
    if ($company) {
        echo "   - Gmail-Benachrichtigungen aktiviert: " . ($company->gmail_notifications_enabled ? "Ja" : "Nein") . "\n";
        echo "   - Gmail-Token vorhanden: " . ($company->gmail_access_token ? "Ja" : "Nein") . "\n";
        echo "   - Gmail-Refresh-Token vorhanden: " . ($company->gmail_refresh_token ? "Ja" : "Nein") . "\n";
    } else {
        echo "   ✗ Keine Company Settings gefunden!\n";
    }
} catch (Exception $e) {
    echo "   ✗ Fehler beim Company-Check: " . $e->getMessage() . "\n";
}

// 5. User Settings prüfen
echo "\n5. User Settings für Benachrichtigungen...\n";
try {
    $user = \App\Models\User::first();
    if ($user) {
        echo "   - User ID: {$user->id}\n";
        echo "   - Gmail-Benachrichtigungen aktiviert: " . ($user->gmail_notifications_enabled ? "Ja" : "Nein") . "\n";
        echo "   - Company Setting ID: " . ($user->company_setting_id ?? "Nicht gesetzt") . "\n";
    } else {
        echo "   ✗ Kein User gefunden!\n";
    }
} catch (Exception $e) {
    echo "   ✗ Fehler beim User-Check: " . $e->getMessage() . "\n";
}

// 6. JavaScript-Layout prüfen
echo "\n6. JavaScript-Layout prüfen...\n";
$jsLayoutPath = resource_path('views/layouts/filament-notifications.blade.php');
if (file_exists($jsLayoutPath)) {
    echo "   ✓ JavaScript-Layout existiert\n";
    
    $jsContent = file_get_contents($jsLayoutPath);
    $hasPolling = strpos($jsContent, 'setInterval') !== false;
    $hasApiCall = strpos($jsContent, '/api/notifications/count') !== false;
    $hasNotificationClass = strpos($jsContent, 'GmailNotificationManager') !== false;
    
    echo "   - Polling implementiert: " . ($hasPolling ? "Ja" : "Nein") . "\n";
    echo "   - API-Call vorhanden: " . ($hasApiCall ? "Ja" : "Nein") . "\n";
    echo "   - Notification-Klasse: " . ($hasNotificationClass ? "Ja" : "Nein") . "\n";
} else {
    echo "   ✗ JavaScript-Layout nicht gefunden!\n";
}

// 7. AdminPanelProvider prüfen
echo "\n7. AdminPanelProvider Integration...\n";
$providerPath = app_path('Providers/Filament/AdminPanelProvider.php');
if (file_exists($providerPath)) {
    echo "   ✓ AdminPanelProvider existiert\n";
    
    $providerContent = file_get_contents($providerPath);
    $hasRenderHook = strpos($providerContent, 'renderHook') !== false;
    $hasNotificationLayout = strpos($providerContent, 'filament-notifications') !== false;
    
    echo "   - RenderHook verwendet: " . ($hasRenderHook ? "Ja" : "Nein") . "\n";
    echo "   - Notification-Layout eingebunden: " . ($hasNotificationLayout ? "Ja" : "Nein") . "\n";
} else {
    echo "   ✗ AdminPanelProvider nicht gefunden!\n";
}

// 8. Neue Test-Benachrichtigung erstellen
echo "\n8. Neue Test-Benachrichtigung erstellen...\n";
try {
    $user = \App\Models\User::first();
    if ($user) {
        $notification = \App\Models\Notification::create([
            'user_id' => $user->id,
            'title' => 'Push-Test Benachrichtigung',
            'message' => 'Dies ist eine Test-Benachrichtigung für Push-Debugging um ' . now()->format('H:i:s'),
            'type' => 'gmail',
            'data' => json_encode(['test' => true, 'timestamp' => now()->timestamp]),
            'read_at' => null
        ]);
        
        echo "   ✓ Test-Benachrichtigung erstellt (ID: {$notification->id})\n";
        echo "   - Titel: {$notification->title}\n";
        echo "   - Erstellt: {$notification->created_at}\n";
    } else {
        echo "   ✗ Kein User für Test-Benachrichtigung gefunden!\n";
    }
} catch (Exception $e) {
    echo "   ✗ Fehler beim Erstellen der Test-Benachrichtigung: " . $e->getMessage() . "\n";
}

// 9. API-Endpunkt simulieren
echo "\n9. API-Endpunkt simulieren...\n";
try {
    $user = \App\Models\User::first();
    if ($user) {
        $unreadCount = \App\Models\Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
        
        $response = [
            'unread_count' => $unreadCount,
            'timestamp' => now()->timestamp
        ];
        
        echo "   ✓ API-Response simuliert:\n";
        echo "   " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Fehler bei API-Simulation: " . $e->getMessage() . "\n";
}

echo "\n=== Debugging-Empfehlungen ===\n";
echo "1. Browser-Konsole öffnen und auf JavaScript-Fehler prüfen\n";
echo "2. Netzwerk-Tab öffnen und auf /api/notifications/count Aufrufe achten\n";
echo "3. Browser-Benachrichtigungen in den Einstellungen erlauben\n";
echo "4. Seite neu laden und 30 Sekunden warten\n";
echo "5. Neue Test-Benachrichtigung wurde erstellt - sollte Push auslösen\n";

echo "\n=== Debug abgeschlossen ===\n";
