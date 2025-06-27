<?php

namespace App\Filament\Resources\SolarPlantResource\Pages;

use App\Filament\Resources\SolarPlantResource;
use App\Filament\Widgets\SolarPlantStatsWidget;
use App\Filament\Widgets\SolarPlantCapacityChartWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolarPlants extends ListRecords
{
    protected static string $resource = SolarPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SolarPlantStatsWidget::class,
            SolarPlantCapacityChartWidget::class,
        ];
    }
}
