<?php

namespace App\Filament\Resources\SolarPlantResource\Pages;

use App\Filament\Resources\SolarPlantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSolarPlant extends EditRecord
{
    protected static string $resource = SolarPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
