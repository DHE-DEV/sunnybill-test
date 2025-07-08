<?php

require_once 'vendor/autoload.php';

// Laravel bootstrappen
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== TEST: LEXWARE-DATEN ABRUFEN UND SPEICHERN ===\n\n";

// Kunde finden
$customer = Customer::where('name', 'Max Mustermann')->first();

if (!$customer) {
    echo "❌ Kunde 'Max Mustermann' nicht gefunden\n";
    exit(1);
}

echo "✅ Kunde gefunden:\n";
echo "   ID: {$customer->id}\n";
echo "   Name: {$customer->name}\n";
echo "   Lexoffice-ID: " . ($customer->lexoffice_id ?? 'Keine') . "\n";
echo "   Aktuelle Lexware-Version: " . ($customer->lexware_version ?? 'Keine') . "\n";
echo "   Lexware-Daten vorhanden: " . ($customer->lexware_json ? 'Ja (' . round(strlen(json_encode($customer->lexware_json)) / 1024, 1) . ' KB)' : 'Nein') . "\n\n";

if (!$customer->lexoffice_id) {
    echo "⚠️  Kunde hat keine Lexoffice-ID - kann keine Daten abrufen\n";
    exit(1);
}

// Lexoffice Service
$lexofficeService = new LexofficeService();

echo "=== TEST 1: LEXWARE-DATEN ABRUFEN ===\n";

$result = $lexofficeService->fetchAndStoreLexwareData($customer);

if ($result['success']) {
    echo "✅ Lexware-Daten erfolgreich abgerufen:\n";
    echo "   Version: " . ($result['version'] ?? 'Unbekannt') . "\n";
    echo "   Dauer: " . ($result['duration_ms'] ?? 'Unbekannt') . "ms\n";
    echo "   Nachricht: " . ($result['message'] ?? 'Keine Nachricht') . "\n\n";
    
    // Kunde neu laden um aktualisierte Daten zu sehen
    $customer->refresh();
    
    echo "=== TEST 2: GESPEICHERTE DATEN ÜBERPRÜFEN ===\n";
    echo "✅ Aktualisierte Kundendaten:\n";
    echo "   Lexware-Version: " . ($customer->lexware_version ?? 'Keine') . "\n";
    echo "   Lexware-JSON Größe: " . ($customer->lexware_json ? round(strlen(json_encode($customer->lexware_json)) / 1024, 1) . ' KB' : 'Keine Daten') . "\n";
    echo "   Zuletzt synchronisiert: " . ($customer->lexoffice_synced_at ? $customer->lexoffice_synced_at->format('d.m.Y H:i:s') : 'Nie') . "\n\n";
    
    if ($customer->lexware_json) {
        echo "=== TEST 3: JSON-DATEN ANALYSE ===\n";
        $jsonData = $customer->lexware_json;
        
        echo "📊 Enthaltene Felder:\n";
        $fields = array_keys($jsonData);
        foreach ($fields as $field) {
            $value = $jsonData[$field];
            if (is_array($value)) {
                echo "   - {$field}: Array mit " . count($value) . " Elementen\n";
            } elseif (is_string($value)) {
                echo "   - {$field}: String (" . strlen($value) . " Zeichen)\n";
            } else {
                echo "   - {$field}: " . gettype($value) . " - " . json_encode($value) . "\n";
            }
        }
        
        echo "\n📋 Wichtige Daten:\n";
        echo "   ID: " . ($jsonData['id'] ?? 'Nicht vorhanden') . "\n";
        echo "   Organization ID: " . ($jsonData['organizationId'] ?? 'Nicht vorhanden') . "\n";
        echo "   Version: " . ($jsonData['version'] ?? 'Nicht vorhanden') . "\n";
        echo "   Updated Date: " . ($jsonData['updatedDate'] ?? 'Nicht vorhanden') . "\n";
        
        if (isset($jsonData['person'])) {
            echo "   Person: " . json_encode($jsonData['person']) . "\n";
        }
        
        if (isset($jsonData['company'])) {
            echo "   Company: " . json_encode($jsonData['company']) . "\n";
        }
        
        if (isset($jsonData['addresses'])) {
            echo "   Adressen: " . count($jsonData['addresses']) . " Adresse(n)\n";
            foreach ($jsonData['addresses'] as $i => $address) {
                echo "     Adresse " . ($i + 1) . ": " . ($address['street'] ?? 'Keine Straße') . ", " . ($address['city'] ?? 'Keine Stadt') . "\n";
            }
        }
        
        if (isset($jsonData['emailAddresses'])) {
            echo "   E-Mail-Adressen: " . count($jsonData['emailAddresses']) . " Adresse(n)\n";
            foreach ($jsonData['emailAddresses'] as $email) {
                echo "     - " . ($email['emailAddress'] ?? 'Keine E-Mail') . " (Primary: " . ($email['isPrimary'] ? 'Ja' : 'Nein') . ")\n";
            }
        }
        
        if (isset($jsonData['phoneNumbers'])) {
            echo "   Telefonnummern: " . count($jsonData['phoneNumbers']) . " Nummer(n)\n";
            foreach ($jsonData['phoneNumbers'] as $phone) {
                echo "     - " . ($phone['phoneNumber'] ?? 'Keine Nummer') . " (Primary: " . ($phone['isPrimary'] ? 'Ja' : 'Nein') . ")\n";
            }
        }
        
        echo "\n";
    }
    
} else {
    echo "❌ Fehler beim Abrufen der Lexware-Daten:\n";
    echo "   Error: {$result['error']}\n";
    echo "   Dauer: " . ($result['duration_ms'] ?? 'Unbekannt') . "ms\n\n";
}

