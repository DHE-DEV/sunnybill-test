<?php

namespace App\Filament\Resources\MermaidChartResource\Pages;

use App\Filament\Resources\MermaidChartResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMermaidChart extends CreateRecord
{
    protected static string $resource = MermaidChartResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Mermaid-Chart wurde erfolgreich erstellt';
    }
}