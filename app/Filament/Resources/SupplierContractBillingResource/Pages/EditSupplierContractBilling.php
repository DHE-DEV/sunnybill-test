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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Finde die verknüpfte Solaranlage über den Lieferantenvertrag
        $record = $this->getRecord();
        if ($record && $record->supplierContract) {
            $solarPlant = $record->supplierContract->solarPlants()->first();
            if ($solarPlant) {
                $data['temp_solar_plant_id'] = $solarPlant->id;
            }
        }

        return $data;
    }
}
