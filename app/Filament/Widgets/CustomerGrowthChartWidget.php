<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\ChartWidget;

class CustomerGrowthChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Kundenwachstum - Aktiv vs. Inaktiv (letzte 12 Monate)';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $activeData = [];
        $inactiveData = [];
        $totalData = [];
        $labels = [];
        
        // Basis-Query: gefilterte Kunden oder alle Kunden
        $baseQuery = $this->getFilteredQuery();
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $endOfMonth = $date->endOfMonth();
            $labels[] = $date->format('M Y');
            
            // Alle Kunden, die bis zu diesem Datum erstellt wurden
            $allCustomersQuery = (clone $baseQuery)
                ->whereDate('created_at', '<=', $endOfMonth);
            
            // Aktive Kunden: Erstellt bis zu diesem Datum UND
            // (noch nie deaktiviert ODER nach diesem Datum deaktiviert)
            $activeCount = (clone $allCustomersQuery)
                ->where(function ($query) use ($endOfMonth) {
                    $query->where('is_active', true)
                          ->orWhere(function ($subQuery) use ($endOfMonth) {
                              $subQuery->where('is_active', false)
                                       ->where(function ($dateQuery) use ($endOfMonth) {
                                           $dateQuery->whereNull('deactivated_at')
                                                    ->orWhereDate('deactivated_at', '>', $endOfMonth);
                                       });
                          });
                })
                ->count();
            
            // Inaktive Kunden: Erstellt bis zu diesem Datum UND
            // deaktiviert bis zu diesem Datum (deactivated_at <= endOfMonth)
            $inactiveCount = (clone $allCustomersQuery)
                ->where('is_active', false)
                ->whereNotNull('deactivated_at')
                ->whereDate('deactivated_at', '<=', $endOfMonth)
                ->count();
            
            $activeData[] = $activeCount;
            $inactiveData[] = $inactiveCount;
            
            // Gesamtanzahl
            $totalCount = $activeCount + $inactiveCount;
            $totalData[] = $totalCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Aktive Kunden',
                    'data' => $activeData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => false,
                ],
                [
                    'label' => 'Inaktive Kunden',
                    'data' => $inactiveData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => false,
                ],
                [
                    'label' => 'Kunden gesamt',
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

    protected function getFilteredQuery()
    {
        // Fallback: alle Kunden (Filter-Funktionalität wird später implementiert)
        return Customer::query();
    }
}