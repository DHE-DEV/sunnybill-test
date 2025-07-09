<?php

namespace App\Filament\Resources\PdfExtractionRuleResource\Pages;

use App\Filament\Resources\PdfExtractionRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPdfExtractionRule extends ViewRecord
{
    protected static string $resource = PdfExtractionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Bearbeiten'),
        ];
    }
}