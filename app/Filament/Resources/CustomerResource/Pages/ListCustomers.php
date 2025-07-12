<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Widgets\CustomerStatsWidget;
use App\Filament\Widgets\CustomerGrowthChartWidget;
use App\Filament\Widgets\CustomerTypeDistributionChartWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

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
            CustomerStatsWidget::class,
            CustomerGrowthChartWidget::class,
            CustomerTypeDistributionChartWidget::class,
        ];
    }

}
