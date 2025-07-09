<?php

namespace App\Filament\Resources\PdfExtractionRuleResource\Pages;

use App\Filament\Resources\PdfExtractionRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPdfExtractionRules extends ListRecords
{
    protected static string $resource = PdfExtractionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neue Extraktionsregel'),
        ];
    }
}