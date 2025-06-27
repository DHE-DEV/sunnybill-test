<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Versuche die gefilterte Query von der Parent-Page zu erhalten
        $query = $this->getFilteredQuery();
        
        $totalCustomers = $query->count();
        $privateCustomers = (clone $query)->where('customer_type', 'private')->count();
        $businessCustomers = (clone $query)->where('customer_type', 'business')->count();
        $activeCustomers = (clone $query)->where('is_active', true)->count();
        $customersWithInvoices = (clone $query)->whereHas('invoices')->count();
        $customersWithSolarParticipations = (clone $query)->whereHas('solarParticipations')->count();
        
        // Durchschnittliche Anzahl Rechnungen pro Kunde (nur für gefilterte Kunden)
        $customerIds = (clone $query)->pluck('id');
        $invoiceCount = $customerIds->isNotEmpty() ? Invoice::whereIn('customer_id', $customerIds)->count() : 0;
        $avgInvoicesPerCustomer = $totalCustomers > 0 ? round($invoiceCount / $totalCustomers, 1) : 0;
        
        return [
            Stat::make('Gesamt Kunden', $totalCustomers)
                ->description('Alle registrierten Kunden')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('Privatkunden', $privateCustomers)
                ->description(($totalCustomers > 0 ? round(($privateCustomers / $totalCustomers) * 100, 1) : 0) . '% aller Kunden')
                ->descriptionIcon('heroicon-m-user')
                ->color('info'),
                
            Stat::make('Firmenkunden', $businessCustomers)
                ->description(($totalCustomers > 0 ? round(($businessCustomers / $totalCustomers) * 100, 1) : 0) . '% aller Kunden')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),
                
            Stat::make('Aktive Kunden', $activeCustomers)
                ->description(($totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 1) : 0) . '% sind aktiv')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('warning'),
                
            Stat::make('Kunden mit Rechnungen', $customersWithInvoices)
                ->description('Ø ' . $avgInvoicesPerCustomer . ' Rechnungen pro Kunde')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('danger'),
                
            Stat::make('Solar-Beteiligungen', $customersWithSolarParticipations)
                ->description(($totalCustomers > 0 ? round(($customersWithSolarParticipations / $totalCustomers) * 100, 1) : 0) . '% haben Solar-Anteile')
                ->descriptionIcon('heroicon-m-sun')
                ->color('yellow'),
        ];
    }

    protected function getFilteredQuery()
    {
        // Fallback: alle Kunden (Filter-Funktionalität wird später implementiert)
        return Customer::query();
    }
}