<?php

namespace App\Filament\Resources\LexofficeLogResource\Pages;

use App\Filament\Resources\LexofficeLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLexofficeLog extends EditRecord
{
    protected static string $resource = LexofficeLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
