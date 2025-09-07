<?php

// Production Logging Diagnostic Script
// Run this on production to check logging configuration

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Production Logging Diagnostic ===\n\n";

// Check environment
echo "1. Environment Settings:\n";
echo "   APP_ENV: " . env('APP_ENV', 'not set') . "\n";
echo "   APP_DEBUG: " . (env('APP_DEBUG', false) ? 'true' : 'false') . "\n";
echo "   LOG_CHANNEL: " . env('LOG_CHANNEL', 'not set') . "\n";
echo "   LOG_STACK: " . env('LOG_STACK', 'not set') . "\n";
echo "   LOG_LEVEL: " . env('LOG_LEVEL', 'not set') . "\n\n";

// Check log file permissions
echo "2. Log File Permissions:\n";
$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    echo "   File exists: Yes\n";
    echo "   Is writable: " . (is_writable($logPath) ? 'Yes' : 'No') . "\n";
    echo "   File size: " . number_format(filesize($logPath) / 1024, 2) . " KB\n";
    echo "   Last modified: " . date('Y-m-d H:i:s', filemtime($logPath)) . "\n";
    echo "   Permissions: " . substr(sprintf('%o', fileperms($logPath)), -4) . "\n";
} else {
    echo "   File exists: No\n";
    $logDir = dirname($logPath);
    echo "   Directory exists: " . (is_dir($logDir) ? 'Yes' : 'No') . "\n";
    if (is_dir($logDir)) {
        echo "   Directory writable: " . (is_writable($logDir) ? 'Yes' : 'No') . "\n";
        echo "   Directory permissions: " . substr(sprintf('%o', fileperms($logDir)), -4) . "\n";
    }
}
echo "\n";

// Test logging
echo "3. Testing Log Writing:\n";
try {
    \Illuminate\Support\Facades\Log::info('Test log entry from diagnostic script', [
        'timestamp' => now()->toISOString(),
        'env' => env('APP_ENV'),
        'script' => 'check_production_logging.php'
    ]);
    echo "   Test log written successfully\n";
    
    // Check if it was actually written
    if (file_exists($logPath)) {
        $lastLines = array_slice(file($logPath), -5);
        $found = false;
        foreach ($lastLines as $line) {
            if (strpos($line, 'Test log entry from diagnostic script') !== false) {
                $found = true;
                break;
            }
        }
        echo "   Test log found in file: " . ($found ? 'Yes' : 'No') . "\n";
    }
} catch (\Exception $e) {
    echo "   Error writing log: " . $e->getMessage() . "\n";
}
echo "\n";

// Check logging configuration
echo "4. Logging Configuration:\n";
$config = config('logging');
echo "   Default channel: " . $config['default'] . "\n";
echo "   Available channels: " . implode(', ', array_keys($config['channels'])) . "\n";

if (isset($config['channels'][$config['default']])) {
    $defaultChannel = $config['channels'][$config['default']];
    echo "   Default channel driver: " . ($defaultChannel['driver'] ?? 'not set') . "\n";
    
    if ($defaultChannel['driver'] === 'stack') {
        echo "   Stack channels: " . implode(', ', $defaultChannel['channels'] ?? []) . "\n";
    }
}
echo "\n";

// Check disk space
echo "5. Disk Space:\n";
$logDir = storage_path('logs');
$freeSpace = disk_free_space($logDir);
$totalSpace = disk_total_space($logDir);
echo "   Free space: " . number_format($freeSpace / 1024 / 1024 / 1024, 2) . " GB\n";
echo "   Total space: " . number_format($totalSpace / 1024 / 1024 / 1024, 2) . " GB\n";
echo "   Usage: " . number_format((($totalSpace - $freeSpace) / $totalSpace) * 100, 1) . "%\n\n";

// Check recent webhook logs
echo "6. Recent Webhook Logs (last 24 hours):\n";
if (file_exists($logPath)) {
    $handle = fopen($logPath, 'r');
    if ($handle) {
        $webhookLogs = 0;
        $yesterday = time() - 86400;
        
        while (($line = fgets($handle)) !== false) {
            if (strpos($line, 'Router webhook') !== false) {
                // Try to extract timestamp
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    $logTime = strtotime($matches[1]);
                    if ($logTime >= $yesterday) {
                        $webhookLogs++;
                    }
                }
            }
        }
        fclose($handle);
        echo "   Webhook log entries in last 24h: " . $webhookLogs . "\n";
    }
} else {
    echo "   Log file not found\n";
}

echo "\n=== End of Diagnostic ===\n";