<?php

namespace App\Filament\Resources\SolarPlantResource\Pages;

use App\Filament\Resources\SolarPlantResource;
use App\Filament\Resources\SolarPlantResource\RelationManagers;
use App\Traits\HasPersistentInfolistState;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Forms;
use Filament\Notifications\Notification;
use App\Models\Customer;

class ViewSolarPlant extends ViewRecord
{
    use HasPersistentInfolistState;
    
    protected static string $resource = SolarPlantResource::class;
    

    public function getRelationManagers(): array
    {
        return [
            RelationManagers\ArticlesRelationManager::class,
            RelationManagers\ParticipationsRelationManager::class,
            RelationManagers\BillingsRelationManager::class,
            RelationManagers\MonthlyResultsRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\ContractsRelationManager::class,
            RelationManagers\SuppliersRelationManager::class,
            RelationManagers\MilestonesRelationManager::class,
        ];
    }


    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Lade die Beteiligungen mit den Kunden
        $this->record->load('participations.customer');
        return $data;
    }

    public function getTitle(): string
    {
        $plant = $this->record;
        return "Solaranlage Details - {$plant->plant_number} - {$plant->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Bearbeiten'),
            Actions\DeleteAction::make()
                ->label('Löschen'),
        ];
    }


    public function infolist(Infolist $infolist): Infolist
    {
        // Lade gespeicherte Infolist-Zustände
        $savedState = $this->loadInfolistState();
        
        return $infolist
            ->extraAttributes(['data-table-name' => $this->getInfolistTableName()])
            ->schema([
                Infolists\Components\Section::make('Übersicht')
                    ->id('overview')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Anlagenname')
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('primary'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'in_planning' => 'In Planung',
                                        'planned' => 'Geplant',
                                        'under_construction' => 'Im Bau',
                                        'awaiting_commissioning' => 'Warte auf Inbetriebnahme',
                                        'active' => 'Aktiv',
                                        'maintenance' => 'Wartung',
                                        'inactive' => 'Inaktiv',
                                        default => $state,
                                    })
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($state) => match($state) {
                                        'in_planning' => 'gray',
                                        'planned' => 'info',
                                        'under_construction' => 'warning',
                                        'awaiting_commissioning' => 'primary',
                                        'active' => 'success',
                                        'maintenance' => 'info',
                                        'inactive' => 'danger',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('total_capacity_kw')
                                    ->label('Gesamtleistung')
                                    ->formatStateUsing(fn ($state) => number_format($state, 3, ',', '.') . ' kWp')
                                    ->badge()
                                    ->size('lg')
                                    ->color('success'),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['overview'] ?? false)
                    ->extraAttributes(['data-section-id' => 'overview']),
                Infolists\Components\Tabs::make('Tabs')
                    ->extraAttributes(['class' => 'solar-plant-detail'])
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('Übersicht')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Infolists\Components\Section::make('Standort & Status')
                                    ->id('location-status')
                                    ->icon('heroicon-o-map-pin')
                                    ->schema([
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('location')
                                                    ->label('Standort')
                                                    ->icon('heroicon-o-map-pin')
                                                    ->weight('medium')
                                                    ->size('lg'),
                                                Infolists\Components\IconEntry::make('is_active')
                                                    ->label('Betriebsbereit')
                                                    ->boolean()
                                                    ->trueIcon('heroicon-o-check-circle')
                                                    ->falseIcon('heroicon-o-x-circle')
                                                    ->trueColor('success')
                                                    ->falseColor('danger'),
                                            ]),
                                        Infolists\Components\Grid::make(4)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('mastr_number_unit')
                                                    ->label('MaStR-Nr. der Einheit')
                                                    ->placeholder('Nicht hinterlegt')
                                                    ->copyable()
                                                    ->badge()
                                                    ->color('info'),
                                                Infolists\Components\TextEntry::make('mastr_registration_date_unit')
                                                    ->label('Registrierungsdatum der Einheit')
                                                    ->date('d.m.Y')
                                                    ->placeholder('Nicht hinterlegt'),
                                                Infolists\Components\TextEntry::make('mastr_number_eeg_plant')
                                                    ->label('MaStR-Nr. der EEG-Anlage')
                                                    ->placeholder('Nicht hinterlegt')
                                                    ->copyable()
                                                    ->badge()
                                                    ->color('success'),
                                                Infolists\Components\TextEntry::make('commissioning_date_eeg_plant')
                                                    ->label('Inbetriebnahme der EEG-Anlage')
                                                    ->date('d.m.Y')
                                                    ->placeholder('Nicht hinterlegt'),
                                            ]),
                                        Infolists\Components\Grid::make(4)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('melo_id')
                                                    ->label('MeLo-ID')
                                                    ->placeholder('Nicht hinterlegt')
                                                    ->copyable()
                                                    ->badge()
                                                    ->color('primary'),
                                                Infolists\Components\TextEntry::make('malo_id')
                                                    ->label('MaLo-ID')
                                                    ->placeholder('Nicht hinterlegt')
                                                    ->copyable()
                                                    ->badge()
                                                    ->color('primary'),
                                                Infolists\Components\TextEntry::make('vnb_process_number')
                                                    ->label('VNB-Vorgangsnummer')
                                                    ->placeholder('Nicht hinterlegt')
                                                    ->copyable()
                                                    ->badge()
                                                    ->color('warning'),
                                                Infolists\Components\TextEntry::make('pv_soll_project_number')
                                                    ->label('PV-Soll Projektnummer')
                                                    ->placeholder('Nicht hinterlegt')
                                                    ->copyable()
                                                    ->badge()
                                                    ->color('success'),
                                            ]),
                                        Infolists\Components\Grid::make(4)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('commissioning_date_unit')
                                                    ->label('Datum der Inbetriebsetzung')
                                                    ->date('d.m.Y')
                                                    ->placeholder('Nicht hinterlegt'),
                                            ]),
                                    ])
                                    ->headerActions([
                                        Infolists\Components\Actions\Action::make('calculate_route')
                                            ->label('Route berechnen')
                                            ->icon('heroicon-o-map')
                                            ->color('success')
                                            ->url(function ($record) {
                                                // Verwende Adresse wenn vorhanden, sonst Koordinaten
                                                if (!empty($record->location)) {
                                                    $destination = urlencode($record->location);
                                                    return 'https://www.google.com/maps/dir//' . $destination;
                                                } elseif ($record->hasCoordinates()) {
                                                    $destination = $record->latitude . ',' . $record->longitude;
                                                    return 'https://www.google.com/maps/dir//' . $destination;
                                                } else {
                                                    // Fallback: Verwende den Namen der Anlage für die Suche
                                                    $destination = urlencode($record->name);
                                                    return 'https://www.google.com/maps/search/' . $destination;
                                                }
                                            })
                                            ->openUrlInNewTab()
                                            ->visible(true),
                                        Infolists\Components\Actions\Action::make('show_map')
                                            ->label('Karte anzeigen')
                                            ->icon('heroicon-o-map-pin')
                                            ->color('primary')
                                            ->modalHeading(fn ($record) => 'Standort: ' . $record->name)
                                            ->modalContent(fn ($record) => view('filament.infolists.components.openstreetmap-modal', [
                                                'latitude' => $record->latitude,
                                                'longitude' => $record->longitude,
                                                'name' => $record->name,
                                                'location' => $record->location,
                                                'hasCoordinates' => $record->hasCoordinates(),
                                            ]))
                                            ->modalWidth('7xl')
                                            ->modalSubmitAction(false)
                                            ->modalCancelAction(false)
                                    ])
                                    ->compact()
                                    ->collapsible()
                                    ->collapsed($savedState['location-status'] ?? false)
                                    ->extraAttributes(['data-section-id' => 'location-status']),
                                Infolists\Components\Section::make('Beschreibung')
                                    ->id('description')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('description')
                                            ->label('')
                                            ->placeholder('Keine Beschreibung vorhanden')
                                            ->prose()
                                            ->markdown(),
                                    ])
                                    ->compact()
                                    ->collapsible()
                                    ->collapsed($savedState['description'] ?? true)
                                    ->extraAttributes(['data-section-id' => 'description']),
                            ]),
                        Infolists\Components\Tabs\Tab::make('Technische Daten')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Infolists\Components\Section::make('Anlagenkomponenten')
                                    ->id('components')
                                    ->icon('heroicon-o-squares-2x2')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('panel_count')
                                                    ->label('Solarmodule')
                                                    ->placeholder('Nicht angegeben')
                                                    ->badge()
                                                    ->size('lg')
                                                    ->color('info'),
                                                Infolists\Components\TextEntry::make('inverter_count')
                                                    ->label('Wechselrichter')
                                                    ->placeholder('Nicht angegeben')
                                                    ->badge()
                                                    ->size('lg')
                                                    ->color('info'),
                                                Infolists\Components\TextEntry::make('battery_capacity_kwh')
                                                    ->label('Batteriekapazität')
                                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1, ',', '.') . ' kWh' : 'Keine Batterie')
                                                    ->badge()
                                                    ->size('lg')
                                                    ->color(fn ($state) => $state ? 'warning' : 'gray'),
                                            ]),
                                    ])
                                    ->compact()
                                    ->collapsible()
                                    ->collapsed($savedState['components'] ?? false)
                                    ->extraAttributes(['data-section-id' => 'components']),
                            ]),
                        Infolists\Components\Tabs\Tab::make('Projekttermine')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Infolists\Components\Section::make('Wichtige Termine')
                                    ->icon('heroicon-o-clock')
                                    ->schema([
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('installation_date')
                                                    ->label('Installationsdatum')
                                                    ->date('d.m.Y')
                                                    ->icon('heroicon-o-wrench-screwdriver')
                                                    ->color('primary')
                                                    ->size('lg')
                                                    ->weight('medium'),
                                                Infolists\Components\TextEntry::make('commissioning_date')
                                                    ->label('Inbetriebnahme')
                                                    ->date('d.m.Y')
                                                    ->icon('heroicon-o-bolt')
                                                    ->placeholder('Noch nicht in Betrieb')
                                                    ->color('success')
                                                    ->size('lg')
                                                    ->weight('medium'),
                                            ]),
                                    ])
                                    ->compact()
                                    ->collapsible()
                                    ->collapsed(false),
                            ]),
                        Infolists\Components\Tabs\Tab::make('Finanzen')
                            ->icon('heroicon-o-currency-euro')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\Section::make('Investition')
                                            ->icon('heroicon-o-banknotes')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('total_investment')
                                                    ->label('Gesamtinvestition')
                                                    ->formatStateUsing(fn ($state) => $state ? '€ ' . number_format($state, 0, ',', '.') : 'Nicht angegeben')
                                                    ->size('xl')
                                                    ->weight('bold')
                                                    ->color('primary'),
                                                Infolists\Components\TextEntry::make('annual_operating_costs')
                                                    ->label('Jährliche Betriebskosten')
                                                    ->formatStateUsing(fn ($state) => $state ? '€ ' . number_format($state, 0, ',', '.') : 'Nicht angegeben')
                                                    ->color('warning'),
                                            ])
                                            ->compact()
                                            ->collapsible()
                                            ->collapsed(false),
                                        Infolists\Components\Section::make('Tarife')
                                            ->icon('heroicon-o-calculator')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('feed_in_tariff_per_kwh')
                                                    ->label('Einspeisevergütung')
                                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 4, ',', '.') . ' ct/kWh' : 'Nicht angegeben')
                                                    ->badge()
                                                    ->color('success'),
                                                Infolists\Components\TextEntry::make('electricity_price_per_kwh')
                                                    ->label('Strompreis')
                                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 4, ',', '.') . ' ct/kWh' : 'Nicht angegeben')
                                                    ->badge()
                                                    ->color('info'),
                                            ])
                                            ->compact()
                                            ->collapsible()
                                            ->collapsed(false),
                                        Infolists\Components\Section::make('Ertragsprognose')
                                            ->icon('heroicon-o-chart-bar')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('expected_annual_yield_kwh')
                                                    ->label('Erwarteter Jahresertrag')
                                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : 'Nicht angegeben')
                                                    ->size('xl')
                                                    ->weight('bold')
                                                    ->color('success'),
                                            ])
                                            ->compact()
                                            ->collapsible()
                                            ->collapsed(false),
                                    ]),
                            ]),
                        Infolists\Components\Tabs\Tab::make('Beteiligungen')
                            ->icon('heroicon-o-users')
                            ->schema([
                                Infolists\Components\Section::make('Beteiligungsübersicht')
                                    ->icon('heroicon-o-chart-pie')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('total_participation')
                                                    ->label('Gesamtbeteiligung')
                                                    ->formatStateUsing(fn ($state) => number_format($state, 1, ',', '.') . '%')
                                                    ->badge()
                                                    ->color(fn ($state) => $state >= 100 ? 'success' : 'warning')
                                                    ->size('xl'),
                                                Infolists\Components\TextEntry::make('available_participation')
                                                    ->label('Verfügbar')
                                                    ->formatStateUsing(fn ($state) => number_format($state, 1, ',', '.') . '%')
                                                    ->badge()
                                                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray')
                                                    ->size('xl'),
                                                Infolists\Components\TextEntry::make('participations_count')
                                                    ->label('Beteiligte Kunden')
                                                    ->badge()
                                                    ->color('primary')
                                                    ->size('xl'),
                                            ]),
                                    ])
                                    ->compact()
                                    ->collapsible()
                                    ->collapsed(false),
                                Infolists\Components\Section::make('Beteiligte')
                                    ->icon('heroicon-o-user-group')
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('participations')
                                            ->label('')
                                            ->schema([
                                                Infolists\Components\Grid::make(4)
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('customer_name')
                                                            ->label('')
                                                            ->state(function ($record) {
                                                                $customer = $record->customer;
                                                                if (!$customer) return 'Kunde nicht gefunden';
                                                                
                                                                return $customer->customer_type === 'business'
                                                                    ? ($customer->company_name ?: $customer->name)
                                                                    : $customer->name;
                                                            })
                                                            ->weight('medium')
                                                            ->size('lg')
                                                            ->color('primary')
                                                            ->url(fn ($record) => $record->customer ? route('filament.admin.resources.customers.view', $record->customer) : null)
                                                            ->openUrlInNewTab(false),
                                                        Infolists\Components\TextEntry::make('customer.email')
                                                            ->label('')
                                                            ->placeholder('Keine E-Mail')
                                                            ->color('gray')
                                                            ->url(fn ($record) => $record->customer?->email ? 'mailto:' . $record->customer->email : null)
                                                            ->openUrlInNewTab(false),
                                                        Infolists\Components\TextEntry::make('percentage')
                                                            ->label('')
                                                            ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                                                            ->badge()
                                                            ->color('success')
                                                            ->size('lg'),
                                                        Infolists\Components\TextEntry::make('created_at')
                                                            ->label('')
                                                            ->date('d.m.Y')
                                                            ->color('gray'),
                                                    ]),
                                            ])
                                            ->contained(true)
                                            ->grid(1),
                                    ])
                                    ->headerActions([
                                        Infolists\Components\Actions\Action::make('create_participation')
                                            ->label('Neue Beteiligung')
                                            ->icon('heroicon-o-plus')
                                            ->color('primary')
                                            ->visible(fn ($record) => $record->total_participation < 100)
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
                                                            ->required(),
                                                        Forms\Components\TextInput::make('email')
                                                            ->label('E-Mail')
                                                            ->email(),
                                                        Forms\Components\TextInput::make('phone')
                                                            ->label('Telefon'),
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
                                                    ->helperText(function ($record) {
                                                        $available = $record->available_participation;
                                                        return "Verfügbar: {$available}% (Gesamt: {$record->total_participation}% von 100%)";
                                                    })
                                                    ->dehydrateStateUsing(fn ($state) => str_replace(',', '.', $state))
                                                    ->rules([
                                                        function ($record) {
                                                            return function (string $attribute, $value, \Closure $fail) use ($record) {
                                                                // Komma durch Punkt ersetzen für Berechnung
                                                                $numericValue = (float) str_replace(',', '.', $value);
                                                                $existingParticipation = $record->participations()->sum('percentage');
                                                                $totalParticipation = $existingParticipation + $numericValue;
                                                                
                                                                if ($totalParticipation > 100) {
                                                                    $available = 100 - $existingParticipation;
                                                                    $fail("Die Gesamtbeteiligung würde {$totalParticipation}% betragen. Maximal verfügbar: {$available}%");
                                                                }
                                                            };
                                                        },
                                                    ]),
                                            ])
                                            ->action(function (array $data, $record, $livewire) {
                                                $record->participations()->create($data);
                                                
                                                Notification::make()
                                                    ->title('Beteiligung hinzugefügt')
                                                    ->body('Die Kundenbeteiligung wurde erfolgreich erstellt.')
                                                    ->success()
                                                    ->send();
                                                    
                                                // Livewire-Komponente aktualisieren
                                                $livewire->dispatch('$refresh');
                                            })
                                            ->modalHeading('Neue Beteiligung hinzufügen')
                                            ->modalSubmitActionLabel('Beteiligung erstellen')
                                            ->modalWidth('lg'),
                                    ])
                                    ->compact()
                                    ->collapsible()
                                    ->collapsed(true),
                            ]),
                        Infolists\Components\Tabs\Tab::make('Notizen')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Infolists\Components\Section::make('Zusätzliche Informationen')
                                    ->icon('heroicon-o-pencil-square')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('notes')
                                            ->label('')
                                            ->placeholder('Keine zusätzlichen Notizen vorhanden')
                                            ->prose()
                                            ->markdown(),
                                    ])
                                    ->compact()
                                    ->collapsible()
                                    ->collapsed(false),
                            ]),
                    ])
                    ->columnSpanFull(),
                    
                Infolists\Components\Section::make('Kundenbeteiligungen')
                    ->id('customers')
                    ->icon('heroicon-o-users')
                    ->description('Übersicht der beteiligten Kunden zur Solaranlage.')
                    ->extraAttributes([
                        'class' => 'customers-section-gray',
                        'style' => 'background-color: #1e6fc0ff !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #af9a3aff !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('participations_count')
                                    ->label('Anzahl Beteiligte')
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('total_participation')
                                    ->label('Gesamtbeteiligung')
                                    ->formatStateUsing(fn ($state) => number_format($state, 1, ',', '.') . '%')
                                    ->badge()
                                    ->color(fn ($state) => $state >= 100 ? 'success' : 'warning')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('available_participation')
                                    ->label('Verfügbare Beteiligung')
                                    ->formatStateUsing(fn ($state) => number_format($state, 1, ',', '.') . '%')
                                    ->badge()
                                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray')
                                    ->size('xl'),
                            ]),
                        \Filament\Infolists\Components\Livewire::make(\App\Livewire\ParticipationsTable::class, ['solarPlant' => $this->record])
                            ->key('participations-table'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['customers'] ?? true)
                    ->extraAttributes(['data-section-id' => 'customers']),

                Infolists\Components\Section::make('Kundenabrechnungen')
                    ->id('customer-billings')
                    ->icon('heroicon-o-document-currency-euro')
                    ->description('Übersicht der Abrechnungen aller Kunden zur Solaranlage.')
                    ->extraAttributes([
                        'class' => 'customer-billings-section-gray',
                        'style' => 'background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('billings_count')
                                    ->label('Anzahl Abrechnungen')
                                    ->state(fn ($record) => $record->billings()->count())
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('total_billing_amount')
                                    ->label('Gesamtbetrag')
                                    ->state(fn ($record) => '€ ' . number_format($record->billings()->sum('net_amount'), 2, ',', '.'))
                                    ->badge()
                                    ->color('success')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('paid_billings_count')
                                    ->label('Bezahlte Rechnungen')
                                    ->state(fn ($record) => $record->billings()->where('status', 'paid')->count())
                                    ->badge()
                                    ->color('info')
                                    ->size('xl'),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['customer-billings'] ?? true)
                    ->extraAttributes(['data-section-id' => 'customer-billings']),

                Infolists\Components\Section::make('Vertragspartner')
                    ->id('suppliers')
                    ->icon('heroicon-o-building-office-2')
                    ->description('Übersicht der Lieferanten, Dienstleister und weitere Vertragspartner zur Solaranlage.')
                    ->extraAttributes([
                        'class' => 'suppliers-section-gray',
                        'style' => 'background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('suppliers_count')
                                    ->label('Anzahl Lieferanten')
                                    ->state(fn ($record) => $record->supplierAssignments()->count())
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('contracts_count')
                                    ->label('Aktive Verträge')
                                    ->state(fn ($record) => $record->activeSupplierContracts()->count())
                                    ->badge()
                                    ->color('success')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('contract_billings_count')
                                    ->label('Lieferantenrechnungen')
                                    ->state(fn ($record) => $record->activeSupplierContracts()->withCount('billings')->get()->sum('billings_count'))
                                    ->badge()
                                    ->color('info')
                                    ->size('xl'),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['suppliers'] ?? false)
                    ->extraAttributes(['data-section-id' => 'suppliers']),

                Infolists\Components\Section::make('Verträge')
                    ->id('contracts')
                    ->icon('heroicon-o-document-text')
                    ->description('Übersicht der Lieferantenverträge zur Solaranlage')
                    ->extraAttributes([
                        'class' => 'contracts-section-gray',
                        'style' => 'background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_contracts_count')
                                    ->label('Gesamte Verträge')
                                    ->state(fn ($record) => $record->supplierContracts()->count())
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('active_contracts_count')
                                    ->label('Aktive Verträge')
                                    ->state(fn ($record) => $record->activeSupplierContracts()->count())
                                    ->badge()
                                    ->color('success')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('total_contract_percentage')
                                    ->label('Gesamte Vertragsanteile')
                                    ->state(fn ($record) => number_format($record->total_supplier_contract_percentage, 2, ',', '.') . '%')
                                    ->badge()
                                    ->color('info')
                                    ->size('xl'),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['contracts'] ?? false)
                    ->extraAttributes(['data-section-id' => 'contracts']),

                Infolists\Components\Section::make('Dokumente')
                    ->id('documents')
                    ->icon('heroicon-o-folder')
                    ->description('Übersicht der Dokumente zur Solaranlage')
                    ->extraAttributes([
                        'class' => 'documents-section-gray',
                        'style' => 'background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_documents_count')
                                    ->label('Gesamte Dokumente')
                                    ->state(fn ($record) => $record->documents()->count())
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('favorite_documents_count')
                                    ->label('Favorisierte Dokumente')
                                    ->state(fn ($record) => $record->documents()->where('is_favorite', true)->count())
                                    ->badge()
                                    ->color('warning')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('recent_documents_count')
                                    ->label('Letzte 30 Tage')
                                    ->state(fn ($record) => $record->documents()->where('created_at', '>=', now()->subDays(30))->count())
                                    ->badge()
                                    ->color('info')
                                    ->size('xl'),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['documents'] ?? false)
                    ->extraAttributes(['data-section-id' => 'documents']),

                Infolists\Components\Section::make('Artikel')
                    ->id('articles')
                    ->icon('heroicon-o-squares-plus')
                    ->description('Übersicht der Artikel und Komponenten zur Solaranlage')
                    ->extraAttributes([
                        'class' => 'articles-section-gray',
                        'style' => 'background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_articles_count')
                                    ->label('Gesamte Artikel')
                                    ->state(fn ($record) => $record->articles()->count())
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('active_articles_count')
                                    ->label('Aktive Artikel')
                                    ->state(fn ($record) => $record->articles()->wherePivot('is_active', true)->count())
                                    ->badge()
                                    ->color('success')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('total_article_value')
                                    ->label('Gesamtwert')
                                    ->state(fn ($record) => '€ ' . number_format($record->articles()->get()->sum(function($article) {
                                        return $article->pivot->quantity * $article->pivot->unit_price;
                                    }), 2, ',', '.'))
                                    ->badge()
                                    ->color('info')
                                    ->size('xl'),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['articles'] ?? false)
                    ->extraAttributes(['data-section-id' => 'articles']),

                Infolists\Components\Section::make('Termine')
                    ->id('milestones')
                    ->icon('heroicon-o-calendar-days')
                    ->description('Übersicht der Meilensteine und Termine zur Solaranlage')
                    ->extraAttributes([
                        'class' => 'milestones-section-gray',
                        'style' => 'background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_milestones_count')
                                    ->label('Gesamte Termine')
                                    ->state(fn ($record) => $record->milestones()->count())
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('completed_milestones_count')
                                    ->label('Abgeschlossene Termine')
                                    ->state(fn ($record) => $record->milestones()->where('status', 'completed')->count())
                                    ->badge()
                                    ->color('success')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('upcoming_milestones_count')
                                    ->label('Anstehende Termine')
                                    ->state(fn ($record) => $record->milestones()->where('planned_date', '>=', now())->where('status', '!=', 'completed')->count())
                                    ->badge()
                                    ->color('warning')
                                    ->size('xl'),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['milestones'] ?? false)
                    ->extraAttributes(['data-section-id' => 'milestones']),

                Infolists\Components\Section::make('Wichtige Notizen')
                    ->id('important-notes')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->description('Wichtige Notizen und Hinweise zur Solaranlage')
                    ->extraAttributes([
                        'class' => 'important-notes-section-gray',
                        'style' => 'background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_notes_count')
                                    ->label('Gesamte Notizen')
                                    ->state(fn ($record) => $record->notes()->count())
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('important_notes_count')
                                    ->label('Als wichtig markiert')
                                    ->state(fn ($record) => $record->notes()->where('is_important', true)->count())
                                    ->badge()
                                    ->color('danger')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('recent_notes_count')
                                    ->label('Letzte 7 Tage')
                                    ->state(fn ($record) => $record->notes()->where('created_at', '>=', now()->subDays(7))->count())
                                    ->badge()
                                    ->color('info')
                                    ->size('xl'),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['important-notes'] ?? false)
                    ->extraAttributes(['data-section-id' => 'important-notes']),
           ]);
   }

    public function getHeaderScripts(): array
    {
        return [
            asset('js/infolist-state.js'),
        ];
    }

    protected function getHeaderStyles(): array
    {
        return [
            asset('css/solar-plant-view.css'),
        ];
    }

    public function getHeadContent(): string
    {
        return '<style>
            /* Kunden Section Styling */
            [data-section-id="customers"] {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }
            
            [data-section-id="customers"] > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
            }
            
            /* Alternative selector falls der erste nicht funktioniert */
            .customers-section-gray,
            .customers-section-gray > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }

            /* Kundenabrechnungen Section Styling */
            [data-section-id="customer-billings"] {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }
            
            [data-section-id="customer-billings"] > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
            }
            
            /* Alternative selector falls der erste nicht funktioniert */
            .customer-billings-section-gray,
            .customer-billings-section-gray > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }

            /* Lieferanten Section Styling */
            [data-section-id="suppliers"] {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }
            
            [data-section-id="suppliers"] > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
            }
            
            /* Alternative selector falls der erste nicht funktioniert */
            .suppliers-section-gray,
            .suppliers-section-gray > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }

            /* Verträge Section Styling */
            [data-section-id="contracts"] {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }
            
            [data-section-id="contracts"] > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
            }
            
            /* Alternative selector falls der erste nicht funktioniert */
            .contracts-section-gray,
            .contracts-section-gray > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }

            /* Dokumente Section Styling */
            [data-section-id="documents"] {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }
            
            [data-section-id="documents"] > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
            }
            
            /* Alternative selector falls der erste nicht funktioniert */
            .documents-section-gray,
            .documents-section-gray > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }

            /* Artikel Section Styling */
            [data-section-id="articles"] {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }
            
            [data-section-id="articles"] > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
            }
            
            /* Alternative selector falls der erste nicht funktioniert */
            .articles-section-gray,
            .articles-section-gray > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }

            /* Termine Section Styling */
            [data-section-id="milestones"] {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }
            
            [data-section-id="milestones"] > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
            }
            
            /* Alternative selector falls der erste nicht funktioniert */
            .milestones-section-gray,
            .milestones-section-gray > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }

            /* Wichtige Notizen Section Styling */
            [data-section-id="important-notes"] {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }
            
            [data-section-id="important-notes"] > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
            }
            
            /* Alternative selector falls der erste nicht funktioniert */
            .important-notes-section-gray,
            .important-notes-section-gray > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }
        </style>';
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'infolistTableName' => $this->getInfolistTableName(),
        ]);
    }
}
