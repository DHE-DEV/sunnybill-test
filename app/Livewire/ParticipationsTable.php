<?php

namespace App\Livewire;

use App\Models\PlantParticipation;
use App\Models\SolarPlant;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ParticipationsTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public SolarPlant $solarPlant;

    public function mount(SolarPlant $solarPlant): void
    {
        $this->solarPlant = $solarPlant;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PlantParticipation::query()
                    ->where('solar_plant_id', $this->solarPlant->id)
                    ->with(['customer'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->description(fn ($record) => $record->customer?->customer_type === 'business' ? $record->customer?->company_name : null)
                    ->searchable(['customers.name', 'customers.company_name'])
                    ->sortable()
                    ->weight('medium')
                    ->color('primary')
                    ->url(fn ($record) => $record->customer ? route('filament.admin.resources.customers.view', $record->customer) : null)
                    ->openUrlInNewTab(false),
                
                Tables\Columns\TextColumn::make('customer.email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Keine E-Mail')
                    ->copyable()
                    ->color('gray')
                    ->url(fn ($record) => $record->customer?->email ? 'mailto:' . $record->customer->email : null)
                    ->openUrlInNewTab(false),
                
                Tables\Columns\TextColumn::make('customer.phone')
                    ->label('Telefon')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Keine Telefonnummer')
                    ->copyable()
                    ->color('gray')
                    ->url(fn ($record) => $record->customer?->phone ? 'tel:' . $record->customer->phone : null)
                    ->openUrlInNewTab(false),
                
                Tables\Columns\TextColumn::make('percentage')
                    ->label('Beteiligung (%)')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('participation_kwp')
                    ->label('Beteiligung (kWp)')
                    ->state(fn ($record) => number_format(($record->percentage / 100) * $this->solarPlant->total_capacity_kw, 3, ',', '.') . ' kWp')
                    ->sortable(false)
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Beitritt')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Letzte Änderung')
                    ->date('d.m.Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_type')
                    ->label('Kundentyp')
                    ->options([
                        'individual' => 'Privatperson',
                        'business' => 'Unternehmen',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => $query->whereHas('customer', 
                                fn (Builder $query) => $query->where('customer_type', $value)
                            ),
                        );
                    }),
                
                Tables\Filters\Filter::make('percentage_range')
                    ->label('Beteiligungsbereich')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('percentage_from')
                                    ->label('Von (%)')
                                    ->numeric()
                                    ->placeholder('0')
                                    ->step(0.01),
                                Forms\Components\TextInput::make('percentage_to')
                                    ->label('Bis (%)')
                                    ->numeric()
                                    ->placeholder('100')
                                    ->step(0.01),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['percentage_from'],
                                fn (Builder $query, $value): Builder => $query->where('percentage', '>=', $value),
                            )
                            ->when(
                                $data['percentage_to'],
                                fn (Builder $query, $value): Builder => $query->where('percentage', '<=', $value),
                            );
                    }),
                
                Tables\Filters\Filter::make('has_contact')
                    ->label('Kontaktdaten')
                    ->toggle()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['isActive'],
                            fn (Builder $query): Builder => $query->whereHas('customer', 
                                fn (Builder $query) => $query->where(function ($q) {
                                    $q->whereNotNull('email')
                                      ->orWhereNotNull('phone');
                                })
                            ),
                        );
                    }),
                
                Tables\Filters\Filter::make('created_at')
                    ->label('Beitrittsdatum')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Von')
                            ->placeholder('Startdatum'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Bis')
                            ->placeholder('Enddatum'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Neue Beteiligung')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->visible(fn () => $this->solarPlant->total_participation < 100)
                    ->form([
                        Forms\Components\Select::make('customer_id')
                            ->label('Kunde')
                            ->options(Customer::all()->mapWithKeys(function ($customer) {
                                $displayName = $customer->customer_type === 'business'
                                    ? ($customer->company_name ?: $customer->name)
                                    : $customer->name;
                                return [$customer->id => $displayName];
                            }))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('E-Mail')
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Telefon')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\Select::make('customer_type')
                                    ->label('Kundentyp')
                                    ->options([
                                        'individual' => 'Privatperson',
                                        'business' => 'Unternehmen',
                                    ])
                                    ->default('individual')
                                    ->required(),
                                Forms\Components\TextInput::make('company_name')
                                    ->label('Firmenname')
                                    ->maxLength(255)
                                    ->visible(fn (Forms\Get $get) => $get('customer_type') === 'business'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return Customer::create($data)->id;
                            }),
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
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state && $state > 0) {
                                            if ($this->solarPlant && $this->solarPlant->total_capacity_kw > 0) {
                                                $percentage = ($state / $this->solarPlant->total_capacity_kw) * 100;
                                                $set('percentage', round($percentage, 4));
                                            }
                                        }
                                    })
                                    ->helperText(function () {
                                        return $this->solarPlant ? "Anlagenkapazität: " . number_format($this->solarPlant->total_capacity_kw ?? 0, 4, ',', '.') . " kWp" : '';
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
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state && $state > 0) {
                                            if ($this->solarPlant && $this->solarPlant->total_capacity_kw > 0) {
                                                $kwp = ($state / 100) * $this->solarPlant->total_capacity_kw;
                                                $set('participation_kwp', round($kwp, 4));
                                            }
                                        }
                                    })
                                    ->helperText(function () {
                                        $available = $this->solarPlant->available_participation ?? 0;
                                        return "Verfügbar: " . number_format($available, 4, ',', '.') . "% (Gesamt: " . number_format($this->solarPlant->total_participation ?? 0, 4, ',', '.') . "% von 100%)";
                                    })
                                    ->dehydrateStateUsing(fn ($state) => str_replace(',', '.', $state))
                                    ->rules([
                                        function () {
                                            return function (string $attribute, $value, \Closure $fail) {
                                                // Komma durch Punkt ersetzen für Berechnung
                                                $numericValue = (float) str_replace(',', '.', $value);
                                                $existingParticipation = $this->solarPlant->participations()->sum('percentage');
                                                $totalParticipation = $existingParticipation + $numericValue;
                                                
                                                if ($totalParticipation > 100) {
                                                    $available = 100 - $existingParticipation;
                                                    $fail("Die Gesamtbeteiligung würde {$totalParticipation}% betragen. Maximal verfügbar: {$available}%");
                                                }
                                            };
                                        },
                                    ]),
                                
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('calculate_from_kwp')
                                        ->label('Aus kWp berechnen')
                                        ->icon('heroicon-m-calculator')
                                        ->color('info')
                                        ->action(function (Forms\Set $set, array $data) {
                                            $kwp = $data['participation_kwp'] ?? 0;
                                            if ($kwp && $kwp > 0) {
                                                if ($this->solarPlant && $this->solarPlant->total_capacity_kw > 0) {
                                                    $percentage = ($kwp / $this->solarPlant->total_capacity_kw) * 100;
                                                    $set('percentage', round($percentage, 4));
                                                }
                                            }
                                        }),
                                    Forms\Components\Actions\Action::make('calculate_from_percentage')
                                        ->label('Aus % berechnen')
                                        ->icon('heroicon-m-calculator')
                                        ->color('success')
                                        ->action(function (Forms\Set $set, array $data) {
                                            $percentage = $data['percentage'] ?? 0;
                                            if ($percentage && $percentage > 0) {
                                                if ($this->solarPlant && $this->solarPlant->total_capacity_kw > 0) {
                                                    $kwp = ($percentage / 100) * $this->solarPlant->total_capacity_kw;
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
                    ])
                    ->using(function (array $data) {
                        return PlantParticipation::create([
                            'solar_plant_id' => $this->solarPlant->id,
                            'customer_id' => $data['customer_id'],
                            'percentage' => $data['percentage'],
                            'participation_kwp' => $data['participation_kwp'] ?? null,
                            'eeg_compensation_per_kwh' => $data['eeg_compensation_per_kwh'] ?? null,
                        ]);
                    })
                    ->successNotificationTitle('Beteiligung hinzugefügt')
                    ->modalHeading('Neue Beteiligung hinzufügen')
                    ->modalSubmitActionLabel('Beteiligung erstellen')
                    ->modalWidth('lg'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => $record->customer ? route('filament.admin.resources.customers.view', $record->customer) : null)
                        ->openUrlInNewTab(false),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('customer_id')
                                ->label('Kunde')
                                ->options(Customer::all()->mapWithKeys(function ($customer) {
                                    $displayName = $customer->customer_type === 'business'
                                        ? ($customer->company_name ?: $customer->name)
                                        : $customer->name;
                                    return [$customer->id => $displayName];
                                }))
                                ->required()
                                ->searchable()
                                ->preload(),
                            
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
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            if ($state && $state > 0) {
                                                if ($this->solarPlant && $this->solarPlant->total_capacity_kw > 0) {
                                                    $percentage = ($state / $this->solarPlant->total_capacity_kw) * 100;
                                                    $set('percentage', round($percentage, 4));
                                                }
                                            }
                                        })
                                        ->helperText(function () {
                                            return $this->solarPlant ? "Anlagenkapazität: " . number_format($this->solarPlant->total_capacity_kw ?? 0, 4, ',', '.') . " kWp" : '';
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
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            if ($state && $state > 0) {
                                                if ($this->solarPlant && $this->solarPlant->total_capacity_kw > 0) {
                                                    $kwp = ($state / 100) * $this->solarPlant->total_capacity_kw;
                                                    $set('participation_kwp', round($kwp, 4));
                                                }
                                            }
                                        })
                                        ->helperText(function ($record) {
                                            $currentPercentage = $record->percentage;
                                            $otherParticipation = $this->solarPlant->participations()
                                                ->where('id', '!=', $record->id)
                                                ->sum('percentage');
                                            $available = 100 - $otherParticipation;
                                            return "Verfügbar (inkl. aktueller Beteiligung): " . number_format($available, 4, ',', '.') . "% | Aktuelle Beteiligung: " . number_format($currentPercentage, 4, ',', '.') . "%";
                                        })
                                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '.', $state))
                                        ->rules([
                                            function ($record) {
                                                return function (string $attribute, $value, \Closure $fail) use ($record) {
                                                    // Komma durch Punkt ersetzen für Berechnung
                                                    $numericValue = (float) str_replace(',', '.', $value);
                                                    $otherParticipation = $this->solarPlant->participations()
                                                        ->where('id', '!=', $record->id)
                                                        ->sum('percentage');
                                                    $totalParticipation = $otherParticipation + $numericValue;
                                                    
                                                    if ($totalParticipation > 100) {
                                                        $available = 100 - $otherParticipation;
                                                        $fail("Die Gesamtbeteiligung würde {$totalParticipation}% betragen. Maximal verfügbar: {$available}%");
                                                    }
                                                };
                                            },
                                        ]),
                                    
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('calculate_from_kwp')
                                            ->label('Aus kWp berechnen')
                                            ->icon('heroicon-m-calculator')
                                            ->color('info')
                                            ->action(function (Forms\Set $set, array $data) {
                                                $kwp = $data['participation_kwp'] ?? 0;
                                                if ($kwp && $kwp > 0) {
                                                    if ($this->solarPlant && $this->solarPlant->total_capacity_kw > 0) {
                                                        $percentage = ($kwp / $this->solarPlant->total_capacity_kw) * 100;
                                                        $set('percentage', round($percentage, 4));
                                                    }
                                                }
                                            }),
                                        Forms\Components\Actions\Action::make('calculate_from_percentage')
                                            ->label('Aus % berechnen')
                                            ->icon('heroicon-m-calculator')
                                            ->color('success')
                                            ->action(function (Forms\Set $set, array $data) {
                                                $percentage = $data['percentage'] ?? 0;
                                                if ($percentage && $percentage > 0) {
                                                    if ($this->solarPlant && $this->solarPlant->total_capacity_kw > 0) {
                                                        $kwp = ($percentage / 100) * $this->solarPlant->total_capacity_kw;
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
                        ])
                        ->successNotificationTitle('Beteiligung aktualisiert'),
                    
                    Tables\Actions\DeleteAction::make()
                        ->label('Löschen')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Beteiligung löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie diese Beteiligung löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, löschen')
                        ->successNotificationTitle('Beteiligung gelöscht'),
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
                        ->label('Ausgewählte löschen')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Beteiligungen löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Beteiligungen löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, löschen')
                        ->successNotificationTitle('Beteiligungen gelöscht'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->searchOnBlur()
            ->deferLoading()
            ->emptyStateHeading('Keine Beteiligungen vorhanden')
            ->emptyStateDescription('Es wurden noch keine Beteiligungen für diese Solaranlage erstellt.')
            ->emptyStateIcon('heroicon-o-users');
    }

    protected function getTableQuery(): Builder
    {
        return PlantParticipation::query()
            ->where('solar_plant_id', $this->solarPlant->id)
            ->with(['customer']);
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    protected function getTableName(): string
    {
        return 'participations-table-' . $this->solarPlant->id;
    }

    public function render(): View
    {
        return view('livewire.participations-table');
    }
}
