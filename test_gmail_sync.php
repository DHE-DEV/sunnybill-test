<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GmailService;
use App\Models\CompanySetting;
use App\Models\GmailEmail;

echo "🔍 Gmail Synchronisation Test\n";
echo "============================\n\n";

try {
    // Gmail Service initialisieren
    $gmailService = new GmailService();
    
    // 1. Konfiguration prüfen
    echo "📋 1. Gmail Konfiguration prüfen...\n";
    
    $settings = CompanySetting::current();
    
    echo "   ✅ Gmail aktiviert: " . ($settings->isGmailEnabled() ? 'Ja' : 'Nein') . "\n";
    echo "   ✅ Client ID vorhanden: " . ($settings->getGmailClientId() ? 'Ja' : 'Nein') . "\n";
    echo "   ✅ Client Secret vorhanden: " . ($settings->getGmailClientSecret() ? 'Ja' : 'Nein') . "\n";
    echo "   ✅ Access Token vorhanden: " . ($settings->getGmailAccessToken() ? 'Ja' : 'Nein') . "\n";
    echo "   ✅ Refresh Token vorhanden: " . ($settings->getGmailRefreshToken() ? 'Ja' : 'Nein') . "\n";
    echo "   ✅ E-Mail-Adresse: " . ($settings->gmail_email_address ?? 'Nicht gesetzt') . "\n";
    
    if ($settings->getGmailTokenExpiresAt()) {
        $expiresAt = $settings->getGmailTokenExpiresAt();
        $isExpired = $expiresAt->isPast();
        echo "   ✅ Token läuft ab: " . $expiresAt->format('d.m.Y H:i:s') . ($isExpired ? ' (ABGELAUFEN)' : ' (Gültig)') . "\n";
    }
    
    echo "\n";
    
    // 2. Verbindung testen
    echo "🔗 2. Gmail Verbindung testen...\n";
    
    $connectionTest = $gmailService->testConnection();
    
    if ($connectionTest['success']) {
        echo "   ✅ Verbindung erfolgreich!\n";
        echo "   ✅ E-Mail: " . $connectionTest['email'] . "\n";
        if (isset($connectionTest['name'])) {
            echo "   ✅ Name: " . $connectionTest['name'] . "\n";
        }
    } else {
        echo "   ❌ Verbindung fehlgeschlagen: " . $connectionTest['error'] . "\n";
        
        if (strpos($connectionTest['error'], 'Keine Autorisierung') !== false) {
            echo "\n";
            echo "🔧 Gmail OAuth2 Autorisierung erforderlich:\n";
            echo "   1. Gehen Sie zu: https://sunnybill-test.test/admin/company-settings\n";
            echo "   2. Klicken Sie auf 'Gmail autorisieren'\n";
            echo "   3. Folgen Sie dem OAuth2-Prozess\n";
            echo "   4. Führen Sie dieses Script erneut aus\n";
        }
        
        exit(1);
    }
    
    echo "\n";
    
    // 3. Aktuelle E-Mail-Statistiken
    echo "📊 3. Aktuelle E-Mail-Statistiken...\n";
    
    $totalEmails = GmailEmail::count();
    $unreadEmails = GmailEmail::unread()->count();
    $todayEmails = GmailEmail::whereDate('created_at', today())->count();
    
    echo "   📧 Gesamt E-Mails in DB: {$totalEmails}\n";
    echo "   📬 Ungelesene E-Mails: {$unreadEmails}\n";
    echo "   📅 Heute synchronisiert: {$todayEmails}\n";
    
    if ($settings->getGmailLastSync()) {
        echo "   🕐 Letzte Synchronisation: " . $settings->getGmailLastSync()->format('d.m.Y H:i:s') . "\n";
    } else {
        echo "   🕐 Letzte Synchronisation: Noch nie\n";
    }
    
    if ($settings->getGmailLastError()) {
        echo "   ⚠️  Letzter Fehler: " . $settings->getGmailLastError() . "\n";
    }
    
    echo "\n";
    
    // 4. E-Mails synchronisieren
    echo "🔄 4. E-Mails synchronisieren...\n";
    
    $syncOptions = [
        'maxResults' => 50, // Nur die letzten 50 E-Mails
    ];
    
    echo "   📥 Synchronisiere die letzten 50 E-Mails...\n";
    
    $syncStats = $gmailService->syncEmails($syncOptions);
    
    echo "   ✅ Verarbeitet: {$syncStats['processed']}\n";
    echo "   ✅ Neue E-Mails: {$syncStats['new']}\n";
    echo "   ✅ Aktualisierte E-Mails: {$syncStats['updated']}\n";
    echo "   ✅ Fehler: {$syncStats['errors']}\n";
    
    echo "\n";
    
    // 5. Aktualisierte Statistiken
    echo "📊 5. Aktualisierte Statistiken...\n";
    
    $newTotalEmails = GmailEmail::count();
    $newUnreadEmails = GmailEmail::unread()->count();
    $newTodayEmails = GmailEmail::whereDate('created_at', today())->count();
    
    echo "   📧 Gesamt E-Mails in DB: {$newTotalEmails} (+" . ($newTotalEmails - $totalEmails) . ")\n";
    echo "   📬 Ungelesene E-Mails: {$newUnreadEmails}\n";
    echo "   📅 Heute synchronisiert: {$newTodayEmails}\n";
    
    echo "\n";
    
    // 6. Neueste E-Mails anzeigen
    echo "📬 6. Neueste E-Mails (Top 5)...\n";
    
    $latestEmails = GmailEmail::latest('gmail_date')->take(5)->get();
    
    if ($latestEmails->count() > 0) {
        foreach ($latestEmails as $index => $email) {
            $fromEmail = is_array($email->from) && count($email->from) > 0 
                ? $email->from[0]['email'] ?? 'Unbekannt'
                : 'Unbekannt';
            
            $fromName = is_array($email->from) && count($email->from) > 0 
                ? $email->from[0]['name'] ?? ''
                : '';
            
            $from = $fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail;
            
            echo "   " . ($index + 1) . ". {$email->subject}\n";
            echo "      Von: {$from}\n";
            echo "      Datum: " . ($email->gmail_date ? $email->gmail_date->format('d.m.Y H:i') : 'Unbekannt') . "\n";
            echo "      Status: " . ($email->is_read ? 'Gelesen' : 'Ungelesen') . "\n";
            echo "      Snippet: " . substr($email->snippet ?? '', 0, 100) . "...\n";
            echo "\n";
        }
    } else {
        echo "   📭 Keine E-Mails gefunden.\n";
    }
    
    echo "\n";
    
    // 7. Labels anzeigen
    echo "🏷️  7. Verfügbare Gmail Labels...\n";
    
    $labels = $gmailService->getLabels();
    
    if (!empty($labels)) {
        echo "   Gefundene Labels: " . count($labels) . "\n";
        foreach (array_slice($labels, 0, 10) as $label) {
            echo "   - {$label['name']} (ID: {$label['id']})\n";
        }
        if (count($labels) > 10) {
            echo "   ... und " . (count($labels) - 10) . " weitere\n";
        }
    } else {
        echo "   ❌ Keine Labels gefunden.\n";
    }
    
    echo "\n";
    echo "✅ Gmail Synchronisation abgeschlossen!\n";
    echo "\n";
    echo "🔗 Nächste Schritte:\n";
    echo "   1. Besuchen Sie: https://sunnybill-test.test/admin/gmail-emails\n";
    echo "   2. Prüfen Sie die synchronisierten E-Mails\n";
    echo "   3. Konfigurieren Sie automatische Synchronisation (Cron Job)\n";
    echo "   4. Testen Sie Benachrichtigungen unter: https://sunnybill-test.test/admin/notifications\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "📍 Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    if ($e->getPrevious()) {
        echo "🔗 Ursprünglicher Fehler: " . $e->getPrevious()->getMessage() . "\n";
    }
    
    exit(1);
}
