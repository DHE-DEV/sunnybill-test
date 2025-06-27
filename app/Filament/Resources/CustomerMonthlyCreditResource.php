<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerMonthlyCreditResource\Pages;
use App\Models\CustomerMonthlyCredit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerMonthlyCreditResource extends Resource
{
    protected static ?string $model = CustomerMonthlyCredit::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';

    protected static ?string $navigationLabel = 'Gutschriften';

    protected static ?string $modelLabel = 'Gutschrift';

    protected static ?string $pluralModelLabel = 'Gutschriften';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Fakturierung';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Gutschrift Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->label('Kunde')
                                    ->relationship('customer', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disabled(),
                                Forms\Components\Select::make('solar_plant_id')
                                    ->label('Solaranlage')
                                    ->relationship('solarPlant', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disabled(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('month')
                                    ->label('Monat')
                                    ->required()
                                    ->displayFormat('m/Y')
                                    ->format('Y-m-01')
                                    ->disabled(),
                                Forms\Components\TextInput::make('participation_percentage')
                                    ->label('Beteiligung (%)')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->disabled(),
                            ]),
                    ]),
                Forms\Components\Section::make('Berechnete Werte')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('energy_share_kwh')
                                    ->label('Energieanteil (kWh)')
                                    ->required()
                                    ->numeric()
                                    ->step(0.000001)
                                    ->suffix('kWh')
                                    ->disabled(),
                                Forms\Components\TextInput::make('savings_amount')
                                    ->label('Ersparnis')
                                    ->required()
                                    ->numeric()
                                    ->step(0.000001)
                                    ->prefix('€')
                                    ->disabled(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('feed_in_revenue')
                                    ->label('Einspeiseerlös')
                                    ->required()
                                    ->numeric()
                                    ->step(0.000001)
                                    ->prefix('€')
                                    ->disabled(),
                                Forms\Components\TextInput::make('total_credit')
                                    ->label('Gesamtgutschrift')
                                    ->required()
                                    ->numeric()
                                    ->step(0.000001)
                                    ->prefix('€')
                                    ->disabled(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('month')
                    ->label('Monat')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('m/Y'))
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->description(fn ($record) => 'Nr: ' . ($record->customer->customer_number ?? 'N/A'))
                    ->searchable(['name', 'customer_number'])
                    ->sortable()
                    ->weight('bold')
                    ->url(fn ($record) => route('filament.admin.resources.customers.view', $record->customer_id))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Solaranlage')
                    ->searchable()
                    ->limit(25)
                    ->url(fn ($record) => route('filament.admin.resources.solar-plants.view', $record->solar_plant_id))
                    ->color('primary')
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('participation_percentage')
                    ->label('Beteiligung')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('energy_share_kwh')
                    ->label('Energieanteil')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . ' kWh')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('savings_amount')
                    ->label('Ersparnis')
                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 2, ',', '.'))
                    ->sortable()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('feed_in_revenue')
                    ->label('Einspeiseerlös')
                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 2, ',', '.'))
                    ->sortable()
                    ->color('warning')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_credit')
                    ->label('Gesamtgutschrift')
                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 2, ',', '.'))
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Berechnet am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('current_year')
                    ->label('Aktuelles Jahr')
                    ->query(fn (Builder $query): Builder => $query->whereYear('month', now()->year))
                    ->default(),
                Tables\Filters\Filter::make('last_6_months')
                    ->label('Letzte 6 Monate')
                    ->query(fn (Builder $query): Builder => $query->where('month', '>=', now()->subMonths(6)->startOfMonth())),
                Tables\Filters\SelectFilter::make('customer')
                    ->label('Kunde')
                    ->relationship('customer', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name . ' (Nr: ' . ($record->customer_number ?? 'N/A') . ')')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('solarPlant')
                    ->label('Solaranlage')
                    ->relationship('solarPlant', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('high_credits')
                    ->label('Hohe Gutschriften (>= €50)')
                    ->query(fn (Builder $query): Builder => $query->where('total_credit', '>=', 50)),
                Tables\Filters\Filter::make('very_high_credits')
                    ->label('Sehr hohe Gutschriften (>= €100)')
                    ->query(fn (Builder $query): Builder => $query->where('total_credit', '>=', 100)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Keine Edit/Delete-Actions, da Gutschriften automatisch berechnet werden
            ])
            ->bulkActions([
                // Keine Bulk-Actions für automatisch berechnete Daten
            ])
            ->defaultSort('month', 'desc')
            ->emptyStateHeading('Keine Gutschriften')
            ->emptyStateDescription('Gutschriften werden automatisch erstellt, wenn monatliche Ergebnisse für Solaranlagen eingegeben werden.')
            ->emptyStateIcon('heroicon-o-currency-euro')
            ->poll('30s'); // Auto-refresh alle 30 Sekunden
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerMonthlyCredits::route('/'),
            'view' => Pages\ViewCustomerMonthlyCredit::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Gutschriften werden automatisch erstellt
    }

    public static function canEdit($record): bool
    {
        return false; // Gutschriften sind schreibgeschützt
    }

    public static function canDelete($record): bool
    {
        return false; // Gutschriften können nicht gelöscht werden
    }
}