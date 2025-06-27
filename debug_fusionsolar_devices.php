<?php

require_once 'vendor/autoload.php';

use App\Services\FusionSolarService;
use Illuminate\Support\Facades\Log;

// Laravel Bootstrap für Konfiguration
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Analysiere FusionSolar API-Antworten...\n\n";

$service = new FusionSolarService();

// 1. Anlagenliste abrufen
echo "1. 📋 Anlagenliste abrufen:\n";
$plantList = $service->getPlantList();

if ($plantList) {
    echo "✅ Anlagenliste erhalten:\n";
    echo json_encode($plantList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Erste Anlage für weitere Tests verwenden
    $firstPlant = $plantList[0] ?? null;
    if ($firstPlant) {
        $stationCode = $firstPlant['stationCode'] ?? null;
        echo "🏭 Verwende Anlage: {$stationCode}\n\n";
        
        // 2. Geräteliste abrufen
        echo "2. 🔧 Geräteliste abrufen:\n";
        $deviceList = $service->getDeviceList($stationCode);
        
        if ($deviceList) {
            echo "✅ Geräteliste erhalten:\n";
            echo json_encode($deviceList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
            
            // 3. Für jedes Gerät Details abrufen
            foreach ($deviceList as $index => $device) {
                $deviceId = $device['devId'] ?? 'unknown';
                $deviceType = $device['devTypeId'] ?? 'unknown';
                $deviceName = $device['devName'] ?? 'unknown';
                
                echo "3.{$index} 📱 Gerät: {$deviceName} (ID: {$deviceId}, Typ: {$deviceType})\n";
                
                // Geräteinformationen abrufen
                echo "   📋 Geräteinformationen:\n";
                $deviceInfo = $service->getDeviceInfo($deviceId);
                if ($deviceInfo) {
                    echo "   " . json_encode($deviceInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                } else {
                    echo "   ❌ Keine Geräteinformationen verfügbar\n";
                }
                
                // Echtzeitdaten je nach Gerätetyp
                echo "   📊 Echtzeitdaten:\n";
                switch ($deviceType) {
                    case 1: // Wechselrichter
                        $details = $service->getInverterDetails($deviceId);
                        break;
                    case 47: // String
                        $details = $service->getStringDetails($deviceId);
                        break;
                    case 39: // Batterie
                        $details = $service->getBatteryDetails($deviceId);
                        break;
                    default:
                        // Allgemeine Echtzeitdaten
                        $details = $service->getPlantRealtimeData($deviceId);
                        break;
                }
                
                if ($details) {
                    echo "   " . json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                } else {
                    echo "   ❌ Keine Echtzeitdaten verfügbar\n";
                }
                
                echo "\n" . str_repeat("-", 80) . "\n\n";
            }
        } else {
            echo "❌ Keine Geräteliste verfügbar\n";
            echo "💡 Mögliche Ursachen:\n";
            echo "   - API-Berechtigung 'Device List Query' nicht aktiviert\n";
            echo "   - Northbound-User hat keinen Zugriff auf Geräte\n\n";
        }
        
        // 4. Alternative: Anlagen-Details abrufen
        echo "4. 🏭 Anlagen-Details abrufen:\n";
        $plantDetails = $service->getPlantDetails($stationCode);
        if ($plantDetails) {
            echo "✅ Anlagen-Details erhalten:\n";
            echo json_encode($plantDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        } else {
            echo "❌ Keine Anlagen-Details verfügbar\n\n";
        }
        
        // 5. Alternative: Echtzeitdaten der Anlage
        echo "5. ⚡ Anlagen-Echtzeitdaten abrufen:\n";
        $realtimeData = $service->getPlantRealtimeData($stationCode);
        if ($realtimeData) {
            echo "✅ Echtzeitdaten erhalten:\n";
            echo json_encode($realtimeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        } else {
            echo "❌ Keine Echtzeitdaten verfügbar\n\n";
        }
    }
} else {
    echo "❌ Keine Anlagenliste verfügbar\n";
}

echo "🎯 Analyse abgeschlossen!\n";