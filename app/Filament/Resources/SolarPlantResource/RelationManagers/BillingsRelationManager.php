<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

use App\Models\SolarPlantBilling;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class BillingsRelationManager extends RelationManager
{
    protected static string $relationship = 'billings';

    protected static ?string $title = 'Kundenabrechnungen';

    protected static ?string $modelLabel = 'Kundenabrechnung';

    protected static ?string $pluralModelLabel = 'Kundenabrechnungen';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->billings()->count();
    }


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('Kunde')
                    ->options(Customer::all()->mapWithKeys(function ($customer) {
                        $displayName = $customer->customer_type === 'business' && $customer->company_name
                            ? $customer->company_name
                            : $customer->name;
                        return [$customer->id => $displayName];
                    }))
                    ->searchable()
                    ->required(),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('billing_year')
                            ->label('Abrechnungsjahr')
                            ->options(function () {
                                $currentYear = now()->year;
                                $years = [];
                                for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            })
                            ->default(now()->year)
                            ->required(),

                        Forms\Components\Select::make('billing_month')
                            ->label('Abrechnungsmonat')
                            ->options([
                                1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                                5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                            ])
                            ->default(now()->month)
                            ->required(),
                    ]),

                Forms\Components\TextInput::make('participation_percentage')
                    ->label('Beteiligungsprozentsatz')
                    ->suffix('%')
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0)
                    ->maxValue(100)
                    ->required(),

                Forms\Components\TextInput::make('produced_energy_kwh')
                    ->label('Produzierte Energie (kWh)')
                    ->suffix('kWh')
                    ->numeric()
                    ->step(0.001)
                    ->minValue(0)
                    ->placeholder('z.B. 2500.000'),

                Forms\Components\TextInput::make('total_costs')
                    ->label('Gesamtkosten')
                    ->prefix('€')
                    ->numeric()
                    ->step(0.01)
                    ->default(0),

                Forms\Components\TextInput::make('total_credits')
                    ->label('Gesamtgutschriften')
                    ->prefix('€')
                    ->numeric()
                    ->step(0.01)
                    ->default(0),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(SolarPlantBilling::getStatusOptions())
                    ->default('draft')
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notizen')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('formatted_month')
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->getStateUsing(function (SolarPlantBilling $record): string {
                        $customer = $record->customer;
                        return $customer->customer_type === 'business' && $customer->company_name 
                            ? $customer->company_name 
                            : $customer->name;
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_month')
                    ->label('Abrechnungsmonat')
                    ->sortable(['billing_year', 'billing_month']),

                Tables\Columns\TextColumn::make('produced_energy_kwh')
                    ->label('Produzierte Energie')
                    ->suffix(' kWh')
                    ->numeric(3)
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('participation_percentage')
                    ->label('Beteiligung')
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

                Tables\Columns\TextColumn::make('breakdown_summary')
                    ->label('Aufschlüsselung')
                    ->getStateUsing(function (SolarPlantBilling $record): string {
                        $summary = '';
                        
                        if ($record->cost_breakdown && count($record->cost_breakdown) > 0) {
                            $costCount = count($record->cost_breakdown);
                            $summary .= "{$costCount} Kostenposition" . ($costCount > 1 ? 'en' : '');
                        }
                        
                        if ($record->credit_breakdown && count($record->credit_breakdown) > 0) {
                            $creditCount = count($record->credit_breakdown);
                            if ($summary) $summary .= ', ';
                            $summary .= "{$creditCount} Gutschrift" . ($creditCount > 1 ? 'en' : '');
                        }
                        
                        return $summary ?: 'Keine Details';
                    })
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'finalized' => 'warning',
                        'sent' => 'info',
                        'paid' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => SolarPlantBilling::getStatusOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SolarPlantBilling::getStatusOptions()),

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

                Tables\Filters\SelectFilter::make('billing_month')
                    ->label('Monat')
                    ->options([
                        1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                        5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Neue Kundenabrechnung'),
                Tables\Actions\Action::make('create_monthly_billings')
                    ->label('Monatliche Abrechnungen erstellen')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('billing_year')
                            ->label('Abrechnungsjahr')
                            ->options(function () {
                                $currentYear = now()->year;
                                $years = [];
                                for ($i = $currentYear - 1; $i <= $currentYear; $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            })
                            ->default(now()->year)
                            ->required(),

                        Forms\Components\Select::make('billing_month')
                            ->label('Abrechnungsmonat')
                            ->options([
                                1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                                5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                            ])
                            ->default(now()->subMonth()->month)
                            ->required(),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        try {
                            $billings = SolarPlantBilling::createBillingsForMonth(
                                $livewire->ownerRecord->id,
                                $data['billing_year'],
                                $data['billing_month']
                            );

                            $count = count($billings);
                            $monthName = Carbon::createFromDate($data['billing_year'], $data['billing_month'], 1)
                                ->locale('de')
                                ->translatedFormat('F Y');

                            Notification::make()
                                ->title('Abrechnungen erfolgreich erstellt')
                                ->body("{$count} Kundenabrechnungen für {$monthName} wurden erstellt.")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Fehler beim Erstellen der Abrechnungen')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalWidth('md'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (SolarPlantBilling $record): string => 
                        \App\Filament\Resources\SolarPlantBillingResource::getUrl('view', ['record' => $record])
                    )
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine Kundenabrechnungen')
            ->emptyStateDescription('Erstellen Sie die erste Kundenabrechnung für diese Solaranlage.')
            ->emptyStateIcon('heroicon-o-calculator');
    }
}
