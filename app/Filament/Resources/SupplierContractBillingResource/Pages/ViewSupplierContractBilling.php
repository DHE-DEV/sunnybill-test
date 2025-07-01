<?php

namespace App\Filament\Resources\SupplierContractBillingResource\Pages;

use App\Filament\Resources\SupplierContractBillingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplierContractBilling extends ViewRecord
{
    protected static string $resource = SupplierContractBillingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Bearbeiten'),
            Actions\DeleteAction::make()
                ->label('Löschen'),
        ];
    }
}