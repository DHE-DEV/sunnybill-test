<?php

namespace App\Filament\Resources\UploadedPdfResource\Pages;

use App\Filament\Resources\UploadedPdfResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUploadedPdfs extends ListRecords
{
    protected static string $resource = UploadedPdfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('PDF hochladen')
                ->icon('heroicon-o-plus'),
        ];
    }
}