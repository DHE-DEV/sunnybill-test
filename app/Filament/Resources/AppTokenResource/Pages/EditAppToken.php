<?php

namespace App\Filament\Resources\AppTokenResource\Pages;

use App\Filament\Resources\AppTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAppToken extends EditRecord
{
    protected static string $resource = AppTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
