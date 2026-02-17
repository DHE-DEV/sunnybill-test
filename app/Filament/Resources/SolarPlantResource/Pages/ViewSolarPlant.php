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
            //RelationManagers\ParticipationsRelationManager::class,
            //RelationManagers\BillingsRelationManager::class,
            //RelationManagers\MonthlyResultsRelationManager::class,
            //RelationManagers\DocumentsRelationManager::class,
            //RelationManagers\ContractsRelationManager::class,
            //RelationManagers\SuppliersRelationManager::class,
            //RelationManagers\MilestonesRelationManager::class,
            //RelationManagers\FavoriteNotesRelationManager::class,
            //RelationManagers\StandardNotesRelationManager::class,
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
                        Infolists\Components\Grid::make(4)
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
                                Infolists\Components\TextEntry::make('billing')
                                    ->label('Fakturierbar')
                                    ->formatStateUsing(fn ($state) => $state ? 'Ja' : 'Nein')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
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
                                    ->collapsed($savedState['location-status'] ?? true)
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
                    ->heading(fn ($record) => 'Kundenbeteiligungen' . ($record->participations()->count() > 0 ? ' (' . $record->participations()->count() . ')' : ''))
                    ->description('Übersicht der beteiligten Kunden zur Solaranlage ' . $this->record->name . '.')
                    ->extraAttributes([
                        'class' => 'customers-section-gray',
                        'style' => 'background-color: #1e6fc0ff !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #af9a3aff !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(5)
                            ->schema([
                                Infolists\Components\TextEntry::make('participations_count')
                                    ->label('Anzahl Beteiligte')
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('total_participation_kwp')
                                    ->label('Gesamtbeteiligung (kWp)')
                                    ->state(fn ($record) => number_format(($record->total_participation / 100) * $record->total_capacity_kw, 3, ',', '.') . ' kWp')
                                    ->badge()
                                    ->color('success')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('total_participation')
                                    ->label('Gesamtbeteiligung (%)')
                                    ->formatStateUsing(fn ($state) => number_format($state, 1, ',', '.') . '%')
                                    ->badge()
                                    ->color(fn ($state) => $state >= 100 ? 'success' : 'warning')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('available_participation')
                                    ->label('Verfügbare Beteiligung (%)')
                                    ->formatStateUsing(fn ($state) => number_format($state, 1, ',', '.') . '%')
                                    ->badge()
                                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('available_participation_kwp')
                                    ->label('Verfügbare Beteiligung (kWp)')
                                    ->state(fn ($record) => number_format(($record->available_participation / 100) * $record->total_capacity_kw, 3, ',', '.') . ' kWp')
                                    ->badge()
                                    ->color('info')
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
                    ->heading(fn ($record) => 'Kundenabrechnungen' . ($record->billings()->count() > 0 ? ' (' . $record->billings()->count() . ')' : ''))
                    ->description('Übersicht der Kundenabrechnungen aller Kunden zur Solaranlage ' . $this->record->name . '.')
                    ->extraAttributes([
                        'class' => 'customer-billings-section-gray',
                        'style' => 'background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('billings_count')
                                    ->label('Anzahl Abrechnungen')
                                    ->state(fn ($record) => $record->billings()->whereNot('status', 'cancelled')->count())
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('total_billing_amount')
                                    ->label('Gesamtbetrag')
                                    ->state(fn ($record) => '€ ' . number_format($record->billings()->whereNot('status', 'cancelled')->sum('net_amount'), 2, ',', '.'))
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
                        \Filament\Infolists\Components\Livewire::make(\App\Livewire\CustomerBillingsTable::class, ['solarPlant' => $this->record])
                            ->key('customer-billings-table'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['customer-billings'] ?? true)
                    ->extraAttributes(['data-section-id' => 'customer-billings']),

                Infolists\Components\Section::make('Vertragspartner')
                    ->id('suppliers')
                    ->icon('heroicon-o-building-office-2')
                    ->heading(fn ($record) => 'Vertragspartner' . ($record->supplierAssignments()->count() > 0 ? ' (' . $record->supplierAssignments()->count() . ')' : ''))
                    ->description('Übersicht der Lieferanten, Dienstleister und weiterer Vertragspartner zur Solaranlage ' . $this->record->name . '.')
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
                        \Filament\Infolists\Components\Livewire::make(\App\Livewire\SuppliersTable::class, ['solarPlant' => $this->record])
                            ->key('suppliers-table'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['suppliers'] ?? true)
                    ->extraAttributes(['data-section-id' => 'suppliers']),

                Infolists\Components\Section::make('Verträge')
                    ->id('contracts')
                    ->icon('heroicon-o-document-text')
                    ->heading(fn ($record) => 'Verträge' . ($record->supplierContracts()->count() > 0 ? ' (' . $record->supplierContracts()->count() . ')' : ''))
                    ->description('Übersicht der Lieferantenverträge zur Solaranlage ' . $this->record->name . '. Diese Verträge sind die Grundlage für Kundenabrechnungen.')
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
                        \Filament\Infolists\Components\Livewire::make(\App\Livewire\ContractsTable::class, ['solarPlant' => $this->record])
                            ->key('contracts-table'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['contracts'] ?? true)
                    ->extraAttributes(['data-section-id' => 'contracts']),

                Infolists\Components\Section::make('Dokumente')
                    ->id('documents')
                    ->icon('heroicon-o-folder')
                    ->heading(fn ($record) => 'Dokumente' . ($record->documents()->count() > 0 ? ' (' . $record->documents()->count() . ')' : ''))
                    ->description('Übersicht der Dokumente zur Solaranlage ' . $this->record->name . '. Diese Dokumente können Verträge, Pläne und weitere wichtige Dateien enthalten.')
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
                        Infolists\Components\Section::make('Dokumente pro Typ')
                            ->schema([
                                Infolists\Components\Grid::make(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('documents_by_type')
                                            ->label('')
                                            ->state(function ($record) {
                                                $documentTypes = \App\Models\DocumentType::withCount(['documents' => function ($query) use ($record) {
                                                    $query->where('documentable_type', 'App\Models\SolarPlant')
                                                          ->where('documentable_id', $record->id);
                                                }])->having('documents_count', '>', 0)->get();

                                                if ($documentTypes->isEmpty()) {
                                                    return 'Keine Dokumente nach Typ kategorisiert';
                                                }

                                                return $documentTypes->map(function ($type) {
                                                    return $type->name . ': ' . $type->documents_count;
                                                })->join(' | ');
                                            })
                                            ->columnSpanFull()
                                            ->color('gray'),
                                    ])
                                    ->schema(function ($record) {
                                        $documentTypes = \App\Models\DocumentType::withCount(['documents' => function ($query) use ($record) {
                                            $query->where('documentable_type', 'App\Models\SolarPlant')
                                                  ->where('documentable_id', $record->id);
                                        }])->having('documents_count', '>', 0)->get();

                                        if ($documentTypes->isEmpty()) {
                                            return [
                                                Infolists\Components\TextEntry::make('no_document_types')
                                                    ->label('Dokumenttypen')
                                                    ->state('Keine Dokumente nach Typ kategorisiert')
                                                    ->columnSpanFull()
                                                    ->color('gray'),
                                            ];
                                        }

                                        return $documentTypes->map(function ($type) {
                                            return Infolists\Components\TextEntry::make("document_type_{$type->id}")
                                                ->label($type->name)
                                                ->state($type->documents_count)
                                                ->badge()
                                                ->color(match($type->name) {
                                                    'Verträge' => 'primary',
                                                    'Rechnungen' => 'danger',
                                                    'Pläne' => 'info',
                                                    'Genehmigungen' => 'warning',
                                                    'Zertifikate' => 'success',
                                                    'Fotos' => 'purple',
                                                    'Protokolle' => 'gray',
                                                    default => 'secondary',
                                                })
                                                ->size('lg');
                                        })->toArray();
                                    }),
                            ])
                            ->compact()
                            ->collapsible()
                            ->collapsed(false),
                        \Filament\Infolists\Components\Livewire::make(\App\Livewire\DocumentsTable::class, ['solarPlant' => $this->record])
                            ->key('documents-table'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['documents'] ?? true)
                    ->extraAttributes(['data-section-id' => 'documents']),

                Infolists\Components\Section::make('Artikel')
                    ->id('articles')
                    ->icon('heroicon-o-squares-plus')
                    ->heading(fn ($record) => 'Artikel' . ($record->articles()->count() > 0 ? ' (' . $record->articles()->count() . ')' : ''))
                    ->description('Übersicht der Artikel und zur Solaranlage ' . $this->record->name . '. Artikel können Komponenten, Ersatzteile und weitere Materialien sein, die zur Rechnungserstellung herangezogen werden können.')
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
                        \Filament\Infolists\Components\Livewire::make(\App\Livewire\ArticlesTable::class, ['solarPlant' => $this->record])
                            ->key('articles-table'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['articles'] ?? true)
                    ->extraAttributes(['data-section-id' => 'articles']),

                Infolists\Components\Section::make('Aufgaben')
                    ->id('tasks')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->heading(fn ($record) => 'Aufgaben' . ((\App\Models\Task::where(function ($query) use ($record) {
                        $query->where('solar_plant_id', $record->id)
                              ->orWhere('applies_to_all_solar_plants', true);
                    })->count() > 0) ? ' (' . \App\Models\Task::where(function ($query) use ($record) {
                        $query->where('solar_plant_id', $record->id)
                              ->orWhere('applies_to_all_solar_plants', true);
                    })->count() . ')' : ''))
                    ->description('Übersicht der Aufgaben und To-Dos zur Solaranlage ' . $this->record->name . '.')
                    ->extraAttributes([
                        'class' => 'tasks-section-gray',
                        'style' => 'background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_tasks_count')
                                    ->label('Gesamte Aufgaben')
                                    ->state(fn ($record) => \App\Models\Task::where(function ($query) use ($record) {
                                        $query->where('solar_plant_id', $record->id)
                                              ->orWhere('applies_to_all_solar_plants', true);
                                    })->count())
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('open_tasks_count')
                                    ->label('Offene Aufgaben')
                                    ->state(fn ($record) => \App\Models\Task::where(function ($query) use ($record) {
                                        $query->where('solar_plant_id', $record->id)
                                              ->orWhere('applies_to_all_solar_plants', true);
                                    })->whereIn('status', ['open', 'in_progress', 'waiting_external', 'waiting_internal'])->count())
                                    ->badge()
                                    ->color('warning')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('completed_tasks_count')
                                    ->label('Abgeschlossene Aufgaben')
                                    ->state(fn ($record) => \App\Models\Task::where(function ($query) use ($record) {
                                        $query->where('solar_plant_id', $record->id)
                                              ->orWhere('applies_to_all_solar_plants', true);
                                    })->where('status', 'completed')->count())
                                    ->badge()
                                    ->color('success')
                                    ->size('xl'),
                            ]),
                        \Filament\Infolists\Components\Livewire::make(\App\Livewire\TasksTable::class, ['solarPlant' => $this->record])
                            ->key('tasks-table'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['tasks'] ?? true)
                    ->extraAttributes(['data-section-id' => 'tasks']),

                Infolists\Components\Section::make('Projekte')
                    ->id('projects')
                    ->icon('heroicon-o-briefcase')
                    ->heading(fn ($record) => 'Projekte' . ($record->projects()->count() > 0 ? ' (' . $record->projects()->count() . ')' : ''))
                    ->description('Übersicht der Projekte zur Solaranlage ' . $this->record->name . '.')
                    ->extraAttributes([
                        'class' => 'projects-section-gray',
                        'style' => 'background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_projects_count')
                                    ->label('Gesamte Projekte')
                                    ->state(fn ($record) => $record->projects()->count())
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('active_projects_count')
                                    ->label('Aktive Projekte')
                                    ->state(fn ($record) => $record->projects()->whereIn('status', ['planning', 'active'])->count())
                                    ->badge()
                                    ->color('warning')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('completed_projects_count')
                                    ->label('Abgeschlossene Projekte')
                                    ->state(fn ($record) => $record->projects()->where('status', 'completed')->count())
                                    ->badge()
                                    ->color('success')
                                    ->size('xl'),
                            ]),
                        \Filament\Infolists\Components\Livewire::make(\App\Livewire\ProjectsTable::class, ['solarPlant' => $this->record])
                            ->key('projects-table'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['projects'] ?? true)
                    ->extraAttributes(['data-section-id' => 'projects']),

                Infolists\Components\Section::make('Termine')
                    ->id('milestones')
                    ->icon('heroicon-o-calendar-days')
                    ->heading(fn ($record) => 'Termine' . ((\App\Models\ProjectMilestone::whereHas('project', function ($query) use ($record) {
                        $query->where('solar_plant_id', $record->id);
                    })->count() + \App\Models\ProjectAppointment::whereHas('project', function ($query) use ($record) {
                        $query->where('solar_plant_id', $record->id);
                    })->count()) > 0 ? ' (' . (\App\Models\ProjectMilestone::whereHas('project', function ($query) use ($record) {
                        $query->where('solar_plant_id', $record->id);
                    })->count() + \App\Models\ProjectAppointment::whereHas('project', function ($query) use ($record) {
                        $query->where('solar_plant_id', $record->id);
                    })->count()) . ')' : ''))
                    ->description('Übersicht der Termine und Meilensteine zur Solaranlage ' . $this->record->name . '.')
                    ->extraAttributes([
                        'class' => 'milestones-section-gray',
                        'style' => 'background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_milestones_count')
                                    ->label('Gesamte Meilensteine')
                                    ->state(function ($record) {
                                        return \App\Models\ProjectMilestone::whereHas('project', function ($query) use ($record) {
                                            $query->where('solar_plant_id', $record->id);
                                        })->count();
                                    })
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('total_appointments_count')
                                    ->label('Gesamte Termine')
                                    ->state(function ($record) {
                                        return \App\Models\ProjectAppointment::whereHas('project', function ($query) use ($record) {
                                            $query->where('solar_plant_id', $record->id);
                                        })->count();
                                    })
                                    ->badge()
                                    ->color('info')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('completed_milestones_count')
                                    ->label('Abgeschlossene')
                                    ->state(function ($record) {
                                        $milestones = \App\Models\ProjectMilestone::whereHas('project', function ($query) use ($record) {
                                            $query->where('solar_plant_id', $record->id);
                                        })->where('status', 'completed')->count();
                                        
                                        $appointments = \App\Models\ProjectAppointment::whereHas('project', function ($query) use ($record) {
                                            $query->where('solar_plant_id', $record->id);
                                        })->where('status', 'completed')->count();
                                        
                                        return $milestones + $appointments;
                                    })
                                    ->badge()
                                    ->color('success')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('upcoming_milestones_count')
                                    ->label('Anstehende')
                                    ->state(function ($record) {
                                        $milestones = \App\Models\ProjectMilestone::whereHas('project', function ($query) use ($record) {
                                            $query->where('solar_plant_id', $record->id);
                                        })->where('planned_date', '>=', now())->where('status', '!=', 'completed')->count();
                                        
                                        $appointments = \App\Models\ProjectAppointment::whereHas('project', function ($query) use ($record) {
                                            $query->where('solar_plant_id', $record->id);
                                        })->where('start_datetime', '>=', now())->whereNotIn('status', ['completed', 'cancelled'])->count();
                                        
                                        return $milestones + $appointments;
                                    })
                                    ->badge()
                                    ->color('warning')
                                    ->size('xl'),
                            ]),
                        \Filament\Infolists\Components\Livewire::make(\App\Livewire\MilestonesTable::class, ['solarPlant' => $this->record])
                            ->key('milestones-table'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['milestones'] ?? true)
                    ->extraAttributes(['data-section-id' => 'milestones']),

                Infolists\Components\Section::make('Favoriten Notizen')
                    ->id('favorite-notes')
                    ->icon('heroicon-o-star')
                    ->heading(fn ($record) => 'Favoriten Notizen' . ($record->notes()->where('is_favorite', true)->count() > 0 ? ' (' . $record->notes()->where('is_favorite', true)->count() . ')' : ''))
                    ->description('Favorisierte Notizen und wichtige Hinweise zur Solaranlage ' . $this->record->name . '. Diese Notizen sind für alle Beteiligten von Bedeutung und sollten hervorgehoben werden.')
                    ->extraAttributes([
                        'class' => 'favorite-notes-section-gray',
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
                                Infolists\Components\TextEntry::make('favorite_notes_count')
                                    ->label('Favorisierte Notizen')
                                    ->state(fn ($record) => $record->notes()->where('is_favorite', true)->count())
                                    ->badge()
                                    ->color('warning')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('recent_notes_count')
                                    ->label('Letzte 7 Tage')
                                    ->state(fn ($record) => $record->notes()->where('created_at', '>=', now()->subDays(7))->count())
                                    ->badge()
                                    ->color('info')
                                    ->size('xl'),
                            ]),
                        \Filament\Infolists\Components\Livewire::make(\App\Livewire\NotesTable::class, ['solarPlant' => $this->record, 'showOnlyFavorites' => true])
                            ->key('favorite-notes-table'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['favorite-notes'] ?? true)
                    ->extraAttributes(['data-section-id' => 'favorite-notes']),

                Infolists\Components\Section::make('Standard Notizen')
                    ->id('standard-notes')
                    ->icon('heroicon-o-document-text')
                    ->heading(fn ($record) => 'Standard Notizen' . ($record->notes()->where('is_favorite', false)->count() > 0 ? ' (' . $record->notes()->where('is_favorite', false)->count() . ')' : ''))
                    ->description('Allgemeine Notizen und Bemerkungen zur Solaranlage ' . $this->record->name . '. Diese Notizen sind nicht favorisiert, aber dennoch wichtig für die Dokumentation und Kommunikation.')
                    ->extraAttributes([
                        'class' => 'standard-notes-section-gray',
                        'style' => 'background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;'
                    ])
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('standard_notes_count')
                                    ->label('Standard Notizen')
                                    ->state(fn ($record) => $record->notes()->where('is_favorite', false)->count())
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('maintenance_notes_count')
                                    ->label('Wartungsnotizen')
                                    ->state(fn ($record) => $record->notes()->where('type', 'maintenance')->count())
                                    ->badge()
                                    ->color('warning')
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('issue_notes_count')
                                    ->label('Problem-Notizen')
                                    ->state(fn ($record) => $record->notes()->where('type', 'issue')->count())
                                    ->badge()
                                    ->color('danger')
                                    ->size('xl'),
                            ]),
                        \Filament\Infolists\Components\Livewire::make(\App\Livewire\NotesTable::class, ['solarPlant' => $this->record, 'showOnlyFavorites' => false])
                            ->key('standard-notes-table'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed($savedState['standard-notes'] ?? true)
                    ->extraAttributes(['data-section-id' => 'standard-notes']),
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

            /* Aufgaben Section Styling */
            [data-section-id="tasks"] {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }
            
            [data-section-id="tasks"] > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
            }
            
            /* Alternative selector falls der erste nicht funktioniert */
            .tasks-section-gray,
            .tasks-section-gray > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }

            /* Projekte Section Styling */
            [data-section-id="projects"] {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }
            
            [data-section-id="projects"] > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
            }
            
            /* Alternative selector falls der erste nicht funktioniert */
            .projects-section-gray,
            .projects-section-gray > div {
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

            /* Favoriten Notizen Section Styling */
            [data-section-id="favorite-notes"] {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }
            
            [data-section-id="favorite-notes"] > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
            }
            
            /* Alternative selector falls der erste nicht funktioniert */
            .favorite-notes-section-gray,
            .favorite-notes-section-gray > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }

            /* Standard Notizen Section Styling */
            [data-section-id="standard-notes"] {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
                padding: 16px !important;
                margin: 8px 0 !important;
                border: 1px solid #e5e7eb !important;
            }
            
            [data-section-id="standard-notes"] > div {
                background-color: #f9fafb !important;
                border-radius: 8px !important;
            }
            
            /* Alternative selector falls der erste nicht funktioniert */
            .standard-notes-section-gray,
            .standard-notes-section-gray > div {
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
