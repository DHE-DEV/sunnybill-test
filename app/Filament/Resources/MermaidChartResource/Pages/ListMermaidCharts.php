<?php

namespace App\Filament\Resources\MermaidChartResource\Pages;

use App\Filament\Resources\MermaidChartResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMermaidCharts extends ListRecords
{
    protected static string $resource = MermaidChartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neuer Chart')
                ->icon('heroicon-o-plus'),
        ];
    }
}