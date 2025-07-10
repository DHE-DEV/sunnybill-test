<?php

namespace App\Filament\Resources\UploadedPdfResource\Pages;

use App\Filament\Resources\UploadedPdfResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUploadedPdf extends EditRecord
{
    protected static string $resource = UploadedPdfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\Action::make('analyze')
                ->label('PDF analysieren')
                ->icon('heroicon-o-magnifying-glass')
                ->color('info')
                ->url(fn (): string => route('uploaded-pdfs.analyze', $this->record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->fileExists()),
            Actions\Action::make('download')
                ->label('Herunterladen')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn (): string => route('uploaded-pdfs.download', $this->record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->fileExists()),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'PDF-Informationen aktualisiert';
    }
}