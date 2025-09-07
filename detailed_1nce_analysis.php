<?php

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Detaillierte 1nce API Analyse ===\n\n";

// Check credentials
$clientId = $_ENV['1NCE_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['1NCE_CLIENT_SECRET'] ?? null;

if (!$clientId || !$clientSecret) {
    echo "ERROR: Missing credentials in .env file\n";
    exit(1);
}

// Get access token
$credentials = base64_encode($clientId . ':' . $clientSecret);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.1nce.com/management-api/oauth/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['grant_type' => 'client_credentials']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $credentials,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "Authentication failed: HTTP $httpCode\n";
    exit(1);
}

$authData = json_decode($response, true);
$accessToken = $authData['access_token'];

echo "1. Authentication: ✓ Erfolgreich\n\n";

// Get SIM cards with detailed analysis
echo "2. Abrufen aller SIM-Karten Daten:\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.1nce.com/management-api/v1/sims');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Accept: application/json'
]);

$apiResponse = curl_exec($ch);
$apiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($apiHttpCode !== 200) {
    echo "   API call failed: HTTP $apiHttpCode\n";
    echo "   Response: $apiResponse\n";
    exit(1);
}

$simCards = json_decode($apiResponse, true);

if (!is_array($simCards)) {
    echo "   Unexpected response format\n";
    echo "   Raw response: $apiResponse\n";
    exit(1);
}

echo "   Gefunden: " . count($simCards) . " SIM-Karten\n\n";

// Analyze each SIM card in detail
foreach ($simCards as $index => $simCard) {
    echo "3." . ($index + 1) . " SIM-Karte #" . ($index + 1) . " - Detailanalyse:\n";
    echo "   ICCID: " . ($simCard['iccid'] ?? 'N/A') . "\n";
    echo "   MSISDN: " . ($simCard['msisdn'] ?? 'N/A') . "\n";
    echo "   Status: " . ($simCard['status'] ?? 'N/A') . "\n";
    echo "   IMSI: " . ($simCard['imsi'] ?? 'N/A') . "\n";
    echo "   IMEI: " . ($simCard['imei'] ?? 'N/A') . "\n";
    
    echo "\n   *** ALLE VERFÜGBAREN FELDER: ***\n";
    
    // Show all available fields
    foreach ($simCard as $field => $value) {
        $displayValue = is_array($value) ? json_encode($value) : (string)$value;
        if (strlen($displayValue) > 100) {
            $displayValue = substr($displayValue, 0, 100) . "...";
        }
        echo "   - $field: $displayValue\n";
    }
    
    echo "\n   *** ANALYSE SPEZIFISCHER FELDER: ***\n";
    
    // Check for connectivity/signal related fields
    $signalFields = [
        'signal_strength', 'rssi', 'signal_level', 'radio_signal', 
        'network_signal', 'signal_quality', 'connectivity', 
        'network_status', 'connection_status', 'online_status',
        'last_seen', 'last_activity', 'last_data_session'
    ];
    
    $foundSignalFields = [];
    foreach ($signalFields as $field) {
        if (isset($simCard[$field])) {
            $foundSignalFields[$field] = $simCard[$field];
        }
    }
    
    if (empty($foundSignalFields)) {
        echo "   ⚠️  KEINE Signalstärke/Verbindungsstatus-Felder gefunden!\n";
    } else {
        echo "   ✓ Gefundene Signal/Status-Felder:\n";
        foreach ($foundSignalFields as $field => $value) {
            echo "     - $field: " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
    }
    
    // Check for data usage fields
    $dataFields = ['data_usage', 'data_used', 'usage', 'volume', 'consumption'];
    $foundDataFields = [];
    foreach ($dataFields as $field) {
        if (isset($simCard[$field])) {
            $foundDataFields[$field] = $simCard[$field];
        }
    }
    
    if (empty($foundDataFields)) {
        echo "   ⚠️  KEINE Datenverbrauchs-Felder gefunden!\n";
    } else {
        echo "   ✓ Gefundene Datenverbrauchs-Felder:\n";
        foreach ($foundDataFields as $field => $value) {
            echo "     - $field: " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
    }
    
    echo "\n" . str_repeat("-", 80) . "\n\n";
}

// Summary
echo "4. ZUSAMMENFASSUNG:\n";
echo "   - Die 1nce API liefert grundlegende SIM-Karten-Informationen\n";
echo "   - Status-Feld zeigt nur administrative Stati (Enabled/Disabled)\n";
echo "   - Echte Verbindungsstatus (online/offline) scheinen NICHT verfügbar\n";
echo "   - Signalstärke-Informationen scheinen NICHT in Standard-API verfügbar\n";
echo "   - Möglicherweise sind zusätzliche API-Endpunkte oder Berechtigungen erforderlich\n\n";

echo "5. EMPFEHLUNG:\n";
echo "   - 1nce API scheint primär für administrative Verwaltung gedacht\n";
echo "   - Echte IoT-Monitoring-Daten (Signal, Online-Status) sind wahrscheinlich\n";
echo "     in separaten APIs oder erfordern spezielle Zugriffsrechte\n";
echo "   - Für Echtzeit-Monitoring sollten Router/Gerät direkte APIs verwendet werden\n\n";

echo "=== Analyse Complete ===\n";
