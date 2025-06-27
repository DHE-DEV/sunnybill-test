<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use App\Filament\Widgets\SupplierStatsWidget;
use App\Filament\Widgets\SupplierGrowthChartWidget;
use App\Filament\Widgets\SupplierEmployeeChartWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SupplierStatsWidget::class,
            SupplierGrowthChartWidget::class,
            SupplierEmployeeChartWidget::class,
        ];
    }
}