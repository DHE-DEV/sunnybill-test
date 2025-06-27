<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MonthlyCreditsRelationManager extends RelationManager
{
    protected static string $relationship = 'monthlyCredits';

    protected static ?string $title = 'Monatliche Gutschriften';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('solar_plant_id')
                    ->label('Solaranlage')
                    ->relationship('solarPlant', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\DatePicker::make('month')
                    ->label('Monat')
                    ->required()
                    ->displayFormat('m/Y')
                    ->format('Y-m-01'),
                Forms\Components\TextInput::make('participation_percentage')
                    ->label('Beteiligung (%)')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->suffix('%')
                    ->disabled(),
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('month')
            ->columns([
                Tables\Columns\TextColumn::make('month')
                    ->label('Monat')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('m/Y'))
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Solaranlage')
                    ->searchable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('participation_percentage')
                    ->label('Beteiligung')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('energy_share_kwh')
                    ->label('Energieanteil')
                    ->formatStateUsing(fn ($state) => number_format($state, 6, ',', '.') . ' kWh')
                    ->sortable(),
                Tables\Columns\TextColumn::make('savings_amount')
                    ->label('Ersparnis')
                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 6, ',', '.'))
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('feed_in_revenue')
                    ->label('Einspeiseerlös')
                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 6, ',', '.'))
                    ->sortable()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('total_credit')
                    ->label('Gesamtgutschrift')
                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 6, ',', '.'))
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
                Tables\Filters\SelectFilter::make('solarPlant')
                    ->label('Solaranlage')
                    ->relationship('solarPlant', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('high_credits')
                    ->label('Hohe Gutschriften (>= €50)')
                    ->query(fn (Builder $query): Builder => $query->where('total_credit', '>=', 50)),
            ])
            ->headerActions([
                // Keine Create-Action, da Gutschriften automatisch erstellt werden
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Details'),
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

    public function isReadOnly(): bool
    {
        return true; // Relation Manager ist nur lesend, da Gutschriften automatisch berechnet werden
    }
}