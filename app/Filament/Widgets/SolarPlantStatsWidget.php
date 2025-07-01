<?php

namespace App\Filament\Widgets;

use App\Models\SolarPlant;
use App\Models\PlantParticipation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SolarPlantStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalPlants = SolarPlant::count();
        $activePlants = SolarPlant::where('is_active', true)->count();
        $inactivePlants = SolarPlant::where('is_active', false)->count();
        $plantsWithParticipations = SolarPlant::whereHas('participations')->count();
        $totalCapacity = SolarPlant::sum('total_capacity_kw');
        $totalInvestment = SolarPlant::sum('total_investment');
        
        return [
            Stat::make('Gesamt Solaranlagen', $totalPlants)
                ->description('Alle registrierten Anlagen')
                ->descriptionIcon('heroicon-m-sun')
                ->color('primary'),
                
            Stat::make('Aktive Anlagen', $activePlants)
                ->description(($totalPlants > 0 ? round(($activePlants / $totalPlants) * 100, 1) : 0) . '% sind aktiv')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Inaktive Anlagen', $inactivePlants)
                ->description(($totalPlants > 0 ? round(($inactivePlants / $totalPlants) * 100, 1) : 0) . '% sind inaktiv')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
                
            Stat::make('Anlagen mit Beteiligungen', $plantsWithParticipations)
                ->description(($totalPlants > 0 ? round(($plantsWithParticipations / $totalPlants) * 100, 1) : 0) . '% haben Kunden-Beteiligungen')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
                
            Stat::make('Gesamtkapazität', number_format($totalCapacity, 2, ',', '.') . ' kWp')
                ->description('Installierte Leistung aller Anlagen')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('warning'),
                
            Stat::make('Gesamtinvestition', number_format($totalInvestment, 2, ',', '.') . ' €')
                ->description('Summe aller Investitionen')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('gray'),
        ];
    }
}