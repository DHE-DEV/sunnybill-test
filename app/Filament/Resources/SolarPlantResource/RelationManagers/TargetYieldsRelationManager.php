<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

use App\Models\SolarPlantTargetYield;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TargetYieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'targetYields';

    protected static ?string $title = 'SOLL kWh';

    protected static ?string $modelLabel = 'SOLL-Ertrag';

    protected static ?string $pluralModelLabel = 'SOLL-Erträge';

    protected static ?string $icon = 'heroicon-o-chart-bar';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('SOLL-Erträge pro Monat')
                    ->description('Erfassen Sie die erwarteten kWh-Werte für jeden Monat des Jahres')
                    ->schema([
                        Forms\Components\Select::make('year')
                            ->label('Jahr')
                            ->options(function () {
                                $currentYear = now()->year;
                                $years = [];
                                for ($year = $currentYear - 2; $year <= $currentYear + 5; $year++) {
                                    $years[$year] = $year;
                                }
                                return $years;
                            })
                            ->default(now()->year)
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('january_kwh')
                                    ->label('Januar')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kWh')
                                    ->minValue(0)
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('february_kwh')
                                    ->label('Februar')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kWh')
                                    ->minValue(0)
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('march_kwh')
                                    ->label('März')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kWh')
                                    ->minValue(0)
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('april_kwh')
                                    ->label('April')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kWh')
                                    ->minValue(0)
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('may_kwh')
                                    ->label('Mai')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kWh')
                                    ->minValue(0)
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('june_kwh')
                                    ->label('Juni')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kWh')
                                    ->minValue(0)
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('july_kwh')
                                    ->label('Juli')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kWh')
                                    ->minValue(0)
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('august_kwh')
                                    ->label('August')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kWh')
                                    ->minValue(0)
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('september_kwh')
                                    ->label('September')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kWh')
                                    ->minValue(0)
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('october_kwh')
                                    ->label('Oktober')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kWh')
                                    ->minValue(0)
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('november_kwh')
                                    ->label('November')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kWh')
                                    ->minValue(0)
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('december_kwh')
                                    ->label('Dezember')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('kWh')
                                    ->minValue(0)
                                    ->placeholder('0.00'),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->placeholder('Zusätzliche Informationen zu den SOLL-Werten...')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('year')
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->label('Jahr')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('january_kwh')
                    ->label('Jan')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('february_kwh')
                    ->label('Feb')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('march_kwh')
                    ->label('Mär')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('april_kwh')
                    ->label('Apr')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('may_kwh')
                    ->label('Mai')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('june_kwh')
                    ->label('Jun')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('july_kwh')
                    ->label('Jul')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('august_kwh')
                    ->label('Aug')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('september_kwh')
                    ->label('Sep')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('october_kwh')
                    ->label('Okt')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('november_kwh')
                    ->label('Nov')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('december_kwh')
                    ->label('Dez')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : '-')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_yearly_target')
                    ->label('Gesamt')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.') . ' kWh')
                    ->weight('bold')
                    ->color('success')
                    ->alignEnd(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Jahr')
                    ->options(function () {
                        $currentYear = now()->year;
                        $years = [];
                        for ($year = $currentYear - 5; $year <= $currentYear + 5; $year++) {
                            $years[$year] = $year;
                        }
                        return $years;
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('SOLL-Werte hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['solar_plant_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten'),
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplizieren')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('target_year')
                                ->label('Ziel-Jahr')
                                ->options(function () {
                                    $currentYear = now()->year;
                                    $years = [];
                                    for ($year = $currentYear - 2; $year <= $currentYear + 5; $year++) {
                                        $years[$year] = $year;
                                    }
                                    return $years;
                                })
                                ->default(now()->year + 1)
                                ->required()
                                ->helperText('Jahr, in das die SOLL-Werte kopiert werden sollen'),
                        ])
                        ->action(function (SolarPlantTargetYield $record, array $data): void {
                            $targetYear = $data['target_year'];
                            
                            // Prüfe ob bereits ein Datensatz für das Ziel-Jahr existiert
                            $existing = SolarPlantTargetYield::where('solar_plant_id', $record->solar_plant_id)
                                ->where('year', $targetYear)
                                ->first();
                            
                            if ($existing) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Fehler beim Duplizieren')
                                    ->body("Für das Jahr {$targetYear} existieren bereits SOLL-Werte.")
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            // Erstelle neuen Datensatz mit kopierten Werten
                            SolarPlantTargetYield::create([
                                'solar_plant_id' => $record->solar_plant_id,
                                'year' => $targetYear,
                                'january_kwh' => $record->january_kwh,
                                'february_kwh' => $record->february_kwh,
                                'march_kwh' => $record->march_kwh,
                                'april_kwh' => $record->april_kwh,
                                'may_kwh' => $record->may_kwh,
                                'june_kwh' => $record->june_kwh,
                                'july_kwh' => $record->july_kwh,
                                'august_kwh' => $record->august_kwh,
                                'september_kwh' => $record->september_kwh,
                                'october_kwh' => $record->october_kwh,
                                'november_kwh' => $record->november_kwh,
                                'december_kwh' => $record->december_kwh,
                                'notes' => $record->notes ? "Kopiert von Jahr {$record->year}: " . $record->notes : "Kopiert von Jahr {$record->year}",
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Erfolgreich dupliziert')
                                ->body("SOLL-Werte von {$record->year} wurden nach {$targetYear} kopiert.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('SOLL-Werte duplizieren')
                        ->modalDescription('Kopieren Sie die SOLL-Werte dieses Jahres in ein anderes Jahr.')
                        ->modalSubmitActionLabel('Duplizieren'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Löschen'),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Ausgewählte löschen'),
                ]),
            ])
            ->defaultSort('year', 'desc')
            ->emptyStateHeading('Keine SOLL-Werte erfasst')
            ->emptyStateDescription('Erfassen Sie SOLL-Erträge für verschiedene Jahre.')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }
}