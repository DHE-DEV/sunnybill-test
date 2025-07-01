<?php

namespace App\Filament\Resources\SupplierTypeResource\Pages;

use App\Filament\Resources\SupplierTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierTypes extends ListRecords
{
    protected static string $resource = SupplierTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}