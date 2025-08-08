<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\TaskApiController;
use App\Models\AppToken;
use App\Models\User;
use App\Models\TaskType;
use Illuminate\Support\Facades\Auth;

// Laravel App laden
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TASK CREATION VALIDATION TEST ===\n\n";

// Token für User 57
$plainTextToken = 'sb_HrMgJVlEEua9OvTuk2FYFkEqzA0MLMNfxnEIv0PRnatCrcrGKg2ayYqwLHWywXpY';

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
    
    // User laden und authentifizieren
    $user = User::find($appToken->user_id);
    Auth::setUser($user);
    
    // Prüfe TaskType ID 1
    $taskType = TaskType::find(1);
    echo "TaskType ID 1: " . ($taskType ? $taskType->name : 'NICHT GEFUNDEN') . "\n";
    
    // Prüfe User IDs
    $assignedUser = User::find(57);
    $ownerUser = User::find(1);
    echo "User 57 (assigned_to): " . ($assignedUser ? $assignedUser->name : 'NICHT GEFUNDEN') . "\n";
    echo "User 1 (owner_id): " . ($ownerUser ? $ownerUser->name : 'NICHT GEFUNDEN') . "\n\n";
    
    // Die exakten Daten aus der Fehlermeldung
    $taskData = [
        "title" => "111",
        "description" => "Diese Aufgabe wurde über die API erstellt mit korrekten Zuordnungen",
        "task_type_id" => 1,
        "priority" => "medium",
        "status" => "open",
        "assigned_to" => 57,
        "owner_id" => 1,
        "due_date" => "2025-08-15",
        "due_time" => "14:30",
        "estimated_minutes" => 120
    ];
    
    // Zusätzliche Felder hinzufügen, die möglicherweise fehlen
    $taskData['task_type'] = $taskType ? $taskType->name : 'Installation';
    
    echo "📝 Task-Daten für Validierung:\n";
    echo json_encode($taskData, JSON_PRETTY_PRINT) . "\n\n";
    
    // Request erstellen mit korrekter POST-Datenübertragung
    $request = Request::create('/api/app/tasks', 'POST');
    $request->headers->set('Authorization', 'Bearer ' . $plainTextToken);
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('Content-Type', 'application/json');
    
    // POST-Daten richtig setzen
    $request->replace($taskData);
    
    // User und Token im Request setzen
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    $request->app_token = $appToken;
    
    // Debug: Prüfe was im Request ankommt
    echo "🔍 Request-Parameter:\n";
    echo "  All: " . json_encode($request->all()) . "\n";
    echo "  Title: " . $request->get('title') . "\n";
    echo "  Task Type ID: " . $request->get('task_type_id') . "\n\n";
    
    echo "🔄 Teste Task-Erstellung über Controller...\n\n";
    
    // Controller aufrufen
    $controller = new TaskApiController();
    $response = $controller->store($request);
    
    echo "📊 Response Status: " . $response->getStatusCode() . "\n";
    echo "📄 Response:\n";
    
    $responseData = json_decode($response->getContent(), true);
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response->getStatusCode() === 201) {
        echo "🎉 TASK ERFOLGREICH ERSTELLT!\n\n";
        echo "Task ID: " . $responseData['data']['id'] . "\n";
        echo "Task Number: " . $responseData['data']['task_number'] . "\n";
    } else {
        echo "❌ VALIDIERUNGSFEHLER!\n";
        
        if (isset($responseData['errors'])) {
            echo "Validierungs-Fehler:\n";
            foreach ($responseData['errors'] as $field => $errors) {
                echo "  $field: " . implode(', ', $errors) . "\n";
            }
        }
        
        if (isset($responseData['message'])) {
            echo "Fehlermeldung: {$responseData['message']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ FEHLER:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== ZUSÄTZLICHE DEBUGGING INFORMATIONEN ===\n";
echo "🔍 Prüfe Validierungs-Regeln aus TaskApiController:\n";
echo "- title: required|string|max:255\n";
echo "- description: nullable|string\n";  
echo "- task_type: required|string|max:255\n";
echo "- task_type_id: required|integer|exists:task_types,id\n";
echo "- priority: required|in:low,medium,high,urgent\n";
echo "- status: required|in:open,in_progress,waiting_external,waiting_internal,completed,cancelled\n";
echo "- assigned_to: nullable|exists:users,id\n";
echo "- owner_id: nullable|exists:users,id\n";
echo "- due_date: nullable|date\n";
echo "- due_time: nullable|date_format:H:i\n";
echo "- estimated_minutes: nullable|integer|min:0\n";
