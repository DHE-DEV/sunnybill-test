<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolarPlantBillingOverviewResource\Pages;
use App\Models\SolarPlant;
use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class SolarPlantBillingOverviewResource extends Resource
{
    protected static ?string $model = SolarPlant::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Abrechnungsübersicht';

    protected static ?string $modelLabel = 'Abrechnungsübersicht';

    protected static ?string $pluralModelLabel = 'Abrechnungsübersicht';

    protected static ?string $navigationGroup = 'Solar Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Kein Form benötigt - nur Übersicht
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('deleted_at'))
            ->columns([
                Tables\Columns\TextColumn::make('plant_number')
                    ->label('Anlagennummer')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Anlagenname')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('active_supplier_contracts_count')
                    ->label('Aktive Verträge')
                    ->getStateUsing(fn (SolarPlant $record) => $record->activeSupplierContracts()->get()->unique('id')->count())
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('current_month_status')
                    ->label('Aktueller Monat')
                    ->getStateUsing(function (SolarPlant $record) {
                        $currentMonth = now()->format('Y-m');
                        return static::getBillingStatusForMonth($record, $currentMonth);
                    })
                    ->badge()
                    ->icon('')
                    ->color(fn (string $state): string => match ($state) {
                        'Vollständig' => 'success',
                        'Unvollständig' => 'warning',
                        'Keine Verträge' => 'gray',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('last_month_status')
                    ->label('Vormonat')
                    ->getStateUsing(function (SolarPlant $record) {
                        $lastMonth = now()->subMonth()->format('Y-m');
                        return static::getBillingStatusForMonth($record, $lastMonth);
                    })
                    ->badge()
                    ->icon('')
                    ->color(fn (string $state): string => match ($state) {
                        'Vollständig' => 'success',
                        'Unvollständig' => 'warning',
                        'Keine Verträge' => 'gray',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('missing_billings_count')
                    ->label('Fehlende Abrechnungen')
                    ->getStateUsing(function (SolarPlant $record) {
                        $currentMonth = now()->format('Y-m');
                        $missing = static::getMissingBillingsForMonth($record, $currentMonth);
                        return $missing->count();
                    })
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Abrechnungsstatus')
                    ->options([
                        'complete' => 'Vollständig',
                        'incomplete' => 'Unvollständig',
                        'no_contracts' => 'Keine Verträge',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value'])) {
                            return $query;
                        }

                        $currentMonth = now()->format('Y-m');
                        
                        return $query->whereHas('activeSupplierContracts', function ($q) use ($data, $currentMonth) {
                            // Keine zusätzliche deleted_at Bedingung hier, da activeSupplierContracts bereits korrekt filtert
                            if ($data['value'] === 'complete') {
                                // Nur Anlagen mit vollständigen Abrechnungen
                                $q->whereHas('billings', function ($billingQuery) use ($currentMonth) {
                                    $billingQuery->where('billing_year', (int) substr($currentMonth, 0, 4))
                                               ->where('billing_month', (int) substr($currentMonth, 5, 2));
                                });
                            } elseif ($data['value'] === 'incomplete') {
                                // Nur Anlagen mit unvollständigen Abrechnungen
                                $q->whereDoesntHave('billings', function ($billingQuery) use ($currentMonth) {
                                    $billingQuery->where('billing_year', (int) substr($currentMonth, 0, 4))
                                               ->where('billing_month', (int) substr($currentMonth, 5, 2));
                                });
                            }
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('Details anzeigen')
                    ->icon('heroicon-o-eye')
                    ->url(fn (SolarPlant $record): string => static::getUrl('view', ['record' => $record])),
                
            ])
            ->bulkActions([
                // Keine Bulk-Actions benötigt
            ])
            ->defaultSort('plant_number');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSolarPlantBillingOverview::route('/'),
            'view' => Pages\ViewSolarPlantBillingOverview::route('/{record}'),
        ];
    }

    /**
     * Ermittelt den Abrechnungsstatus für einen bestimmten Monat
     */
    public static function getBillingStatusForMonth(SolarPlant $solarPlant, string $month): string
    {
        $activeContracts = $solarPlant->activeSupplierContracts()->with('supplier')->get();
        
        // Filtere gelöschte Verträge und entferne Duplikate
        $uniqueContracts = $activeContracts->filter(function ($contract) {
            return $contract->deleted_at === null;
        })->unique('id');
        
        if ($uniqueContracts->isEmpty()) {
            return 'Keine Verträge';
        }

        $year = (int) substr($month, 0, 4);
        $monthNumber = (int) substr($month, 5, 2);

        $contractsWithBilling = $uniqueContracts->filter(function ($contract) use ($year, $monthNumber) {
            return $contract->billings()
                ->where('billing_year', $year)
                ->where('billing_month', $monthNumber)
                ->exists();
        });

        return $contractsWithBilling->count() === $uniqueContracts->count() ? 'Vollständig' : 'Unvollständig';
    }

    /**
     * Ermittelt fehlende Abrechnungen für einen bestimmten Monat
     */
    public static function getMissingBillingsForMonth(SolarPlant $solarPlant, string $month): Collection
    {
        $activeContracts = $solarPlant->activeSupplierContracts()->with('supplier')->get();
        $year = (int) substr($month, 0, 4);
        $monthNumber = (int) substr($month, 5, 2);

        // Filtere gelöschte Verträge und entferne Duplikate
        $uniqueContracts = $activeContracts->filter(function ($contract) {
            return $contract->deleted_at === null;
        })->unique('id');

        return $uniqueContracts->filter(function ($contract) use ($year, $monthNumber) {
            return !$contract->billings()
                ->where('billing_year', $year)
                ->where('billing_month', $monthNumber)
                ->exists();
        });
    }

    /**
     * Ermittelt alle Monate mit unvollständigen Abrechnungen
     */
    public static function getIncompleteMonths(SolarPlant $solarPlant, int $lookbackMonths = 12): array
    {
        $incompleteMonths = [];
        
        for ($i = 0; $i < $lookbackMonths; $i++) {
            $month = now()->subMonths($i)->format('Y-m');
            $status = static::getBillingStatusForMonth($solarPlant, $month);
            
            if ($status === 'Unvollständig') {
                $incompleteMonths[] = [
                    'month' => $month,
                    'formatted' => Carbon::createFromFormat('Y-m', $month)->locale('de')->translatedFormat('F Y'),
                    'missing_contracts' => static::getMissingBillingsForMonth($solarPlant, $month),
                ];
            }
        }
        
        return $incompleteMonths;
    }
}
