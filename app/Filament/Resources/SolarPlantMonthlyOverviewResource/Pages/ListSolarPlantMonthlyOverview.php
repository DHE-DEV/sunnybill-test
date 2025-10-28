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

    public ?string $plantBillingFilter = 'alle';

    public ?string $viewMode = 'four_months'; // 'four_months' oder 'single_month'

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('showFourMonths')
                ->label('4-Monats-Übersicht')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->visible(fn () => $this->viewMode === 'single_month')
                ->action(function () {
                    $this->viewMode = 'four_months';
                    session(['solar_plant_monthly_overview.view_mode' => $this->viewMode]);
                    $this->dispatch('$refresh');
                }),

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
                    $this->viewMode = 'single_month';
                    // Speichere in der Session
                    session(['solar_plant_monthly_overview.selected_month' => $this->selectedMonth]);
                    session(['solar_plant_monthly_overview.view_mode' => $this->viewMode]);
                    $this->dispatch('monthSelected', month: $this->selectedMonth);
                }),
            
            Actions\Action::make('filterStatus')
                ->label('Status filtern')
                ->icon('heroicon-o-funnel')
                ->color('gray')
                ->visible(fn () => $this->viewMode === 'single_month')
                ->modalHeading('Nach Status filtern')
                ->modalWidth(MaxWidth::Medium)
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Abrechnungsstatus')
                        ->options([
                            'all' => 'Alle anzeigen',
                            'no_contracts' => 'Anlagen ohne Lieferantenverträge',
                            'few_contracts' => 'Anlagen mit weniger als 5 Verträgen',
                            'incomplete' => 'Anlagen mit fehlende Lieferantenbelegen',
                            'complete' => 'Anlagen mit komplett erfassten Lieferantenbelegen',
                        ])
                        ->default($this->statusFilter)
                        ->required()
                        ->placeholder('Status auswählen...'),
                ])
                ->action(function (array $data) {
                    $this->statusFilter = $data['status'];
                    // Speichere in der Session
                    session(['solar_plant_monthly_overview.status_filter' => $this->statusFilter]);
                    $this->dispatch('statusFilterChanged', status: $this->statusFilter);
                }),

            Actions\Action::make('filterPlantBilling')
                ->label('Anlagen-Abrechnung filtern')
                ->icon('heroicon-o-document-currency-dollar')
                ->color('gray')
                ->visible(fn () => $this->viewMode === 'single_month')
                ->modalHeading('Nach Anlagen-Abrechnung filtern')
                ->modalWidth(MaxWidth::Medium)
                ->form([
                    Forms\Components\Select::make('plantBilling')
                        ->label('Anlagen-Abrechnungsstatus')
                        ->options([
                            'alle' => 'Alle',
                            'mit_abrechnungen' => 'Mit Abrechnungen',
                            'ohne_abrechnungen' => 'Ohne Abrechnungen',
                        ])
                        ->default($this->plantBillingFilter)
                        ->required()
                        ->placeholder('Status auswählen...'),
                ])
                ->action(function (array $data) {
                    $this->plantBillingFilter = $data['plantBilling'];
                    // Speichere in der Session
                    session(['solar_plant_monthly_overview.plant_billing_filter' => $this->plantBillingFilter]);
                    $this->dispatch('plantBillingFilterChanged', plantBillingStatus: $this->plantBillingFilter);
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
        // Lade Werte aus der Session oder setze Defaults
        $this->selectedMonth = session('solar_plant_monthly_overview.selected_month', now()->format('Y-m'));
        $this->statusFilter = session('solar_plant_monthly_overview.status_filter', 'all');
        $this->plantBillingFilter = session('solar_plant_monthly_overview.plant_billing_filter', 'alle');
        $this->viewMode = session('solar_plant_monthly_overview.view_mode', 'four_months');
    }

    protected function getViewData(): array
    {
        // Wenn 4-Monats-Ansicht, sammle Daten für die letzten 4 Monate
        if ($this->viewMode === 'four_months') {
            return $this->getFourMonthsViewData();
        }

        // Einzelmonatsansicht
        $month = $this->selectedMonth ?? now()->format('Y-m');

        // Hole alle Solaranlagen (nur die mit billing = true)
        $solarPlants = SolarPlant::whereNull('deleted_at')
            ->where('billing', true)
            ->orderBy('plant_number')
            ->get();

        $plantsData = [];

        $year = (int) substr($month, 0, 4);
        $monthNumber = (int) substr($month, 5, 2);
        $monthDate = Carbon::create($year, $monthNumber, 1);

        foreach ($solarPlants as $plant) {
            $status = SolarPlantMonthlyOverviewResource::getBillingStatusForMonth($plant, $month);
            $missingBillings = SolarPlantMonthlyOverviewResource::getMissingBillingsForMonth($plant, $month);

            // Hole alle aktiven Verträge und filtere sie für den gewählten Monat
            $allActiveContracts = $plant->activeSupplierContracts()->with('supplier')->get();
            $uniqueContracts = $allActiveContracts->filter(function ($contract) {
                return $contract->deleted_at === null;
            })->unique('id');

            // Filtere Verträge, die für diesen Monat gültig sind
            $activeContracts = $uniqueContracts->filter(function ($contract) use ($monthDate) {
                // Prüfe ob Vertrag aktiv ist
                if (!$contract->is_active) {
                    return false;
                }

                // Wenn start_date gesetzt ist, prüfe ob der Monat danach liegt
                if ($contract->start_date && $monthDate->isBefore($contract->start_date->startOfMonth())) {
                    return false;
                }

                // Wenn end_date gesetzt ist, prüfe ob der Monat davor liegt
                if ($contract->end_date && $monthDate->isAfter($contract->end_date->endOfMonth())) {
                    return false;
                }

                return true;
            });

            // Prüfe Anlagen-Abrechnungen
            $hasPlantBillings = SolarPlantMonthlyOverviewResource::hasPlantBillingsForMonth($plant, $month);
            $plantBillingsCount = SolarPlantMonthlyOverviewResource::getPlantBillingsCountForMonth($plant, $month);

            // Hole die erste Anlagen-Abrechnung für direkten Link
            $firstPlantBilling = $hasPlantBillings ? $plant->billings()
                ->where('billing_year', $year)
                ->where('billing_month', $monthNumber)
                ->first() : null;

            $plantsData[] = [
                'plant' => $plant,
                'status' => $status,
                'missingBillings' => $missingBillings,
                'activeContracts' => $activeContracts,
                'totalContracts' => $activeContracts->count(),
                'missingCount' => $missingBillings->count(),
                'hasPlantBillings' => $hasPlantBillings,
                'plantBillingsCount' => $plantBillingsCount,
                'firstPlantBilling' => $firstPlantBilling,
            ];
        }

        // Filtere nach Status wenn gewünscht
        if ($this->statusFilter && $this->statusFilter !== 'all') {
            $plantsData = array_filter($plantsData, function ($plantData) {
                $status = $plantData['status'];
                
                return match ($this->statusFilter) {
                    'incomplete' => $status === 'Unvollständig',
                    'complete' => $status === 'Vollständig',
                    'no_contracts' => $status === 'Keine Verträge',
                    'few_contracts' => $plantData['totalContracts'] > 0 && $plantData['totalContracts'] < 5,
                    default => true,
                };
            });
        }

        // Filtere nach Anlagen-Abrechnungsstatus wenn gewünscht
        if ($this->plantBillingFilter && $this->plantBillingFilter !== 'alle') {
            $plantsData = array_filter($plantsData, function ($plantData) {
                return match ($this->plantBillingFilter) {
                    'mit_abrechnungen' => $plantData['hasPlantBillings'],
                    'ohne_abrechnungen' => !$plantData['hasPlantBillings'],
                    default => true,
                };
            });
        }

        // Berechne die Statistiken NACH der Filterung für korrekte Anzeige
        $allPlantsStats = [
            'total' => count($plantsData),
            'incomplete' => count(array_filter($plantsData, fn($p) => $p['status'] === 'Unvollständig')),
            'complete' => count(array_filter($plantsData, fn($p) => $p['status'] === 'Vollständig')),
            'no_contracts' => count(array_filter($plantsData, fn($p) => $p['status'] === 'Keine Verträge')),
            'few_contracts' => count(array_filter($plantsData, fn($p) => $p['totalContracts'] > 0 && $p['totalContracts'] < 5)),
        ];

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
            'viewMode' => 'single_month',
            'selectedMonth' => $month,
            'monthLabel' => Carbon::createFromFormat('Y-m', $month)->locale('de')->translatedFormat('F Y'),
            'plantsData' => $plantsData,
            'allPlantsStats' => $allPlantsStats,
            'statusFilter' => $this->statusFilter,
            'plantBillingFilter' => $this->plantBillingFilter,
        ];
    }

    protected function getFourMonthsViewData(): array
    {
        // Die letzten 4 Monate generieren
        $months = [];
        $currentDate = now();

        for ($i = 3; $i >= 0; $i--) {
            $date = $currentDate->copy()->subMonths($i);
            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->locale('de')->translatedFormat('F Y'),
                'year' => (int) $date->format('Y'),
                'month' => (int) $date->format('m'),
            ];
        }

        // Hole alle Solaranlagen (nur die mit billing = true)
        $solarPlants = SolarPlant::whereNull('deleted_at')
            ->where('billing', true)
            ->orderBy('plant_number')
            ->get();

        $plantsData = [];

        foreach ($solarPlants as $plant) {
            $monthsData = [];

            foreach ($months as $monthInfo) {
                $month = $monthInfo['value'];
                $year = $monthInfo['year'];
                $monthNumber = $monthInfo['month'];
                $monthDate = Carbon::create($year, $monthNumber, 1);

                $status = SolarPlantMonthlyOverviewResource::getBillingStatusForMonth($plant, $month);

                // Hole alle aktiven Verträge und filtere sie für den gewählten Monat
                $allActiveContracts = $plant->activeSupplierContracts()->with('supplier')->get();
                $uniqueContracts = $allActiveContracts->filter(function ($contract) {
                    return $contract->deleted_at === null;
                })->unique('id');

                // Filtere Verträge, die für diesen Monat gültig sind
                $activeContracts = $uniqueContracts->filter(function ($contract) use ($monthDate) {
                    if (!$contract->is_active) {
                        return false;
                    }

                    if ($contract->start_date && $monthDate->isBefore($contract->start_date->startOfMonth())) {
                        return false;
                    }

                    if ($contract->end_date && $monthDate->isAfter($contract->end_date->endOfMonth())) {
                        return false;
                    }

                    return true;
                });

                $missingBillings = SolarPlantMonthlyOverviewResource::getMissingBillingsForMonth($plant, $month);
                $hasPlantBillings = SolarPlantMonthlyOverviewResource::hasPlantBillingsForMonth($plant, $month);
                $plantBillingsCount = SolarPlantMonthlyOverviewResource::getPlantBillingsCountForMonth($plant, $month);

                // Lade bestehende Notizen für diesen Monat
                $notes = $plant->billingNotes()
                    ->forMonth($year, $monthNumber)
                    ->with(['creator', 'supplierContract'])
                    ->orderBy('created_at', 'desc')
                    ->get();

                $monthsData[] = [
                    'month' => $month,
                    'monthLabel' => $monthInfo['label'],
                    'status' => $status,
                    'totalContracts' => $activeContracts->count(),
                    'missingCount' => $missingBillings->count(),
                    'missingBillings' => $missingBillings,
                    'activeContracts' => $activeContracts,
                    'hasPlantBillings' => $hasPlantBillings,
                    'plantBillingsCount' => $plantBillingsCount,
                    'year' => $year,
                    'monthNumber' => $monthNumber,
                    'notes' => $notes,
                ];
            }

            $plantsData[] = [
                'plant' => $plant,
                'monthsData' => $monthsData,
            ];
        }

        return [
            'viewMode' => 'four_months',
            'months' => $months,
            'plantsData' => $plantsData,
            'statusFilter' => $this->statusFilter,
            'plantBillingFilter' => $this->plantBillingFilter,
        ];
    }

    public function getTitle(): string
    {
        if ($this->viewMode === 'four_months') {
            return "Monatliche Detailansicht - 4-Monats-Übersicht";
        }

        $monthLabel = $this->selectedMonth
            ? Carbon::createFromFormat('Y-m', $this->selectedMonth)->locale('de')->translatedFormat('F Y')
            : 'Aktueller Monat';

        return "Monatliche Detailansicht - {$monthLabel}";
    }
}
