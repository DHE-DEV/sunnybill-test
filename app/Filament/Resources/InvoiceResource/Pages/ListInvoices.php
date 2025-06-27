<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Widgets\InvoiceStatsWidget;
use App\Filament\Widgets\InvoiceRevenueChartWidget;
use App\Filament\Widgets\InvoiceStatusChartWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InvoiceStatsWidget::class,
            InvoiceRevenueChartWidget::class,
            InvoiceStatusChartWidget::class,
        ];
    }
}
