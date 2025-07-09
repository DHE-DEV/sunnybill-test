<?php

namespace App\Filament\Resources\PdfExtractionRuleResource\Pages;

use App\Filament\Resources\PdfExtractionRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPdfExtractionRule extends EditRecord
{
    protected static string $resource = PdfExtractionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Anzeigen'),
            Actions\DeleteAction::make()
                ->label('Löschen'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}