<?php

namespace App\Livewire;

use App\Models\Participation;
use App\Models\SolarPlant;
use App\Models\Customer;
use App\Traits\HasPersistentTableState;
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
    use HasPersistentTableState;

    public SolarPlant $solarPlant;

    public function mount(SolarPlant $solarPlant): void
    {
        $this->solarPlant = $solarPlant;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Participation::query()
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
                    ->label('Beteiligung')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Beitritt')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter(),
                
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
                        Forms\Components\TextInput::make('percentage')
                            ->label('Beteiligung (%)')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->minValue(0.01)
                            ->maxValue(100)
                            ->placeholder('z.B. 25,50')
                            ->inputMode('decimal')
                            ->extraInputAttributes(['pattern' => '[0-9]+([,\.][0-9]+)?'])
                            ->helperText(function () {
                                $available = $this->solarPlant->available_participation;
                                $total = $this->solarPlant->total_participation;
                                return "Verfügbar: {$available}% (Gesamt: {$total}% von 100%)";
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
                    ])
                    ->using(function (array $data) {
                        return Participation::create([
                            'solar_plant_id' => $this->solarPlant->id,
                            'customer_id' => $data['customer_id'],
                            'percentage' => $data['percentage'],
                        ]);
                    })
                    ->successNotificationTitle('Beteiligung hinzugefügt')
                    ->modalHeading('Neue Beteiligung hinzufügen')
                    ->modalSubmitActionLabel('Beteiligung erstellen')
                    ->modalWidth('lg'),
            ])
            ->actions([
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
                        
                        Forms\Components\TextInput::make('percentage')
                            ->label('Beteiligung (%)')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->minValue(0.01)
                            ->maxValue(100)
                            ->placeholder('z.B. 25,50')
                            ->inputMode('decimal')
                            ->extraInputAttributes(['pattern' => '[0-9]+([,\.][0-9]+)?'])
                            ->helperText(function ($record) {
                                $currentPercentage = $record->percentage;
                                $otherParticipation = $this->solarPlant->participations()
                                    ->where('id', '!=', $record->id)
                                    ->sum('percentage');
                                $available = 100 - $otherParticipation;
                                return "Verfügbar (inkl. aktueller Beteiligung): {$available}% | Aktuelle Beteiligung: {$currentPercentage}%";
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
        return Participation::query()
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
