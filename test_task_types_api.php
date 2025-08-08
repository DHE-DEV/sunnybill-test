<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\TaskApiController;
use App\Models\AppToken;
use App\Models\User;

// Laravel App laden
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TASK TYPES API TEST ===\n\n";

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
    echo "User ID: {$appToken->user_id}\n\n";
    
    // User laden
    $user = User::find($appToken->user_id);
    
    // Request für Task Types erstellen
    $request = Request::create('/api/app/task-types', 'GET');
    $request->headers->set('Authorization', 'Bearer ' . $plainTextToken);
    $request->headers->set('Accept', 'application/json');
    
    // User und Token im Request setzen
    $request->setUserResolver(function() use ($user) {
        return $user;
    });
    
    $request->app_token = $appToken;
    
    echo "🔄 Teste GET /api/app/task-types...\n\n";
    
    // Controller aufrufen
    $controller = new TaskApiController();
    $response = $controller->taskTypes();
    
    echo "📊 Response Status: " . $response->getStatusCode() . "\n";
    echo "📄 Task Types Response:\n";
    
    $responseData = json_decode($response->getContent(), true);
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response->getStatusCode() === 200 && $responseData['success']) {
        echo "🎉 TASK TYPES ERFOLGREICH ABGERUFEN!\n\n";
        
        if (isset($responseData['data']) && is_array($responseData['data'])) {
            echo "📋 Verfügbare Task Types:\n";
            foreach ($responseData['data'] as $taskType) {
                echo "   - ID: {$taskType['id']} | Name: {$taskType['name']}";
                if (isset($taskType['slug'])) {
                    echo " | Slug: {$taskType['slug']}";
                }
                if (isset($taskType['description']) && $taskType['description']) {
                    echo " | Beschreibung: {$taskType['description']}";
                }
                echo "\n";
            }
            
            echo "\n💡 Für Task-Erstellung verwende die 'id' als 'task_type_id'!\n";
            echo "Beispiel: 'task_type_id' => {$responseData['data'][0]['id']}\n";
        }
    } else {
        echo "❌ Fehler beim Abrufen der Task Types\n";
        echo "Status Code: " . $response->getStatusCode() . "\n";
        
        if (isset($responseData['message'])) {
            echo "Fehlermeldung: {$responseData['message']}\n";
        }
        
        if (isset($responseData['errors'])) {
            echo "Fehler-Details:\n";
            print_r($responseData['errors']);
        }
    }
    
} catch (Exception $e) {
    echo "❌ FEHLER:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== API ENDPUNKT INFORMATIONEN ===\n";
echo "🔗 Endpunkt: GET /api/app/task-types\n";
echo "🔑 Authorization: Bearer {$plainTextToken}\n";
echo "📝 Für HTTP-Requests starte: php artisan serve\n\n";

echo "💡 cURL-Beispiel:\n";
echo "curl -X GET http://127.0.0.1:8000/api/app/task-types \\\n";
echo "  -H \"Accept: application/json\" \\\n";
echo "  -H \"Authorization: Bearer {$plainTextToken}\"\n";
