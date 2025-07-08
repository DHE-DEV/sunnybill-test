<?php

require_once 'vendor/autoload.php';

use App\Models\CompanySetting;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Gmail-Konfiguration Setup ===\n\n";

try {
    // Pfad zur Google OAuth2 Client-Datei
    $clientSecretPath = 'c:/Users/dh/Downloads/client_secret_164979257393-36s2f08tp17a6a1bqqk69fvpegc2j3nb.apps.googleusercontent.com.json';
    
    if (!file_exists($clientSecretPath)) {
        throw new Exception("Client Secret Datei nicht gefunden: {$clientSecretPath}");
    }
    
    echo "✓ Client Secret Datei gefunden\n";
    
    // JSON-Datei lesen
    $clientSecretContent = file_get_contents($clientSecretPath);
    $clientSecretData = json_decode($clientSecretContent, true);
    
    if (!$clientSecretData) {
        throw new Exception("Fehler beim Parsen der Client Secret JSON-Datei");
    }
    
    echo "✓ Client Secret JSON erfolgreich gelesen\n";
    
    // OAuth2-Daten extrahieren
    $webConfig = $clientSecretData['web'] ?? null;
    if (!$webConfig) {
        throw new Exception("'web' Konfiguration nicht in der JSON-Datei gefunden");
    }
    
    $clientId = $webConfig['client_id'] ?? null;
    $clientSecret = $webConfig['client_secret'] ?? null;
    
    if (!$clientId || !$clientSecret) {
        throw new Exception("Client ID oder Client Secret nicht in der JSON-Datei gefunden");
    }
    
    echo "✓ Client ID und Client Secret extrahiert\n";
    echo "  Client ID: " . substr($clientId, 0, 20) . "...\n";
    echo "  Client Secret: " . substr($clientSecret, 0, 10) . "...\n\n";
    
    // CompanySetting laden oder erstellen
    $settings = CompanySetting::current();
    
    echo "=== Gmail-Konfiguration wird gespeichert ===\n";
    
    // Gmail-Einstellungen aktualisieren
    $settings->update([
        'gmail_enabled' => true,
        'gmail_client_id' => $clientId,
        'gmail_client_secret' => $clientSecret,
        'gmail_auto_sync' => true,
        'gmail_sync_interval' => 5,
        'gmail_download_attachments' => true,
        'gmail_attachment_path' => 'gmail-attachments',
        'gmail_mark_as_read' => false,
        'gmail_archive_processed' => false,
        'gmail_processed_label' => 'Processed',
        'gmail_max_results' => 100,
    ]);
    
    echo "✓ Gmail-Grundkonfiguration gespeichert\n";
    
    // Konfigurationsstatus prüfen
    $configStatus = $settings->getGmailConfigStatus();
    
    echo "\n=== Konfigurationsstatus ===\n";
    echo "Gmail aktiviert: " . ($configStatus['enabled'] ? '✓ Ja' : '✗ Nein') . "\n";
    echo "Client ID gesetzt: " . ($configStatus['client_id_set'] ? '✓ Ja' : '✗ Nein') . "\n";
    echo "Client Secret gesetzt: " . ($configStatus['client_secret_set'] ? '✓ Ja' : '✗ Nein') . "\n";
    echo "Refresh Token gesetzt: " . ($configStatus['refresh_token_set'] ? '✓ Ja' : '✗ Nein') . "\n";
    echo "Konfiguration gültig: " . ($configStatus['is_valid'] ? '✓ Ja' : '✗ Nein (Refresh Token fehlt)') . "\n";
    
    if (!$configStatus['is_valid']) {
        echo "\n=== Nächste Schritte ===\n";
        echo "1. Gehen Sie zu: https://sunnybill-test.test/admin/company-settings\n";
        echo "2. Wechseln Sie zum Tab 'Gmail-Integration'\n";
        echo "3. Die Client ID und Client Secret sind bereits konfiguriert\n";
        echo "4. Sie müssen noch die OAuth2-Autorisierung durchführen\n";
        echo "5. Danach können Sie E-Mails unter https://sunnybill-test.test/admin/gmail-emails synchronisieren\n";
    }
    
    echo "\n=== Setup abgeschlossen ===\n";
    echo "Gmail-Integration ist grundlegend konfiguriert.\n";
    echo "Für die vollständige Funktionalität ist noch eine OAuth2-Autorisierung erforderlich.\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    exit(1);
}
