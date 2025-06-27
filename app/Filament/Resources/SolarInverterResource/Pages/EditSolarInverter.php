<?php

namespace App\Filament\Resources\SolarInverterResource\Pages;

use App\Filament\Resources\SolarInverterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSolarInverter extends EditRecord
{
    protected static string $resource = SolarInverterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
