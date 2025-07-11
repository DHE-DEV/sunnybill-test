<?php

namespace App\Filament\Resources\DummyFieldConfigResource\Pages;

use App\Filament\Resources\DummyFieldConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDummyFieldConfig extends EditRecord
{
    protected static string $resource = DummyFieldConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
