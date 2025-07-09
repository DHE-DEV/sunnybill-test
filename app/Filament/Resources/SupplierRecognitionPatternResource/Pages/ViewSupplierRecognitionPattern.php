<?php

namespace App\Filament\Resources\SupplierRecognitionPatternResource\Pages;

use App\Filament\Resources\SupplierRecognitionPatternResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplierRecognitionPattern extends ViewRecord
{
    protected static string $resource = SupplierRecognitionPatternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}