<?php

namespace App\Filament\Resources\RouterResource\Pages;

use App\Filament\Resources\RouterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditRouter extends EditRecord
{
    protected static string $resource = RouterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('restart')
                ->label('Router Neustart')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Router neu starten')
                ->modalDescription('Möchten Sie den Router wirklich neu starten? Dies kann einige Minuten dauern.')
                ->modalSubmitActionLabel('Ja, neu starten')
                ->modalCancelActionLabel('Abbrechen')
                ->visible(fn () => $this->record->ip_address && !$this->record->hasRecentRestart())
                ->disabled(fn () => !$this->record->ip_address || $this->record->hasRecentRestart())
                ->action(function () {
                    $success = $this->record->restart();
                    
                    if ($success) {
                        Notification::make()
                            ->title('Router wird neu gestartet')
                            ->body('Der Router wird neu gestartet. Dies kann einige Minuten dauern.')
                            ->success()
                            ->duration(5000)
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Neustart fehlgeschlagen')
                            ->body('Der Router konnte nicht neu gestartet werden. Möglicherweise ist er nicht erreichbar.')
                            ->danger()
                            ->duration(8000)
                            ->send();
                    }
                })
                ->after(function () {
                    // Seite nach 2 Sekunden aktualisieren
                    $this->js('setTimeout(() => window.location.reload(), 2000)');
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
