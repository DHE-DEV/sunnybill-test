<?php

namespace App\Filament\Widgets;

use App\Models\SolarPlant;
use App\Models\Customer;
use App\Models\PlantParticipation;
use App\Models\CustomerMonthlyCredit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SolarStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalPlants = SolarPlant::count();
        $activePlants = SolarPlant::where('is_active', true)->count();
        $totalCapacity = SolarPlant::sum('total_capacity_kw');
        $totalCustomers = Customer::whereHas('solarParticipations')->count();
        $totalParticipations = PlantParticipation::count();
        $thisMonthCredits = CustomerMonthlyCredit::whereMonth('month', now()->month)
            ->whereYear('month', now()->year)
            ->sum('total_credit');

        return [
            Stat::make('Solaranlagen Gesamt', $totalPlants)
                ->description('Anzahl aller Solaranlagen im System')
                ->descriptionIcon('heroicon-m-sun')
                ->color('success'),
                
            Stat::make('Aktive Anlagen', $activePlants)
                ->description('Derzeit in Betrieb')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('primary'),
                
            Stat::make('Gesamtleistung', number_format($totalCapacity, 2, ',', '.') . ' kWp')
                ->description('Installierte Kapazität')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('warning'),
                
            Stat::make('Beteiligte Kunden', $totalCustomers)
                ->description('Kunden mit Solar-Beteiligungen')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
                
            Stat::make('Beteiligungen', $totalParticipations)
                ->description('Gesamtanzahl Beteiligungen')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('gray'),
                
            Stat::make('Gutschriften diesen Monat', '€ ' . number_format($thisMonthCredits, 2, ',', '.'))
                ->description('Berechnete Kundengutschriften')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success'),
        ];
    }
}