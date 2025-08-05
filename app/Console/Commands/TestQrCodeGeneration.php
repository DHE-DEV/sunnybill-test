<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AppTokenQrCodeService;
use App\Models\AppToken;

class TestQrCodeGeneration extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'qr:test {--type=simple : Type of QR code to test (simple, api, mobile)}';

    /**
     * The console command description.
     */
    protected $description = 'Test QR code generation for app tokens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing QR Code Generation...');
        
        $qrService = new AppTokenQrCodeService();
        $testToken = 'sb_test_' . str_repeat('a', 60); // Test token
        
        $type = $this->option('type');
        
        try {
            switch ($type) {
                case 'simple':
                    $this->testSimpleQrCode($qrService, $testToken);
                    break;
                case 'api':
                    $this->testApiConfigQrCode($qrService, $testToken);
                    break;
                case 'mobile':
                    $this->testMobileAppQrCode($qrService, $testToken);
                    break;
                default:
                    $this->testAllTypes($qrService, $testToken);
                    break;
            }
            
            $this->info('✅ QR Code generation test completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('❌ QR Code generation failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
        }
    }
    
    private function testSimpleQrCode($qrService, $testToken)
    {
        $this->info('📱 Testing Simple Token QR Code...');
        
        $qrCode = $qrService->generateSimpleTokenQrCode($testToken);
        
        $this->info('Token length: ' . strlen($testToken));
        $this->info('QR Code base64 length: ' . strlen($qrCode));
        $this->info('QR Code preview (first 100 chars): ' . substr($qrCode, 0, 100) . '...');
        
        // Speichere Test-QR-Code
        $this->saveTestQrCode($qrCode, 'simple_token_qr.png');
    }
    
    private function testApiConfigQrCode($qrService, $testToken)
    {
        $this->info('⚙️ Testing API Config QR Code...');
        
        $abilities = ['tasks:read', 'tasks:create', 'customers:read'];
        $qrCode = $qrService->generateApiConfigQrCode($testToken, 'Test Token', $abilities);
        
        $this->info('Token length: ' . strlen($testToken));
        $this->info('Abilities: ' . implode(', ', $abilities));
        $this->info('QR Code base64 length: ' . strlen($qrCode));
        $this->info('QR Code preview (first 100 chars): ' . substr($qrCode, 0, 100) . '...');
        
        // Zeige JSON-Inhalt
        $jsonData = [
            'type' => 'vm_api',
            'token' => $testToken,
            'name' => 'Test Token',
            'url' => config('app.url', 'https://prosoltec.voltmaster.cloud'),
            'v' => '1.0',
            'perms' => $abilities,
            'api' => '/api/app'
        ];
        
        $jsonString = json_encode($jsonData, JSON_UNESCAPED_SLASHES);
        $this->info('JSON content length: ' . strlen($jsonString));
        $this->info('JSON content: ' . $jsonString);
        
        // Speichere Test-QR-Code
        $this->saveTestQrCode($qrCode, 'api_config_qr.png');
    }
    
    private function testMobileAppQrCode($qrService, $testToken)
    {
        $this->info('📱 Testing Mobile App QR Code...');
        
        $appConfig = ['theme' => 'dark', 'notifications' => true];
        $qrCode = $qrService->generateMobileAppQrCode($testToken, $appConfig);
        
        $this->info('Token length: ' . strlen($testToken));
        $this->info('App config: ' . json_encode($appConfig));
        $this->info('QR Code base64 length: ' . strlen($qrCode));
        $this->info('QR Code preview (first 100 chars): ' . substr($qrCode, 0, 100) . '...');
        
        // Speichere Test-QR-Code
        $this->saveTestQrCode($qrCode, 'mobile_app_qr.png');
    }
    
    private function testAllTypes($qrService, $testToken)
    {
        $this->info('🔄 Testing all QR Code types...');
        
        $this->testSimpleQrCode($qrService, $testToken);
        $this->line('');
        $this->testApiConfigQrCode($qrService, $testToken);
        $this->line('');
        $this->testMobileAppQrCode($qrService, $testToken);
    }
    
    private function saveTestQrCode($base64QrCode, $filename)
    {
        $path = storage_path('app/qr-tests');
        
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        
        $fullPath = $path . '/' . $filename;
        file_put_contents($fullPath, base64_decode($base64QrCode));
        
        $this->info("💾 QR Code saved to: {$fullPath}");
        $this->info("📏 File size: " . number_format(filesize($fullPath)) . " bytes");
    }
}