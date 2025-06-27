<?php

namespace App\Filament\Resources\InvoiceVersionResource\Pages;

use App\Filament\Resources\InvoiceVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoiceVersions extends ListRecords
{
    protected static string $resource = InvoiceVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Keine Create-Action, da Versionen automatisch erstellt werden
        ];
    }
}