<?php

namespace App\Filament\Resources\SolarBatteryResource\Pages;

use App\Filament\Resources\SolarBatteryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolarBatteries extends ListRecords
{
    protected static string $resource = SolarBatteryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
