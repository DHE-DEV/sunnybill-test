<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\InvoiceItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ArticleStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalArticles = Article::count();
        $productArticles = Article::where('type', 'PRODUCT')->count();
        $serviceArticles = Article::where('type', 'SERVICE')->count();
        $articlesInUse = Article::whereHas('invoiceItems')->count();
        $avgPrice = Article::avg('price');
        $totalInvoiceItems = InvoiceItem::count();
        
        return [
            Stat::make('Gesamt Artikel', $totalArticles)
                ->description('Alle verfügbaren Artikel')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
                
            Stat::make('Produkte', $productArticles)
                ->description(($totalArticles > 0 ? round(($productArticles / $totalArticles) * 100, 1) : 0) . '% sind Produkte')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('success'),
                
            Stat::make('Dienstleistungen', $serviceArticles)
                ->description(($totalArticles > 0 ? round(($serviceArticles / $totalArticles) * 100, 1) : 0) . '% sind Services')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('info'),
                
            Stat::make('Artikel in Verwendung', $articlesInUse)
                ->description(($totalArticles > 0 ? round(($articlesInUse / $totalArticles) * 100, 1) : 0) . '% werden verwendet')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('warning'),
                
            Stat::make('Ø Artikelpreis', number_format($avgPrice, 2, ',', '.') . ' €')
                ->description('Durchschnittlicher Preis aller Artikel')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('danger'),
                
            Stat::make('Rechnungspositionen', $totalInvoiceItems)
                ->description('Gesamt verwendete Artikel in Rechnungen')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),
        ];
    }
}