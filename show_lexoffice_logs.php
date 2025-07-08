<?php

echo "=== LEXOFFICE LOGS MONITOR ===\n";
echo "Überwacht Laravel-Logs für Lexoffice-Requests...\n";
echo "Drücken Sie Ctrl+C zum Beenden.\n\n";

$logFile = 'storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "❌ Log-Datei nicht gefunden: $logFile\n";
    exit(1);
}

// Aktuelle Position am Ende der Datei
$lastPosition = filesize($logFile);

while (true) {
    clearstatcache();
    $currentSize = filesize($logFile);
    
    if ($currentSize > $lastPosition) {
        // Neue Daten verfügbar
        $handle = fopen($logFile, 'r');
        fseek($handle, $lastPosition);
        
        while (($line = fgets($handle)) !== false) {
            // Nur Lexoffice-relevante Logs anzeigen
            if (strpos($line, 'Lexoffice') !== false) {
                echo $line;
                
                // Wenn es ein JSON-Log ist, versuche es zu formatieren
                if (strpos($line, '{"') !== false) {
                    $jsonStart = strpos($line, '{"');
                    $jsonString = substr($line, $jsonStart);
                    $jsonData = json_decode($jsonString, true);
                    
                    if ($jsonData && isset($jsonData['request_data'])) {
                        echo "\n📤 REQUEST DATA:\n";
                        echo json_encode($jsonData['request_data'], JSON_PRETTY_PRINT) . "\n";
                    }
                    
                    if ($jsonData && isset($jsonData['response_data'])) {
                        echo "\n📥 RESPONSE DATA:\n";
                        echo json_encode($jsonData['response_data'], JSON_PRETTY_PRINT) . "\n";
                    }
                    
                    if ($jsonData && isset($jsonData['error_response'])) {
                        echo "\n❌ ERROR RESPONSE:\n";
                        echo $jsonData['error_response'] . "\n";
                    }
                    
                    echo "\n" . str_repeat("-", 80) . "\n\n";
                }
            }
        }
        
        $lastPosition = ftell($handle);
        fclose($handle);
    }
    
    // Kurz warten bevor nächste Prüfung
    usleep(500000); // 0.5 Sekunden
}
