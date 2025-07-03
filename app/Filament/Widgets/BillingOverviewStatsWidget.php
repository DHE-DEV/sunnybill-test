<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SolarPlantBillingOverviewResource;
use App\Models\SolarPlant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BillingOverviewStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');
        
        // Alle aktiven Solaranlagen
        $activePlants = SolarPlant::whereHas('activeSupplierContracts')->get();
        
        // Statistiken für aktuellen Monat
        $currentMonthComplete = 0;
        $currentMonthIncomplete = 0;
        $currentMonthNoContracts = 0;
        
        // Statistiken für letzten Monat
        $lastMonthComplete = 0;
        $lastMonthIncomplete = 0;
        
        foreach ($activePlants as $plant) {
            // Aktueller Monat
            $currentStatus = SolarPlantBillingOverviewResource::getBillingStatusForMonth($plant, $currentMonth);
            switch ($currentStatus) {
                case 'Vollständig':
                    $currentMonthComplete++;
                    break;
                case 'Unvollständig':
                    $currentMonthIncomplete++;
                    break;
                case 'Keine Verträge':
                    $currentMonthNoContracts++;
                    break;
            }
            
            // Letzter Monat
            $lastStatus = SolarPlantBillingOverviewResource::getBillingStatusForMonth($plant, $lastMonth);
            switch ($lastStatus) {
                case 'Vollständig':
                    $lastMonthComplete++;
                    break;
                case 'Unvollständig':
                    $lastMonthIncomplete++;
                    break;
            }
        }
        
        // Berechne Prozentsätze
        $totalWithContracts = $activePlants->count() - $currentMonthNoContracts;
        $currentMonthPercentage = $totalWithContracts > 0 ? round(($currentMonthComplete / $totalWithContracts) * 100, 1) : 0;
        
        $lastMonthTotal = $lastMonthComplete + $lastMonthIncomplete;
        $lastMonthPercentage = $lastMonthTotal > 0 ? round(($lastMonthComplete / $lastMonthTotal) * 100, 1) : 0;
        
        return [
            Stat::make('Aktueller Monat - Vollständig', $currentMonthComplete)
                ->description("{$currentMonthPercentage}% aller Anlagen mit Verträgen")
                ->descriptionIcon($currentMonthPercentage >= 80 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($currentMonthPercentage >= 80 ? 'success' : ($currentMonthPercentage >= 50 ? 'warning' : 'danger'))
                ->chart([7, 2, 10, 3, 15, 4, 17]),
            
            Stat::make('Aktueller Monat - Unvollständig', $currentMonthIncomplete)
                ->description('Anlagen mit fehlenden Abrechnungen')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($currentMonthIncomplete > 0 ? 'danger' : 'success'),
            
            Stat::make('Vormonat - Vollständig', $lastMonthComplete)
                ->description("{$lastMonthPercentage}% aller Anlagen")
                ->descriptionIcon($lastMonthPercentage >= $currentMonthPercentage ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($lastMonthPercentage >= 80 ? 'success' : ($lastMonthPercentage >= 50 ? 'warning' : 'danger')),
            
            Stat::make('Gesamt Solaranlagen', $activePlants->count())
                ->description('Mit aktiven Lieferantenverträgen')
                ->descriptionIcon('heroicon-m-sun')
                ->color('info'),
        ];
    }
    
    protected function getColumns(): int
    {
        return 4;
    }
}