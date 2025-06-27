<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;

class InvoiceRevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Umsatzentwicklung (letzte 12 Monate)';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $revenueData = [];
        $invoiceCountData = [];
        $labels = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');
            
            // Umsatz aus bezahlten Rechnungen des Monats
            $monthlyRevenue = Invoice::where('status', 'paid')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('total');
            $revenueData[] = round($monthlyRevenue, 2);
            
            // Anzahl Rechnungen des Monats
            $monthlyInvoiceCount = Invoice::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $invoiceCountData[] = $monthlyInvoiceCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Umsatz (€)',
                    'data' => $revenueData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Anzahl Rechnungen',
                    'data' => $invoiceCountData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => false,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Umsatz (€)',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Anzahl Rechnungen',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}