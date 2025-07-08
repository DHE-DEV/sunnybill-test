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

echo "=== Filament Benachrichtigungen Display Test ===\n\n";

// 1. Prüfe Benutzer und deren Benachrichtigungen
echo "1. Benutzer und Benachrichtigungen:\n";
$users = User::with('notifications')->get();

foreach ($users as $user) {
    $unreadCount = $user->unread_notifications_count;
    echo "   - {$user->name} ({$user->email}): {$unreadCount} ungelesene Benachrichtigungen\n";
    
    // Zeige die letzten 3 Benachrichtigungen
    $recentNotifications = $user->notifications()->latest()->take(3)->get();
    foreach ($recentNotifications as $notification) {
        $status = $notification->is_read ? 'gelesen' : 'ungelesen';
        echo "     * {$notification->title} ({$status}) - {$notification->created_at->format('d.m.Y H:i')}\n";
    }
}

echo "\n2. Gesamtstatistik:\n";
$totalNotifications = Notification::count();
$unreadNotifications = Notification::unread()->count();
$readNotifications = Notification::read()->count();

echo "   - Gesamt: {$totalNotifications}\n";
echo "   - Ungelesen: {$unreadNotifications}\n";
echo "   - Gelesen: {$readNotifications}\n";

// 3. Teste User-Menü Label Generierung
echo "\n3. User-Menü Label Test:\n";
foreach ($users as $user) {
    $unreadCount = $user->unread_notifications_count;
    $label = 'Benachrichtigungen' . ($unreadCount > 0 ? " ({$unreadCount})" : '');
    echo "   - {$user->name}: '{$label}'\n";
}

// 4. Prüfe CompanySetting für Gmail-Benachrichtigungen
echo "\n4. Gmail-Benachrichtigungseinstellungen:\n";
$company = CompanySetting::first();
if ($company) {
    $gmailEnabled = $company->gmail_notifications_enabled ? 'aktiviert' : 'deaktiviert';
    echo "   - Gmail-Benachrichtigungen: {$gmailEnabled}\n";
    echo "   - Firma: {$company->company_name}\n";
} else {
    echo "   - Keine CompanySetting gefunden\n";
}

// 5. Teste Notification Model Methoden
echo "\n5. Notification Model Methoden Test:\n";
$testUser = $users->first();
if ($testUser) {
    echo "   - Test-Benutzer: {$testUser->name}\n";
    
    // Teste unread_notifications_count
    $unreadCount = $testUser->unread_notifications_count;
    echo "   - Ungelesene Benachrichtigungen (Accessor): {$unreadCount}\n";
    
    // Teste direkte Abfrage
    $directCount = Notification::countUnreadForUser($testUser->id);
    echo "   - Ungelesene Benachrichtigungen (Direkt): {$directCount}\n";
    
    // Teste Recent Notifications
    $recentNotifications = $testUser->getRecentNotifications(5);
    echo "   - Letzte 5 Benachrichtigungen: " . $recentNotifications->count() . " gefunden\n";
}

echo "\n=== Test abgeschlossen ===\n";
