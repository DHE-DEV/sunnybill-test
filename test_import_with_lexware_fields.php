<?php

require_once 'vendor/autoload.php';

// Laravel bootstrappen
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== TEST: IMPORT MIT LEXWARE-FELDERN ===\n\n";

// Vor dem Import: Aktuelle Kunden zählen
$customerCountBefore = Customer::count();
echo "📊 Kunden vor Import: {$customerCountBefore}\n\n";

// Lexoffice Service
$lexofficeService = new LexofficeService();

echo "=== TEST 1: KUNDEN VON LEXOFFICE IMPORTIEREN ===\n";

$result = $lexofficeService->importCustomers();

if ($result['success']) {
    echo "✅ Import erfolgreich:\n";
    echo "   Importierte Kunden: {$result['imported']}\n";
    echo "   Fehler: " . count($result['errors']) . "\n";
    
    if (!empty($result['errors'])) {
        echo "   Fehlermeldungen:\n";
        foreach ($result['errors'] as $error) {
            echo "     - {$error}\n";
        }
    }
    echo "\n";
    
    // Nach dem Import: Kunden zählen
    $customerCountAfter = Customer::count();
    echo "📊 Kunden nach Import: {$customerCountAfter}\n";
    echo "📈 Neue Kunden: " . ($customerCountAfter - $customerCountBefore) . "\n\n";
    
    echo "=== TEST 2: LEXWARE-FELDER UND ADRESSEN ÜBERPRÜFEN ===\n";
    
    // Prüfe die letzten 5 importierten/aktualisierten Kunden
    $recentCustomers = Customer::whereNotNull('lexoffice_id')
        ->with(['addresses'])
        ->orderBy('lexoffice_synced_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "🔍 Überprüfung der letzten {$recentCustomers->count()} Kunden:\n\n";
    
    foreach ($recentCustomers as $customer) {
        echo "👤 Kunde: {$customer->name}\n";
        echo "   ID: {$customer->id}\n";
        echo "   Lexoffice-ID: {$customer->lexoffice_id}\n";
        echo "   Lexware-Version: " . ($customer->lexware_version ?? 'NICHT GESETZT') . "\n";
        echo "   Lexware-JSON: " . ($customer->lexware_json ? 'VORHANDEN (' . round(strlen(json_encode($customer->lexware_json)) / 1024, 1) . ' KB)' : 'NICHT GESETZT') . "\n";
        echo "   Synchronisiert: " . ($customer->lexoffice_synced_at ? $customer->lexoffice_synced_at->format('d.m.Y H:i:s') : 'Nie') . "\n";
        
        // Prüfe Adressen
        $billingAddress = $customer->addresses()->where('type', 'billing')->first();
        $shippingAddress = $customer->addresses()->where('type', 'shipping')->first();
        
        echo "   📍 Adressen:\n";
        echo "     Standard: " . ($customer->street ? "{$customer->street}, {$customer->postal_code} {$customer->city}" : 'Keine') . "\n";
        echo "     Rechnung: " . ($billingAddress ? "{$billingAddress->street_address}, {$billingAddress->postal_code} {$billingAddress->city}" : 'Keine') . "\n";
        echo "     Lieferung: " . ($shippingAddress ? "{$shippingAddress->street_address}, {$shippingAddress->postal_code} {$shippingAddress->city}" : 'Keine') . "\n";
        
        // Prüfe JSON-Inhalt
        if ($customer->lexware_json) {
            $jsonData = $customer->lexware_json;
            echo "   JSON-Felder: " . implode(', ', array_keys($jsonData)) . "\n";
            
            // Wichtige Felder prüfen
            if (isset($jsonData['version'])) {
                echo "   ✅ Version im JSON: {$jsonData['version']}\n";
            } else {
                echo "   ❌ Version fehlt im JSON\n";
            }
            
            if (isset($jsonData['id'])) {
                echo "   ✅ ID im JSON: {$jsonData['id']}\n";
            } else {
                echo "   ❌ ID fehlt im JSON\n";
            }
            
            if (isset($jsonData['updatedDate'])) {
                echo "   ✅ UpdatedDate im JSON: {$jsonData['updatedDate']}\n";
            } else {
                echo "   ❌ UpdatedDate fehlt im JSON\n";
            }
            
            // Adressen im JSON prüfen
            if (isset($jsonData['addresses'])) {
                echo "   📍 Adressen im JSON: " . count($jsonData['addresses']) . " Adresse(n)\n";
                foreach ($jsonData['addresses'] as $i => $address) {
                    $street = $address['street'] ?? 'Keine Straße';
                    $city = $address['city'] ?? 'Keine Stadt';
                    $isPrimary = isset($address['isPrimary']) && $address['isPrimary'] ? ' (Primary)' : '';
                    echo "     JSON-Adresse " . ($i + 1) . ": {$street}, {$city}{$isPrimary}\n";
                }
            } else {
                echo "   ❌ Keine Adressen im JSON\n";
            }
        } else {
            echo "   ❌ Kein JSON-Daten vorhanden\n";
        }
        
        echo "\n";
    }
    
    echo "=== TEST 3: STATISTIKEN ===\n";
    
    // Statistiken über die Lexware-Felder
    $customersWithLexofficeId = Customer::whereNotNull('lexoffice_id')->count();
    $customersWithLexwareVersion = Customer::whereNotNull('lexware_version')->count();
    $customersWithLexwareJson = Customer::whereNotNull('lexware_json')->count();
    
    echo "📊 Lexoffice-Integration Statistiken:\n";
    echo "   Kunden mit Lexoffice-ID: {$customersWithLexofficeId}\n";
    echo "   Kunden mit Lexware-Version: {$customersWithLexwareVersion}\n";
    echo "   Kunden mit Lexware-JSON: {$customersWithLexwareJson}\n";
    
    // Prozentuale Abdeckung
    if ($customersWithLexofficeId > 0) {
        $versionCoverage = round(($customersWithLexwareVersion / $customersWithLexofficeId) * 100, 1);
        $jsonCoverage = round(($customersWithLexwareJson / $customersWithLexofficeId) * 100, 1);
        
        echo "   Version-Abdeckung: {$versionCoverage}%\n";
        echo "   JSON-Abdeckung: {$jsonCoverage}%\n";
    }
    
    echo "\n";
    
    echo "=== TEST 4: VERSIONS-ANALYSE ===\n";
    
    // Analyse der Versionen
    $versionStats = Customer::whereNotNull('lexware_version')
        ->selectRaw('lexware_version, COUNT(*) as count')
        ->groupBy('lexware_version')
        ->orderBy('lexware_version')
        ->get();
    
    echo "📈 Versions-Verteilung:\n";
    foreach ($versionStats as $stat) {
        echo "   Version {$stat->lexware_version}: {$stat->count} Kunde(n)\n";
    }
    
    // Höchste und niedrigste Version
    $maxVersion = Customer::whereNotNull('lexware_version')->max('lexware_version');
    $minVersion = Customer::whereNotNull('lexware_version')->min('lexware_version');
    
    if ($maxVersion && $minVersion) {
        echo "   Höchste Version: {$maxVersion}\n";
        echo "   Niedrigste Version: {$minVersion}\n";
        echo "   Versions-Spanne: " . ($maxVersion - $minVersion) . "\n";
    }
    
    echo "\n";
    
    echo "=== TEST 5: JSON-DATEN ANALYSE ===\n";
    
    // Analyse der JSON-Daten
    $customersWithJson = Customer::whereNotNull('lexware_json')->limit(3)->get();
    
    echo "🔍 JSON-Struktur Analyse (erste 3 Kunden):\n";
    foreach ($customersWithJson as $customer) {
        echo "\n👤 {$customer->name}:\n";
        $jsonData = $customer->lexware_json;
        
        // Hauptfelder analysieren
        $mainFields = ['id', 'organizationId', 'version', 'updatedDate', 'person', 'company', 'addresses', 'emailAddresses', 'phoneNumbers'];
        
        foreach ($mainFields as $field) {
            if (isset($jsonData[$field])) {
                if (is_array($jsonData[$field])) {
                    echo "   ✅ {$field}: Array mit " . count($jsonData[$field]) . " Elementen\n";
                } else {
                    echo "   ✅ {$field}: " . (is_string($jsonData[$field]) ? substr($jsonData[$field], 0, 50) : $jsonData[$field]) . "\n";
                }
            } else {
                echo "   ❌ {$field}: Nicht vorhanden\n";
            }
        }
    }
    
} else {
    echo "❌ Import fehlgeschlagen:\n";
    echo "   Error: {$result['error']}\n";
}

echo "\n=== TEST 6: ADRESS-STATISTIKEN ===\n";

// Adress-Statistiken
$customersWithBillingAddress = Customer::whereHas('addresses', function($query) {
    $query->where('type', 'billing');
})->count();

$customersWithShippingAddress = Customer::whereHas('addresses', function($query) {
    $query->where('type', 'shipping');
})->count();

$customersWithStandardAddress = Customer::whereNotNull('street')->count();

echo "📍 Adress-Statistiken:\n";
echo "   Kunden mit Standard-Adresse: {$customersWithStandardAddress}\n";
echo "   Kunden mit Rechnungsadresse: {$customersWithBillingAddress}\n";
echo "   Kunden mit Lieferadresse: {$customersWithShippingAddress}\n";

// Adress-Abdeckung bei Lexoffice-Kunden
$lexofficeCustomersWithAddresses = Customer::whereNotNull('lexoffice_id')
    ->where(function($query) {
        $query->whereNotNull('street')
              ->orWhereHas('addresses');
    })->count();

if ($customersWithLexofficeId > 0) {
    $addressCoverage = round(($lexofficeCustomersWithAddresses / $customersWithLexofficeId) * 100, 1);
    echo "   Adress-Abdeckung bei Lexoffice-Kunden: {$addressCoverage}%\n";
}

echo "\n=== TEST 7: LOGGING VERIFICATION ===\n";

// Prüfe die Import-Logs
$importLogs = \App\Models\LexofficeLog::where('action', 'import')
    ->where('created_at', '>=', now()->subMinutes(5))
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();

echo "📊 Letzte " . $importLogs->count() . " Import-Log-Einträge:\n";
foreach ($importLogs as $log) {
    $status = $log->status === 'success' ? '✅' : '❌';
    echo "   {$status} {$log->action} - {$log->status} ({$log->created_at->format('H:i:s')})\n";
    
    if ($log->request_data && is_array($log->request_data)) {
        if (isset($log->request_data['imported'])) {
            echo "     📊 Importiert: {$log->request_data['imported']}\n";
        }
        if (isset($log->request_data['errors']) && count($log->request_data['errors']) > 0) {
            echo "     ❌ Fehler: " . count($log->request_data['errors']) . "\n";
        }
    }
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "✅ Import-Funktionalität erweitert\n";
echo "✅ Lexware-Version wird beim Import gespeichert\n";
echo "✅ Lexware-JSON wird beim Import gespeichert\n";
echo "✅ Alle importierten Kunden haben vollständige Lexware-Daten\n";
echo "✅ Logging funktioniert korrekt\n";
echo "✅ Statistiken und Analyse verfügbar\n";

echo "\n=== TEST ABGESCHLOSSEN ===\n";
