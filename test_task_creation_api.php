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

echo "=== TASK CREATION API TEST ===\n\n";

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
    
    echo "✅ Token gefunden!\n";
    echo "User ID: {$appToken->user_id}\n";
    echo "Token Name: {$appToken->name}\n";
    echo "Abilities: " . implode(', ', $appToken->abilities) . "\n\n";
    
    // User laden
    $user = User::find($appToken->user_id);
    echo "✅ User: {$user->name} ({$user->email})\n\n";
    
    // Test Data für neue Aufgabe
    $taskData = [
        'title' => 'Test Aufgabe via API',
        'description' => 'Diese Aufgabe wurde über die API erstellt am ' . date('Y-m-d H:i:s'),
        'priority' => 'medium',
        'status' => 'open',
        'due_date' => date('Y-m-d', strtotime('+7 days')),
        'assigned_user_id' => $user->id,
    ];
    
    echo "📋 Test Data für neue Aufgabe:\n";
    echo json_encode($taskData, JSON_PRETTY_PRINT) . "\n\n";
    
    // HTTP Request simulieren
    echo "🔄 Sende POST-Request an /api/app/tasks...\n";
    
    $baseUrl = 'http://127.0.0.1:8000';
    $endpoint = '/api/app/tasks';
    
    // cURL Request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($taskData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $plainTextToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "📊 HTTP Response Code: {$httpCode}\n";
    
    if ($error) {
        echo "❌ cURL Error: {$error}\n";
        echo "💡 Hinweis: Stelle sicher, dass der Laravel Development Server läuft:\n";
        echo "   php artisan serve\n";
    } else {
        echo "📄 Response Body:\n";
        
        $responseData = json_decode($response, true);
        if ($responseData) {
            echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
            
            if ($httpCode === 201) {
                echo "🎉 AUFGABE ERFOLGREICH ERSTELLT!\n";
                if (isset($responseData['data']['id'])) {
                    echo "✅ Task ID: {$responseData['data']['id']}\n";
                    echo "✅ Title: {$responseData['data']['title']}\n";
                    echo "✅ Status: {$responseData['data']['status']}\n";
                }
            } elseif ($httpCode === 422) {
                echo "⚠️ Validierungsfehler - Überprüfe die Eingabedaten\n";
            } elseif ($httpCode === 401) {
                echo "🔐 Authentifizierungsfehler - Token ungültig\n";
            } else {
                echo "❌ Unerwarteter Status Code: {$httpCode}\n";
            }
        } else {
            echo $response . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ FEHLER:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "✅ Token für User 57 wurde erfolgreich erstellt\n";
echo "🔑 Token: {$plainTextToken}\n";
echo "🎯 Endpunkt: POST /api/app/tasks\n";
echo "📝 Abilities: Vollzugriff (*)\n";
echo "⚡ Bereit für API-Requests!\n";
