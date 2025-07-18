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
        // Nur nicht-gelöschte Anlagen berücksichtigen (SoftDeletes automatisch berücksichtigt)
        $totalPlants = SolarPlant::count();
        
        // Konsistente Logik für Status-Zählung
        $activePlants = SolarPlant::where('status', 'active')->count();
        $inactivePlants = SolarPlant::where('status', 'inactive')->count();
        $planningPlants = SolarPlant::where('status', 'in_planning')->count();
        $maintenancePlants = SolarPlant::where('status', 'maintenance')->count();
        $constructionPlants = SolarPlant::where('status', 'under_construction')->count();
        $awaitingPlants = SolarPlant::where('status', 'awaiting_commissioning')->count();
        $plannedPlants = SolarPlant::where('status', 'planned')->count();
        
        // Zusätzliche Statistiken
        $plantsWithParticipations = SolarPlant::whereHas('participations')->count();
        $totalCapacity = SolarPlant::sum('total_capacity_kw') ?? 0;
        $totalInvestment = SolarPlant::sum('total_investment') ?? 0;
        
        // Anlagen mit is_active = true (unabhängig vom Status)
        $operationalPlants = SolarPlant::where('is_active', true)->count();
        
        return [
            Stat::make('Gesamt Solaranlagen', $totalPlants)
                ->description('Alle registrierten Anlagen')
                ->descriptionIcon('heroicon-m-sun')
                ->color('primary'),
                
            Stat::make('Aktive Anlagen', $activePlants)
                ->description(($totalPlants > 0 ? round(($activePlants / $totalPlants) * 100, 1) : 0) . '% sind aktiv')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('In Planung', $planningPlants)
                ->description(($totalPlants > 0 ? round(($planningPlants / $totalPlants) * 100, 1) : 0) . '% sind in Planung')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
                
            Stat::make('Inaktive Anlagen', $inactivePlants)
                ->description(($totalPlants > 0 ? round(($inactivePlants / $totalPlants) * 100, 1) : 0) . '% sind inaktiv')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
                
            Stat::make('Wartung', $maintenancePlants)
                ->description(($totalPlants > 0 ? round(($maintenancePlants / $totalPlants) * 100, 1) : 0) . '% in Wartung')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('info'),
                
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
