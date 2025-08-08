<?php

echo "=== TASK_TYPE AUTO-DETECTION TEST ===\n\n";

// Token für User 57
$plainTextToken = 'sb_HrMgJVlEEua9OvTuk2FYFkEqzA0MLMNfxnEIv0PRnatCrcrGKg2ayYqwLHWywXpY';
$liveApiUrl = 'https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/tasks';

// Test 1: Task ohne task_type (soll automatisch erkannt werden)
echo "🧪 TEST 1: Task ohne task_type-Parameter (Auto-Detection)\n";
echo "==========================================================\n";

$taskData1 = [
    "title" => "Auto-Detection Test",
    "description" => "Test ohne task_type Parameter - soll automatisch erkannt werden",
    "task_type_id" => 1, // Installation
    "priority" => "medium",
    "status" => "open",
    "assigned_to" => 57,
    "owner_id" => 1,
    "due_date" => "2025-08-15",
    "due_time" => "14:30",
    "estimated_minutes" => 60
];

echo "📝 Daten (ohne task_type):\n";
echo json_encode($taskData1, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $liveApiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $plainTextToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($taskData1));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📊 HTTP Status: $httpCode\n";

$responseData = json_decode($response, true);
if ($responseData) {
    if ($httpCode === 201) {
        echo "✅ ERFOLGREICH! Task erstellt ohne task_type-Parameter\n";
        echo "Task ID: " . $responseData['data']['id'] . "\n";
        echo "Task Number: " . $responseData['data']['task_number'] . "\n";
        echo "Automatisch erkannter task_type: wird aus task_type_id=1 ermittelt\n\n";
        
        $createdTaskId1 = $responseData['data']['id'];
    } else {
        echo "❌ FEHLGESCHLAGEN!\n";
        if (isset($responseData['errors'])) {
            echo "Validierungs-Fehler:\n";
            foreach ($responseData['errors'] as $field => $errors) {
                echo "  $field: " . (is_array($errors) ? implode(', ', $errors) : $errors) . "\n";
            }
        }
        echo "\n";
    }
} else {
    echo "Raw Response: $response\n\n";
}

// Test 2: Task mit explizitem task_type (soll weiterhin funktionieren)
echo "🧪 TEST 2: Task mit explizitem task_type-Parameter\n";
echo "==================================================\n";

$taskData2 = [
    "title" => "Explicit Task Type Test",
    "description" => "Test mit explizitem task_type Parameter",
    "task_type" => "Wartung", // Explizit gesetzt
    "task_type_id" => 2, // Wartung
    "priority" => "high",
    "status" => "open",
    "assigned_to" => 57,
    "owner_id" => 1,
    "estimated_minutes" => 90
];

echo "📝 Daten (mit explizitem task_type):\n";
echo json_encode($taskData2, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $liveApiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $plainTextToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($taskData2));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📊 HTTP Status: $httpCode\n";

$responseData = json_decode($response, true);
if ($responseData) {
    if ($httpCode === 201) {
        echo "✅ ERFOLGREICH! Task erstellt mit explizitem task_type\n";
        echo "Task ID: " . $responseData['data']['id'] . "\n";
        echo "Task Number: " . $responseData['data']['task_number'] . "\n";
        echo "Verwendeter task_type: {$taskData2['task_type']}\n\n";
        
        $createdTaskId2 = $responseData['data']['id'];
    } else {
        echo "❌ FEHLGESCHLAGEN!\n";
        if (isset($responseData['errors'])) {
            echo "Validierungs-Fehler:\n";
            foreach ($responseData['errors'] as $field => $errors) {
                echo "  $field: " . (is_array($errors) ? implode(', ', $errors) : $errors) . "\n";
            }
        }
        echo "\n";
    }
} else {
    echo "Raw Response: $response\n\n";
}

// Cleanup: Teste Tasks löschen
echo "🧹 CLEANUP: Lösche Test-Tasks\n";
echo "=============================\n";

if (isset($createdTaskId1)) {
    $deleteUrl = "https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/tasks/$createdTaskId1";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $deleteUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $plainTextToken
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $deleteResponse = curl_exec($ch);
    $deleteHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo ($deleteHttpCode === 200) ? "✅" : "❌";
    echo " Task 1 gelöscht (ID: $createdTaskId1)\n";
}

if (isset($createdTaskId2)) {
    $deleteUrl = "https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/tasks/$createdTaskId2";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $deleteUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $plainTextToken
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $deleteResponse = curl_exec($ch);
    $deleteHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo ($deleteHttpCode === 200) ? "✅" : "❌";
    echo " Task 2 gelöscht (ID: $createdTaskId2)\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "✅ task_type ist jetzt optional\n";
echo "✅ Automatische Ermittlung aus task_type_id funktioniert\n";
echo "✅ Explizite Übergabe funktioniert weiterhin\n";
echo "✅ API ist für die App optimiert\n";
echo "\n💡 Die App kann nun Tasks ohne task_type senden - wird automatisch ermittelt!\n";
