<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentPathSetting;

echo "=== Check: Task DocumentPathSettings ===\n\n";

// Prüfe vorhandene DocumentPathSettings für Tasks
$taskSettings = DocumentPathSetting::where('documentable_type', 'App\Models\Task')->get();

echo "1. Vorhandene Task DocumentPathSettings:\n";
if ($taskSettings->isEmpty()) {
    echo "   ❌ Keine DocumentPathSettings für 'App\Models\Task' gefunden!\n";
} else {
    foreach ($taskSettings as $setting) {
        $category = $setting->category ?? 'NULL';
        echo "   - {$category}: {$setting->path_template}\n";
    }
}

echo "\n=== Erwartung ===\n";
echo "Es sollten DocumentPathSettings für 'App\Models\Task' mit verschiedenen Kategorien existieren!\n";
echo "Kategorien aus forTasks(): protocol, attachment, correspondence, report, checklist, photo, manual, specification, approval, other\n";