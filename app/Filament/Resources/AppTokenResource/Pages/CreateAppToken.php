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

        // Generiere QR-Code für den Token (nur API-Key)
        $qrCodeService = new AppTokenQrCodeService();
        $qrCodeBase64 = $qrCodeService->generateSimpleTokenQrCode($plainTextToken);
        $qrCodeType = 'API-Token';

        // Zeige den Token mit QR-Code in einer Benachrichtigung mit Kopierschaltfläche
        Notification::make()
            ->title('Token erfolgreich erstellt')
            ->body("
                <div class='space-y-6'>
                    <div>
                        <strong>Token-Name:</strong> {$appToken->name}
                    </div>
                    <div class='bg-gray-50 p-4 rounded-lg border'>
                        <div class='flex justify-between items-center mb-2'>
                            <strong>API-Token:</strong>
                            <button onclick='copyToClipboard(\"{$plainTextToken}\")' 
                                    class='bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs font-medium transition-colors duration-200 flex items-center gap-1'
                                    title='Token in Zwischenablage kopieren'>
                                <svg class='w-3 h-3' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z'></path>
                                </svg>
                                Kopieren
                            </button>
                        </div>
                        <code class='bg-white p-3 rounded text-sm font-mono break-all block border'>{$plainTextToken}</code>
                    </div>
                    <div class='text-center bg-white p-6 rounded-lg border border-gray-200'>
                        <strong class='text-lg mb-4 block'>QR-Code ({$qrCodeType}):</strong>
                        <div class='bg-white p-6 rounded-lg inline-block shadow-lg border-2 border-gray-100'>
                            <img src='data:image/png;base64,{$qrCodeBase64}' alt='Token QR-Code' style='width: 400px; height: 400px; display: block;' />
                        </div>
                        <div class='text-sm text-gray-600 mt-4 max-w-md mx-auto'>
                            📱 <strong>Scannen Sie den QR-Code</strong> mit Ihrer App für eine schnelle Token-Konfiguration<br>
                            <span class='text-xs text-gray-500 mt-2 block'>Der QR-Code enthält nur den Token (keine URL) und ist für optimale Lesbarkeit konfiguriert</span>
                        </div>
                    </div>
                    <div class='text-sm text-red-600 bg-red-50 p-4 rounded-lg border border-red-200'>
                        <div class='flex items-start gap-2'>
                            <svg class='w-4 h-4 text-red-500 mt-0.5 flex-shrink-0' fill='currentColor' viewBox='0 0 20 20'>
                                <path fill-rule='evenodd' d='M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z' clip-rule='evenodd'></path>
                            </svg>
                            <div>
                                <strong>⚠️ Wichtiger Sicherheitshinweis:</strong><br>
                                Kopieren Sie diesen Token jetzt und speichern Sie ihn sicher. Er wird aus Sicherheitsgründen nicht mehr angezeigt.
                            </div>
                        </div>
                    </div>
                </div>
                
                <script>
                function copyToClipboard(text) {
                    navigator.clipboard.writeText(text).then(function() {
                        // Erfolgsmeldung anzeigen
                        const button = event.target.closest('button');
                        const originalText = button.innerHTML;
                        button.innerHTML = '<svg class=\"w-3 h-3\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"></path></svg>Kopiert!';
                        button.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                        button.classList.add('bg-green-500');
                        
                        setTimeout(function() {
                            button.innerHTML = originalText;
                            button.classList.remove('bg-green-500');
                            button.classList.add('bg-blue-500', 'hover:bg-blue-600');
                        }, 2000);
                    }).catch(function(err) {
                        console.error('Fehler beim Kopieren: ', err);
                        alert('Fehler beim Kopieren in die Zwischenablage');
                    });
                }
                </script>
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
