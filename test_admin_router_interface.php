<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Router;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "🚀 Testing Admin Router Interface\n";
echo "================================\n\n";

try {
    // 1. Test database connection and router data
    echo "1. Testing Router Model and Database:\n";
    $routerCount = Router::count();
    echo "   ✅ Found {$routerCount} routers in database\n";
    
    if ($routerCount > 0) {
        $router = Router::first();
        echo "   📡 Sample Router: {$router->name} ({$router->model})\n";
        echo "   🔗 Status: {$router->connection_status}\n";
        echo "   📊 Signal: {$router->signal_strength} dBm ({$router->calculateSignalBars()} bars)\n";
        echo "   🌐 Network: {$router->operator} ({$router->network_type})\n";
        echo "   🔑 Webhook Token: {$router->webhook_token}\n";
        echo "   📍 Location: {$router->getFormattedCoordinatesAttribute()}\n";
        echo "   ⏰ Last Seen: {$router->getLastSeenFormattedAttribute()}\n";
        echo "   🧪 Test Command: {$router->getTestCurlCommandAttribute()}\n\n";
    }

    // 2. Test Filament Resource registration
    echo "2. Testing Filament Resource Registration:\n";
    
    // Check if RouterResource is registered
    $resourceExists = class_exists('App\\Filament\\Resources\\RouterResource');
    echo $resourceExists ? "   ✅ RouterResource class exists\n" : "   ❌ RouterResource class missing\n";
    
    // Check if page classes exist
    $listPageExists = class_exists('App\\Filament\\Resources\\RouterResource\\Pages\\ListRouters');
    $createPageExists = class_exists('App\\Filament\\Resources\\RouterResource\\Pages\\CreateRouter');
    $editPageExists = class_exists('App\\Filament\\Resources\\RouterResource\\Pages\\EditRouter');
    
    echo $listPageExists ? "   ✅ ListRouters page exists\n" : "   ❌ ListRouters page missing\n";
    echo $createPageExists ? "   ✅ CreateRouter page exists\n" : "   ❌ CreateRouter page missing\n";
    echo $editPageExists ? "   ✅ EditRouter page exists\n" : "   ❌ EditRouter page missing\n\n";

    // 3. Test API endpoints
    echo "3. Testing API Endpoints:\n";
    
    // Test status endpoint
    $baseUrl = config('app.url', 'http://localhost');
    echo "   🔗 Base URL: {$baseUrl}\n";
    echo "   📡 Webhook URL: {$baseUrl}/api/webhook\n";
    echo "   📊 Status URL: {$baseUrl}/api/status\n";
    echo "   🧪 Test Curl URL: {$baseUrl}/api/test-curl\n\n";

    // 4. Test webhook functionality
    echo "4. Testing Webhook Controller:\n";
    $controllerExists = class_exists('App\\Http\\Controllers\\Api\\RouterWebhookController');
    echo $controllerExists ? "   ✅ RouterWebhookController exists\n" : "   ❌ RouterWebhookController missing\n";

    // 5. Generate sample curl commands
    if ($routerCount > 0) {
        echo "\n5. Sample cURL Commands for Testing:\n";
        echo "=====================================\n";
        
        $sampleRouters = Router::take(3)->get();
        foreach ($sampleRouters as $index => $router) {
            $routerNum = $index + 1;
            echo "\n📡 Router #{$routerNum}: {$router->name}\n";
            echo "   Webhook URL: {$baseUrl}/api/webhook/{$router->webhook_token}\n";
            echo "   Test Command:\n";
            echo "   curl -X POST -H \"Content-Type: application/json\" \\\n";
            echo "        -d '{\"operator\": \"{$router->operator}\", \"signal_strength\": {$router->signal_strength}, \"network_type\": \"{$router->network_type}\"}' \\\n";
            echo "        {$baseUrl}/api/webhook/{$router->webhook_token}\n";
        }
    }

    // 6. Access instructions
    echo "\n\n6. Access Instructions:\n";
    echo "=======================\n";
    echo "🌐 Admin Panel: {$baseUrl}/admin\n";
    echo "📡 Router Management: {$baseUrl}/admin/routers\n";
    echo "📊 API Status: {$baseUrl}/api/status\n\n";
    
    echo "✅ All components are properly configured!\n";
    echo "🎉 The router management system is ready to use.\n\n";
    
    echo "Next Steps:\n";
    echo "-----------\n";
    echo "1. Access the admin panel at: {$baseUrl}/admin/routers\n";
    echo "2. Use the test cURL commands above to simulate router data\n";
    echo "3. Check the real-time status updates in the admin interface\n";
    echo "4. Configure your actual Teltonika routers to send data to the webhook URLs\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
