<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Task;
use App\Services\DocumentUploadConfig;
use App\Services\DocumentStorageService;

echo "=== Test: Task Upload-Directories ===\n\n";

// Test-Task laden
$task = Task::first();
if (!$task) {
    echo "❌ Keine Task gefunden!\n";
    exit(1);
}

echo "1. Test-Task: {$task->title} ({$task->task_number})\n\n";

// Test DocumentUploadConfig für Tasks
$config = DocumentUploadConfig::forTasks($task);
echo "2. DocumentUploadConfig pathType: " . $config->get('pathType') . "\n";

// Test Upload-Directory
try {
    $uploadDir = $config->getStorageDirectory();
    echo "3. Upload-Directory: {$uploadDir}\n\n";
} catch (Exception $e) {
    echo "❌ Fehler beim Abrufen des Upload-Directory: " . $e->getMessage() . "\n\n";
}

// Test verschiedene Kategorien aus forTasks()
echo "4. Upload-Directories für verschiedene Kategorien:\n";
$categories = ['protocol', 'attachment', 'correspondence', 'report', 'checklist', 'photo', 'manual', 'specification', 'approval', 'other', null];

foreach ($categories as $category) {
    try {
        $directory = DocumentStorageService::getUploadDirectoryForModel(
            'tasks',
            $task,
            ['category' => $category]
        );
        $categoryLabel = $category ?? 'NULL';
        echo "   {$categoryLabel}: {$directory}\n";
    } catch (Exception $e) {
        echo "   {$category}: ❌ Fehler - " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Test abgeschlossen!\n";
echo "\n=== Erwartung ===\n";
echo "Alle Pfade sollten mit 'aufgaben/{$task->task_number}/' beginnen\n";
echo "und kategorie-spezifische Unterordner haben!\n";