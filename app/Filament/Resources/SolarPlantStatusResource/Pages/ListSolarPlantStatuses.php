<?php

namespace App\Filament\Resources\SolarPlantStatusResource\Pages;

use App\Filament\Resources\SolarPlantStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolarPlantStatuses extends ListRecords
{
    protected static string $resource = SolarPlantStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
