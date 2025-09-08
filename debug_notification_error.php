<?php

/**
 * Comprehensive debugging script for notification API issues
 * This will help identify the exact cause of the HTML vs JSON problem
 */

require_once 'vendor/autoload.php';

echo "=== Notification API Debug Tool ===\n\n";

// Test different scenarios to identify the problem
$baseUrl = 'http://localhost:8000'; // Adjust if needed
$testCases = [
    [
        'name' => 'Test without token (should get 401)',
        'url' => '/api/notifications/count',
        'headers' => [
            'Accept: application/json',
            'Content-Type: application/json',
        ]
    ],
    [
        'name' => 'Test with invalid token (should get 401)', 
        'url' => '/api/notifications/count',
        'headers' => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer invalid-token-123'
        ]
    ],
    [
        'name' => 'Test wrong endpoint (should get 404)',
        'url' => '/api/notifications/wrong-endpoint',
        'headers' => [
            'Accept: application/json',
            'Content-Type: application/json',
        ]
    ]
];

foreach ($testCases as $test) {
    echo "--- {$test['name']} ---\n";
    echo "URL: {$baseUrl}{$test['url']}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $test['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $test['headers']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    
    echo "HTTP Status: $httpCode\n";
    if ($redirectUrl) {
        echo "Redirect URL: $redirectUrl\n";
    }
    
    // Check response type
    if (empty($response)) {
        echo "Response: [EMPTY]\n";
    } else if (substr($response, 0, 9) === '<!DOCTYPE') {
        echo "Response Type: HTML (ERROR - this is the problem!)\n";
        echo "Response Preview: " . substr($response, 0, 200) . "...\n";
    } else if (substr($response, 0, 1) === '{' || substr($response, 0, 1) === '[') {
        echo "Response Type: JSON (GOOD)\n";
        echo "Response: $response\n";
    } else {
        echo "Response Type: OTHER\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
    
    echo "\n" . str_repeat('-', 60) . "\n\n";
}

echo "=== Debugging Notes ===\n";
echo "1. If all tests return HTML, the Laravel app might be misconfigured\n";
echo "2. If 401/403 returns HTML instead of JSON, there's a middleware redirect issue\n";
echo "3. If 404 returns HTML, Laravel's exception handling isn't returning JSON\n";
echo "4. Check if the frontend is actually calling the right URL\n";
echo "5. Verify that Accept: application/json header is being sent by frontend\n\n";

echo "Run this script with: php debug_notification_error.php\n";
