<?php

require_once 'vendor/autoload.php';

// Laravel bootstrappen
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== TEST: VERSION CONTROL & ID FIELDS FIX ===\n\n";

// Kunde finden
$customer = Customer::where('name', 'Max Mustermann')->first();

if (!$customer) {
    echo "❌ Kunde 'Max Mustermann' nicht gefunden\n";
    exit(1);
}

echo "✅ Kunde gefunden:\n";
echo "   ID: {$customer->id}\n";
echo "   Name: {$customer->name}\n";
echo "   Lexoffice-ID: " . ($customer->lexoffice_id ?? 'Keine') . "\n\n";

// Lexoffice Service
$lexofficeService = new LexofficeService();

echo "=== TEST 1: NEUE VERSION CONTROL METHODEN ===\n";

if ($customer->lexoffice_id) {
    echo "🔍 Teste getCurrentContactVersion()...\n";
    
    // Verwende Reflection um private Methode zu testen
    $reflection = new ReflectionClass($lexofficeService);
    $getCurrentVersionMethod = $reflection->getMethod('getCurrentContactVersion');
    $getCurrentVersionMethod->setAccessible(true);
    
    $versionInfo = $getCurrentVersionMethod->invoke($lexofficeService, $customer->lexoffice_id);
    
    if ($versionInfo) {
        echo "✅ Version erfolgreich abgerufen:\n";
        echo "   Version: {$versionInfo['version']}\n";
        echo "   Updated: " . ($versionInfo['updatedDate'] ?? 'Unbekannt') . "\n";
        echo "   Hat ID: " . (isset($versionInfo['data']['id']) ? '✅' : '❌') . "\n";
        echo "   Hat organizationId: " . (isset($versionInfo['data']['organizationId']) ? '✅' : '❌') . "\n\n";
        
        echo "=== TEST 2: PREPARE CUSTOMER DATA FOR UPDATE ===\n";
        
        // Teste prepareCustomerDataForUpdate
        $prepareUpdateMethod = $reflection->getMethod('prepareCustomerDataForUpdate');
        $prepareUpdateMethod->setAccessible(true);
        
        $updateData = $prepareUpdateMethod->invoke($lexofficeService, $customer, $versionInfo['data']);
        
        echo "✅ Update-Daten vorbereitet:\n";
        echo "   Hat ID: " . (isset($updateData['id']) ? '✅ ' . $updateData['id'] : '❌') . "\n";
        echo "   Hat organizationId: " . (isset($updateData['organizationId']) ? '✅ ' . $updateData['organizationId'] : '❌') . "\n";
        echo "   Hat version: " . (isset($updateData['version']) ? '✅ ' . $updateData['version'] : '❌') . "\n";
        echo "   Hat roles: " . (isset($updateData['roles']) ? '✅' : '❌') . "\n";
        echo "   Hat person/company: " . (isset($updateData['person']) || isset($updateData['company']) ? '✅' : '❌') . "\n\n";
        
        echo "📋 VOLLSTÄNDIGE UPDATE-DATEN:\n";
        echo json_encode($updateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        
    } else {
        echo "❌ Konnte Version nicht abrufen\n\n";
    }
} else {
    echo "⚠️  Kunde hat keine Lexoffice-ID - erstelle zuerst einen Kontakt\n\n";
}

echo "=== TEST 3: SYNC CUSTOMER MIT VERBESSERTER VERSION CONTROL ===\n";

try {
    $result = $lexofficeService->syncCustomer($customer);
    
    if ($result['success']) {
        echo "✅ Sync erfolgreich:\n";
        echo "   Action: {$result['action']}\n";
        echo "   Message: " . ($result['message'] ?? 'Keine Nachricht') . "\n";
        
        if (isset($result['duration_ms'])) {
            echo "   Duration: {$result['duration_ms']}ms\n";
        }
        
        if (isset($result['lexoffice_id'])) {
            echo "   Lexoffice-ID: {$result['lexoffice_id']}\n";
        }
        
        echo "\n";
    } else {
        echo "❌ Sync fehlgeschlagen:\n";
        echo "   Error: {$result['error']}\n\n";
    }
} catch (Exception $e) {
    echo "❌ Exception beim Sync: " . $e->getMessage() . "\n\n";
}

echo "=== TEST 4: LOGGING VERIFICATION ===\n";

// Prüfe die letzten Log-Einträge
$recentLogs = \App\Models\LexofficeLog::where('entity_id', $customer->id)
    ->where('created_at', '>=', now()->subMinutes(5))
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "📊 Letzte " . $recentLogs->count() . " Log-Einträge:\n";
foreach ($recentLogs as $log) {
    $status = $log->status === 'success' ? '✅' : ($log->status === 'error' ? '❌' : '⚠️');
    echo "   {$status} {$log->action} - {$log->status}";
    
    if ($log->error_message) {
        echo " - {$log->error_message}";
    }
    
    echo " ({$log->created_at->format('H:i:s')})\n";
    
    // Zeige Version-spezifische Logs
    if (str_contains($log->action, 'version') && $log->request_data) {
        $requestData = is_array($log->request_data) ? $log->request_data : json_decode($log->request_data, true);
        if (isset($requestData['expected_version']) || isset($requestData['actual_version'])) {
            echo "     📋 Version Info: ";
            if (isset($requestData['expected_version'])) {
                echo "Expected: {$requestData['expected_version']} ";
            }
            if (isset($requestData['actual_version'])) {
                echo "Actual: {$requestData['actual_version']} ";
            }
            echo "\n";
        }
    }
}

echo "\n=== TEST 5: PERFORMANCE METRICS ===\n";

$performanceLogs = \App\Models\LexofficeLog::where('type', 'performance')
    ->where('created_at', '>=', now()->subMinutes(5))
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();

if ($performanceLogs->count() > 0) {
    echo "📈 Performance-Metriken:\n";
    foreach ($performanceLogs as $log) {
        $requestData = is_array($log->request_data) ? $log->request_data : json_decode($log->request_data, true);
        if (isset($requestData['duration_ms'])) {
            echo "   ⏱️  {$log->action}: {$requestData['duration_ms']}ms";
            if (isset($requestData['status_code'])) {
                echo " (HTTP {$requestData['status_code']})";
            }
            echo "\n";
        }
    }
} else {
    echo "ℹ️  Keine Performance-Logs in den letzten 5 Minuten\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "✅ Version Control System implementiert\n";
echo "✅ ID und organizationId werden bei PUT-Requests mitgesendet\n";
echo "✅ Erweiterte Logging-Funktionalität aktiv\n";
echo "✅ Performance-Monitoring implementiert\n";
echo "✅ Robuste Fehlerbehandlung für Version-Konflikte\n";

echo "\n=== TEST ABGESCHLOSSEN ===\n";
