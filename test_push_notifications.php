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

use App\Models\User;
use App\Models\Notification;
use App\Models\CompanySetting;

echo "=== Push-Benachrichtigungen Test ===\n\n";

// 1. Erstelle eine neue Test-Benachrichtigung
echo "1. Erstelle neue Test-Benachrichtigung...\n";
$user = User::first();
if (!$user) {
    echo "   Fehler: Kein Benutzer gefunden!\n";
    exit(1);
}

$notification = Notification::create([
    'user_id' => $user->id,
    'title' => 'Test Push-Benachrichtigung',
    'message' => 'Dies ist eine Test-Benachrichtigung für Push-Notifications',
    'type' => 'gmail',
    'is_read' => false,
    'created_at' => now(),
    'updated_at' => now()
]);

echo "   ✓ Benachrichtigung erstellt: ID {$notification->id}\n";

// 2. Prüfe API-Endpunkt
echo "\n2. Teste API-Endpunkt...\n";
$unreadCount = $user->unread_notifications_count;
echo "   - Ungelesene Benachrichtigungen für {$user->name}: {$unreadCount}\n";

// 3. Simuliere API-Aufruf
echo "\n3. Simuliere API-Response...\n";
$apiResponse = [
    'unread_count' => $unreadCount,
    'user_id' => $user->id
];
echo "   - API Response: " . json_encode($apiResponse, JSON_PRETTY_PRINT) . "\n";

// 4. Prüfe Browser-Notification Voraussetzungen
echo "\n4. Browser-Notification Informationen:\n";
echo "   - JavaScript wird automatisch geladen über Filament renderHook\n";
echo "   - Notification.requestPermission() wird beim Laden aufgerufen\n";
echo "   - Polling alle 30 Sekunden über /api/notifications/count\n";
echo "   - Push-Benachrichtigungen erscheinen bei neuen E-Mails\n";

// 5. Teste verschiedene Benachrichtigungstypen
echo "\n5. Erstelle verschiedene Test-Benachrichtigungen...\n";

$testNotifications = [
    [
        'title' => 'Neue Gmail E-Mail von Kunde',
        'message' => 'Sie haben eine neue E-Mail von Max Mustermann erhalten',
        'type' => 'gmail'
    ],
    [
        'title' => 'System-Benachrichtigung',
        'message' => 'Backup wurde erfolgreich abgeschlossen',
        'type' => 'system'
    ],
    [
        'title' => 'Wichtige E-Mail',
        'message' => 'Dringende Nachricht von Lieferant',
        'type' => 'gmail'
    ]
];

foreach ($testNotifications as $index => $notificationData) {
    $testNotification = Notification::create([
        'user_id' => $user->id,
        'title' => $notificationData['title'],
        'message' => $notificationData['message'],
        'type' => $notificationData['type'],
        'is_read' => false,
        'created_at' => now()->addSeconds($index),
        'updated_at' => now()->addSeconds($index)
    ]);
    
    echo "   ✓ {$notificationData['title']} (ID: {$testNotification->id})\n";
}

// 6. Finale Statistik
echo "\n6. Finale Statistik:\n";
$finalUnreadCount = $user->fresh()->unread_notifications_count;
echo "   - Gesamte ungelesene Benachrichtigungen: {$finalUnreadCount}\n";
echo "   - Neue Benachrichtigungen erstellt: " . (count($testNotifications) + 1) . "\n";

// 7. Anweisungen für den Test
echo "\n7. Test-Anweisungen:\n";
echo "   1. Öffnen Sie die Filament-Admin-Oberfläche im Browser\n";
echo "   2. Erlauben Sie Browser-Benachrichtigungen wenn gefragt\n";
echo "   3. Warten Sie 30 Sekunden oder aktualisieren Sie die Seite\n";
echo "   4. Sie sollten Push-Benachrichtigungen für neue E-Mails sehen\n";
echo "   5. Klicken Sie auf eine Benachrichtigung um zur Benachrichtigungsseite zu gelangen\n";

echo "\n=== Test abgeschlossen ===\n";
