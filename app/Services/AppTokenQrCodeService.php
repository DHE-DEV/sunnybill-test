<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

class AppTokenQrCodeService
{
    /**
     * Generiert einen QR-Code für einen App-Token
     */
    public function generateTokenQrCode(string $token, array $config = []): string
    {
        // Standard-Konfiguration für Token QR-Codes
        $defaultConfig = [
            'size' => 300,
            'margin' => 10,
            'errorCorrectionLevel' => ErrorCorrectionLevel::Medium,
            'encoding' => 'UTF-8'
        ];
        
        $config = array_merge($defaultConfig, $config);
        
        // QR-Code generieren
        $qrCode = new QrCode(
            $token,
            new Encoding($config['encoding']),
            $config['errorCorrectionLevel'],
            $config['size'],
            $config['margin']
        );
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        return base64_encode($result->getString());
    }
    
    /**
     * Generiert einen QR-Code mit Token-Konfiguration für Mobile Apps
     */
    public function generateMobileAppQrCode(string $token, array $appConfig = []): string
    {
        // Erstelle eine JSON-Struktur für Mobile Apps
        $qrData = [
            'type' => 'voltmaster_api_token',
            'token' => $token,
            'api_url' => config('app.url', 'https://prosoltec.voltmaster.cloud'),
            'version' => '1.0'
        ];
        
        // Füge zusätzliche App-Konfiguration hinzu
        if (!empty($appConfig)) {
            $qrData['config'] = $appConfig;
        }
        
        $jsonData = json_encode($qrData, JSON_UNESCAPED_SLASHES);
        
        return $this->generateTokenQrCode($jsonData, [
            'size' => 350, // Größer für bessere Lesbarkeit bei JSON
            'errorCorrectionLevel' => ErrorCorrectionLevel::High // Höhere Fehlerkorrektur für komplexere Daten
        ]);
    }
    
    /**
     * Generiert einen einfachen Token QR-Code (nur der Token)
     */
    public function generateSimpleTokenQrCode(string $token): string
    {
        return $this->generateTokenQrCode($token, [
            'size' => 250,
            'margin' => 8
        ]);
    }
    
    /**
     * Generiert einen QR-Code mit API-Konfiguration
     */
    public function generateApiConfigQrCode(string $token, string $tokenName, array $abilities = []): string
    {
        $qrData = [
            'type' => 'voltmaster_api_config',
            'token' => $token,
            'token_name' => $tokenName,
            'api_url' => config('app.url', 'https://prosoltec.voltmaster.cloud'),
            'api_version' => '1.0',
            'abilities' => $abilities,
            'endpoints' => [
                'base' => config('app.url', 'https://prosoltec.voltmaster.cloud'),
                'api' => '/api/app',
                'docs' => '/api/documentation'
            ],
            'generated_at' => now()->toISOString()
        ];
        
        $jsonData = json_encode($qrData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
        return $this->generateTokenQrCode($jsonData, [
            'size' => 400,
            'errorCorrectionLevel' => ErrorCorrectionLevel::High
        ]);
    }
}