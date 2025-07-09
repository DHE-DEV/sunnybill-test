<?php

namespace App\Filament\Resources\SupplierRecognitionPatternResource\Pages;

use App\Filament\Resources\SupplierRecognitionPatternResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierRecognitionPatterns extends ListRecords
{
    protected static string $resource = SupplierRecognitionPatternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}