<?php

namespace App\Filament\Widgets;

use App\Models\Supplier;
use App\Models\SupplierEmployee;
use App\Models\SolarPlantSupplier;
use App\Models\Article;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SupplierStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::where('is_active', true)->count();
        $inactiveSuppliers = Supplier::where('is_active', false)->count();
        $suppliersWithEmployees = Supplier::whereHas('employees')->count();
        $suppliersWithSolarPlants = Supplier::whereHas('solarPlants')->count();
        $suppliersWithNotes = Supplier::whereHas('notes')->count();
        
        // Durchschnittliche Anzahl Mitarbeiter pro Lieferant
        $totalEmployees = SupplierEmployee::count();
        $avgEmployeesPerSupplier = $totalSuppliers > 0 ? round($totalEmployees / $totalSuppliers, 1) : 0;
        
        return [
            Stat::make('Gesamt Lieferanten', $totalSuppliers)
                ->description('Alle registrierten Lieferanten')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),
                
            Stat::make('Aktive Lieferanten', $activeSuppliers)
                ->description(($totalSuppliers > 0 ? round(($activeSuppliers / $totalSuppliers) * 100, 1) : 0) . '% sind aktiv')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Inaktive Lieferanten', $inactiveSuppliers)
                ->description(($totalSuppliers > 0 ? round(($inactiveSuppliers / $totalSuppliers) * 100, 1) : 0) . '% sind inaktiv')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
                
            Stat::make('Lieferanten mit Mitarbeitern', $suppliersWithEmployees)
                ->description('Ø ' . $avgEmployeesPerSupplier . ' Mitarbeiter pro Lieferant')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
                
            Stat::make('Solar-Zuordnungen', $suppliersWithSolarPlants)
                ->description(($totalSuppliers > 0 ? round(($suppliersWithSolarPlants / $totalSuppliers) * 100, 1) : 0) . '% haben Solar-Projekte')
                ->descriptionIcon('heroicon-m-sun')
                ->color('warning'),
                
            Stat::make('Lieferanten mit Notizen', $suppliersWithNotes)
                ->description(($totalSuppliers > 0 ? round(($suppliersWithNotes / $totalSuppliers) * 100, 1) : 0) . '% haben Notizen')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),
        ];
    }
}