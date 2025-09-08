<?php

require 'vendor/autoload.php';

// Test notification API endpoints
$baseUrl = 'http://sunnybill-test.test'; // Adjust this to your local domain
$endpoints = [
    '/api/notifications/count',
    '/api/notifications'
];

// You'll need a valid API token - get this from your database or create one
$token = 'your-api-token-here'; // Replace with actual token

foreach ($endpoints as $endpoint) {
    echo "Testing: {$baseUrl}{$endpoint}\n";
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $baseUrl . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    echo "HTTP Status: {$httpCode}\n";
    echo "Response: " . ($response ?: 'No response') . "\n";
    echo str_repeat('-', 50) . "\n";
}
