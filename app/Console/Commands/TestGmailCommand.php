<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GmailService;
use App\Models\CompanySetting;

class TestGmailCommand extends Command
{
    protected $signature = 'gmail:test';
    protected $description = 'Test Gmail configuration and email sending';

    public function handle()
    {
        $this->info('=== Gmail Configuration Test ===');
        
        try {
            // 1. Prüfe CompanySetting
            $settings = CompanySetting::current();
            $this->info('✓ CompanySetting loaded');
            
            // 2. Prüfe Gmail-Konfiguration
            $this->info('Gmail enabled: ' . ($settings->isGmailEnabled() ? 'YES' : 'NO'));
            $this->info('Client ID: ' . ($settings->getGmailClientId() ? 'SET (' . substr($settings->getGmailClientId(), 0, 20) . '...)' : 'NOT SET'));
            $this->info('Client Secret: ' . ($settings->getGmailClientSecret() ? 'SET (' . substr($settings->getGmailClientSecret(), 0, 10) . '...)' : 'NOT SET'));
            $this->info('Refresh Token: ' . ($settings->getGmailRefreshToken() ? 'SET (' . substr($settings->getGmailRefreshToken(), 0, 20) . '...)' : 'NOT SET'));
            $this->info('Access Token: ' . ($settings->getGmailAccessToken() ? 'SET (' . substr($settings->getGmailAccessToken(), 0, 20) . '...)' : 'NOT SET'));
            $this->info('Email Address: ' . ($settings->getGmailEmailAddress() ?: 'NOT SET'));
            $this->info('Token Expired: ' . ($settings->isGmailTokenExpired() ? 'YES' : 'NO'));
            
            // 3. Teste GmailService
            $this->info('');
            $this->info('=== Testing GmailService ===');
            
            $gmailService = new GmailService();
            
            // 4. Teste Konfiguration
            if (!$gmailService->isConfigured()) {
                $this->error('❌ Gmail is not configured');
                return 1;
            }
            $this->info('✓ Gmail is configured');
            
            // 5. Teste Verbindung
            $this->info('Testing connection...');
            $connectionResult = $gmailService->testConnection();
            
            if ($connectionResult['success']) {
                $this->info('✓ Connection successful: ' . $connectionResult['email']);
            } else {
                $this->error('❌ Connection failed: ' . $connectionResult['error']);
                return 1;
            }
            
            // 6. Teste E-Mail-Versendung
            $this->info('');
            $this->info('=== Testing Email Sending ===');
            
            $to = 'dh@dhe.de';
            $subject = 'SunnyBill Gmail Test - ' . now()->format('Y-m-d H:i:s');
            $body = "Hallo,\n\ndies ist eine Test-E-Mail von der SunnyBill Gmail-Integration.\n\nGesendet am: " . now()->format('d.m.Y H:i:s') . "\n\nMit freundlichen Grüßen\nIhr SunnyBill Team";
            
            $this->info("Sending test email to: {$to}");
            $this->info("Subject: {$subject}");
            
            $result = $gmailService->sendEmail($to, $subject, $body);
            
            if ($result['success']) {
                $this->info('✓ Email sent successfully!');
                $this->info('Message ID: ' . ($result['message_id'] ?? 'N/A'));
                $this->info('Thread ID: ' . ($result['thread_id'] ?? 'N/A'));
            } else {
                $this->error('❌ Email sending failed: ' . $result['error']);
                return 1;
            }
            
            $this->info('');
            $this->info('🎉 All tests passed! Gmail integration is working correctly.');
            
        } catch (\Exception $e) {
            $this->error('❌ Test failed with exception: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }
        
        return 0;
    }
}