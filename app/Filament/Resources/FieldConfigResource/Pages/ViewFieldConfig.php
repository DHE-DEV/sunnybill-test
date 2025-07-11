<?php

namespace App\Filament\Resources\FieldConfigResource\Pages;

use App\Filament\Resources\FieldConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFieldConfig extends ViewRecord
{
    protected static string $resource = FieldConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}