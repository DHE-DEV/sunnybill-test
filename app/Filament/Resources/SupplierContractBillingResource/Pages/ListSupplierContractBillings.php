<?php

namespace App\Filament\Resources\SupplierContractBillingResource\Pages;

use App\Filament\Resources\SupplierContractBillingResource;
use App\Traits\HasPersistentTableState;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierContractBillings extends ListRecords
{
    use HasPersistentTableState;

    protected static string $resource = SupplierContractBillingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neuen Beleg erfassen'),
        ];
    }
}
