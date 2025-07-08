<?php

require_once 'vendor/autoload.php';

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

echo "=== Gmail Auto-Sync Test mit neuer E-Mail ===\n\n";

// 1. Aktuelle E-Mail-Anzahl prüfen
$currentCount = \App\Models\GmailEmail::count();
echo "1. Aktuelle E-Mail-Anzahl in Datenbank: $currentCount\n\n";

// 2. Letzte Synchronisation prüfen
$company = \App\Models\CompanySetting::where('gmail_auto_sync', true)->first();
echo "2. Letzte Synchronisation: " . ($company->gmail_last_sync ?? 'Nie') . "\n";
echo "   Sync-Intervall: {$company->gmail_sync_interval} Minuten\n\n";

// 3. Manuelle Synchronisation durchführen
echo "3. Führe manuelle Synchronisation durch...\n";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$startTime = microtime(true);
$exitCode = $kernel->call('gmail:sync');
$endTime = microtime(true);

echo "   Synchronisation abgeschlossen in " . round(($endTime - $startTime) * 1000, 2) . "ms\n";
echo "   Exit Code: $exitCode\n\n";

// 4. Neue E-Mail-Anzahl prüfen
$newCount = \App\Models\GmailEmail::count();
$difference = $newCount - $currentCount;

echo "4. Neue E-Mail-Anzahl: $newCount\n";
echo "   Unterschied: " . ($difference > 0 ? "+$difference" : $difference) . " E-Mails\n\n";

// 5. Letzte 5 E-Mails anzeigen
echo "5. Letzte 5 E-Mails:\n";
$recentEmails = \App\Models\GmailEmail::orderBy('created_at', 'desc')->take(5)->get();

foreach ($recentEmails as $email) {
    $isNew = $email->created_at->diffInMinutes(now()) < 5 ? " [NEU]" : "";
    echo "   - {$email->subject} (von: {$email->from_email})$isNew\n";
    echo "     Erstellt: {$email->created_at}\n";
    echo "     Gmail ID: {$email->gmail_id}\n\n";
}

// 6. Scheduler-Status prüfen
echo "6. Scheduler-Status:\n";
$company->refresh();
echo "   Letzte Sync nach Test: " . ($company->gmail_last_sync ?? 'Nie') . "\n";
echo "   Auto-Sync aktiv: " . ($company->gmail_auto_sync ? 'Ja' : 'Nein') . "\n\n";

echo "=== Test abgeschlossen ===\n";
echo "\nHinweis: Wenn keine neuen E-Mails gefunden wurden, senden Sie eine neue\n";
echo "Test-E-Mail an die konfigurierte Gmail-Adresse und warten Sie 1-2 Minuten.\n";
echo "Der Scheduler läuft jede Minute und wird neue E-Mails automatisch synchronisieren.\n";
