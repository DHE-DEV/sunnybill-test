<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Widgets\CustomerStatsWidget;
use App\Filament\Widgets\CustomerGrowthChartWidget;
use App\Filament\Widgets\CustomerTypeDistributionChartWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CustomerStatsWidget::class,
            CustomerGrowthChartWidget::class,
            CustomerTypeDistributionChartWidget::class,
        ];
    }

}
