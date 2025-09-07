<?php

// Simple test script to verify router webhook functionality

$webhook_url = 'https://sunnybill-test.test/api/router-webhook/test-token-123';

$test_data = [
    'operator' => 'Telekom',
    'signal_strength' => -75,
    'network_type' => 'LTE', 
    'connection_time' => 3600,
    'data_usage_mb' => 125.5,
    'ip_address' => '192.168.1.100'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "Testing Router Webhook Integration\n";
echo "URL: $webhook_url\n";
echo "Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_error($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
} else {
    echo "HTTP Status: $http_code\n";
    echo "Response: $response\n";
}

curl_close($ch);

// Also test a case with missing data to verify validation
echo "\n" . str_repeat("-", 50) . "\n";
echo "Testing validation with incomplete data:\n";

$invalid_data = [
    'operator' => 'Telekom'
    // Missing required fields signal_strength and network_type
];

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $webhook_url);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($invalid_data));
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 30);

echo "Invalid Data: " . json_encode($invalid_data, JSON_PRETTY_PRINT) . "\n\n";

$response2 = curl_exec($ch2);
$http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

if (curl_error($ch2)) {
    echo "cURL Error: " . curl_error($ch2) . "\n";
} else {
    echo "HTTP Status: $http_code2\n";
    echo "Response: $response2\n";
}

curl_close($ch2);

echo "\nTest completed!\n";
