<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\InvoiceItem;
use Filament\Widgets\ChartWidget;

class ArticleUsageChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Top 10 meistverwendete Artikel';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Hole die Top 10 Artikel nach Verwendung in Rechnungen
        $topArticles = Article::withCount('invoiceItems')
            ->having('invoice_items_count', '>', 0)
            ->orderBy('invoice_items_count', 'desc')
            ->limit(10)
            ->get();

        $labels = [];
        $usageData = [];
        
        foreach ($topArticles as $article) {
            $labels[] = $article->name;
            $usageData[] = $article->invoice_items_count;
        }
        
        // Falls weniger als 10 Artikel vorhanden sind, fülle mit Nullen auf
        while (count($labels) < 10) {
            $labels[] = 'Kein Artikel';
            $usageData[] = 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Anzahl Verwendungen',
                    'data' => $usageData,
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
            'indexAxis' => 'y', // Horizontale Balken
        ];
    }
}