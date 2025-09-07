<?php

namespace App\Filament\Resources\SimCardResource\Pages;

use App\Filament\Resources\SimCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSimCards extends ListRecords
{
    protected static string $resource = SimCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...parent::getResource()::getHeaderActions(),
        ];
    }
}
