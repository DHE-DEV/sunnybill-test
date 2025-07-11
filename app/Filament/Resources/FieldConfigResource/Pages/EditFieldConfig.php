<?php

namespace App\Filament\Resources\FieldConfigResource\Pages;

use App\Filament\Resources\FieldConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFieldConfig extends EditRecord
{
    protected static string $resource = FieldConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}