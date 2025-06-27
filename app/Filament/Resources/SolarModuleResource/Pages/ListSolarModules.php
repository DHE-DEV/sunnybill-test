<?php

namespace App\Filament\Resources\SolarModuleResource\Pages;

use App\Filament\Resources\SolarModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolarModules extends ListRecords
{
    protected static string $resource = SolarModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
