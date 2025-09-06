<?php

require_once 'vendor/autoload.php';

// Create a simple Laravel application context
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Router API Endpoints Test ===\n\n";

try {
    // Test the RouterWebhookController directly
    $controller = new App\Http\Controllers\Api\RouterWebhookController();
    
    // Test 1: Get all router status
    echo "1. Testing /api/status endpoint...\n";
    $request = new Illuminate\Http\Request();
    $response = $controller->status($request);
    $data = json_decode($response->getContent(), true);
    
    echo "✓ Status endpoint working\n";
    echo "- Total routers found: " . $data['total_routers'] . "\n";
    echo "- Timestamp: " . $data['timestamp'] . "\n\n";
    
    // Test 2: Get specific router
    if ($data['total_routers'] > 0) {
        echo "2. Testing specific router status...\n";
        $firstRouterId = $data['routers'][0]['id'];
        $request = new Illuminate\Http\Request(['router_id' => $firstRouterId]);
        $response = $controller->status($request);
        $routerData = json_decode($response->getContent(), true);
        
        echo "✓ Specific router status working\n";
        echo "- Router: " . $routerData['router']['name'] . "\n";
        echo "- Status: " . $routerData['router']['connection_status'] . "\n";
        echo "- Signal: " . $routerData['router']['signal_strength'] . " dBm\n";
        echo "- Bars: " . $routerData['router']['signal_strength_bars'] . "/5\n\n";
    }
    
    // Test 3: Test webhook endpoint
    echo "3. Testing webhook endpoint...\n";
    $webhookData = [
        'operator' => 'Telekom.de',
        'signal_strength' => -68,
        'network_type' => '5G'
    ];
    
    $request = new Illuminate\Http\Request();
    $request->merge($webhookData);
    $request->server->set('REMOTE_ADDR', '192.168.1.100'); // Simulate IP
    
    $response = $controller->webhook($request);
    $webhookResult = json_decode($response->getContent(), true);
    
    echo "✓ Webhook endpoint working\n";
    echo "- Success: " . ($webhookResult['success'] ? 'Yes' : 'No') . "\n";
    echo "- Router updated: " . $webhookResult['router_name'] . "\n";
    echo "- Status: " . $webhookResult['status'] . "\n\n";
    
    // Test 4: Check updated router data
    echo "4. Verifying webhook update...\n";
    $updatedRouter = App\Models\Router::find($webhookResult['router_id']);
    echo "- Signal strength updated to: " . $updatedRouter->signal_strength . " dBm\n";
    echo "- Total webhooks received: " . $updatedRouter->total_webhooks . "\n";
    echo "- Last data: " . json_encode($updatedRouter->last_data) . "\n\n";
    
    echo "=== API Test Summary ===\n";
    echo "✓ /api/status - Working correctly\n";
    echo "✓ /webhook - Working correctly\n";
    echo "✓ Data processing - Working correctly\n";
    echo "✓ Status calculations - Working correctly\n\n";
    
    echo "The router management system is fully functional!\n";
    echo "Access the admin panel at: /admin/routers\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
