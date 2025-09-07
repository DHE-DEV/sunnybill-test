<?php

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== 1nce API Debug Script ===\n\n";

// Check credentials
$clientId = $_ENV['1NCE_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['1NCE_CLIENT_SECRET'] ?? null;

echo "1. Checking credentials:\n";
echo "   Client ID: " . ($clientId ? $clientId : 'NOT FOUND') . "\n";
echo "   Client Secret: " . ($clientSecret ? str_repeat('*', strlen($clientSecret)) : 'NOT FOUND') . "\n\n";

if (!$clientId || !$clientSecret) {
    echo "ERROR: Missing credentials in .env file\n";
    exit(1);
}

// Test authentication
echo "2. Testing authentication:\n";

// Use Basic Authentication with Base64 encoded credentials
$credentials = base64_encode($clientId . ':' . $clientSecret);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.1nce.com/management-api/oauth/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'grant_type' => 'client_credentials'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $credentials,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
if ($error) {
    echo "   cURL Error: $error\n";
}

if ($response) {
    echo "   Response: $response\n\n";
    
    $data = json_decode($response, true);
    if (isset($data['access_token'])) {
        echo "   ✓ Authentication successful!\n";
        $accessToken = $data['access_token'];
        
        // Test API call
        echo "\n3. Testing API call:\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.1nce.com/management-api/v1/sims');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);
        
        $apiResponse = curl_exec($ch);
        $apiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $apiError = curl_error($ch);
        curl_close($ch);
        
        echo "   HTTP Code: $apiHttpCode\n";
        if ($apiError) {
            echo "   cURL Error: $apiError\n";
        }
        
        if ($apiResponse) {
            echo "   API Response: " . substr($apiResponse, 0, 200) . "...\n";
            
            $apiData = json_decode($apiResponse, true);
            if (is_array($apiData) && count($apiData) > 0) {
                echo "   ✓ API call successful! Found " . count($apiData) . " SIM cards\n";
                
                // Show sample data from first SIM card
                if (isset($apiData[0])) {
                    $firstSim = $apiData[0];
                    echo "   Sample SIM data:\n";
                    echo "     - ICCID: " . ($firstSim['iccid'] ?? 'N/A') . "\n";
                    echo "     - MSISDN: " . ($firstSim['msisdn'] ?? 'N/A') . "\n";
                    echo "     - Status: " . ($firstSim['status'] ?? 'N/A') . "\n";
                }
            } else {
                echo "   ⚠ Unexpected response format or no data\n";
                echo "   Raw response: " . substr($apiResponse, 0, 500) . "\n";
            }
        } else {
            echo "   ✗ No API response received\n";
        }
        
    } else {
        echo "   ✗ Authentication failed!\n";
        if (isset($data['error'])) {
            echo "   Error: " . $data['error'] . "\n";
            if (isset($data['error_description'])) {
                echo "   Description: " . $data['error_description'] . "\n";
            }
        }
    }
} else {
    echo "   ✗ No response received\n";
}

echo "\n=== Debug Complete ===\n";
