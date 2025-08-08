<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\TaskType;

// Laravel App laden
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TASK TYPES CHECK ===\n\n";

try {
    $taskTypes = TaskType::all();
    
    if ($taskTypes->count() > 0) {
        echo "✅ Gefundene Task Types:\n";
        foreach ($taskTypes as $taskType) {
            echo "- ID: {$taskType->id} | Name: {$taskType->name} | Slug: {$taskType->slug}\n";
        }
        
        echo "\n📋 Für API verwende task_type_id statt task_type!\n";
        echo "Beispiel: 'task_type_id' => {$taskTypes->first()->id}\n";
        
    } else {
        echo "❌ Keine Task Types gefunden!\n";
        echo "💡 Lösung: Erstelle einen Standard-TaskType\n";
        
        // Erstelle einen Standard-TaskType
        $defaultTaskType = TaskType::create([
            'name' => 'General',
            'slug' => 'general',
            'description' => 'General task type',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);
        
        echo "✅ Standard TaskType erstellt:\n";
        echo "- ID: {$defaultTaskType->id}\n";
        echo "- Name: {$defaultTaskType->name}\n";
        echo "- Slug: {$defaultTaskType->slug}\n";
    }
    
} catch (Exception $e) {
    echo "❌ FEHLER:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}
