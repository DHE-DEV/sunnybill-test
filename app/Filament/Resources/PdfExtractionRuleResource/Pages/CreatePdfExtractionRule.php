<?php

namespace App\Filament\Resources\PdfExtractionRuleResource\Pages;

use App\Filament\Resources\PdfExtractionRuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePdfExtractionRule extends CreateRecord
{
    protected static string $resource = PdfExtractionRuleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}