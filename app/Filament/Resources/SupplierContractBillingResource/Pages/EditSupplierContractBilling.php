<?php

namespace App\Filament\Resources\SupplierContractBillingResource\Pages;

use App\Filament\Resources\SupplierContractBillingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierContractBilling extends EditRecord
{
    protected static string $resource = SupplierContractBillingResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $contract = $record->supplierContract;
        
        if ($contract) {
            return "Abrechnung bearbeiten - {$contract->contract_number} - {$contract->title}";
        }
        
        return 'Abrechnung bearbeiten';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Anzeigen'),
            Actions\DeleteAction::make()
                ->label('Löschen'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
