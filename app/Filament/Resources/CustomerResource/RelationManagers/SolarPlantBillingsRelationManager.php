<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\SolarPlantBilling;
use App\Models\SolarPlant;
use Carbon\Carbon;

class SolarPlantBillingsRelationManager extends RelationManager
{
    protected static string $relationship = 'solarPlantBillings';

    protected static ?string $title = 'Abrechnungen';

    protected static ?string $modelLabel = 'Abrechnung';

    protected static ?string $pluralModelLabel = 'Abrechnungen';

    protected static string $view = 'filament.resources.customer-resource.relation-managers.solar-plant-billings';

    protected function getViewData(): array
    {
        return [
            'missingBillingsOverview' => $this->getMissingBillingsOverview(),
        ];
    }

    protected function getMissingBillingsOverview(): array
    {
        $customer = $this->getOwnerRecord();
        $now = now();
        $earliestDate = Carbon::create(2025, 1, 1);
        $lastCompletedMonth = $now->copy()->subMonth()->startOfMonth();
        $sixMonthsAgo = $now->copy()->subMonths(6)->startOfMonth();

        $monthLabels = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
        $fullMonthNames = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

        // 1. Load active participations with billing-enabled solar plants
        $participations = $customer->plantParticipations()
            ->where('is_active', true)
            ->whereHas('solarPlant', fn ($q) => $q->where('billing', true))
            ->with('solarPlant')
            ->get();

        if ($participations->isEmpty()) {
            return ['totalMissing' => 0, 'hasParticipations' => false, 'participations' => []];
        }

        // 2. Load all existing non-cancelled SolarPlantBillings for this customer
        $existingBillings = SolarPlantBilling::where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->groupBy('solar_plant_id');

        // 3. Load supplier contracts with billings for all relevant solar plants
        $solarPlantIds = $participations->pluck('solar_plant_id')->unique();
        $solarPlantsData = SolarPlant::whereIn('id', $solarPlantIds)
            ->with(['activeSupplierContracts' => fn ($q) => $q->with(['supplier', 'billings'])])
            ->get()
            ->keyBy('id');

        $totalMissing = 0;
        $participationData = [];

        foreach ($participations as $participation) {
            $solarPlant = $participation->solarPlant;
            if (!$solarPlant) {
                continue;
            }

            // Letzte existierende Abrechnung für diese Anlage finden
            $plantBillings = $existingBillings->get($solarPlant->id, collect());
            $latestBilling = $plantBillings->sortByDesc(
                fn ($b) => $b->billing_year * 100 + $b->billing_month
            )->first();

            if ($latestBilling) {
                // Ab dem Monat NACH der letzten Abrechnung beginnen
                $startDate = Carbon::create($latestBilling->billing_year, $latestBilling->billing_month, 1)->addMonth();
            } else {
                // Keine Abrechnung vorhanden - ab Beteiligungsbeginn (frühestens Jan 2025)
                $startDate = $participation->start_date->copy()->startOfMonth();
                if ($startDate->lt($earliestDate)) {
                    $startDate = $earliestDate->copy();
                }
            }

            // End-Monat: Vormonat oder Beteiligungsende (was früher ist)
            $endDate = $participation->end_date && $participation->end_date->lt($lastCompletedMonth)
                ? $participation->end_date->copy()->startOfMonth()
                : $lastCompletedMonth->copy();

            // Wenn Start nach Ende liegt, fehlt nichts
            if ($startDate->gt($endDate)) {
                continue;
            }

            // Ausstehende Monate chronologisch durchgehen (älteste zuerst)
            $pendingMonths = [];
            $recentMissingSupplierBillings = [];
            $olderMissingSupplierBillings = [];

            $cursor = $startDate->copy();
            while ($cursor->lte($endDate)) {
                $year = $cursor->year;
                $month = $cursor->month;

                // Fehlende Lieferantenbelege prüfen
                $plantData = $solarPlantsData->get($solarPlant->id);
                $missingBillings = [];

                if ($plantData) {
                    foreach ($plantData->activeSupplierContracts as $contract) {
                        $hasBilling = $contract->billings
                            ->where('billing_year', $year)
                            ->where('billing_month', $month)
                            ->isNotEmpty();

                        if (!$hasBilling) {
                            $missingBillings[] = [
                                'contractTitle' => $contract->title,
                                'supplierName' => $contract->supplier->company_name ?? $contract->supplier->name ?? 'Unbekannt',
                            ];
                        }
                    }
                }

                $monthLabel = $monthLabels[$month - 1] . ' ' . substr((string) $year, 2);

                $pendingMonths[] = [
                    'label' => $monthLabel,
                    'fullLabel' => $fullMonthNames[$month - 1] . ' ' . $year,
                    'status' => empty($missingBillings) ? 'ready' : 'blocked',
                    'missingSupplierBillings' => $missingBillings,
                ];

                // Fehlende Lieferantenbelege nach Alter aufteilen
                if (!empty($missingBillings)) {
                    if ($cursor->gte($sixMonthsAgo)) {
                        $recentMissingSupplierBillings[$monthLabel] = $missingBillings;
                    } else {
                        $olderMissingSupplierBillings[$monthLabel] = $missingBillings;
                    }
                }

                $cursor->addMonth();
            }

            if (!empty($pendingMonths)) {
                $missingCount = count($pendingMonths);
                $totalMissing += $missingCount;
                $participationData[] = [
                    'plantNumber' => $solarPlant->plant_number,
                    'plantName' => $solarPlant->name,
                    'percentage' => number_format($participation->percentage, 2, ',', '.'),
                    'missingCount' => $missingCount,
                    'nextBillingLabel' => $pendingMonths[0]['fullLabel'],
                    'lastBillingLabel' => $latestBilling
                        ? $fullMonthNames[$latestBilling->billing_month - 1] . ' ' . $latestBilling->billing_year
                        : null,
                    'pendingMonths' => $pendingMonths,
                    'recentMissingSupplierBillings' => $recentMissingSupplierBillings,
                    'olderMissingSupplierBillings' => $olderMissingSupplierBillings,
                ];
            }
        }

        return [
            'totalMissing' => $totalMissing,
            'hasParticipations' => $participations->isNotEmpty(),
            'participations' => $participationData,
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form is read-only, managed via SolarPlantBillingResource
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('solarPlant.plant_number')
                    ->label('Anlagen-Nr.')
                    ->searchable()
                    ->sortable()
                    ->url(fn (SolarPlantBilling $record): string =>
                        \App\Filament\Resources\SolarPlantResource::getUrl('view', ['record' => $record->solar_plant_id])
                    )
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Anlagenname')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('formatted_month')
                    ->label('Abrechnungsmonat')
                    ->sortable(['billing_year', 'billing_month']),

                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Rechnungsnummer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('participation_percentage')
                    ->label('Beteiligung (%)')
                    ->suffix('%')
                    ->numeric(2)
                    ->alignRight(),

                Tables\Columns\TextColumn::make('formatted_total_costs')
                    ->label('Kosten')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('formatted_total_credits')
                    ->label('Gutschriften')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('formatted_net_amount')
                    ->label('Gesamtbetrag')
                    ->alignRight()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'finalized' => 'warning',
                        'sent' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => SolarPlantBilling::getStatusOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('cancellation_date')
                    ->label('Storniert am')
                    ->date()
                    ->sortable()
                    ->color('danger')
                    ->badge(fn ($record) => $record->cancellation_date ? true : false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('billing_year')
                    ->label('Jahr')
                    ->options(function () {
                        $currentYear = now()->year;
                        $years = [];
                        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SolarPlantBilling::getStatusOptions()),
            ])
            ->headerActions([
                // No create action - billings are created via SolarPlantBillingResource
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (SolarPlantBilling $record): string =>
                        \App\Filament\Resources\SolarPlantBillingResource::getUrl('view', ['record' => $record->id])
                    )
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // No bulk actions
            ])
            ->defaultSort('billing_year', 'desc')
            ->defaultSort('billing_month', 'desc');
    }
}
