<?php

echo "=== LIVE TASK CREATION API TEST ===\n\n";

// Token für User 57
$plainTextToken = 'sb_HrMgJVlEEua9OvTuk2FYFkEqzA0MLMNfxnEIv0PRnatCrcrGKg2ayYqwLHWywXpY';
$liveApiUrl = 'https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/tasks';

// Die exakten Daten aus der Fehlermeldung
$taskData = [
    "title" => "Live API Test Task",
    "description" => "Diese Aufgabe wurde über die Live-API erstellt",
    "task_type" => "Installation",
    "task_type_id" => 1,
    "priority" => "medium",
    "status" => "open",
    "assigned_to" => 57,
    "owner_id" => 1,
    "due_date" => "2025-08-15",
    "due_time" => "14:30",
    "estimated_minutes" => 120
];

echo "📝 Task-Daten für Live-API:\n";
echo json_encode($taskData, JSON_PRETTY_PRINT) . "\n\n";

echo "🔄 Teste Task-Erstellung über Live-API...\n";
echo "URL: $liveApiUrl\n\n";

// cURL Request für POST
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $liveApiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $plainTextToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($taskData));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "📊 HTTP Status: $httpCode\n";

if ($error) {
    echo "❌ cURL Fehler: $error\n";
} else {
    echo "📄 Response:\n";
    $responseData = json_decode($response, true);
    if ($responseData) {
        echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
        
        if ($httpCode === 201) {
            echo "🎉 TASK ERFOLGREICH ÜBER LIVE-API ERSTELLT!\n\n";
            echo "Task ID: " . $responseData['data']['id'] . "\n";
            echo "Task Number: " . $responseData['data']['task_number'] . "\n";
            
            // Jetzt teste DELETE auf die gerade erstellte Task
            $newTaskId = $responseData['data']['id'];
            echo "\n🗑️ Teste DELETE der gerade erstellten Task (ID: $newTaskId)...\n";
            
            $deleteUrl = "https://sunnybill-test.eu-1.sharedwithexpose.com/api/app/tasks/$newTaskId";
            echo "DELETE URL: $deleteUrl\n\n";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $deleteUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Authorization: Bearer ' . $plainTextToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $deleteResponse = curl_exec($ch);
            $deleteHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $deleteError = curl_error($ch);
            curl_close($ch);
            
            echo "DELETE HTTP Status: $deleteHttpCode\n";
            
            if ($deleteError) {
                echo "❌ DELETE cURL Fehler: $deleteError\n";
            } else {
                $deleteResponseData = json_decode($deleteResponse, true);
                if ($deleteResponseData) {
                    echo "DELETE Response:\n";
                    echo json_encode($deleteResponseData, JSON_PRETTY_PRINT) . "\n\n";
                    
                    if ($deleteHttpCode === 200) {
                        echo "🎉 TASK ERFOLGREICH ÜBER LIVE-API GELÖSCHT!\n";
                    } else {
                        echo "❌ DELETE FEHLGESCHLAGEN über Live-API\n";
                    }
                } else {
                    echo "DELETE Raw Response: $deleteResponse\n";
                }
            }
            
        } else {
            echo "❌ TASK-ERSTELLUNG FEHLGESCHLAGEN!\n";
            
            if (isset($responseData['errors'])) {
                echo "Validierungs-Fehler:\n";
                foreach ($responseData['errors'] as $field => $errors) {
                    echo "  $field: " . (is_array($errors) ? implode(', ', $errors) : $errors) . "\n";
                }
            }
        }
    } else {
        echo "Raw Response: $response\n\n";
    }
}

echo "\n=== DEBUGGING INFORMATIONEN ===\n";
echo "Request URL: $liveApiUrl\n";
echo "Request Method: POST\n";
echo "Request Headers:\n";
echo "  - Accept: application/json\n";
echo "  - Authorization: Bearer [TOKEN]\n";
echo "  - Content-Type: application/json\n";
echo "Request Body: " . json_encode($taskData) . "\n";
echo "HTTP Response Code: $httpCode\n";
echo "Total Time: " . number_format($info['total_time'], 2) . "s\n";

echo "\n💡 cURL-Befehl zum Nachstellen:\n";
echo "curl -X POST '$liveApiUrl' \\\n";
echo "  -H 'Accept: application/json' \\\n";
echo "  -H 'Authorization: Bearer $plainTextToken' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '" . json_encode($taskData) . "'\n";
