<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LexofficeLog;

echo "=== LEXOFFICE LOGS (Letzte 5 Einträge) ===\n\n";

$logs = LexofficeLog::latest()->take(5)->get();

if ($logs->isEmpty()) {
    echo "Keine Logs gefunden.\n";
    exit;
}

foreach ($logs as $log) {
    echo "[{$log->created_at}] {$log->type}/{$log->action} - {$log->status}\n";
    
    if ($log->error_message) {
        echo "ERROR: {$log->error_message}\n";
    }
    
    if ($log->request_data) {
        echo "REQUEST DATA:\n";
        echo json_encode($log->request_data, JSON_PRETTY_PRINT) . "\n";
    }
    
    if ($log->response_data) {
        echo "RESPONSE DATA:\n";
        echo json_encode($log->response_data, JSON_PRETTY_PRINT) . "\n";
    }
    
    echo str_repeat("-", 80) . "\n\n";
}
