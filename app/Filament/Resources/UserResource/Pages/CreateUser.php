<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Benutzer erstellt')
            ->body('Der neue Benutzer wurde erfolgreich erstellt.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Setze Standard-Werte falls nicht angegeben
        $data['is_active'] = $data['is_active'] ?? true;
        $data['role'] = $data['role'] ?? 'user';
        
        return $data;
    }
}