<?php

namespace App\Filament\Resources\SolarPlantBillingOverviewResource\Pages;

use App\Filament\Resources\SolarPlantBillingOverviewResource;
use App\Traits\HasPersistentTableState;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolarPlantBillingOverview extends ListRecords
{
    use HasPersistentTableState;

    protected static string $resource = SolarPlantBillingOverviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Aktualisieren')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->redirect(request()->url())),
        ];
    }

    public function getTitle(): string
    {
        return 'Abrechnungsübersicht Solaranlagen';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Hier könnten später Widgets für Statistiken hinzugefügt werden
        ];
    }
}
