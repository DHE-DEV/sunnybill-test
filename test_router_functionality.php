<?php

require_once 'vendor/autoload.php';

use App\Models\Router;
use Illuminate\Foundation\Application;

// Create a simple Laravel application context
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Router Management Test ===\n\n";

try {
    // Test 1: Create sample routers
    echo "1. Creating sample routers...\n";
    
    $router1 = Router::create([
        'name' => 'Hauptgebäude Router',
        'model' => 'RUTX50',
        'serial_number' => 'RUTX50-001',
        'location' => 'Hauptgebäude, Erdgeschoss',
        'ip_address' => '192.168.1.100',
        'latitude' => 51.1657,
        'longitude' => 10.4515,
        'operator' => 'Telekom.de',
        'signal_strength' => -65,
        'network_type' => '5G',
        'is_active' => true,
        'last_seen_at' => now(),
        'notes' => 'Hauptrouter für das Bürogebäude'
    ]);
    
    $router2 = Router::create([
        'name' => 'Nebengebäude Router', 
        'model' => 'RUTX50',
        'serial_number' => 'RUTX50-002',
        'location' => 'Nebengebäude, 1. Stock',
        'ip_address' => '192.168.1.101',
        'latitude' => 51.1658,
        'longitude' => 10.4516,
        'operator' => 'Vodafone',
        'signal_strength' => -78,
        'network_type' => '4G',
        'is_active' => true,
        'last_seen_at' => now()->subMinutes(5),
        'notes' => 'Backup-Router für das Nebengebäude'
    ]);
    
    $router3 = Router::create([
        'name' => 'Mobile Router',
        'model' => 'RUTX50', 
        'serial_number' => 'RUTX50-003',
        'location' => 'Fahrzeug #1',
        'ip_address' => '192.168.1.102',
        'operator' => 'O2',
        'signal_strength' => -85,
        'network_type' => '4G',
        'is_active' => true,
        'last_seen_at' => now()->subMinutes(15),
        'notes' => 'Mobiler Router im Servicefahrzeug'
    ]);
    
    echo "✓ Created 3 sample routers\n\n";
    
    // Test 2: Test status calculations
    echo "2. Testing status calculations...\n";
    
    foreach (Router::all() as $router) {
        $router->updateConnectionStatus();
        echo "- {$router->name}: {$router->connection_status}\n";
        echo "  Signal: {$router->signal_strength} dBm ({$router->calculateSignalBars()} bars)\n";
        echo "  Last seen: {$router->getLastSeenFormattedAttribute()}\n";
        echo "  Webhook URL: {$router->getWebhookUrlAttribute()}\n\n";
    }
    
    // Test 3: Test webhook simulation
    echo "3. Testing webhook data processing...\n";
    
    $testWebhookData = [
        'operator' => 'Telekom.de',
        'signal_strength' => -72,
        'network_type' => '5G'
    ];
    
    $router1->updateFromWebhook($testWebhookData);
    $router1->refresh();
    
    echo "✓ Updated router1 with webhook data\n";
    echo "- New signal strength: {$router1->signal_strength} dBm\n";
    echo "- Connection status: {$router1->connection_status}\n";
    echo "- Total webhooks: {$router1->total_webhooks}\n\n";
    
    // Test 4: Show curl commands
    echo "4. Test curl commands for webhook testing:\n\n";
    
    foreach (Router::all() as $router) {
        echo "Router: {$router->name}\n";
        echo $router->getTestCurlCommandAttribute() . "\n\n";
    }
    
    echo "=== Test Summary ===\n";
    echo "Total routers created: " . Router::count() . "\n";
    echo "Active routers: " . Router::where('is_active', true)->count() . "\n";
    echo "Online routers: " . Router::where('connection_status', 'online')->count() . "\n";
    echo "Delayed routers: " . Router::where('connection_status', 'delayed')->count() . "\n";
    echo "Offline routers: " . Router::where('connection_status', 'offline')->count() . "\n";
    
    echo "\n✓ Router functionality test completed successfully!\n";
    echo "\nYou can now:\n";
    echo "1. Access the admin panel at /admin/routers to manage routers\n";
    echo "2. Test webhook endpoints using the curl commands shown above\n";
    echo "3. View router status at /api/status\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
