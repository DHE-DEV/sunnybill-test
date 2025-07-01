<?php

namespace App\Filament\Resources\DocumentPathSettingResource\Pages;

use App\Filament\Resources\DocumentPathSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentPathSetting extends ViewRecord
{
    protected static string $resource = DocumentPathSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}