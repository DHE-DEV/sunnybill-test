<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\RouterNotificationService;
use Illuminate\Console\Command;

class TestRouterNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'routers:test-notifications {--send : Actually send test emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test router notification configuration and optionally send test emails';

    protected RouterNotificationService $notificationService;

    public function __construct(RouterNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Router Notification Configuration Test ===');
        $this->newLine();

        // Configuration Summary
        $this->displayConfigurationSummary();

        // Email parsing test
        $this->testEmailParsing();

        // Send test email if requested
        if ($this->option('send')) {
            $this->sendTestNotification();
        } else {
            $this->newLine();
            $this->info('💡 Verwende --send um eine Test-E-Mail zu versenden');
            $this->info('   Beispiel: php artisan routers:test-notifications --send');
        }

        $this->newLine();
        $this->info('✅ Test abgeschlossen!');
    }

    /**
     * Display configuration summary
     */
    private function displayConfigurationSummary(): void
    {
        $summary = $this->notificationService->getNotificationSummary();
        $emails = $this->notificationService->getEmailConfiguration();

        $this->info('📧 E-Mail-Konfiguration:');

        if (!$summary['enabled']) {
            $this->error('   ❌ Benachrichtigungen sind DEAKTIVIERT');
            $this->warn('   💡 Setze ROUTER_NOTIFICATION_ENABLED=true in der .env');
            return;
        }

        $this->info('   ✅ Benachrichtigungen sind aktiviert');
        $this->newLine();

        // TO Recipients
        if (!empty($emails['to'])) {
            $this->info('   📬 TO-Empfänger (' . count($emails['to']) . '):');
            foreach ($emails['to'] as $email) {
                $this->info("      - {$email}");
            }
        } else {
            $this->error('   ❌ Keine TO-Empfänger konfiguriert!');
            $this->warn('   💡 Setze ROUTER_NOTIFICATION_TO="email1@domain.com,email2@domain.com"');
        }

        // CC Recipients
        if (!empty($emails['cc'])) {
            $this->info('   📋 CC-Empfänger (' . count($emails['cc']) . '):');
            foreach ($emails['cc'] as $email) {
                $this->info("      - {$email}");
            }
        }

        // BCC Recipients
        if (!empty($emails['bcc'])) {
            $this->info('   🔒 BCC-Empfänger (' . count($emails['bcc']) . '):');
            foreach ($emails['bcc'] as $email) {
                $this->info("      - {$email}");
            }
        }

        $this->newLine();
        $this->info('   📋 Benachrichtigungstypen:');
        foreach ($summary['notification_types'] as $type => $enabled) {
            $status = $enabled ? '✅ Aktiviert' : '❌ Deaktiviert';
            $this->info("      - {$type}: {$status}");
        }
    }

    /**
     * Test email parsing
     */
    private function testEmailParsing(): void
    {
        $this->newLine();
        $this->info('🧪 E-Mail-Parsing-Test:');

        $testStrings = [
            'ROUTER_NOTIFICATION_TO' => config('router-notifications.emails.to', ''),
            'ROUTER_NOTIFICATION_CC' => config('router-notifications.emails.cc', ''),
            'ROUTER_NOTIFICATION_BCC' => config('router-notifications.emails.bcc', ''),
        ];

        foreach ($testStrings as $key => $value) {
            if (empty($value)) {
                $this->info("   {$key}: (leer)");
                continue;
            }

            $this->info("   {$key}: \"{$value}\"");

            // Parse emails
            $emails = array_map('trim', explode(',', $value));
            $validEmails = array_filter($emails, function ($email) {
                return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
            });

            $this->info("      Gefundene E-Mails: " . count($emails));
            $this->info("      Gültige E-Mails: " . count($validEmails));

            if (count($emails) !== count($validEmails)) {
                $invalidEmails = array_diff($emails, $validEmails);
                $this->warn("      Ungültige E-Mails: " . implode(', ', $invalidEmails));
            }
        }
    }

    /**
     * Send test notification
     */
    private function sendTestNotification(): void
    {
        $this->newLine();
        $this->info('📧 Test-E-Mail wird gesendet...');

        // Find first router for testing
        $router = Router::first();

        if (!$router) {
            $this->error('   ❌ Kein Router in der Datenbank gefunden für Test');
            return;
        }

        $this->info("   📡 Verwende Router: {$router->name} (ID: {$router->id})");

        try {
            $success = $this->notificationService->sendStatusNotification(
                $router, 
                'online → offline (TEST-BENACHRICHTIGUNG)'
            );

            if ($success) {
                $this->info('   ✅ Test-E-Mail erfolgreich gesendet!');
                $this->info('   📬 Prüfe deine E-Mail-Postfächer');
            } else {
                $this->error('   ❌ Test-E-Mail konnte nicht gesendet werden');
                $this->warn('   💡 Prüfe die Logs für weitere Details');
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Fehler beim Senden der Test-E-Mail:');
            $this->error("   {$e->getMessage()}");
        }
    }
}