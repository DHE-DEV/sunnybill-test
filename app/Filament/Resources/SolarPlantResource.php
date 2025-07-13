<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolarPlantResource\Pages;
use App\Filament\Resources\SolarPlantResource\RelationManagers;
use App\Models\SolarPlant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SolarPlantResource extends Resource
{
    protected static ?string $model = SolarPlant::class;

    protected static ?string $navigationIcon = 'heroicon-o-sun';

    protected static ?string $navigationLabel = 'Solaranlagen';

    protected static ?string $modelLabel = 'Solaranlage';

    protected static ?string $pluralModelLabel = 'Solaranlagen';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Solar Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Grunddaten')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Anlagenname')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('z.B. Solaranlage Musterstraße 1'),
                                        Forms\Components\TextInput::make('location')
                                            ->label('Standort')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('z.B. Musterstraße 1, 12345 Musterstadt'),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('mastr_number')
                                            ->label('MaStR-Nr.')
                                            ->helperText('Marktstammdatenregister'),
                                        Forms\Components\DatePicker::make('mastr_registration_date')
                                            ->label('MaStR Registrierungsdatum')
                                            ->helperText('Registrierungsdatum des Marktstammdatenregisters'),
                                        Forms\Components\TextInput::make('malo_id')
                                            ->label('MaLo-ID')
                                            ->helperText('Marktlokations ID'),
                                        Forms\Components\TextInput::make('melo_id')
                                            ->label('MeLo-ID')
                                            ->helperText('Messlokations ID'),
                                        Forms\Components\TextInput::make('vnb_process_number')
                                            ->label('VNB-Vorgangsnummer'),
                                        Forms\Components\DatePicker::make('unit_commissioning_date')
                                            ->label('Inbetriebnahmedatum der Einheit'),
                                        Forms\Components\DatePicker::make('pv_soll_planning_date')
                                            ->label('PV-Soll Planung erfolgte am:'),
                                        Forms\Components\TextInput::make('pv_soll_project_number')
                                            ->label('PV-Soll Projektnummer'),
                                    ]),
                                Forms\Components\Section::make('Geokoordinaten')
                                    ->description('Genaue Position der Solaranlage für Kartendarstellung')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('latitude')
                                                    ->label('Breitengrad (Latitude)')
                                                    ->numeric()
                                                    ->step(0.00000001)
                                                    ->placeholder('z.B. 52.520008')
                                                    ->suffix('°N')
                                                    ->helperText('Dezimalgrad (WGS84)'),
                                                Forms\Components\TextInput::make('longitude')
                                                    ->label('Längengrad (Longitude)')
                                                    ->numeric()
                                                    ->step(0.00000001)
                                                    ->placeholder('z.B. 13.404954')
                                                    ->suffix('°E')
                                                    ->helperText('Dezimalgrad (WGS84)'),
                                            ]),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('total_capacity_kw')
                                            ->label('Gesamtleistung (kWp)')
                                            ->required()
                                            ->numeric()
                                            ->step(0.000001)
                                            ->suffix('kWp')
                                            ->minValue(0)
                                            ->placeholder('z.B. 29.920000'),
                                        Forms\Components\DatePicker::make('planned_installation_date')
                                            ->label('Geplante Installation')
                                            ->displayFormat('d.m.Y')
                                            ->helperText('Ursprünglich geplantes Installationsdatum'),
                                        Forms\Components\DatePicker::make('installation_date')
                                            ->label('Tatsächliche Installation')
                                            ->displayFormat('d.m.Y')
                                            ->helperText('Tatsächliches Installationsdatum'),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('planned_commissioning_date')
                                            ->label('Geplante Inbetriebnahme')
                                            ->displayFormat('d.m.Y')
                                            ->after('planned_installation_date')
                                            ->helperText('Ursprünglich geplantes Inbetriebnahmedatum'),
                                        Forms\Components\DatePicker::make('commissioning_date')
                                            ->label('Tatsächliche Inbetriebnahme')
                                            ->displayFormat('d.m.Y')
                                            ->after('installation_date')
                                            ->helperText('Tatsächliches Inbetriebnahmedatum'),
                                    ]),
                                Forms\Components\Textarea::make('description')
                                    ->label('Beschreibung')
                                    ->rows(3)
                                    ->placeholder('Zusätzliche Informationen zur Anlage...')
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Technische Daten')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('panel_count')
                                            ->label('Anzahl Module')
                                            ->numeric()
                                            ->minValue(0)
                                            ->placeholder('z.B. 92'),
                                        Forms\Components\TextInput::make('inverter_count')
                                            ->label('Anzahl Wechselrichter')
                                            ->numeric()
                                            ->minValue(0)
                                            ->placeholder('z.B. 3'),
                                    ]),
                                Forms\Components\TextInput::make('battery_capacity_kwh')
                                    ->label('Batteriekapazität (kWh)')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->suffix('kWh')
                                    ->minValue(0)
                                    ->placeholder('z.B. 15.000000')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('degradation_rate')
                                    ->label('Degradationsrate (%/Jahr)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->suffix('%/Jahr')
                                    ->minValue(0)
                                    ->maxValue(10)
                                    ->placeholder('z.B. 0.50')
                                    ->helperText('Jährlicher Leistungsverlust in Prozent (typisch: 0,5-0,8%)')
                                    ->columnSpan(1),
                            ]),
                        Forms\Components\Tabs\Tab::make('Finanzierung')
                            ->icon('heroicon-o-currency-euro')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('total_investment')
                                            ->label('Gesamtinvestition')
                                            ->numeric()
                                            ->step(0.01)
                                            ->prefix('€')
                                            ->minValue(0)
                                            ->placeholder('z.B. 45000.00'),
                                        Forms\Components\TextInput::make('feed_in_tariff_per_kwh')
                                            ->label('Einspeisevergütung (€/kWh)')
                                            ->numeric()
                                            ->step(0.000001)
                                            ->prefix('€')
                                            ->minValue(0)
                                            ->placeholder('z.B. 0.082500')
                                            ->helperText('Aktuelle Einspeisevergütung'),
                                        Forms\Components\TextInput::make('expected_annual_yield_kwh')
                                            ->label('Erwarteter Jahresertrag (kWh)')
                                            ->numeric()
                                            ->step(0.000001)
                                            ->suffix('kWh/Jahr')
                                            ->minValue(0)
                                            ->placeholder('z.B. 28500.000000'),
                                        Forms\Components\TextInput::make('annual_operating_costs')
                                            ->label('Jährliche Betriebskosten')
                                            ->numeric()
                                            ->step(0.01)
                                            ->prefix('€')
                                            ->minValue(0)
                                            ->placeholder('z.B. 500.00'),
                                        Forms\Components\TextInput::make('electricity_price_per_kwh')
                                            ->label('Strompreis (€/kWh)')
                                            ->numeric()
                                            ->step(0.000001)
                                            ->prefix('€')
                                            ->minValue(0)
                                            ->placeholder('z.B. 0.325000')
                                            ->helperText('Aktueller Strompreis'),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Status')
                            ->icon('heroicon-o-signal')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('status')
                                            ->label('Status')
                                            ->options(\App\Models\SolarPlantStatus::getActiveOptions())
                                            ->default(function () {
                                                $defaultStatus = \App\Models\SolarPlantStatus::getDefault();
                                                return $defaultStatus ? $defaultStatus->key : 'in_planning';
                                            })
                                            ->required(),
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Aktiv')
                                            ->default(true)
                                            ->helperText('Ist die Anlage derzeit in Betrieb?'),
                                    ]),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notizen')
                                    ->rows(4)
                                    ->placeholder('Zusätzliche Notizen zur Anlage...')
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Zusätzliche Felder')
                            ->icon('heroicon-o-plus-circle')
                            ->schema(\App\Models\DummyFieldConfig::getDummyFieldsSchema('solar_plant')),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Grunddaten')
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(2)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('name')
                                    ->label('Anlagenname')
                                    ->weight('bold')
                                    ->size('lg'),
                                \Filament\Infolists\Components\TextEntry::make('location')
                                    ->label('Standort')
                                    ->size('lg'),
                            ]),
                        
                        \Filament\Infolists\Components\Grid::make(3)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('mastr_number')
                                    ->label('MaStR-Nr.')
                                    ->placeholder('Nicht hinterlegt')
                                    ->copyable()
                                    ->badge()
                                    ->color('info'),
                                \Filament\Infolists\Components\TextEntry::make('mastr_registration_date')
                                    ->label('MaStR Registrierungsdatum')
                                    ->date('d.m.Y')
                                    ->placeholder('Nicht hinterlegt'),
                                \Filament\Infolists\Components\TextEntry::make('malo_id')
                                    ->label('MaLo-ID')
                                    ->placeholder('Nicht hinterlegt')
                                    ->copyable()
                                    ->badge()
                                    ->color('primary'),
                            ]),
                        
                        \Filament\Infolists\Components\Grid::make(3)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('melo_id')
                                    ->label('MeLo-ID')
                                    ->placeholder('Nicht hinterlegt')
                                    ->copyable()
                                    ->badge()
                                    ->color('primary'),
                                \Filament\Infolists\Components\TextEntry::make('vnb_process_number')
                                    ->label('VNB-Vorgangsnummer')
                                    ->placeholder('Nicht hinterlegt')
                                    ->copyable()
                                    ->badge()
                                    ->color('warning'),
                                \Filament\Infolists\Components\TextEntry::make('pv_soll_project_number')
                                    ->label('PV-Soll Projektnummer')
                                    ->placeholder('Nicht hinterlegt')
                                    ->copyable()
                                    ->badge()
                                    ->color('success'),
                            ]),
                        
                        \Filament\Infolists\Components\Grid::make(3)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('status')
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
                                \Filament\Infolists\Components\IconEntry::make('is_active')
                                    ->label('Aktiv')
                                    ->boolean(),
                                \Filament\Infolists\Components\TextEntry::make('total_capacity_kw')
                                    ->label('Gesamtleistung')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 6, ',', '.') . ' kWp' : '-')
                                    ->badge()
                                    ->color('success'),
                            ]),
                        
                        \Filament\Infolists\Components\TextEntry::make('description')
                            ->label('Beschreibung')
                            ->prose()
                            ->placeholder('Keine Beschreibung hinterlegt')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->headerActions([
                        \Filament\Infolists\Components\Actions\Action::make('show_map')
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
                    ]),

                \Filament\Infolists\Components\Section::make('Karte')
                    ->description('Standort der Solaranlage')
                    ->schema([
                        \Filament\Infolists\Components\ViewEntry::make('map')
                            ->label('')
                            ->view('filament.infolists.components.openstreetmap')
                            ->viewData(fn ($record) => [
                                'latitude' => $record->latitude,
                                'longitude' => $record->longitude,
                                'name' => $record->name,
                                'location' => $record->location,
                                'hasCoordinates' => $record->hasCoordinates(),
                            ])
                            ->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('formatted_coordinates')
                            ->label('Koordinaten')
                            ->placeholder('Keine Koordinaten hinterlegt')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => !$record->hasCoordinates()),

                \Filament\Infolists\Components\Section::make('Technische Daten')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('total_capacity_kw')
                            ->label('Gesamtleistung')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 6, ',', '.') . ' kWp' : '-'),
                        \Filament\Infolists\Components\TextEntry::make('panel_count')
                            ->label('Anzahl Module')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('inverter_count')
                            ->label('Anzahl Wechselrichter')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('battery_capacity_kwh')
                            ->label('Batteriekapazität')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 6, ',', '.') . ' kWh' : '-')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('expected_annual_yield_kwh')
                            ->label('Erwarteter Jahresertrag')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 6, ',', '.') . ' kWh' : '-')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('formatted_degradation_rate')
                            ->label('Degradationsrate')
                            ->placeholder('-'),
                    ])->columns(3),

                \Filament\Infolists\Components\Section::make('Finanzierung')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('formatted_total_investment')
                            ->label('Gesamtinvestition'),
                        \Filament\Infolists\Components\TextEntry::make('formatted_annual_operating_costs')
                            ->label('Jährliche Betriebskosten'),
                        \Filament\Infolists\Components\TextEntry::make('formatted_feed_in_tariff')
                            ->label('Einspeisevergütung'),
                        \Filament\Infolists\Components\TextEntry::make('formatted_electricity_price')
                            ->label('Strompreis'),
                    ])->columns(2),

                \Filament\Infolists\Components\Section::make('Termine')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('planned_installation_date')
                            ->label('Geplante Installation')
                            ->date('d.m.Y')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('installation_date')
                            ->label('Tatsächliche Installation')
                            ->date('d.m.Y')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('planned_commissioning_date')
                            ->label('Geplante Inbetriebnahme')
                            ->date('d.m.Y')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('commissioning_date')
                            ->label('Tatsächliche Inbetriebnahme')
                            ->date('d.m.Y')
                            ->placeholder('-'),
                    ])->columns(2),

                \Filament\Infolists\Components\Section::make('Sonstiges')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('fusion_solar_id')
                            ->label('FusionSolar ID')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('last_sync_at')
                            ->label('Letzter Sync')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('notes')
                            ->label('Notizen')
                            ->prose()
                            ->placeholder('Keine Notizen')
                            ->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('Erstellt am')
                            ->dateTime('d.m.Y H:i'),
                        \Filament\Infolists\Components\TextEntry::make('updated_at')
                            ->label('Zuletzt geändert')
                            ->dateTime('d.m.Y H:i'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('location')
                    ->label('Standort')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('total_capacity_kw')
                    ->label('Leistung')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . ' kWp')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        $status = \App\Models\SolarPlantStatus::where('key', $state)->first();
                        return $status ? $status->name : $state;
                    })
                    ->badge()
                    ->color(function ($state) {
                        $status = \App\Models\SolarPlantStatus::where('key', $state)->first();
                        return $status ? $status->color : 'gray';
                    }),
                Tables\Columns\TextColumn::make('total_participation')
                    ->label('Beteiligung')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                    ->badge()
                    ->color(fn ($state) => $state >= 100 ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('participations_count')
                    ->label('Kunden')
                    ->counts('participations')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('planned_installation_date')
                    ->label('Geplante Installation')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('installation_date')
                    ->label('Tatsächliche Installation')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('planned_commissioning_date')
                    ->label('Geplante Inbetriebnahme')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('commissioning_date')
                    ->label('Tatsächliche Inbetriebnahme')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_investment')
                    ->label('Gesamtinvestition')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, ',', '.') . ' €' : '-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('annual_operating_costs')
                    ->label('Jährliche Betriebskosten')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, ',', '.') . ' €' : '-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expected_annual_yield_kwh')
                    ->label('Erwarteter Jahresertrag')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : '-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('fusion_solar_id')
                    ->label('FusionSolar ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_sync_at')
                    ->label('Letzter Sync')
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(\App\Models\SolarPlantStatus::getActiveOptions()),
                Tables\Filters\Filter::make('is_active')
                    ->label('Nur aktive Anlagen')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
                Tables\Filters\Filter::make('fully_subscribed')
                    ->label('Vollständig belegt')
                    ->query(fn (Builder $query): Builder => $query->whereHas('participations', function ($q) {
                        $q->havingRaw('SUM(percentage) >= 100');
                    })),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->headerActions([
                // FusionSolar Synchronisation entfernt
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine Solaranlagen')
            ->emptyStateDescription('Erstellen Sie Ihre erste Solaranlage.')
            ->emptyStateIcon('heroicon-o-sun');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ParticipationsRelationManager::class,
            RelationManagers\MonthlyResultsRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\ContractsRelationManager::class,
            RelationManagers\SuppliersRelationManager::class,
            RelationManagers\MilestonesRelationManager::class,
            RelationManagers\FavoriteNotesRelationManager::class,
            RelationManagers\StandardNotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSolarPlants::route('/'),
            'create' => Pages\CreateSolarPlant::route('/create'),
            'view' => Pages\ViewSolarPlant::route('/{record}'),
            'edit' => Pages\EditSolarPlant::route('/{record}/edit'),
        ];
    }
}
