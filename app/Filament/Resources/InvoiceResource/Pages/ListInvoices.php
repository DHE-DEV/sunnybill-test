<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Widgets\InvoiceStatsWidget;
use App\Filament\Widgets\InvoiceRevenueChartWidget;
use App\Filament\Widgets\InvoiceStatusChartWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

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
            InvoiceStatsWidget::class,
            InvoiceRevenueChartWidget::class,
            InvoiceStatusChartWidget::class,
        ];
    }
}
