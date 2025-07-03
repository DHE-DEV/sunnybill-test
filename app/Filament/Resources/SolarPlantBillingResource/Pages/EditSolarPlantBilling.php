<?php

namespace App\Filament\Resources\SolarPlantBillingResource\Pages;

use App\Filament\Resources\SolarPlantBillingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSolarPlantBilling extends EditRecord
{
    protected static string $resource = SolarPlantBillingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Berechne Nettobetrag
        $data['net_amount'] = ($data['total_costs'] ?? 0) - ($data['total_credits'] ?? 0);
        
        return $data;
    }
}