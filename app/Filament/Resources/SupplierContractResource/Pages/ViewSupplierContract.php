<?php

namespace App\Filament\Resources\SupplierContractResource\Pages;

use App\Filament\Resources\SupplierContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplierContract extends ViewRecord
{
    protected static string $resource = SupplierContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}