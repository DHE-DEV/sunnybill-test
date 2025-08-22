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
                
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Startdatum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Enddatum')
                    ->date('d.m.Y')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                
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
                
                Tables\Filters\Filter::make('is_active')
                    ->label('Nur aktive Beteiligungen')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
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
                                    }),
                            ])
                            ->columns(2),
                        
                        Forms\Components\TextInput::make('eeg_compensation_per_kwh')
                            ->label('Vertraglich zugesicherte EEG-Vergütung')
                            ->numeric()
                            ->step(0.000001)
                            ->minValue(0)
                            ->suffix('€/kWh')
                            ->placeholder('0,000000'),
                            
                        Forms\Components\Section::make('Zeitraum und Details')
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Startdatum')
                                    ->default(now())
                                    ->required(),
                                
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Enddatum')
                                    ->nullable(),
                                
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true),
                            ])
                            ->columns(3),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->nullable()
                            ->rows(3)
                            ->placeholder('Zusätzliche Informationen zur Beteiligung...'),
                    ])
                    ->using(function (array $data) {
                        return PlantParticipation::create([
                            'solar_plant_id' => $this->solarPlant->id,
                            'customer_id' => $data['customer_id'],
                            'percentage' => $data['percentage'],
                            'participation_kwp' => $data['participation_kwp'] ?? null,
                            'eeg_compensation_per_kwh' => $data['eeg_compensation_per_kwh'] ?? null,
                            'start_date' => $data['start_date'] ?? null,
                            'end_date' => $data['end_date'] ?? null,
                            'is_active' => $data['is_active'] ?? true,
                            'notes' => $data['notes'] ?? null,
                        ]);
                    })
                    ->successNotificationTitle('Beteiligung hinzugefügt')
                    ->modalHeading('Neue Beteiligung hinzufügen')
                    ->modalSubmitActionLabel('Beteiligung erstellen')
                    ->modalWidth('6xl'),
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
                                        }),
                                ])
                                ->columns(2),
                            
                            Forms\Components\TextInput::make('eeg_compensation_per_kwh')
                                ->label('Vertraglich zugesicherte EEG-Vergütung')
                                ->numeric()
                                ->step(0.000001)
                                ->minValue(0)
                                ->suffix('€/kWh')
                                ->placeholder('0,000000'),
                            
                            Forms\Components\Section::make('Zeitraum und Details')
                                ->schema([
                                    Forms\Components\DatePicker::make('start_date')
                                        ->label('Startdatum')
                                        ->required(),
                                    
                                    Forms\Components\DatePicker::make('end_date')
                                        ->label('Enddatum')
                                        ->nullable(),
                                    
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Aktiv')
                                        ->default(true),
                                ])
                                ->columns(3),
                            
                            Forms\Components\Textarea::make('notes')
                                ->label('Notizen')
                                ->nullable()
                                ->rows(3)
                                ->placeholder('Zusätzliche Informationen zur Beteiligung...'),
                        ])
                        ->modalWidth('6xl')
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
