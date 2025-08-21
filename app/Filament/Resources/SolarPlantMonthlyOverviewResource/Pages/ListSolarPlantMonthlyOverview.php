<?php

namespace App\Filament\Resources\SolarPlantMonthlyOverviewResource\Pages;

use App\Filament\Resources\SolarPlantMonthlyOverviewResource;
use App\Models\SolarPlant;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class ListSolarPlantMonthlyOverview extends Page
{
    protected static string $resource = SolarPlantMonthlyOverviewResource::class;

    protected static string $view = 'filament.pages.solar-plant-monthly-overview';

    public ?string $selectedMonth = null;
    
    public ?string $statusFilter = 'all';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('selectMonth')
                ->label('Monat auswählen')
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->modalHeading('Abrechnungsmonat auswählen')
                ->modalWidth(MaxWidth::Medium)
                ->form([
                    Forms\Components\Select::make('month')
                        ->label('Abrechnungsmonat')
                        ->options(function () {
                            $months = SolarPlantMonthlyOverviewResource::getAvailableMonths();
                            $options = [];
                            foreach ($months as $month) {
                                $options[$month['value']] = $month['label'];
                            }
                            return $options;
                        })
                        ->default($this->selectedMonth ?? now()->format('Y-m'))
                        ->required()
                        ->searchable()
                        ->placeholder('Monat auswählen...'),
                ])
                ->action(function (array $data) {
                    $this->selectedMonth = $data['month'];
                    $this->dispatch('monthSelected', month: $this->selectedMonth);
                }),
            
            Actions\Action::make('filterStatus')
                ->label('Status filtern')
                ->icon('heroicon-o-funnel')
                ->color('gray')
                ->modalHeading('Nach Status filtern')
                ->modalWidth(MaxWidth::Medium)
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Abrechnungsstatus')
                        ->options([
                            'all' => 'Alle anzeigen',
                            'no_contracts' => 'Anlagen ohne Lieferantenverträge',
                            'incomplete' => 'Anlagen mit fehlende Lieferantenbelegen',
                            'complete' => 'Anlagen mit komplett erfassten Lieferantenbelegen',
                        ])
                        ->default($this->statusFilter)
                        ->required()
                        ->placeholder('Status auswählen...'),
                ])
                ->action(function (array $data) {
                    $this->statusFilter = $data['status'];
                    $this->dispatch('statusFilterChanged', status: $this->statusFilter);
                }),
            
            Actions\Action::make('refresh')
                ->label('Aktualisieren')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->dispatch('$refresh');
                }),
        ];
    }

    public function mount(): void
    {
        // Setze den aktuellen Monat als Standard
        if (!$this->selectedMonth) {
            $this->selectedMonth = now()->format('Y-m');
        }
    }

    protected function getViewData(): array
    {
        $month = $this->selectedMonth ?? now()->format('Y-m');
        
        // Hole alle Solaranlagen
        $solarPlants = SolarPlant::whereNull('deleted_at')
            ->orderBy('plant_number')
            ->get();

        $plantsData = [];

        foreach ($solarPlants as $plant) {
            $status = SolarPlantMonthlyOverviewResource::getBillingStatusForMonth($plant, $month);
            $missingBillings = SolarPlantMonthlyOverviewResource::getMissingBillingsForMonth($plant, $month);
            $activeContracts = $plant->activeSupplierContracts()->with('supplier')->get()->unique('id');

            $plantsData[] = [
                'plant' => $plant,
                'status' => $status,
                'missingBillings' => $missingBillings,
                'activeContracts' => $activeContracts,
                'totalContracts' => $activeContracts->count(),
                'missingCount' => $missingBillings->count(),
            ];
        }

        // Berechne die Gesamt-Statistiken (vor Filterung für korrekte Anzeige)
        $allPlantsStats = [
            'total' => count($plantsData),
            'incomplete' => count(array_filter($plantsData, fn($p) => $p['status'] === 'Unvollständig')),
            'complete' => count(array_filter($plantsData, fn($p) => $p['status'] === 'Vollständig')),
            'no_contracts' => count(array_filter($plantsData, fn($p) => $p['status'] === 'Keine Verträge')),
        ];

        // Filtere nach Status wenn gewünscht
        if ($this->statusFilter && $this->statusFilter !== 'all') {
            $plantsData = array_filter($plantsData, function ($plantData) {
                $status = $plantData['status'];
                
                return match ($this->statusFilter) {
                    'incomplete' => $status === 'Unvollständig',
                    'complete' => $status === 'Vollständig',
                    'no_contracts' => $status === 'Keine Verträge',
                    default => true,
                };
            });
        }

        // Sortiere nach Status (Unvollständig zuerst) und dann nach Anlagennummer
        usort($plantsData, function ($a, $b) {
            // Priorität: Unvollständig -> Vollständig -> Keine Verträge
            $statusPriority = [
                'Unvollständig' => 1,
                'Vollständig' => 2,
                'Keine Verträge' => 3,
            ];
            
            $priorityA = $statusPriority[$a['status']] ?? 4;
            $priorityB = $statusPriority[$b['status']] ?? 4;
            
            if ($priorityA !== $priorityB) {
                return $priorityA - $priorityB;
            }
            
            return strcmp($a['plant']->plant_number, $b['plant']->plant_number);
        });

        return [
            'selectedMonth' => $month,
            'monthLabel' => Carbon::createFromFormat('Y-m', $month)->locale('de')->translatedFormat('F Y'),
            'plantsData' => $plantsData,
            'allPlantsStats' => $allPlantsStats,
            'statusFilter' => $this->statusFilter,
        ];
    }

    public function getTitle(): string
    {
        $monthLabel = $this->selectedMonth 
            ? Carbon::createFromFormat('Y-m', $this->selectedMonth)->locale('de')->translatedFormat('F Y')
            : 'Aktueller Monat';
            
        return "Monatliche Detailansicht - {$monthLabel}";
    }
}
