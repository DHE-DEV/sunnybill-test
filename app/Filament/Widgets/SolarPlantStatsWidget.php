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
        
        // Hauptlogik: Verwende primär das 'status' Feld
        $activePlants = SolarPlant::where('status', 'active')->count();
        $inactivePlants = SolarPlant::where('status', 'inactive')->count();
        $planningPlants = SolarPlant::where('status', 'in_planning')->count();
        $maintenancePlants = SolarPlant::where('status', 'maintenance')->count();
        
        // Prüfe auch auf alternative deutsche Status-Werte
        $activeAlternatives = SolarPlant::where('status', 'aktiv')->count();
        $inactiveAlternatives = SolarPlant::where('status', 'inaktiv')->count();
        
        // Kombiniere mögliche Status-Werte
        $activePlants += $activeAlternatives;
        $inactivePlants += $inactiveAlternatives;
        
        // Für bessere Konsistenz: Anlagen mit 'inactive' Status sollten auch als inaktiv gelten,
        // unabhängig vom is_active Boolean-Feld
        $actuallyInactive = SolarPlant::where(function($query) {
            $query->where('status', 'inactive')
                  ->orWhere('status', 'inaktiv')
                  ->orWhere('is_active', false);
        })->count();
        
        // Korrekte Berechnung: Inaktive = alle als inaktiv markierten
        $inactivePlants = $actuallyInactive;
        
        // Aktive = alle mit status 'active' UND is_active = true (für Konsistenz)
        $reallyActive = SolarPlant::where(function($query) {
            $query->where('status', 'active')
                  ->orWhere('status', 'aktiv');
        })->where('is_active', true)->count();
        
        $activePlants = $reallyActive;
        
        $plantsWithParticipations = SolarPlant::whereHas('participations')->count();
        $totalCapacity = SolarPlant::sum('total_capacity_kw') ?? 0;
        $totalInvestment = SolarPlant::sum('total_investment') ?? 0;
        
        // Debug-Informationen für Entwicklung
        $statusDistribution = SolarPlant::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
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
