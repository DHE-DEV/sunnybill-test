<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\RouterNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateRouterStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'routers:update-statuses {--notify : Send notifications for status changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update connection status for all active routers and optionally send notifications';

    protected RouterNotificationService $notificationService;

    public function __construct(RouterNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating router connection statuses...');
        
        $routers = Router::where('is_active', true)->get();
        $statusChanges = [];
        
        foreach ($routers as $router) {
            $oldStatus = $router->connection_status;
            $newStatus = $router->updateConnectionStatus();
            
            // Status in Datenbank speichern wenn geändert
            if ($oldStatus !== $newStatus) {
                $router->save();
                $statusChanges[] = [
                    'router' => $router,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ];
                
                $this->line("Router {$router->name}: {$oldStatus} → {$newStatus}");
                
                // Logging für wichtige Status-Änderungen
                if ($newStatus === 'offline' && $oldStatus !== 'offline') {
                    Log::warning("Router went offline", [
                        'router_id' => $router->id,
                        'router_name' => $router->name,
                        'last_seen' => $router->last_seen_at,
                        'minutes_since_last_seen' => $router->last_seen_at ? 
                            $router->last_seen_at->diffInMinutes(now()) : null
                    ]);
                }
            }
        }
        
        // Benachrichtigungen senden wenn gewünscht
        if ($this->option('notify') && !empty($statusChanges)) {
            $this->sendNotifications($statusChanges);
        }

        // Notification summary anzeigen
        if ($this->option('notify')) {
            $this->displayNotificationSummary();
        }
        
        $this->info('Router status update completed.');
        $this->table(
            ['Router', 'Status', 'Last Seen', 'Minutes Ago'],
            $routers->map(function ($router) {
                return [
                    $router->name,
                    $router->connection_status,
                    $router->last_seen_at ? $router->last_seen_at->format('d.m.Y H:i:s') : 'Nie',
                    $router->last_seen_at ? round($router->last_seen_at->diffInMinutes(now())) . ' min' : '-'
                ];
            })
        );
        
        return 0;
    }
    
    /**
     * Send notifications for status changes
     */
    private function sendNotifications(array $statusChanges): void
    {
        $this->info('Sending notifications for status changes...');
        
        foreach ($statusChanges as $change) {
            $router = $change['router'];
            $newStatus = $change['new_status'];
            $oldStatus = $change['old_status'];
            
            // Nur bei kritischen Änderungen benachrichtigen
            if ($newStatus === 'offline' && $oldStatus !== 'offline') {
                $this->warn("🚨 ALERT: Router {$router->name} is now OFFLINE");
                
                // E-Mail-Benachrichtigung mit dem neuen Service senden
                $success = $this->notificationService->sendStatusNotification($router, "{$oldStatus} → {$newStatus}");
                
                if ($success) {
                    $this->info("   📧 E-Mail-Benachrichtigung gesendet");
                } else {
                    $this->error("   ❌ E-Mail-Benachrichtigung fehlgeschlagen");
                }
                
            } elseif ($newStatus === 'online' && $oldStatus === 'offline') {
                $this->info("✅ Router {$router->name} is back ONLINE");
                
                // Auch bei "wieder online" eine Benachrichtigung senden
                $success = $this->notificationService->sendStatusNotification($router, "{$oldStatus} → {$newStatus}");
                
                if ($success) {
                    $this->info("   📧 E-Mail-Benachrichtigung gesendet");
                } else {
                    $this->error("   ❌ E-Mail-Benachrichtigung fehlgeschlagen");
                }
            }
        }
    }
    
    /**
     * Display notification configuration summary
     */
    private function displayNotificationSummary(): void
    {
        $summary = $this->notificationService->getNotificationSummary();
        
        $this->newLine();
        $this->info('📧 E-Mail-Benachrichtigungs-Konfiguration:');
        
        if (!$summary['enabled']) {
            $this->warn('   ❌ Benachrichtigungen sind deaktiviert');
            return;
        }
        
        $this->info("   ✅ Benachrichtigungen sind aktiviert");
        $this->info("   📬 Empfänger gesamt: {$summary['total_recipients']}");
        
        if ($summary['to_count'] > 0) {
            $this->info("      - TO: {$summary['to_count']} Empfänger");
        }
        
        if ($summary['cc_count'] > 0) {
            $this->info("      - CC: {$summary['cc_count']} Empfänger");
        }
        
        if ($summary['bcc_count'] > 0) {
            $this->info("      - BCC: {$summary['bcc_count']} Empfänger");
        }
        
        $this->info('   📋 Benachrichtigungstypen:');
        foreach ($summary['notification_types'] as $type => $enabled) {
            $status = $enabled ? '✅' : '❌';
            $this->info("      - {$type}: {$status}");
        }
        
        if ($summary['total_recipients'] === 0) {
            $this->warn('   ⚠️  Keine E-Mail-Empfänger konfiguriert!');
            $this->warn('   💡 Konfiguriere ROUTER_NOTIFICATION_TO in der .env Datei');
        }
    }
}