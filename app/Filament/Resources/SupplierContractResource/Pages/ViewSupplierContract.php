<?php

namespace App\Filament\Resources\SupplierContractResource\Pages;

use App\Filament\Resources\SupplierContractResource;
use App\Models\Supplier;
use App\Models\SupplierContract;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplierContract extends ViewRecord
{
    protected static string $resource = SupplierContractResource::class;

    public function form(Form $form): Form
    {
        // We get the form schema from the resource and disable it.
        return static::getResource()::form($form->disabled());
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