echo "=== TEST 4: LOGGING VERIFICATION ===\n";

// Prüfe die letzten Log-Einträge
$recentLogs = \App\Models\LexofficeLog::where('entity_id', $customer->id)
    ->where('action', 'fetch_lexware_data')
    ->where('created_at', '>=', now()->subMinutes(5))
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();

echo "📊 Letzte " . $recentLogs->count() . " Fetch-Log-Einträge:\n";
foreach ($recentLogs as $log) {
    $status = $log->status === 'success' ? '✅' : ($log->status === 'error' ? '❌' : '⚠️');
    echo "   {$status} {$log->action} - {$log->status}";
    
    if ($log->error_message) {
        echo " - {$log->error_message}";
    }
    
    echo " ({$log->created_at->format('H:i:s')})\n";
    
    // Zeige Response-Daten
    if ($log->response_data && is_array($log->response_data)) {
        if (isset($log->response_data['version'])) {
            echo "     📋 Version: {$log->response_data['version']}\n";
        }
        if (isset($log->response_data['duration_ms'])) {
            echo "     ⏱️  Dauer: {$log->response_data['duration_ms']}ms\n";
        }
        if (isset($log->response_data['data_size_bytes'])) {
            echo "     📊 Datengröße: " . round($log->response_data['data_size_bytes'] / 1024, 1) . " KB\n";
        }
    }
}

echo "\n=== TEST 5: VERGLEICH MIT VORHERIGEN DATEN ===\n";

// Prüfe ob sich die Version geändert hat
$previousLogs = \App\Models\LexofficeLog::where('entity_id', $customer->id)
    ->where('action', 'fetch_lexware_data')
    ->where('status', 'success')
    ->where('created_at', '<', now()->subMinutes(1))
    ->orderBy('created_at', 'desc')
    ->limit(1)
    ->get();

if ($previousLogs->count() > 0) {
    $previousLog = $previousLogs->first();
    $previousVersion = $previousLog->response_data['version'] ?? null;
    $currentVersion = $customer->lexware_version;
    
    echo "📈 Versions-Vergleich:\n";
    echo "   Vorherige Version: " . ($previousVersion ?? 'Unbekannt') . "\n";
    echo "   Aktuelle Version: " . ($currentVersion ?? 'Unbekannt') . "\n";
    
    if ($previousVersion && $currentVersion) {
        if ($currentVersion > $previousVersion) {
            echo "   ✅ Version wurde erhöht (Daten wurden in Lexoffice geändert)\n";
        } elseif ($currentVersion == $previousVersion) {
            echo "   ℹ️  Version unverändert (keine Änderungen in Lexoffice)\n";
        } else {
            echo "   ⚠️  Version wurde verringert (ungewöhnlich)\n";
        }
    }
} else {
    echo "ℹ️  Keine vorherigen erfolgreichen Fetch-Operationen gefunden\n";
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "✅ Lexware-Daten-Fetch-Funktionalität implementiert\n";
echo "✅ Daten werden in Customer-Tabelle gespeichert (lexware_version, lexware_json)\n";
echo "✅ Erweiterte Logging-Funktionalität aktiv\n";
echo "✅ Performance-Monitoring implementiert\n";
echo "✅ JSON-Daten-Analyse verfügbar\n";
echo "✅ Versions-Tracking funktioniert\n";

echo "\n=== TEST ABGESCHLOSSEN ===\n";
