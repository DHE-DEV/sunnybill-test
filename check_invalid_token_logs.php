<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RouterWebhookLog;

echo "=== Checking Invalid Token Logs ===\n\n";

// Check for invalid token logs
$invalidTokenLog = RouterWebhookLog::where('webhook_token', 'INVALID_TOKEN_TEST_123456789')->first();

if ($invalidTokenLog) {
    echo "Found log for invalid token!\n";
    echo "ID: " . $invalidTokenLog->id . "\n";
    echo "Token: " . $invalidTokenLog->webhook_token . "\n";
    echo "Status: " . $invalidTokenLog->status . "\n";
    echo "HTTP Code: " . $invalidTokenLog->http_response_code . "\n";
    echo "Router ID: " . ($invalidTokenLog->router_id ?? 'NULL') . "\n";
    echo "Created: " . $invalidTokenLog->created_at . "\n";
    echo "Raw Data: " . json_encode($invalidTokenLog->raw_data) . "\n";
    echo "Response: " . json_encode($invalidTokenLog->response_data) . "\n";
} else {
    echo "No log found for token: INVALID_TOKEN_TEST_123456789\n\n";
    
    // Check last few failed logs
    $failedLogs = RouterWebhookLog::where('status', '!=', 'success')
        ->orWhereNull('router_id')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    if ($failedLogs->count() > 0) {
        echo "Recent failed/invalid logs:\n";
        foreach ($failedLogs as $log) {
            echo "- ID: " . $log->id . ", Token: " . substr($log->webhook_token, 0, 20) . "..., Status: " . $log->status . ", Created: " . $log->created_at . "\n";
        }
    } else {
        echo "No failed logs found.\n";
    }
}