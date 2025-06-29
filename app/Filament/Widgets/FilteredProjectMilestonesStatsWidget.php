<?php

namespace App\Filament\Widgets;

use App\Models\SolarPlantMilestone;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class FilteredProjectMilestonesStatsWidget extends BaseWidget
{
    public ?string $timeFilter = 'today';
    
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $dateRange = $this->getDateRange();
        
        // Projekttermine im gewählten Zeitraum
        $milestonesInPeriod = SolarPlantMilestone::whereBetween('planned_date', $dateRange)->count();
        $milestonesCompleted = SolarPlantMilestone::whereBetween('planned_date', $dateRange)->where('status', 'completed')->count();
        $milestonesOpen = SolarPlantMilestone::whereBetween('planned_date', $dateRange)->whereNotIn('status', ['completed', 'cancelled'])->count();
        $milestonesOverdue = SolarPlantMilestone::where('planned_date', '<', now()->toDateString())->whereNotIn('status', ['completed', 'cancelled'])->count();
        
        // Projekttermine nach Status im Zeitraum
        $inProgressMilestones = SolarPlantMilestone::whereBetween('planned_date', $dateRange)->where('status', 'in_progress')->count();
        $delayedMilestones = SolarPlantMilestone::whereBetween('planned_date', $dateRange)->where('status', 'delayed')->count();
        $plannedMilestones = SolarPlantMilestone::whereBetween('planned_date', $dateRange)->where('status', 'planned')->count();
        
        // Anzahl betroffener Projekte
        $affectedProjects = SolarPlantMilestone::whereBetween('planned_date', $dateRange)
            ->distinct('solar_plant_id')
            ->count('solar_plant_id');
        
        // Fortschritt berechnen
        $progress = $milestonesInPeriod > 0 ? round(($milestonesCompleted / $milestonesInPeriod) * 100, 1) : 0;
        
        $periodLabel = $this->getPeriodLabel();
        
        return [
            Stat::make("Projekttermine {$periodLabel}", $milestonesInPeriod)
                ->description("{$milestonesCompleted} abgeschlossen, {$milestonesOpen} offen")
                ->descriptionIcon('heroicon-m-calendar')
                ->color($milestonesOpen > 0 ? 'warning' : 'success'),
                
            Stat::make('Fortschritt', $progress . '%')
                ->description('Abgeschlossene Termine')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($progress >= 80 ? 'success' : ($progress >= 50 ? 'warning' : 'danger')),
                
            Stat::make('Betroffene Projekte', $affectedProjects)
                ->description('Solaranlagen mit Terminen')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),
                
            Stat::make('In Bearbeitung', $inProgressMilestones)
                ->description("{$plannedMilestones} geplant, {$delayedMilestones} verzögert")
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('primary'),
                
            Stat::make('Überfällig', $milestonesOverdue)
                ->description('Vor heute geplant')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($milestonesOverdue > 0 ? 'danger' : 'success'),
        ];
    }
    
    protected function getDateRange(): array
    {
        return match($this->timeFilter) {
            'today' => [
                Carbon::today()->toDateString(),
                Carbon::today()->toDateString()
            ],
            'next_7_days' => [
                Carbon::today()->toDateString(),
                Carbon::today()->addDays(7)->toDateString()
            ],
            'next_30_days' => [
                Carbon::today()->toDateString(),
                Carbon::today()->addDays(30)->toDateString()
            ],
            default => [
                Carbon::today()->toDateString(),
                Carbon::today()->toDateString()
            ]
        };
    }
    
    protected function getPeriodLabel(): string
    {
        return match($this->timeFilter) {
            'today' => 'heute',
            'next_7_days' => 'nächste 7 Tage',
            'next_30_days' => 'nächste 30 Tage',
            default => 'heute'
        };
    }
}