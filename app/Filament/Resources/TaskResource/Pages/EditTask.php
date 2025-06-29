<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\Action::make('complete')
                ->label('Abschließen')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    $this->record->markAsCompleted();
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn (): bool => $this->record->status !== 'completed'),
            Actions\Action::make('start')
                ->label('Starten')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action(function () {
                    $this->record->markAsInProgress();
                })
                ->visible(fn (): bool => $this->record->status === 'open'),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }
}
