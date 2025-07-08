<?php

namespace App\Filament\Resources\SolarPlantStatusResource\Pages;

use App\Filament\Resources\SolarPlantStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSolarPlantStatus extends EditRecord
{
    protected static string $resource = SolarPlantStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
