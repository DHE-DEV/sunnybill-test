<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RouterWebhookLog;
use App\Models\Router;

echo "=== Router Webhook Logs Check ===\n\n";

// Count total logs
$totalLogs = RouterWebhookLog::count();
echo "Total webhook logs in database: " . $totalLogs . "\n\n";

// Get last 5 logs
$recentLogs = RouterWebhookLog::orderBy('created_at', 'desc')->limit(5)->get();

if ($recentLogs->count() > 0) {
    echo "Last 5 webhook logs:\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($recentLogs as $log) {
        echo "ID: " . $log->id . "\n";
        echo "Time: " . $log->created_at . "\n";
        echo "Router ID: " . ($log->router_id ?? 'NULL') . "\n";
        echo "Token: " . substr($log->webhook_token, 0, 10) . "...\n";
        echo "Status: " . $log->status . "\n";
        echo "HTTP Code: " . $log->http_response_code . "\n";
        echo "Client IP: " . $log->client_ip . "\n";
        echo "Operator: " . ($log->operator ?? 'N/A') . "\n";
        echo "Signal: " . ($log->signal_strength ?? 'N/A') . " dBm\n";
        echo "Network: " . ($log->network_type ?? 'N/A') . "\n";
        echo "Processing Time: " . ($log->processing_time_ms ?? 'N/A') . " ms\n";
        
        if ($log->raw_data) {
            echo "Raw Data: " . substr(json_encode($log->raw_data), 0, 100) . "...\n";
        }
        
        if ($log->response_data) {
            echo "Response: " . substr(json_encode($log->response_data), 0, 100) . "...\n";
        }
        
        if ($log->validation_errors) {
            echo "Errors: " . json_encode($log->validation_errors) . "\n";
        }
        
        echo str_repeat("-", 80) . "\n";
    }
} else {
    echo "No webhook logs found in database.\n\n";
}

// Check logs by status
$successCount = RouterWebhookLog::where('status', 'success')->count();
$validationErrorCount = RouterWebhookLog::where('status', 'validation_error')->count();
$processingErrorCount = RouterWebhookLog::where('status', 'processing_error')->count();

echo "\nLogs by status:\n";
echo "  Success: " . $successCount . "\n";
echo "  Validation errors: " . $validationErrorCount . "\n";
echo "  Processing errors: " . $processingErrorCount . "\n";

// Check logs from last 24 hours
$last24h = RouterWebhookLog::where('created_at', '>=', now()->subHours(24))->count();
echo "\nLogs in last 24 hours: " . $last24h . "\n";

// Check routers with webhook activity
$routersWithLogs = RouterWebhookLog::distinct('router_id')->whereNotNull('router_id')->count('router_id');
echo "Routers with webhook activity: " . $routersWithLogs . "\n";

echo "\n=== End of Check ===\n";