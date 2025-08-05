<?php

namespace App\Filament\Resources\AppTokenResource\Pages;

use App\Filament\Resources\AppTokenResource;
use App\Models\AppToken;
use App\Services\AppTokenQrCodeService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateAppToken extends CreateRecord
{
    protected static string $resource = AppTokenResource::class;

    protected function handleRecordCreation(array $data): AppToken
    {
        // Generiere einen neuen Token
        $plainTextToken = AppToken::generateToken();
        
        // Erstelle den Token-Datensatz
        $appToken = AppToken::create([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $data['abilities'] ?? ['tasks:read'],
            'expires_at' => now()->addYears(2),
            'is_active' => $data['is_active'] ?? true,
            'created_by_ip' => request()->ip(),
            'app_type' => $data['app_type'] ?? 'mobile_app',
            'app_version' => $data['app_version'],
            'device_info' => $data['device_info'],
            'notes' => $data['notes'],
        ]);

        // Generiere QR-Code für den Token
        $qrCodeService = new AppTokenQrCodeService();
        
        // Wähle QR-Code-Typ basierend auf App-Typ
        if (in_array($data['app_type'] ?? 'mobile_app', ['mobile_app', 'desktop_app'])) {
            // Für Mobile/Desktop Apps: Vollständige Konfiguration
            $qrCodeBase64 = $qrCodeService->generateApiConfigQrCode(
                $plainTextToken,
                $appToken->name,
                $data['abilities'] ?? ['tasks:read']
            );
            $qrCodeType = 'API-Konfiguration';
        } else {
            // Für andere Apps: Einfacher Token
            $qrCodeBase64 = $qrCodeService->generateSimpleTokenQrCode($plainTextToken);
            $qrCodeType = 'Token';
        }

        // Zeige den Token mit QR-Code in einer Benachrichtigung
        Notification::make()
            ->title('Token erfolgreich erstellt')
            ->body("
                <div class='space-y-6'>
                    <div>
                        <strong>Token-Name:</strong> {$appToken->name}
                    </div>
                    <div>
                        <strong>API-Token:</strong><br>
                        <code class='bg-gray-100 p-2 rounded text-sm font-mono break-all'>{$plainTextToken}</code>
                    </div>
                    <div class='text-center bg-white p-4 rounded-lg border border-gray-200'>
                        <strong class='text-lg'>QR-Code ({$qrCodeType}):</strong><br>
                        <div class='bg-white p-4 rounded-lg inline-block mt-3 shadow-sm border'>
                            <img src='data:image/png;base64,{$qrCodeBase64}' alt='Token QR-Code' style='width: 300px; height: 300px; display: block;' />
                        </div>
                        <div class='text-sm text-gray-600 mt-3 max-w-md mx-auto'>
                            📱 Scannen Sie den QR-Code mit Ihrer App für eine schnelle Konfiguration<br>
                            <span class='text-xs text-gray-500'>Optimiert für bessere Lesbarkeit</span>
                        </div>
                    </div>
                    <div class='text-sm text-red-600 bg-red-50 p-3 rounded border border-red-200'>
                        <strong>⚠️ Wichtig:</strong> Kopieren Sie diesen Token jetzt und speichern Sie ihn sicher. Er wird aus Sicherheitsgründen nicht mehr angezeigt.
                    </div>
                </div>
            ")
            ->success()
            ->persistent()
            ->send();

        return $appToken;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
