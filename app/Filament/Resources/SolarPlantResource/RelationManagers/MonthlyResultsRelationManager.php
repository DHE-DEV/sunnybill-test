<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

use App\Traits\HasPersistentTableState;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class MonthlyResultsRelationManager extends RelationManager
{
    use HasPersistentTableState;
    
    protected static string $relationship = 'monthlyResults';

    protected static ?string $title = 'Abrechnungen';
    
    public function isReadOnly(): bool
    {
        return false; // Erlaube Aktionen auch im View-Modus
    }
    
    public function canCreate(): bool
    {
        return true;
    }
    
    public function canEdit($record): bool
    {
        return true;
    }
    
    public function canDelete($record): bool
    {
        return true;
    }
    
    public function canViewAny(): bool
    {
        return true;
    }
    
    public function canView($record): bool
    {
        return true;
    }
    
    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
    
    protected function canCreateAny(): bool
    {
        return true;
    }
    
    protected function canEditAny(): bool
    {
        return true;
    }
    
    protected function canDeleteAny(): bool
    {
        return true;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->monthlyResults()->count();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('year')
                            ->label('Jahr')
                            ->required()
                            ->options(function () {
                                $currentYear = now()->year;
                                $years = [];
                                for ($i = $currentYear - 5; $i <= $currentYear + 2; $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            })
                            ->default(now()->year)
                            ->reactive(),
                        Forms\Components\Select::make('month_number')
                            ->label('Monat')
                            ->required()
                            ->options([
                                1 => 'Januar',
                                2 => 'Februar',
                                3 => 'März',
                                4 => 'April',
                                5 => 'Mai',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'August',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Dezember',
                            ])
                            ->default(now()->month)
                            ->reactive(),
                    ]),
                Forms\Components\TextInput::make('energy_produced_kwh')
                    ->label('Produzierte Energie (kWh)')
                    ->required()
                    ->numeric()
                    ->step(0.000001)
                    ->suffix('kWh')
                    ->minValue(0)
                    ->placeholder('z.B. 1250.500000')
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $solarPlant = $this->getOwnerRecord();
                        if ($state && $solarPlant && $solarPlant->feed_in_tariff_per_kwh) {
                            $totalRevenue = $state * $solarPlant->feed_in_tariff_per_kwh;
                            $set('total_revenue', $totalRevenue);
                        }
                    }),
                Forms\Components\TextInput::make('feed_in_tariff_display')
                    ->label('Einspeisevergütung (€/kWh)')
                    ->disabled()
                    ->dehydrated(false)
                    ->default(function () {
                        $solarPlant = $this->getOwnerRecord();
                        return $solarPlant ? number_format($solarPlant->feed_in_tariff_per_kwh, 6, ',', '.') . ' €/kWh' : 'Nicht hinterlegt';
                    })
                    ->helperText('Aus den Stammdaten der Solaranlage'),
                Forms\Components\TextInput::make('total_revenue')
                    ->label('Gesamtsumme (€)')
                    ->disabled()
                    ->dehydrated()
                    ->prefix('€')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 6, ',', '.') : '0.000000')
                    ->dehydrateStateUsing(fn ($state) => $state ? (float) str_replace(',', '.', str_replace('.', '', $state)) : 0)
                    ->helperText('Wird automatisch berechnet: Produzierte Energie × Einspeisevergütung'),
                Forms\Components\Select::make('billing_type')
                    ->label('Abrechnungstyp')
                    ->options([
                        'invoice' => 'Rechnung',
                        'credit_note' => 'Gutschrift',
                    ])
                    ->default('invoice')
                    ->required()
                    ->helperText('Handelt es sich um eine Rechnung oder Gutschrift?'),
                Forms\Components\Hidden::make('month')
                    ->dehydrateStateUsing(function (Forms\Get $get) {
                        $year = $get('year');
                        $month = $get('month_number');
                        return $year && $month ? sprintf('%04d-%02d-01', $year, $month) : null;
                    })
                    ->rules([
                        function (Forms\Get $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                $year = $get('year');
                                $monthNumber = $get('month_number');
                                
                                if ($year && $monthNumber) {
                                    $monthDate = sprintf('%04d-%02d-01', $year, $monthNumber);
                                    $solarPlantId = $this->getOwnerRecord()->id;
                                    
                                    $exists = \App\Models\PlantMonthlyResult::where('solar_plant_id', $solarPlantId)
                                        ->where('month', $monthDate)
                                        ->exists();
                                    
                                    if ($exists) {
                                        $fail('Für diesen Monat existiert bereits ein Ergebnis.');
                                    }
                                }
                            };
                        }
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('month')
            ->columns([
                Tables\Columns\TextColumn::make('month')
                    ->label('Monat')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('F Y'))
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('energy_produced_kwh')
                    ->label('Produzierte Energie')
                    ->formatStateUsing(fn ($state) => number_format($state, 6, ',', '.') . ' kWh')
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('feed_in_tariff')
                    ->label('Einspeisevergütung')
                    ->getStateUsing(fn ($record) => $record->solarPlant->feed_in_tariff_per_kwh)
                    ->formatStateUsing(fn ($state) => number_format($state, 6, ',', '.') . ' €/kWh')
                    ->color('info'),
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Gesamtsumme')
                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 6, ',', '.'))
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('billing_type')
                    ->label('Typ')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'invoice' => 'Rechnung',
                        'credit_note' => 'Gutschrift',
                        default => $state ?? 'Rechnung',
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'invoice' => 'primary',
                        'credit_note' => 'warning',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erfasst am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('current_year')
                    ->label('Aktuelles Jahr')
                    ->query(fn (Builder $query): Builder => $query->whereYear('month', now()->year))
                    ->default(),
                Tables\Filters\Filter::make('high_production')
                    ->label('Hohe Produktion (>1000 kWh)')
                    ->query(fn (Builder $query): Builder => $query->where('energy_produced_kwh', '>', 1000)),
                Tables\Filters\SelectFilter::make('billing_type')
                    ->label('Abrechnungstyp')
                    ->options([
                        'invoice' => 'Rechnung',
                        'credit_note' => 'Gutschrift',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Abrechnung hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->color('warning')
                    ->modalHeading('Neue monatliche Abrechnung')
                    ->modalDescription('Erfassen Sie die produzierte Energie für einen bestimmten Monat.')
                    ->modalSubmitActionLabel('Abrechnung speichern')
                    ->modalCancelActionLabel('Abbrechen')
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Monatliche Abrechnung gespeichert')
                            ->body('Die Kundengutschriften wurden automatisch berechnet und erstellt.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Details'),
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Ergebnis aktualisiert')
                            ->body('Die Kundengutschriften wurden neu berechnet.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Notification::make()
                            ->title('Ergebnis gelöscht')
                            ->body('Das monatliche Ergebnis und die zugehörigen Gutschriften wurden entfernt.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('month', 'desc')
            ->emptyStateHeading('Keine Ergebnisse')
            ->emptyStateDescription('Fügen Sie monatliche Ergebnisse hinzu, um Kundengutschriften zu berechnen.')
            ->emptyStateIcon('heroicon-o-chart-bar')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Erste Abrechnung hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->color('warning')
                    ->modalHeading('Neue monatliche Abrechnung')
                    ->modalDescription('Erfassen Sie die produzierte Energie für einen bestimmten Monat.')
                    ->modalSubmitActionLabel('Abrechnung speichern')
                    ->modalCancelActionLabel('Abbrechen')
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Monatliche Abrechnung gespeichert')
                            ->body('Die Kundengutschriften wurden automatisch berechnet und erstellt.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
