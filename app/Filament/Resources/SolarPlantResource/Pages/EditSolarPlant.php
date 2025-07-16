<?php

namespace App\Filament\Resources\SolarPlantResource\Pages;

use App\Filament\Resources\SolarPlantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSolarPlant extends EditRecord
{
    protected static string $resource = SolarPlantResource::class;

    public function getTitle(): string
    {
        $plant = $this->record;
        return "Solaranlage Bearbeiten - {$plant->plant_number} - {$plant->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
