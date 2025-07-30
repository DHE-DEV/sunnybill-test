<?php

namespace App\Filament\Resources\SolarPlantBillingResource\Pages;

use App\Filament\Resources\SolarPlantBillingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolarPlantBillings extends ListRecords
{
    protected static string $resource = SolarPlantBillingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction entfernt - Abrechnungen werden über "Monatliche Abrechnungen erstellen" erstellt
        ];
    }
}
