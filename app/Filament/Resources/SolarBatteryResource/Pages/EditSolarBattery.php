<?php

namespace App\Filament\Resources\SolarBatteryResource\Pages;

use App\Filament\Resources\SolarBatteryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSolarBattery extends EditRecord
{
    protected static string $resource = SolarBatteryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
