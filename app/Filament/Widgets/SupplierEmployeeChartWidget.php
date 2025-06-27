<?php

namespace App\Filament\Widgets;

use App\Models\Supplier;
use App\Models\SupplierEmployee;
use Filament\Widgets\ChartWidget;

class SupplierEmployeeChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Mitarbeiterverteilung nach Lieferanten';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $suppliersWithEmployees = [];
        $employeeCounts = [];
        $labels = [];
        
        // Hole die Top 10 Lieferanten mit den meisten Mitarbeitern
        $suppliers = Supplier::withCount('employees')
            ->having('employees_count', '>', 0)
            ->orderBy('employees_count', 'desc')
            ->limit(10)
            ->get();
        
        foreach ($suppliers as $supplier) {
            $labels[] = $supplier->display_name;
            $employeeCounts[] = $supplier->employees_count;
        }
        
        // Falls weniger als 10 Lieferanten mit Mitarbeitern vorhanden sind, fülle mit Nullen auf
        while (count($labels) < 10) {
            $labels[] = 'Kein Lieferant';
            $employeeCounts[] = 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Anzahl Mitarbeiter',
                    'data' => $employeeCounts,
                    'borderColor' => 'rgb(168, 85, 247)',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.8)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}