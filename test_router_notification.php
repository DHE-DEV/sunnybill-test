<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Router;
use App\Services\RouterNotificationService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Router Notification Service...\n\n";

// Test configuration
echo "=== Configuration Test ===\n";
$service = new RouterNotificationService();

echo "Notifications enabled: " . ($service->isNotificationEnabled() ? 'YES' : 'NO') . "\n";
echo "Offline notifications enabled: " . ($service->isNotificationTypeEnabled('offline') ? 'YES' : 'NO') . "\n";
echo "Queue notifications: " . (config('router-notifications.queue_notifications') ? 'YES' : 'NO') . "\n";

$emails = $service->getEmailConfiguration();
echo "Email TO: " . (empty($emails['to']) ? 'NONE' : implode(', ', $emails['to'])) . "\n";

// Test email configuration
echo "\n=== Mail Configuration Test ===\n";
echo "Mail driver: " . config('mail.default') . "\n";
echo "SMTP host: " . config('mail.mailers.smtp.host') . "\n";
echo "SMTP port: " . config('mail.mailers.smtp.port') . "\n";
echo "From address: " . config('mail.from.address') . "\n";

// Create a test router
echo "\n=== Creating Test Router ===\n";
$testRouter = new Router();
$testRouter->id = 999;
$testRouter->name = 'Test Router';
$testRouter->ip_address = '192.168.1.1';
$testRouter->last_seen_at = now()->subMinutes(15);

echo "Test router created: ID {$testRouter->id}, Name: {$testRouter->name}\n";

// Test sending notification
echo "\n=== Testing Notification Send ===\n";
try {
    $success = $service->sendStatusNotification($testRouter, 'online → offline');
    echo "Notification send result: " . ($success ? 'SUCCESS' : 'FAILED') . "\n";
    
    if ($success) {
        echo "✅ Router notification email should have been sent to: " . implode(', ', $emails['to']) . "\n";
        echo "Check your email inbox for the notification.\n";
    } else {
        echo "❌ Failed to send router notification email.\n";
        echo "Check the Laravel logs for more details.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception during notification send: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
