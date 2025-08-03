<?php

namespace App\Filament\Resources\SupplierContractResource\Pages;

use App\Filament\Resources\SupplierContractResource;
use App\Traits\HasPersistentTableState;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierContracts extends ListRecords
{
    use HasPersistentTableState;

    protected static string $resource = SupplierContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
