<?php

namespace App\Filament\Resources\DocumentPathSettingResource\Pages;

use App\Filament\Resources\DocumentPathSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentPathSetting extends EditRecord
{
    protected static string $resource = DocumentPathSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}