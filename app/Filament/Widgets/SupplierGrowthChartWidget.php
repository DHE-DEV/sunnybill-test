<?php

namespace App\Filament\Widgets;

use App\Models\Supplier;
use Filament\Widgets\ChartWidget;

class SupplierGrowthChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Lieferantenwachstum - Aktiv vs. Inaktiv (letzte 12 Monate)';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $activeData = [];
        $inactiveData = [];
        $totalData = [];
        $labels = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');
            
            // Aktive Lieferanten bis zu diesem Datum
            $activeCount = Supplier::whereDate('created_at', '<=', $date->endOfMonth())
                ->where('is_active', true)
                ->count();
            $activeData[] = $activeCount;
            
            // Inaktive Lieferanten bis zu diesem Datum
            $inactiveCount = Supplier::whereDate('created_at', '<=', $date->endOfMonth())
                ->where('is_active', false)
                ->count();
            $inactiveData[] = $inactiveCount;
            
            // Gesamtanzahl
            $totalCount = $activeCount + $inactiveCount;
            $totalData[] = $totalCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Aktive Lieferanten',
                    'data' => $activeData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => false,
                ],
                [
                    'label' => 'Inaktive Lieferanten',
                    'data' => $inactiveData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => false,
                ],
                [
                    'label' => 'Lieferanten gesamt',
                    'data' => $totalData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}