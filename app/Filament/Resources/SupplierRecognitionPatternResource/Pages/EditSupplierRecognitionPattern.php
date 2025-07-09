<?php

namespace App\Filament\Resources\SupplierRecognitionPatternResource\Pages;

use App\Filament\Resources\SupplierRecognitionPatternResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierRecognitionPattern extends EditRecord
{
    protected static string $resource = SupplierRecognitionPatternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}