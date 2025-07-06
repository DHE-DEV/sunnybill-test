<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentPathSetting;

echo "=== Hinzufügen: Task DocumentPathSettings für alle Kategorien ===\n\n";

// Kategorien aus DocumentUploadConfig::forTasks()
$taskCategories = [
    'protocol' => 'protokolle',
    'attachment' => 'anhaenge', 
    'correspondence' => 'korrespondenz',
    'report' => 'berichte',
    'checklist' => 'checklisten',
    'photo' => 'fotos',
    'manual' => 'anleitungen',
    'specification' => 'spezifikationen',
    'approval' => 'freigaben',
    'other' => 'sonstiges',
];

echo "1. Hinzufügen der fehlenden Task-Kategorien:\n";

foreach ($taskCategories as $category => $folderName) {
    $setting = DocumentPathSetting::updateOrCreate(
        [
            'documentable_type' => 'App\Models\Task',
            'category' => $category,
        ],
        [
            'path_template' => "aufgaben/{task_number}/{$folderName}",
            'description' => "Pfad für Aufgaben-{$category}-Dokumente",
            'placeholders' => ['task_number', 'task_title', 'task_id'],
            'is_active' => true,
        ]
    );
    
    echo "   ✅ {$category}: aufgaben/{task_number}/{$folderName}\n";
}

echo "\n2. Überprüfung der neuen Settings:\n";
$taskSettings = DocumentPathSetting::where('documentable_type', 'App\Models\Task')->get();
foreach ($taskSettings as $setting) {
    $category = $setting->category ?? 'NULL';
    echo "   - {$category}: {$setting->path_template}\n";
}

echo "\n✅ Alle Task DocumentPathSettings hinzugefügt!\n";