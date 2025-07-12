<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use App\Filament\Widgets\SupplierStatsWidget;
use App\Filament\Widgets\SupplierGrowthChartWidget;
use App\Filament\Widgets\SupplierEmployeeChartWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    public bool $showStats = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleStats')
                ->label(fn (): string => $this->showStats ? 'Statistik ausblenden' : 'Statistik anzeigen')
                ->icon(fn (): string => $this->showStats ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->action('toggleStats'),
            Actions\CreateAction::make(),
        ];
    }

    public function toggleStats(): void
    {
        $this->showStats = !$this->showStats;
    }

    protected function getHeaderWidgets(): array
    {
        if (!$this->showStats) {
            return [];
        }

        return [
            SupplierStatsWidget::class,
            SupplierGrowthChartWidget::class,
            SupplierEmployeeChartWidget::class,
        ];
    }
}
