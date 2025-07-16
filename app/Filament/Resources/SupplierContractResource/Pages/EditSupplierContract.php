<?php

namespace App\Filament\Resources\SupplierContractResource\Pages;

use App\Filament\Resources\SupplierContractResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierContract extends EditRecord
{
    protected static string $resource = SupplierContractResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();
        return "Lieferant Vertrag bearbeiten - {$record->contract_number} - {$record->title}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
