<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoiceStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalInvoices = Invoice::count();
        $draftInvoices = Invoice::where('status', 'draft')->count();
        $sentInvoices = Invoice::where('status', 'sent')->count();
        $paidInvoices = Invoice::where('status', 'paid')->count();
        $totalRevenue = Invoice::where('status', 'paid')->sum('total');
        $avgInvoiceValue = $totalInvoices > 0 ? Invoice::avg('total') : 0;
        
        return [
            Stat::make('Gesamt Rechnungen', $totalInvoices)
                ->description('Alle erstellten Rechnungen')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
                
            Stat::make('Entwürfe', $draftInvoices)
                ->description(($totalInvoices > 0 ? round(($draftInvoices / $totalInvoices) * 100, 1) : 0) . '% sind Entwürfe')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('gray'),
                
            Stat::make('Versendet', $sentInvoices)
                ->description(($totalInvoices > 0 ? round(($sentInvoices / $totalInvoices) * 100, 1) : 0) . '% sind versendet')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('warning'),
                
            Stat::make('Bezahlt', $paidInvoices)
                ->description(($totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 1) : 0) . '% sind bezahlt')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Gesamtumsatz', number_format($totalRevenue, 2, ',', '.') . ' €')
                ->description('Umsatz aus bezahlten Rechnungen')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('info'),
                
            Stat::make('Ø Rechnungswert', number_format($avgInvoiceValue, 2, ',', '.') . ' €')
                ->description('Durchschnittlicher Rechnungsbetrag')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('danger'),
        ];
    }
}