<?php

echo "=== Test: Korrekte Customer API Route ===\n\n";

$token = 'sb_IxnvbXjPpg2Z1sDoj6xDH54kFgT9egB8ndqwP4U7v4mdrsH8TDiBx3OiV3q05pZU';

// Test 1: Alte Route (TaskApiController) - nur 3 Felder
echo "1. FALSCHE Route: /api/app/customers (TaskApiController)\n";
echo "URL: https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/customers\n";
echo "--------------------------------------------------\n";

$ch1 = curl_init();
curl_setopt($ch1, CURLOPT_URL, 'https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/customers');
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response1 = curl_exec($ch1);
$httpCode1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
curl_close($ch1);

echo "HTTP Status: $httpCode1\n";
if ($response1) {
    $data1 = json_decode($response1, true);
    if (isset($data1['data'][0])) {
        echo "Felder: " . count(get_object_vars((object)$data1['data'][0])) . "\n";
        echo "Feldnamen: " . implode(', ', array_keys($data1['data'][0])) . "\n";
    }
} else {
    echo "❌ Keine Response\n";
}

echo "\n============================================================\n\n";

// Test 2: Neue Route (CustomerApiController) - alle Felder
echo "2. RICHTIGE Route: /api/app/customers/ (CustomerApiController)\n";
echo "URL: https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/customers/\n";
echo "--------------------------------------------------\n";

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, 'https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/customers/');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Status: $httpCode2\n";
if ($response2) {
    $data2 = json_decode($response2, true);
    if (isset($data2['data'][0])) {
        echo "✅ Felder: " . count(get_object_vars((object)$data2['data'][0])) . "\n";
        echo "Email: " . ($data2['data'][0]['email'] ?? 'NICHT_GEFUNDEN') . "\n";
        echo "Phone: " . ($data2['data'][0]['phone'] ?? 'NICHT_GEFUNDEN') . "\n";
        echo "Street: " . ($data2['data'][0]['street'] ?? 'NICHT_GEFUNDEN') . "\n";
        echo "City: " . ($data2['data'][0]['city'] ?? 'NICHT_GEFUNDEN') . "\n";
        echo "Postal Code: " . ($data2['data'][0]['postal_code'] ?? 'NICHT_GEFUNDEN') . "\n";
        
        echo "\nAlle Feldnamen:\n";
        foreach (array_keys($data2['data'][0]) as $field) {
            echo "- $field\n";
        }
    }
} else {
    echo "❌ Keine Response\n";
}

echo "\n🎉 Test abgeschlossen!\n";
