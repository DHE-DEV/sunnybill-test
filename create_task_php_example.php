<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\AppToken;
use App\Models\User;
use App\Models\Task;

// Laravel App laden
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== AUFGABE DIREKT IN DATENBANK ERSTELLEN ===\n\n";

// Token für User 57 
$plainTextToken = 'Vsew8fUkghFV6FxY0ADX8rpTCk6k5JCMCEwjEWp1';

try {
    // Token validieren
    $hashedToken = hash('sha256', $plainTextToken);
    $appToken = AppToken::where('token', $hashedToken)->first();
    
    if (!$appToken) {
        echo "❌ Token nicht gefunden!\n";
        exit;
    }
    
    echo "✅ Token gefunden: {$appToken->name}\n";
    echo "User ID: {$appToken->user_id}\n\n";
    
    // Prüfe, ob User 1 (Verantwortlicher) existiert
    $owner = User::find(1);
    if (!$owner) {
        echo "❌ Owner User (ID 1) nicht gefunden!\n";
        exit;
    }
    
    // Prüfe, ob User 57 (Zugeordnet) existiert  
    $assignedUser = User::find(57);
    if (!$assignedUser) {
        echo "❌ Assigned User (ID 57) nicht gefunden!\n";
        exit;
    }
    
    echo "✅ Owner: {$owner->name} (ID: {$owner->id})\n";
    echo "✅ Assigned: {$assignedUser->name} (ID: {$assignedUser->id})\n\n";
    
    // Raw Content für Aufgabe (wie über API)
    $rawTaskData = [
        'title' => 'Neue Aufgabe über API',
        'description' => 'Diese Aufgabe wurde über die API erstellt mit korrekten Zuordnungen',
        'task_type_id' => 1, // Installation Task Type
        'priority' => 'medium',
        'status' => 'open',
        'assigned_to' => 57, // Zugeordnet zu User 57
        'owner_id' => 1,     // Verantwortlich User 1
        'created_by' => 1,   // Erstellt von User 1
        'due_date' => '2025-08-15',
        'due_time' => '14:30:00',
        'estimated_minutes' => 120,
    ];
    
    echo "📋 Raw Task Data:\n";
    echo json_encode($rawTaskData, JSON_PRETTY_PRINT) . "\n\n";
    
    // Task direkt in Datenbank erstellen (umgeht Controller-Validierung)
    $task = Task::create($rawTaskData);
    
    if ($task) {
        echo "🎉 AUFGABE ERFOLGREICH ERSTELLT!\n";
        echo "✅ Task ID: {$task->id}\n";
        echo "✅ Task Number: {$task->task_number}\n";
        echo "✅ Title: {$task->title}\n";
        echo "✅ Status: {$task->status}\n";
        echo "✅ Priority: {$task->priority}\n";
        echo "✅ Owner: {$task->owner->name} (ID: {$task->owner_id})\n";
        echo "✅ Assigned: {$task->assignedTo->name} (ID: {$task->assigned_to})\n";
        echo "✅ Created by: {$task->creator->name} (ID: {$task->created_by})\n";
        echo "✅ Task Type: {$task->taskType->name} (ID: {$task->task_type_id})\n";
        echo "✅ Due Date: {$task->due_date}\n";
        echo "✅ Due Time: {$task->due_time}\n";
        echo "✅ Estimated Minutes: {$task->estimated_minutes}\n";
        
        echo "\n🔗 Diese Task kann über die API wie folgt abgerufen werden:\n";
        echo "GET /api/app/tasks/{$task->id}\n";
        echo "Authorization: Bearer {$plainTextToken}\n";
    }
    
} catch (Exception $e) {
    echo "❌ FEHLER:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== RAW CONTENT FÜR API-CALL ===\n";
echo json_encode([
    'title' => 'Neue Aufgabe über API',
    'description' => 'Diese Aufgabe wurde über die API erstellt mit korrekten Zuordnungen',
    'task_type' => 'Installation', 
    'task_type_id' => 1,
    'priority' => 'medium',
    'status' => 'open',
    'assigned_to' => 57,
    'owner_id' => 1,
    'due_date' => '2025-08-15',
    'due_time' => '14:30',
    'estimated_minutes' => 120
], JSON_PRETTY_PRINT) . "\n";
