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
        // Standard-Konfiguration für Token QR-Codes - optimiert für bessere Lesbarkeit
        $defaultConfig = [
            'size' => 400,
            'margin' => 20,
            'errorCorrectionLevel' => ErrorCorrectionLevel::High,
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
        // Erstelle eine kompakte JSON-Struktur für Mobile Apps
        $qrData = [
            'type' => 'voltmaster_token',
            'token' => $token,
            'url' => config('app.url', 'https://prosoltec.voltmaster.cloud'),
            'v' => '1.0'
        ];
        
        // Füge zusätzliche App-Konfiguration hinzu (kompakt)
        if (!empty($appConfig)) {
            $qrData['cfg'] = $appConfig;
        }
        
        $jsonData = json_encode($qrData, JSON_UNESCAPED_SLASHES);
        
        return $this->generateTokenQrCode($jsonData, [
            'size' => 500, // Größer für bessere Lesbarkeit bei JSON
            'margin' => 30,
            'errorCorrectionLevel' => ErrorCorrectionLevel::High // Höhere Fehlerkorrektur für komplexere Daten
        ]);
    }
    
    /**
     * Generiert einen einfachen Token QR-Code (nur der Token) - optimiert für maximale Lesbarkeit
     */
    public function generateSimpleTokenQrCode(string $token): string
    {
        return $this->generateTokenQrCode($token, [
            'size' => 500,  // Größer für bessere Lesbarkeit
            'margin' => 40, // Mehr Rand für bessere Scanner-Erkennung
            'errorCorrectionLevel' => ErrorCorrectionLevel::High // Höhere Fehlerkorrektur
        ]);
    }
    
    /**
     * Generiert einen QR-Code mit API-Konfiguration (kompakt für bessere Lesbarkeit)
     */
    public function generateApiConfigQrCode(string $token, string $tokenName, array $abilities = []): string
    {
        // Kompakte JSON-Struktur für bessere QR-Code-Lesbarkeit
        $qrData = [
            'type' => 'vm_api',
            'token' => $token,
            'name' => $tokenName,
            'url' => config('app.url', 'https://prosoltec.voltmaster.cloud'),
            'v' => '1.0',
            'perms' => $abilities,
            'api' => '/api/app'
        ];
        
        // Kompakte JSON ohne Pretty Print für kleineren QR-Code
        $jsonData = json_encode($qrData, JSON_UNESCAPED_SLASHES);
        
        return $this->generateTokenQrCode($jsonData, [
            'size' => 500,
            'margin' => 30,
            'errorCorrectionLevel' => ErrorCorrectionLevel::High
        ]);
    }
}
