<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\ChartWidget;

class CustomerTypeDistributionChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Kundenverteilung nach Monaten';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $privateData = [];
        $businessData = [];
        $labels = [];
        
        // Basis-Query: gefilterte Kunden oder alle Kunden
        $baseQuery = $this->getFilteredQuery();
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');
            
            $privateCount = (clone $baseQuery)
                ->where('customer_type', 'private')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
                
            $businessCount = (clone $baseQuery)
                ->where('customer_type', 'business')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
                
            $privateData[] = $privateCount;
            $businessData[] = $businessCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Privatkunden',
                    'data' => $privateData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                ],
                [
                    'label' => 'Firmenkunden',
                    'data' => $businessData,
                    'borderColor' => 'rgb(249, 115, 22)',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.8)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilteredQuery()
    {
        // Fallback: alle Kunden (Filter-Funktionalität wird später implementiert)
        return Customer::query();
    }
}