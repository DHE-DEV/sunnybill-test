<?php

namespace App\Filament\Resources\SupplierTypeResource\Pages;

use App\Filament\Resources\SupplierTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplierType extends ViewRecord
{
    protected static string $resource = SupplierTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}