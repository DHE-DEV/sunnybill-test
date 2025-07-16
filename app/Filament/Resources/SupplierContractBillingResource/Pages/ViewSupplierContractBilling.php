<?php

namespace App\Filament\Resources\SupplierContractBillingResource\Pages;

use App\Filament\Resources\SupplierContractBillingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplierContractBilling extends ViewRecord
{
    protected static string $resource = SupplierContractBillingResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $contract = $record->supplierContract;
        
        if ($contract) {
            return "Abrechnung ansehen - {$contract->contract_number} - {$contract->title}";
        }
        
        return 'Abrechnung ansehen';
    }

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
