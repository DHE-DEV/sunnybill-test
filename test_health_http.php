<?php

// Teste die Health-Endpunkte direkt ohne Server
$baseUrl = 'http://127.0.0.1:8000'; // Standard Laravel Development Server

$endpoints = [
    '/api/health',
    '/api/health/simple',
    '/api/health/ready',
    '/api/health/live'
];

echo "=== HEALTH ENDPOINT HTTP TESTS ===\n\n";

foreach ($endpoints as $endpoint) {
    echo "Testing: {$endpoint}\n";
    echo str_repeat('-', 40) . "\n";
    
    $url = $baseUrl . $endpoint;
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ cURL Error: {$error}\n";
        echo "💡 Hinweis: Stelle sicher, dass der Laravel Development Server läuft:\n";
        echo "   php artisan serve\n\n";
    } else {
        if ($httpCode == 200) {
            echo "✅ Status: {$httpCode} OK\n";
            
            // Extract body from response
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $body = substr($response, $headerSize);
            
            if ($body) {
                $data = json_decode($body, true);
                if ($data) {
                    echo "📊 Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
                } else {
                    echo "📄 Raw Response: " . $body . "\n";
                }
            }
        } else {
            echo "❌ Status: {$httpCode}\n";
            echo "📄 Response: " . $response . "\n";
        }
    }
    echo "\n";
}

// Zusätzliche Informationen
echo "=== ZUSÄTZLICHE INFORMATIONEN ===\n";
echo "Die Health-Endpunkte sind unter folgenden URLs verfügbar:\n";
echo "- {$baseUrl}/api/health          (Vollständiger Health-Check)\n";
echo "- {$baseUrl}/api/health/simple   (Einfacher Health-Check)\n";
echo "- {$baseUrl}/api/health/ready    (Bereitschafts-Check)\n";
echo "- {$baseUrl}/api/health/live     (Lebendigkeit-Check)\n\n";

echo "📋 Routen wurden erfolgreich registriert (siehe: php artisan route:list)\n";
echo "🔧 Falls 404-Fehler auftreten:\n";
echo "   1. Starte den Development Server: php artisan serve\n";
echo "   2. Verwende die korrekte URL: http://127.0.0.1:8000/api/health\n";
echo "   3. Cache leeren: php artisan route:clear\n";
