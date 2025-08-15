<?php

namespace App\Filament\Resources\ExportResource\Pages;

use App\Filament\Resources\ExportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExports extends ListRecords
{
    protected static string $resource = ExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('info')
                ->label('Anleitung')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modalHeading('Wie verwende ich die Export-Funktion?')
                ->modalContent(view('filament.pages.export-info'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Schließen'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Optional: Widgets für Statistiken
        ];
    }

    public function getTitle(): string
    {
        return 'Meine Exports';
    }
}
