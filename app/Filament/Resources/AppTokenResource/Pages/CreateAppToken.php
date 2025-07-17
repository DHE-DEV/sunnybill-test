<?php

namespace App\Filament\Resources\AppTokenResource\Pages;

use App\Filament\Resources\AppTokenResource;
use App\Models\AppToken;
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

        // Zeige den Token in einer Benachrichtigung
        Notification::make()
            ->title('Token erfolgreich erstellt')
            ->body("
                <div class='space-y-4'>
                    <div>
                        <strong>Token-Name:</strong> {$appToken->name}
                    </div>
                    <div>
                        <strong>API-Token:</strong><br>
                        <code class='bg-gray-100 p-2 rounded text-sm font-mono break-all'>{$plainTextToken}</code>
                    </div>
                    <div class='text-sm text-gray-600'>
                        <strong>Wichtig:</strong> Kopieren Sie diesen Token jetzt. Er wird aus Sicherheitsgründen nicht mehr angezeigt.
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
