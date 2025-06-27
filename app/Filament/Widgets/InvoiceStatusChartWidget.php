<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;

class InvoiceStatusChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Rechnungsstatus-Verteilung';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $draftCount = Invoice::where('status', 'draft')->count();
        $sentCount = Invoice::where('status', 'sent')->count();
        $paidCount = Invoice::where('status', 'paid')->count();
        $canceledCount = Invoice::where('status', 'canceled')->count();

        return [
            'datasets' => [
                [
                    'data' => [$draftCount, $sentCount, $paidCount, $canceledCount],
                    'backgroundColor' => [
                        'rgba(107, 114, 128, 0.8)', // Grau für Entwürfe
                        'rgba(245, 158, 11, 0.8)',  // Gelb für Versendet
                        'rgba(34, 197, 94, 0.8)',   // Grün für Bezahlt
                        'rgba(239, 68, 68, 0.8)',   // Rot für Storniert
                    ],
                    'borderColor' => [
                        'rgb(107, 114, 128)',
                        'rgb(245, 158, 11)',
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Entwürfe', 'Versendet', 'Bezahlt', 'Storniert'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}