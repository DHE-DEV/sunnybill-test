<?php

echo "=== Test: Live Customer API Response ===\n\n";

// Test verschiedene API URLs
$urls = [
    'https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/customers',
    'https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/customers?per_page=1',
];

foreach ($urls as $url) {
    echo "Testing URL: $url\n";
    echo str_repeat('-', 50) . "\n";
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer sb_ee977b1e9f9c12ada1a69d64760c2b8f20e29d9489c88f111eea4e86cfd59890',
        ],
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        echo "❌ CURL Error: $error\n\n";
        continue;
    }
    
    echo "HTTP Status: $httpCode\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        
        if ($data) {
            echo "✅ Response received\n";
            echo "Structure:\n";
            print_r(array_keys($data));
            
            if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
                echo "\nFirst Customer Data:\n";
                $firstCustomer = $data['data'][0];
                
                // Prüfe wichtige Felder
                $importantFields = ['id', 'name', 'email', 'phone', 'street', 'city', 'postal_code', 'company_name'];
                foreach ($importantFields as $field) {
                    $value = $firstCustomer[$field] ?? 'NOT_FOUND';
                    echo "- $field: $value\n";
                }
                
                echo "\nAll available fields:\n";
                echo "Fields count: " . count($firstCustomer) . "\n";
                foreach (array_keys($firstCustomer) as $key) {
                    $value = $firstCustomer[$key];
                    if (is_array($value)) {
                        echo "- $key: [Array with " . count($value) . " elements]\n";
                    } elseif (is_null($value)) {
                        echo "- $key: NULL\n";
                    } else {
                        $displayValue = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                        echo "- $key: $displayValue\n";
                    }
                }
            } else {
                echo "❌ No customer data found\n";
            }
        } else {
            echo "❌ Invalid JSON response\n";
            echo "Raw response: " . substr($response, 0, 500) . "\n";
        }
    } else {
        echo "❌ HTTP Error: $httpCode\n";
        echo "Response: " . substr($response, 0, 500) . "\n";
    }
    
    echo "\n" . str_repeat('=', 60) . "\n\n";
}

echo "🎉 Test completed!\n";
