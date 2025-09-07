<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Models\User;
use App\Notifications\RouterOfflineNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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
                
                // E-Mail-Benachrichtigung an alle Admins senden
                $this->sendEmailNotification($router, "{$oldStatus} → {$newStatus}");
                
                // Log für Notification System
                Log::alert("Router offline notification sent", [
                    'router_id' => $router->id,
                    'router_name' => $router->name,
                    'status_change' => "{$oldStatus} → {$newStatus}",
                    'last_seen' => $router->last_seen_at
                ]);
            } elseif ($newStatus === 'online' && $oldStatus === 'offline') {
                $this->info("✅ Router {$router->name} is back ONLINE");
                
                // Auch bei "wieder online" eine Benachrichtigung senden
                $this->sendEmailNotification($router, "{$oldStatus} → {$newStatus}");
                
                Log::info("Router back online notification sent", [
                    'router_id' => $router->id,
                    'router_name' => $router->name,
                    'status_change' => "{$oldStatus} → {$newStatus}"
                ]);
            }
        }
    }
    
    /**
     * Send email notification to administrators
     */
    private function sendEmailNotification(Router $router, string $statusChange): void
    {
        try {
            // Alle Admin-Benutzer finden (E-Mail-basiert da is_admin Spalte nicht existiert)
            $adminUsers = User::where('email', 'like', '%@prosoltec%')->get();
            
            if ($adminUsers->isEmpty()) {
                // Fallback: Erste 2 Benutzer benachrichtigen wenn keine Admins definiert sind
                $adminUsers = User::limit(2)->get(); // Begrenzt auf 2 um Spam zu vermeiden
            }
            
            foreach ($adminUsers as $user) {
                $user->notify(new RouterOfflineNotification($router, $statusChange));
            }
            
            $this->info("E-Mail-Benachrichtigungen an {$adminUsers->count()} Benutzer gesendet");
            
        } catch (\Exception $e) {
            $this->error("Fehler beim Senden der E-Mail-Benachrichtigungen: " . $e->getMessage());
            
            Log::error("Failed to send router notification emails", [
                'router_id' => $router->id,
                'status_change' => $statusChange,
                'error' => $e->getMessage()
            ]);
        }
    }
}