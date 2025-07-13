<?php

namespace App\Filament\Resources\MermaidChartResource\Pages;

use App\Filament\Resources\MermaidChartResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMermaidChart extends EditRecord
{
    protected static string $resource = MermaidChartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Mermaid-Chart wurde erfolgreich aktualisiert';
    }
}