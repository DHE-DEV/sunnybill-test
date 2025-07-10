<?php

namespace App\Filament\Resources\UploadedPdfResource\Pages;

use App\Filament\Resources\UploadedPdfResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateUploadedPdf extends CreateRecord
{
    protected static string $resource = UploadedPdfResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = Auth::id();
        $data['analysis_status'] = 'pending';
        
        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'PDF erfolgreich hochgeladen';
    }
}