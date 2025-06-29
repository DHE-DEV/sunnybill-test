<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            // Aufgaben-Dashboards
            \App\Filament\Widgets\TasksTodayWidget::class,
            \App\Filament\Widgets\TasksThisWeekWidget::class,
            \App\Filament\Widgets\TasksThisMonthWidget::class,
            
            // Übersichts-Statistiken
            \App\Filament\Widgets\CustomerStatsWidget::class,
            \App\Filament\Widgets\SupplierStatsWidget::class,
            \App\Filament\Widgets\InvoiceStatsWidget::class,
            \App\Filament\Widgets\SolarPlantStatsWidget::class,
            \App\Filament\Widgets\ArticleStatsWidget::class,
            
            // Wichtige Charts
            \App\Filament\Widgets\InvoiceRevenueChartWidget::class,
            \App\Filament\Widgets\SolarPlantCapacityChartWidget::class,
            \App\Filament\Widgets\CustomerGrowthChartWidget::class,
            
            // Explizit NICHT enthalten:
            // \App\Filament\Widgets\FilteredProjectMilestonesTableWidget::class, // Projekttermine - entfernt
            // \App\Filament\Widgets\TasksOverviewTableWidget::class, // Wichtige Aufgaben - entfernt
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 3,
            'sm' => 2,
            'md' => 3,
            'lg' => 4,
            'xl' => 4,
            '2xl' => 4,
        ];
    }
}