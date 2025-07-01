<?php

namespace App\Filament\Resources\SupplierTypeResource\Pages;

use App\Filament\Resources\SupplierTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierType extends EditRecord
{
    protected static string $resource = SupplierTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}