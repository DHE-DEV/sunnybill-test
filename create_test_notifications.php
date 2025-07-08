<?php

require_once 'vendor/autoload.php';

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

echo "🔔 Erstelle Test-Benachrichtigungen...\n\n";

// Finde den ersten Benutzer
$user = User::first();

if (!$user) {
    echo "❌ Kein Benutzer gefunden. Bitte erstellen Sie zuerst einen Benutzer.\n";
    exit(1);
}

echo "👤 Erstelle Benachrichtigungen für Benutzer: {$user->name} (ID: {$user->id})\n\n";

// Test-Benachrichtigungen erstellen
$notifications = [
    [
        'type' => 'gmail_email',
        'title' => 'Neue E-Mail von Max Mustermann',
        'message' => 'Sie haben eine neue E-Mail von Max Mustermann erhalten: "Anfrage zu Solaranlage"',
        'icon' => 'heroicon-o-envelope',
        'color' => 'primary',
        'priority' => 'normal',
        'action_url' => '/admin/gmail-emails',
        'action_text' => 'E-Mail öffnen',
        'data' => json_encode([
            'sender' => 'max.mustermann@example.com',
            'subject' => 'Anfrage zu Solaranlage',
            'gmail_id' => 'test_123'
        ])
    ],
    [
        'type' => 'system',
        'title' => 'System-Update verfügbar',
        'message' => 'Ein neues System-Update ist verfügbar. Bitte installieren Sie es in den nächsten Tagen.',
        'icon' => 'heroicon-o-arrow-down-tray',
        'color' => 'warning',
        'priority' => 'high',
        'action_url' => '/admin/settings',
        'action_text' => 'Update installieren',
        'data' => json_encode([
            'version' => '2.1.0',
            'release_date' => now()->format('Y-m-d')
        ])
    ],
    [
        'type' => 'customer',
        'title' => 'Neuer Kunde registriert',
        'message' => 'Ein neuer Kunde "Petra Schmidt" hat sich registriert und wartet auf Freischaltung.',
        'icon' => 'heroicon-o-user-plus',
        'color' => 'success',
        'priority' => 'normal',
        'action_url' => '/admin/customers',
        'action_text' => 'Kunde anzeigen',
        'data' => json_encode([
            'customer_name' => 'Petra Schmidt',
            'email' => 'petra.schmidt@example.com',
            'registration_date' => now()->format('Y-m-d H:i:s')
        ])
    ],
    [
        'type' => 'billing',
        'title' => 'Rechnung überfällig',
        'message' => 'Die Rechnung R-2025-001 von Hans Weber ist seit 5 Tagen überfällig.',
        'icon' => 'heroicon-o-exclamation-triangle',
        'color' => 'danger',
        'priority' => 'urgent',
        'action_url' => '/admin/billings',
        'action_text' => 'Rechnung anzeigen',
        'data' => json_encode([
            'invoice_number' => 'R-2025-001',
            'customer' => 'Hans Weber',
            'amount' => '1.250,00 €',
            'due_date' => now()->subDays(5)->format('Y-m-d')
        ])
    ],
    [
        'type' => 'solar_plant',
        'title' => 'Solaranlage Wartung fällig',
        'message' => 'Die Solaranlage "Anlage Müller" benötigt eine Wartung. Nächster Termin: in 7 Tagen.',
        'icon' => 'heroicon-o-wrench-screwdriver',
        'color' => 'warning',
        'priority' => 'normal',
        'action_url' => '/admin/solar-plants',
        'action_text' => 'Anlage anzeigen',
        'data' => json_encode([
            'plant_name' => 'Anlage Müller',
            'last_maintenance' => now()->subMonths(6)->format('Y-m-d'),
            'next_maintenance' => now()->addDays(7)->format('Y-m-d')
        ])
    ],
    [
        'type' => 'task',
        'title' => 'Aufgabe überfällig',
        'message' => 'Die Aufgabe "Angebot erstellen für Familie Weber" ist seit 2 Tagen überfällig.',
        'icon' => 'heroicon-o-clipboard-document-list',
        'color' => 'danger',
        'priority' => 'high',
        'action_url' => '/admin/tasks',
        'action_text' => 'Aufgabe anzeigen',
        'data' => json_encode([
            'task_title' => 'Angebot erstellen für Familie Weber',
            'due_date' => now()->subDays(2)->format('Y-m-d'),
            'assigned_to' => $user->name
        ])
    ],
    [
        'type' => 'gmail_email',
        'title' => 'Wichtige E-Mail von Lieferant',
        'message' => 'Dringende Nachricht von SolarTech GmbH bezüglich Liefertermin.',
        'icon' => 'heroicon-o-envelope',
        'color' => 'danger',
        'priority' => 'urgent',
        'action_url' => '/admin/gmail-emails',
        'action_text' => 'E-Mail öffnen',
        'data' => json_encode([
            'sender' => 'info@solartech.de',
            'subject' => 'DRINGEND: Liefertermin verschoben',
            'gmail_id' => 'urgent_456'
        ])
    ],
    [
        'type' => 'system',
        'title' => 'Backup erfolgreich',
        'message' => 'Das tägliche Backup wurde erfolgreich um 02:00 Uhr durchgeführt.',
        'icon' => 'heroicon-o-shield-check',
        'color' => 'success',
        'priority' => 'low',
        'data' => json_encode([
            'backup_time' => '02:00:00',
            'backup_size' => '2.3 GB',
            'status' => 'success'
        ])
    ]
];

