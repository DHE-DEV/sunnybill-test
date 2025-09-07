<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test the transformation
$testData = [
    'VoltMaster' => [
        'ip' => ['10.0.0.1'],
        'rssi' => -75,
        'operator' => 'Vodafone_DE',
        'conntype' => '4G'
    ]
];

$controller = new App\Http\Controllers\Api\RouterWebhookController();

// Use reflection to access private method
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('transformVoltMasterData');
$method->setAccessible(true);

$result = $method->invoke($controller, $testData);

echo "Input data:\n";
print_r($testData);

echo "\nTransformed data:\n";
print_r($result);

echo "\nExpected fields:\n";
echo "- operator: " . ($result['operator'] ?? 'MISSING') . "\n";
echo "- signal_strength: " . ($result['signal_strength'] ?? 'MISSING') . "\n";
echo "- network_type: " . ($result['network_type'] ?? 'MISSING') . "\n";
echo "- ip_address: " . ($result['ip_address'] ?? 'MISSING') . "\n";