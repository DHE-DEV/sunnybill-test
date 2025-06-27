<?php

namespace App\Filament\Resources\SolarInverterResource\Pages;

use App\Filament\Resources\SolarInverterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolarInverters extends ListRecords
{
    protected static string $resource = SolarInverterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