$created = 0;
foreach ($notifications as $notificationData) {
    try {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $notificationData['type'],
            'title' => $notificationData['title'],
            'message' => $notificationData['message'],
            'icon' => $notificationData['icon'],
            'color' => $notificationData['color'],
            'priority' => $notificationData['priority'],
            'action_url' => $notificationData['action_url'] ?? null,
            'action_text' => $notificationData['action_text'] ?? null,
            'data' => $notificationData['data'] ?? null,
            'is_read' => false,
            'expires_at' => now()->addDays(30), // Läuft in 30 Tagen ab
        ]);
        
        echo "✅ Benachrichtigung erstellt: {$notification->title}\n";
        $created++;
        
        // Kleine Pause zwischen den Erstellungen für realistische Timestamps
        usleep(100000); // 0.1 Sekunden
        
    } catch (Exception $e) {
        echo "❌ Fehler beim Erstellen der Benachrichtigung '{$notificationData['title']}': {$e->getMessage()}\n";
    }
}

echo "\n🎉 {$created} Test-Benachrichtigungen erfolgreich erstellt!\n\n";

// Statistiken anzeigen
$totalNotifications = Notification::where('user_id', $user->id)->count();
$unreadNotifications = Notification::where('user_id', $user->id)->where('is_read', false)->count();
$readNotifications = $totalNotifications - $unreadNotifications;

echo "📊 Benachrichtigungs-Statistiken:\n";
echo "   Gesamt: {$totalNotifications}\n";
echo "   Ungelesen: {$unreadNotifications}\n";
echo "   Gelesen: {$readNotifications}\n\n";

// Prioritäten-Verteilung
$priorities = Notification::where('user_id', $user->id)
    ->selectRaw('priority, COUNT(*) as count')
    ->groupBy('priority')
    ->pluck('count', 'priority')
    ->toArray();

echo "🎯 Prioritäten-Verteilung:\n";
foreach ($priorities as $priority => $count) {
    $priorityText = match($priority) {
        'urgent' => 'Dringend',
        'high' => 'Hoch',
        'normal' => 'Normal',
        'low' => 'Niedrig',
        default => ucfirst($priority)
    };
    echo "   {$priorityText}: {$count}\n";
}

echo "\n🔗 Sie können die Benachrichtigungen jetzt im Admin-Panel unter 'System > Benachrichtigungen' einsehen.\n";
echo "📱 URL: /admin/pages/notifications\n\n";

echo "💡 Tipp: Markieren Sie einige Benachrichtigungen als gelesen, um die verschiedenen Funktionen zu testen!\n";
