<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SolarParticipationsRelationManager extends RelationManager
{
    protected static string $relationship = 'plantParticipations';

    protected static ?string $title = 'Solar-Beteiligungen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('solar_plant_id')
                    ->label('Solaranlage')
                    ->relationship('solarPlant', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->columnSpanFull(),
                
                Forms\Components\Section::make('Beteiligungsdetails')
                    ->schema([
                        Forms\Components\TextInput::make('participation_kwp')
                            ->label('Beteiligung kWp')
                            ->numeric()
                            ->step(0.0001)
                            ->minValue(0)
                            ->suffix('kWp')
                            ->placeholder('z.B. 25,0000')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, $livewire) {
                                if ($state && $state > 0) {
                                    $solarPlantId = $get('solar_plant_id');
                                    if ($solarPlantId) {
                                        $solarPlant = \App\Models\SolarPlant::find($solarPlantId);
                                        if ($solarPlant && $solarPlant->total_capacity_kw > 0) {
                                            $percentage = ($state / $solarPlant->total_capacity_kw) * 100;
                                            $set('percentage', round($percentage, 4));
                                        }
                                    }
                                }
                            })
                            ->helperText(function (Forms\Get $get) {
                                $solarPlantId = $get('solar_plant_id');
                                if ($solarPlantId) {
                                    $solarPlant = \App\Models\SolarPlant::find($solarPlantId);
                                    return $solarPlant ? "Anlagenkapazität: " . number_format($solarPlant->total_capacity_kw ?? 0, 4, ',', '.') . " kWp" : '';
                                }
                                return 'Bitte zuerst Solaranlage auswählen';
                            }),
                        
                        Forms\Components\TextInput::make('percentage')
                            ->label('Beteiligung (%)')
                            ->required()
                            ->numeric()
                            ->step(0.0001)
                            ->suffix('%')
                            ->minValue(0.0001)
                            ->maxValue(100)
                            ->placeholder('z.B. 25,5000')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, $livewire) {
                                if ($state && $state > 0) {
                                    $solarPlantId = $get('solar_plant_id');
                                    if ($solarPlantId) {
                                        $solarPlant = \App\Models\SolarPlant::find($solarPlantId);
                                        if ($solarPlant && $solarPlant->total_capacity_kw > 0) {
                                            $kwp = ($state / 100) * $solarPlant->total_capacity_kw;
                                            $set('participation_kwp', round($kwp, 4));
                                        }
                                    }
                                }
                            })
                            ->helperText(function (Forms\Get $get, $livewire) {
                                $solarPlantId = $get('solar_plant_id');
                                if ($solarPlantId) {
                                    $solarPlant = \App\Models\SolarPlant::find($solarPlantId);
                                    if ($solarPlant) {
                                        $currentRecord = $livewire->mountedTableActionRecord ?? null;
                                        $existingParticipation = $solarPlant->participations()
                                            ->where('id', '!=', $currentRecord?->id ?? 0)
                                            ->sum('percentage');
                                        $available = 100 - $existingParticipation;
                                        return "Verfügbar: " . number_format($available, 4, ',', '.') . "% (Gesamt: " . number_format($solarPlant->total_participation ?? 0, 4, ',', '.') . "% von 100%)";
                                    }
                                }
                                return 'Bitte zuerst Solaranlage auswählen';
                            })
                            ->rules([
                                function (Forms\Get $get, $livewire) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $livewire) {
                                        $solarPlantId = $get('solar_plant_id');
                                        if ($solarPlantId) {
                                            $solarPlant = \App\Models\SolarPlant::find($solarPlantId);
                                            if ($solarPlant) {
                                                $currentRecord = $livewire->mountedTableActionRecord ?? null;
                                                $existingParticipation = $solarPlant->participations()
                                                    ->where('id', '!=', $currentRecord?->id ?? 0)
                                                    ->sum('percentage');
                                                
                                                $totalParticipation = $existingParticipation + $value;
                                                
                                                if ($totalParticipation > 100) {
                                                    $available = 100 - $existingParticipation;
                                                    $fail("Die Gesamtbeteiligung würde " . number_format($totalParticipation, 4, ',', '.') . "% betragen. Maximal verfügbar: " . number_format($available, 4, ',', '.') . "%");
                                                }
                                            }
                                        }
                                    };
                                },
                            ]),
                        
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('calculate_from_kwp')
                                ->label('Aus kWp berechnen')
                                ->icon('heroicon-m-calculator')
                                ->color('info')
                                ->action(function (Forms\Set $set, Forms\Get $get, array $data) {
                                    $kwp = $data['participation_kwp'] ?? 0;
                                    $solarPlantId = $get('solar_plant_id');
                                    if ($kwp && $kwp > 0 && $solarPlantId) {
                                        $solarPlant = \App\Models\SolarPlant::find($solarPlantId);
                                        if ($solarPlant && $solarPlant->total_capacity_kw > 0) {
                                            $percentage = ($kwp / $solarPlant->total_capacity_kw) * 100;
                                            $set('percentage', round($percentage, 4));
                                        }
                                    }
                                }),
                            Forms\Components\Actions\Action::make('calculate_from_percentage')
                                ->label('Aus % berechnen')
                                ->icon('heroicon-m-calculator')
                                ->color('success')
                                ->action(function (Forms\Set $set, Forms\Get $get, array $data) {
                                    $percentage = $data['percentage'] ?? 0;
                                    $solarPlantId = $get('solar_plant_id');
                                    if ($percentage && $percentage > 0 && $solarPlantId) {
                                        $solarPlant = \App\Models\SolarPlant::find($solarPlantId);
                                        if ($solarPlant && $solarPlant->total_capacity_kw > 0) {
                                            $kwp = ($percentage / 100) * $solarPlant->total_capacity_kw;
                                            $set('participation_kwp', round($kwp, 4));
                                        }
                                    }
                                }),
                        ])
                        ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\TextInput::make('eeg_compensation_per_kwh')
                    ->label('Vertraglich zugesicherte EEG-Vergütung')
                    ->numeric()
                    ->step(0.000001)
                    ->minValue(0)
                    ->suffix('€/kWh')
                    ->placeholder('0,000000')
                    ->helperText('Vergütung pro kWh in EUR mit bis zu 6 Nachkommastellen'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('solarPlant.name')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('solarPlant'))
            ->columns([
                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Solaranlage')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('solarPlant.location')
                    ->label('Standort')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('percentage')
                    ->label('Beteiligung (%)')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('participation_kwp')
                    ->label('Beteiligung (kWp)')
                    ->state(fn ($record) => $record->solarPlant ? 
                        number_format(($record->percentage / 100) * $record->solarPlant->total_capacity_kw, 3, ',', '.') . ' kWp'
                        : '-')
                    ->sortable(false)
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('solarPlant.total_capacity_kw')
                    ->label('Anlagenleistung')
                    ->formatStateUsing(fn ($state) => number_format($state, 3, ',', '.') . ' kWp')
                    ->sortable(),
                Tables\Columns\TextColumn::make('solarPlant.status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'planned' => 'Geplant',
                        'in_planning' => 'In Planung',
                        'under_construction' => 'Im Bau',
                        'awaiting_commissioning' => 'Wartet auf Inbetriebnahme',
                        'active' => 'Aktiv',
                        'maintenance' => 'Wartung',
                        'inactive' => 'Inaktiv',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'planned' => 'gray',
                        'in_planning' => 'gray',
                        'under_construction' => 'warning',
                        'awaiting_commissioning' => 'warning',
                        'active' => 'success',
                        'maintenance' => 'info',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Beteiligung seit')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('solarPlant.status')
                    ->label('Anlagenstatus')
                    ->relationship('solarPlant', 'status')
                    ->options([
                        'planned' => 'Geplant',
                        'in_planning' => 'In Planung',
                        'under_construction' => 'Im Bau',
                        'awaiting_commissioning' => 'Wartet auf Inbetriebnahme',
                        'active' => 'Aktiv',
                        'maintenance' => 'Wartung',
                        'inactive' => 'Inaktiv',
                    ]),
                Tables\Filters\Filter::make('high_participation')
                    ->label('Hohe Beteiligung (>= 25%)')
                    ->query(fn (Builder $query): Builder => $query->where('percentage', '>=', 25)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Beteiligung hinzufügen'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->url(fn ($record) => $record->solarPlant ?
                            route('filament.admin.resources.solar-plants.view', ['record' => $record->solarPlant->id]) :
                            '#'
                        )
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => $record->solarPlant !== null),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->icon('heroicon-o-cog-6-tooth')
                ->tooltip('Aktionen')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine Beteiligungen')
            ->emptyStateDescription('Dieser Kunde hat noch keine Solar-Beteiligungen.')
            ->emptyStateIcon('heroicon-o-sun');
    }
}
