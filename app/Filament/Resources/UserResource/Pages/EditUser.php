<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Benutzer löschen')
                ->modalDescription('Sind Sie sicher, dass Sie diesen Benutzer permanent löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Benutzer aktualisiert')
            ->body('Die Benutzerdaten wurden erfolgreich gespeichert.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Entferne leere Passwort-Felder
        if (empty($data['password'])) {
            unset($data['password']);
        }
        
        return $data;
    }
}