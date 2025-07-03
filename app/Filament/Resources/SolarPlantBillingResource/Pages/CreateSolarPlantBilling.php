<?php

namespace App\Filament\Resources\SolarPlantBillingResource\Pages;

use App\Filament\Resources\SolarPlantBillingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSolarPlantBilling extends CreateRecord
{
    protected static string $resource = SolarPlantBillingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        // Berechne Nettobetrag
        $data['net_amount'] = ($data['total_costs'] ?? 0) - ($data['total_credits'] ?? 0);
        
        return $data;
    }
}