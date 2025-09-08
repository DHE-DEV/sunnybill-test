<?php

// Simple test to check notification API with curl
// This test simulates what the frontend JavaScript should be doing

echo "=== Notification API Test ===\n\n";

// Test the notification count endpoint
echo "Testing /api/notifications/count\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/notifications/count");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    // Note: You'll need to add a valid Bearer token here for testing
    // 'Authorization: Bearer YOUR_TOKEN_HERE'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Response: $response\n\n";

// Check if response is JSON or HTML
if (substr($response, 0, 9) === '<!DOCTYPE') {
    echo "ERROR: Received HTML instead of JSON - this indicates authentication failure or server error\n";
} else {
    echo "SUCCESS: Received what appears to be JSON response\n";
}

echo "\n=== Test completed ===\n";
