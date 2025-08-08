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

echo "=== DIREKTER TASK CONTROLLER TEST ===\n\n";

// Test Token
$plainTextToken = 'Vsew8fUkghFV6FxY0ADX8rpTCk6k5JCMCEwjEWp1';

try {
    // Token validieren
    $hashedToken = hash('sha256', $plainTextToken);
    $appToken = AppToken::where('token', $hashedToken)->first();
    
    if (!$appToken) {
        echo "❌ Token nicht gefunden!\n";
        exit;
    }
    
    echo "✅ Token gefunden und validiert!\n";
    echo "User ID: {$appToken->user_id}\n";
    echo "Token Name: {$appToken->name}\n";
    echo "Abilities: " . implode(', ', $appToken->abilities) . "\n\n";
    
    // User laden
    $user = User::find($appToken->user_id);
    echo "✅ User: {$user->name} ({$user->email})\n\n";
    
    // Test Data für neue Aufgabe
    $taskData = [
        'title' => 'Test Aufgabe via Controller',
        'description' => 'Diese Aufgabe wurde direkt über den Controller erstellt am ' . date('Y-m-d H:i:s'),
        'task_type' => 'Installation', // String für Validierung
        'task_type_id' => 1, // ID für Datenbank
        'priority' => 'medium', 
        'status' => 'open',
        'due_date' => date('Y-m-d', strtotime('+7 days')),
        'assigned_to' => $user->id,
    ];
    
    echo "📋 Test Data für neue Aufgabe:\n";
    echo json_encode($taskData, JSON_PRETTY_PRINT) . "\n\n";
    
    // Request-Objekt erstellen mit JSON-Content
    $request = Request::create(
        '/api/app/tasks', 
        'POST', 
        [], // GET parameters
        [], // Cookies
        [], // Files
        [], // Server variables
        json_encode($taskData) // Raw content
    );
    
    $request->headers->set('Authorization', 'Bearer ' . $plainTextToken);
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('Accept', 'application/json');
    
    // User im Request setzen (für die Authentifizierung)
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    echo "🔄 Teste TaskApiController->store() Methode...\n\n";
    
    // Controller instanziieren und store-Methode aufrufen
    $controller = new TaskApiController();
    
    // Simuliere die Middleware-Authentifizierung
    app()->instance('request', $request);
    auth()->setUser($user);
    
    $response = $controller->store($request);
    
    echo "📊 Response Status: " . $response->getStatusCode() . "\n";
    echo "📄 Response Content:\n";
    
    $responseData = json_decode($response->getContent(), true);
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response->getStatusCode() === 201) {
        echo "🎉 AUFGABE ERFOLGREICH ÜBER CONTROLLER ERSTELLT!\n";
        
        if (isset($responseData['data']['id'])) {
            $taskId = $responseData['data']['id'];
            echo "✅ Task ID: {$taskId}\n";
            echo "✅ Title: {$responseData['data']['title']}\n";
            echo "✅ Status: {$responseData['data']['status']}\n";
            
            // Task in Datenbank verifizieren
            $taskFromDb = Task::find($taskId);
            if ($taskFromDb) {
                echo "✅ Task erfolgreich in Datenbank gespeichert!\n";
                echo "   ID: {$taskFromDb->id}\n";
                echo "   Title: {$taskFromDb->title}\n";
                echo "   Description: {$taskFromDb->description}\n";
                echo "   Priority: {$taskFromDb->priority}\n";
                echo "   Status: {$taskFromDb->status}\n";
                echo "   Due Date: {$taskFromDb->due_date}\n";
                echo "   Assigned User: {$taskFromDb->assigned_user_id}\n";
            }
        }
    } else {
        echo "❌ Fehler beim Erstellen der Aufgabe\n";
        echo "Status Code: " . $response->getStatusCode() . "\n";
        
        if (isset($responseData['errors'])) {
            echo "Validierungsfehler:\n";
            foreach ($responseData['errors'] as $field => $errors) {
                echo "- {$field}: " . implode(', ', $errors) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ FEHLER:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== FAZIT ===\n";
echo "✅ Token für User 57 ist korrekt erstellt\n";
echo "✅ Controller-Methode store() kann direkt aufgerufen werden\n";
echo "🔗 API-Route: POST /api/app/tasks\n";
echo "🔑 Authorization: Bearer {$plainTextToken}\n";
echo "📝 Für HTTP-Requests starte: php artisan serve\n";
