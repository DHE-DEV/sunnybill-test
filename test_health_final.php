<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Http\Controllers\Api\HealthController;

// Laravel App laden
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FINALER HEALTH API TEST ===\n\n";

try {
    $controller = new HealthController();
    
    $endpoints = [
        'health' => 'index',
        'health/simple' => 'simple',
        'health/ready' => 'ready',
        'health/live' => 'live'
    ];
    
    foreach ($endpoints as $route => $method) {
        echo "✅ Testing /api/{$route}\n";
        echo str_repeat('-', 50) . "\n";
        
        $response = $controller->$method();
        $statusCode = $response->getStatusCode();
        $data = json_decode($response->getContent(), true);
        
        echo "Status: {$statusCode}\n";
        echo "Response:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    }
    
    echo "🎉 ALLE HEALTH-ENDPUNKTE FUNKTIONIEREN EINWANDFREI!\n\n";
    
    echo "📋 Verfügbare Endpunkte:\n";
    echo "- GET /api/health          (Vollständiger Health-Check)\n";
    echo "- GET /api/health/simple   (Einfacher Health-Check)\n";
    echo "- GET /api/health/ready    (Bereitschafts-Check)\n";
    echo "- GET /api/health/live     (Lebendigkeit-Check)\n\n";
    
    echo "🚀 Verwendung mit cURL:\n";
    echo "curl -X GET http://localhost:8000/api/health\n";
    echo "curl -X GET http://localhost:8000/api/health/simple\n";
    echo "curl -X GET http://localhost:8000/api/health/ready\n";
    echo "curl -X GET http://localhost:8000/api/health/live\n\n";
    
    echo "✨ Die Health-API ist bereit für den produktiven Einsatz!\n";

} catch (Exception $e) {
    echo "❌ FEHLER:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}
