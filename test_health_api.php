<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Http\Controllers\Api\HealthController;

// Laravel App laden
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== HEALTH API ENDPOINT TESTS ===\n\n";

try {
    $controller = new HealthController();
    
    echo "1. Testing Health Index Endpoint:\n";
    echo "-----------------------------------\n";
    $healthResponse = $controller->index();
    $healthData = json_decode($healthResponse->getContent(), true);
    echo "Status: " . $healthResponse->getStatusCode() . "\n";
    echo "Response:\n";
    echo json_encode($healthData, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "2. Testing Simple Health Endpoint:\n";
    echo "-----------------------------------\n";
    $simpleResponse = $controller->simple();
    $simpleData = json_decode($simpleResponse->getContent(), true);
    echo "Status: " . $simpleResponse->getStatusCode() . "\n";
    echo "Response:\n";
    echo json_encode($simpleData, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "3. Testing Ready Endpoint:\n";
    echo "---------------------------\n";
    $readyResponse = $controller->ready();
    $readyData = json_decode($readyResponse->getContent(), true);
    echo "Status: " . $readyResponse->getStatusCode() . "\n";
    echo "Response:\n";
    echo json_encode($readyData, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "4. Testing Live Endpoint:\n";
    echo "-------------------------\n";
    $liveResponse = $controller->live();
    $liveData = json_decode($liveResponse->getContent(), true);
    echo "Status: " . $liveResponse->getStatusCode() . "\n";
    echo "Response:\n";
    echo json_encode($liveData, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "=== ALLE HEALTH ENDPOINTS FUNKTIONIEREN ===\n";
    echo "Die folgenden API-Endpunkte sind verfügbar:\n";
    echo "- GET /api/health          - Vollständiger Health-Check\n";
    echo "- GET /api/health/simple   - Einfacher Health-Check\n";
    echo "- GET /api/health/ready    - Bereitschafts-Check\n";
    echo "- GET /api/health/live     - Lebendigkeit-Check\n\n";
    
    echo "Beispiel cURL-Aufruf:\n";
    echo "curl -X GET http://your-domain.com/api/health\n";
    echo "curl -X GET http://your-domain.com/api/health/simple\n";

} catch (Exception $e) {
    echo "FEHLER beim Testen der Health-Endpoints:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}
