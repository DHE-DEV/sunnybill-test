<?php

namespace App\Filament\Resources\AppTokenResource\Pages;

use App\Filament\Resources\AppTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppTokens extends ListRecords
{
    protected static string $resource = AppTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
