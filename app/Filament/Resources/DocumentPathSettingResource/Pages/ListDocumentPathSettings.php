<?php

namespace App\Filament\Resources\DocumentPathSettingResource\Pages;

use App\Filament\Resources\DocumentPathSettingResource;
use App\Models\DocumentPathSetting;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentPathSettings extends ListRecords
{
    protected static string $resource = DocumentPathSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('create_defaults')
                ->label('Standard-Konfigurationen erstellen')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('success')
                ->action(function () {
                    DocumentPathSetting::createDefaults();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Standard-Konfigurationen erstellt')
                        ->body('Die Standard-Pfadkonfigurationen für alle Dokumenttypen wurden erfolgreich erstellt.')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Standard-Konfigurationen erstellen')
                ->modalDescription('Möchten Sie die Standard-Pfadkonfigurationen für alle Dokumenttypen erstellen? Bestehende Konfigurationen werden aktualisiert.')
                ->modalSubmitActionLabel('Ja, erstellen'),
        ];
    }
}