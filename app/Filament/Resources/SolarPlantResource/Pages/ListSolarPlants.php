<?php

namespace App\Filament\Resources\SolarPlantResource\Pages;

use App\Filament\Resources\SolarPlantResource;
use App\Filament\Widgets\SolarPlantStatsWidget;
use App\Filament\Widgets\SolarPlantCapacityChartWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListSolarPlants extends ListRecords
{
    protected static string $resource = SolarPlantResource::class;

    public bool $showStats = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleStats')
                ->label(fn (): string => $this->showStats ? 'Statistik ausblenden' : 'Statistik anzeigen')
                ->icon(fn (): string => $this->showStats ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->action('toggleStats'),
            Actions\CreateAction::make(),
        ];
    }

    public function toggleStats(): void
    {
        $this->showStats = !$this->showStats;
    }

    protected function getHeaderWidgets(): array
    {
        if (!$this->showStats) {
            return [];
        }

        return [
            SolarPlantStatsWidget::class,
            SolarPlantCapacityChartWidget::class,
        ];
    }
}
