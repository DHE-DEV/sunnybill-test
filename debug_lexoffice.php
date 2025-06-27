<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Lexoffice Debug-Analyse...\n\n";

// 1. Letzte Logs anzeigen
echo "1. 📋 Letzte Lexoffice-Logs:\n";
$logs = App\Models\LexofficeLog::latest()->take(5)->get();

if ($logs->count() > 0) {
    foreach ($logs as $log) {
        echo "   {$log->created_at} - {$log->type}/{$log->action} - {$log->status}\n";
        if ($log->error_message) {
            echo "   ❌ Fehler: {$log->error_message}\n";
        }
        if ($log->request_data) {
            echo "   📤 Request: " . json_encode($log->request_data, JSON_PRETTY_PRINT) . "\n";
        }
        echo "\n";
    }
} else {
    echo "   ℹ️  Keine Logs gefunden\n\n";
}

// 2. Lexoffice-Verbindung testen
echo "2. 🔗 Lexoffice-Verbindung testen:\n";
try {
    $lexofficeService = new App\Services\LexofficeService();
    $connectionTest = $lexofficeService->testConnection();
    
    if ($connectionTest['success']) {
        echo "   ✅ Verbindung erfolgreich\n";
        echo "   🏢 Firma: {$connectionTest['company']}\n";
        echo "   📧 E-Mail: {$connectionTest['email']}\n\n";
    } else {
        echo "   ❌ Verbindung fehlgeschlagen\n";
        echo "   🚫 Fehler: {$connectionTest['error']}\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Exception: {$e->getMessage()}\n\n";
}

// 3. Konfiguration prüfen
echo "3. ⚙️  Konfiguration prüfen:\n";
$apiKey = config('services.lexoffice.api_key');
if ($apiKey) {
    echo "   ✅ API-Key konfiguriert: " . substr($apiKey, 0, 10) . "...\n";
} else {
    echo "   ❌ API-Key nicht konfiguriert\n";
}

// 4. Test-Kunde erstellen
echo "\n4. 👤 Test-Kunde für Lexoffice-Export:\n";
try {
    $testCustomer = App\Models\Customer::first();
    if ($testCustomer) {
        echo "   📋 Test-Kunde gefunden: {$testCustomer->name}\n";
        echo "   📧 E-Mail: {$testCustomer->email}\n";
        echo "   📍 Adresse: {$testCustomer->street}, {$testCustomer->postal_code} {$testCustomer->city}\n";
        
        // Lexoffice-Export testen
        echo "\n   🚀 Lexoffice-Export testen...\n";
        $result = $lexofficeService->exportCustomer($testCustomer);
        
        if ($result['success']) {
            echo "   ✅ Export erfolgreich\n";
            echo "   🆔 Lexoffice-ID: {$result['lexoffice_id']}\n";
            echo "   🔄 Aktion: {$result['action']}\n";
        } else {
            echo "   ❌ Export fehlgeschlagen\n";
            echo "   🚫 Fehler: {$result['error']}\n";
        }
    } else {
        echo "   ℹ️  Kein Test-Kunde gefunden\n";
    }
} catch (Exception $e) {
    echo "   ❌ Exception beim Kunden-Export: {$e->getMessage()}\n";
}

echo "\n🎯 Debug-Analyse abgeschlossen!\n";