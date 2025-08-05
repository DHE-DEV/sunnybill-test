<?php

namespace App\Filament\Resources\AppTokenResource\Pages;

use App\Filament\Resources\AppTokenResource;
use App\Models\AppToken;
use App\Services\AppTokenQrCodeService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;

class CreateAppToken extends CreateRecord
{
    protected static string $resource = AppTokenResource::class;

    // Speichere Token temporär für Modal-Anzeige
    protected $createdToken = null;
    protected $createdTokenPlainText = null;

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

        // Speichere für erweiterte Benachrichtigung
        $this->createdToken = $appToken;
        $this->createdTokenPlainText = $plainTextToken;

        // Generiere QR-Code für die Benachrichtigung
        $qrCodeService = new AppTokenQrCodeService();
        $qrCodeBase64 = $qrCodeService->generateSimpleTokenQrCode($plainTextToken);

        // Umfassende Benachrichtigung mit allen Details
        Notification::make()
            ->title('🔑 Token erfolgreich erstellt')
            ->body("
                <div class='space-y-6 max-w-4xl'>
                    <!-- Token-Informationen -->
                    <div class='bg-blue-50 p-4 rounded-lg border border-blue-200'>
                        <h3 class='font-semibold text-blue-900 mb-3 flex items-center gap-2'>
                            <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                            </svg>
                            Token-Informationen
                        </h3>
                        <div class='grid grid-cols-2 gap-4 text-sm'>
                            <div><strong>Name:</strong> {$appToken->name}</div>
                            <div><strong>App-Typ:</strong> {$appToken->app_type_label}</div>
                            <div><strong>Berechtigungen:</strong> " . implode(', ', $appToken->abilities_labels ?? []) . "</div>
                            <div><strong>Gültig bis:</strong> {$appToken->expires_at->format('d.m.Y H:i')}</div>
                        </div>
                    </div>

                    <!-- API-Token mit Kopierschaltfläche -->
                    <div class='bg-gray-50 p-6 rounded-lg border'>
                        <div class='flex justify-between items-center mb-4'>
                            <h3 class='font-semibold text-gray-900 flex items-center gap-2'>
                                <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z'></path>
                                </svg>
                                API-Token
                            </h3>
                            <button onclick='copyTokenToClipboard(\"{$plainTextToken}\")' 
                                    id='copy-token-btn-" . uniqid() . "'
                                    class='bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 shadow-sm hover:shadow-md'
                                    title='Token in Zwischenablage kopieren'>
                                <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z'></path>
                                </svg>
                                Token kopieren
                            </button>
                        </div>
                        <div class='bg-white p-4 rounded border border-gray-200 font-mono text-sm break-all select-all'>
                            {$plainTextToken}
                        </div>
                    </div>

                    <!-- QR-Code -->
                    <div class='text-center bg-white p-6 rounded-lg border border-gray-200'>
                        <h3 class='font-semibold text-gray-900 mb-4 flex items-center justify-center gap-2'>
                            <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V4a1 1 0 00-1-1H5a1 1 0 00-1 1v3a1 1 0 001 1zm12 0h2a1 1 0 001-1V4a1 1 0 00-1-1h-2a1 1 0 00-1 1v3a1 1 0 001 1zM5 20h2a1 1 0 001-1v-3a1 1 0 00-1-1H5a1 1 0 00-1 1v3a1 1 0 001 1z'></path>
                            </svg>
                            QR-Code für Mobile App
                        </h3>
                        <div class='bg-white p-8 rounded-lg inline-block shadow-lg border-2 border-gray-100 mb-4'>
                            <img src='data:image/png;base64,{$qrCodeBase64}' alt='Token QR-Code' class='w-80 h-80 block mx-auto' />
                        </div>
                        <div class='text-sm text-gray-600 max-w-md mx-auto'>
                            <p class='mb-2'>📱 <strong>Scannen Sie den QR-Code</strong> mit Ihrer Mobile App</p>
                            <p class='text-xs text-gray-500'>Der QR-Code enthält nur den API-Token und ist für optimale Lesbarkeit konfiguriert</p>
                        </div>
                    </div>

                    <!-- Sicherheitshinweis -->
                    <div class='bg-red-50 p-4 rounded-lg border border-red-200'>
                        <div class='flex items-start gap-3'>
                            <svg class='w-5 h-5 text-red-500 mt-0.5 flex-shrink-0' fill='currentColor' viewBox='0 0 20 20'>
                                <path fill-rule='evenodd' d='M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z' clip-rule='evenodd'></path>
                            </svg>
                            <div>
                                <h4 class='font-semibold text-red-900'>⚠️ Wichtiger Sicherheitshinweis</h4>
                                <p class='text-red-800 mt-1'>
                                    Kopieren Sie diesen Token jetzt und speichern Sie ihn sicher. Er wird aus Sicherheitsgründen nicht mehr angezeigt werden. 
                                    Behandeln Sie den Token wie ein Passwort und teilen Sie ihn niemals mit Unbefugten.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <script>
                function copyTokenToClipboard(text) {
                    navigator.clipboard.writeText(text).then(function() {
                        // Finde den Button anhand des Texts, da IDs dynamisch sind
                        const buttons = document.querySelectorAll('button[title=\"Token in Zwischenablage kopieren\"]');
                        const button = Array.from(buttons).find(b => b.onclick && b.onclick.toString().includes(text));
                        
                        if (button) {
                            const originalText = button.innerHTML;
                            button.innerHTML = '<svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"></path></svg>Erfolgreich kopiert!';
                            button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                            button.classList.add('bg-green-600', 'hover:bg-green-700');
                            
                            setTimeout(function() {
                                button.innerHTML = originalText;
                                button.classList.remove('bg-green-600', 'hover:bg-green-700');
                                button.classList.add('bg-blue-600', 'hover:bg-blue-700');
                            }, 3000);
                        }
                    }).catch(function(err) {
                        console.error('Fehler beim Kopieren: ', err);
                        alert('Fehler beim Kopieren in die Zwischenablage. Bitte kopieren Sie den Token manuell.');
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
