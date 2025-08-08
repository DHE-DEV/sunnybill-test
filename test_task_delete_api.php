<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\TaskApiController;
use App\Models\AppToken;
use App\Models\User;
use App\Models\Task;

// Laravel App laden
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TASK DELETE API TEST ===\n\n";

// Token für User 57 (neuer Token mit allen spezifischen Berechtigungen)
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
    echo "User ID: {$appToken->user_id}\n";
    
    // Prüfe DELETE-Berechtigung
    echo "Hat 'tasks:delete' Berechtigung: " . ($appToken->hasAbility('tasks:delete') ? 'JA' : 'NEIN') . "\n\n";
    
    // User laden
    $user = User::find($appToken->user_id);
    
    // Suche eine existierende Task zum Testen (nach assigned_to, owner_id oder created_by)
    $task = Task::where(function($query) use ($appToken) {
        $query->where('assigned_to', $appToken->user_id)
              ->orWhere('owner_id', $appToken->user_id)
              ->orWhere('created_by', $appToken->user_id);
    })->first();
    
    if (!$task) {
        echo "⚠️ Keine Task für User {$appToken->user_id} gefunden. Erstelle Test-Task...\n";
        
        // Erstelle eine Test-Task
        $task = Task::create([
            'title' => 'Test Task für DELETE API',
            'description' => 'Diese Task wird für DELETE-Tests verwendet',
            'assigned_to' => $appToken->user_id,
            'owner_id' => $appToken->user_id,
            'created_by' => $appToken->user_id,
            'task_type_id' => 1, // Installation
            'priority' => 'medium',
            'status' => 'open',
        ]);
        
        echo "✅ Test-Task erstellt mit ID: {$task->id}\n";
    } else {
        echo "✅ Verwende existierende Task ID: {$task->id}\n";
    }
    
    echo "Task Titel: {$task->title}\n";
    echo "Task Status: {$task->status}\n\n";
    
    // Request für Task Delete erstellen
    $request = Request::create("/api/app/tasks/{$task->id}", 'DELETE');
    $request->headers->set('Authorization', 'Bearer ' . $plainTextToken);
    $request->headers->set('Accept', 'application/json');
    
    // User und Token im Request setzen
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    $request->app_token = $appToken;
    
    echo "🔄 Teste DELETE /api/app/tasks/{$task->id}...\n\n";
    
    // Controller aufrufen
    $controller = new TaskApiController();
    $response = $controller->destroy($task);
    
    echo "📊 Response Status: " . $response->getStatusCode() . "\n";
    echo "📄 Delete Response:\n";
    
    $responseData = json_decode($response->getContent(), true);
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response->getStatusCode() === 200) {
        echo "🎉 TASK ERFOLGREICH GELÖSCHT!\n\n";
        
        // Prüfe ob Task wirklich gelöscht wurde
        $deletedTask = Task::find($task->id);
        if (!$deletedTask) {
            echo "✅ Task wurde aus Datenbank entfernt\n";
        } else {
            echo "⚠️ Task ist noch in Datenbank vorhanden\n";
            echo "Deleted_at: " . ($deletedTask->deleted_at ?? 'null') . "\n";
        }
    } else {
        echo "❌ Fehler beim Löschen der Task\n";
        echo "Status Code: " . $response->getStatusCode() . "\n";
        
        if (isset($responseData['message'])) {
            echo "Fehlermeldung: {$responseData['message']}\n";
        }
        
        if (isset($responseData['errors'])) {
            echo "Fehler-Details:\n";
            print_r($responseData['errors']);
        }
        
        if (isset($responseData['required_abilities'])) {
            echo "Benötigte Berechtigungen: " . implode(', ', $responseData['required_abilities']) . "\n";
        }
        
        if (isset($responseData['token_abilities'])) {
            echo "Token Berechtigungen: " . implode(', ', array_slice($responseData['token_abilities'], 0, 10)) . "...\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ FEHLER:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== API ENDPUNKT INFORMATIONEN ===\n";
echo "🔗 Endpunkt: DELETE /api/app/tasks/{task_id}\n";
echo "🔑 Authorization: Bearer {$plainTextToken}\n";
echo "📝 Benötigt Berechtigung: tasks:delete\n\n";

echo "💡 cURL-Beispiel:\n";
echo "curl -X DELETE https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/tasks/123 \\\n";
echo "  -H \"Accept: application/json\" \\\n";
echo "  -H \"Authorization: Bearer {$plainTextToken}\"\n";
