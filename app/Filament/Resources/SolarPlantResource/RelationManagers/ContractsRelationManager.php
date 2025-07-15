<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\DummyFieldConfig;
use Filament\Notifications\Notification;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'supplierContracts';

    protected static ?string $title = 'Verträge';

    protected static ?string $modelLabel = 'Vertrag';

    protected static ?string $pluralModelLabel = 'Verträge';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->supplierContracts()->count();
        return $count > 0 ? (string) $count : null;
    }

    public function isReadOnly(): bool
    {
        return false; // Erlaube Aktionen auch im View-Modus
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('contract_number')
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('Vertragsnummer')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('supplier.company_name')
                    ->label('Lieferant')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('malo_id')
                    ->label('MaLo-ID')
                    ->searchable()
                    ->placeholder('-')
                    ->copyable()
                    ->toggleable()
                    ->color('info'),
                Tables\Columns\TextColumn::make('ep_id')
                    ->label('EP-ID')
                    ->searchable()
                    ->placeholder('-')
                    ->copyable()
                    ->toggleable()
                    ->color('info'),
                Tables\Columns\TextColumn::make('contract_type')
                    ->label('Vertragsart')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'installation' => 'Installation',
                        'maintenance' => 'Wartung',
                        'components' => 'Komponenten',
                        'planning' => 'Planung',
                        'monitoring' => 'Überwachung',
                        'insurance' => 'Versicherung',
                        'financing' => 'Finanzierung',
                        'other' => 'Sonstiges',
                        default => $state,
                    })
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(fn ($state) => match($state) {
                        'installation' => 'success',
                        'maintenance' => 'warning',
                        'components' => 'info',
                        'planning' => 'primary',
                        'monitoring' => 'info',
                        'insurance' => 'danger',
                        'financing' => 'warning',
                        'other' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Vertragsbeginn')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Vertragsende')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('Unbefristet')
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contract_value')
                    ->label('Vertragswert')
                    ->formatStateUsing(fn ($state) => $state ? '€ ' . number_format($state, 2, ',', '.') : '-')
                    ->sortable()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Entwurf',
                        'active' => 'Aktiv',
                        'expired' => 'Abgelaufen',
                        'terminated' => 'Gekündigt',
                        'completed' => 'Abgeschlossen',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'expired' => 'danger',
                        'terminated' => 'warning',
                        'completed' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('contract_type')
                    ->label('Vertragsart')
                    ->options([
                        'installation' => 'Installation',
                        'maintenance' => 'Wartung',
                        'components' => 'Komponenten',
                        'planning' => 'Planung',
                        'monitoring' => 'Überwachung',
                        'insurance' => 'Versicherung',
                        'financing' => 'Finanzierung',
                        'other' => 'Sonstiges',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Entwurf',
                        'active' => 'Aktiv',
                        'expired' => 'Abgelaufen',
                        'terminated' => 'Gekündigt',
                        'completed' => 'Abgeschlossen',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktive Verträge'),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Läuft bald ab (nächste 30 Tage)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('end_date', '>=', now())
                              ->where('end_date', '<=', now()->addDays(30))
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->url(fn ($record) => route('filament.admin.resources.supplier-contracts.view', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\EditAction::make()
                        ->url(fn ($record) => route('filament.admin.resources.supplier-contracts.edit', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('create_billing')
                        ->label('Abrechnung erfassen')
                        ->icon('heroicon-o-calculator')
                        ->color('warning')
                        ->modalWidth('7xl')
                        ->modalHeading('Neue Abrechnung erfassen')
                        ->modalDescription('Erstellen Sie eine neue Abrechnung für diesen Vertrag.')
                        ->form([
                            Forms\Components\Section::make('Abrechnungsdetails')
                                ->schema([
                                    Forms\Components\Hidden::make('supplier_contract_id')
                                        ->default(fn ($record) => $record->id),
                                    
                                    Forms\Components\TextInput::make('supplier_invoice_number')
                                        ->label('Anbieter-Rechnungsnummer')
                                        ->maxLength(255)
                                        ->placeholder('Rechnungsnummer des Anbieters'),

                                    Forms\Components\Select::make('billing_type')
                                        ->label('Abrechnungstyp')
                                        ->options([
                                            'invoice' => 'Rechnung',
                                            'credit_note' => 'Gutschrift',
                                        ])
                                        ->default('invoice')
                                        ->required(),

                                    Forms\Components\Select::make('billing_year')
                                        ->label('Abrechnungsjahr')
                                        ->options(function () {
                                            $currentYear = now()->year;
                                            $years = [];
                                            for ($year = $currentYear - 5; $year <= $currentYear + 2; $year++) {
                                                $years[$year] = $year;
                                            }
                                            return $years;
                                        })
                                        ->default(function () {
                                            $lastMonth = now()->subMonth();
                                            return $lastMonth->year;
                                        })
                                        ->searchable(),

                                    Forms\Components\Select::make('billing_month')
                                        ->label('Abrechnungsmonat')
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
                                        ->default(function () {
                                            $lastMonth = now()->subMonth();
                                            return $lastMonth->month;
                                        })
                                        ->searchable(),

                                    Forms\Components\TextInput::make('title')
                                        ->label('Titel')
                                        ->required()
                                        ->maxLength(255),

                                    Forms\Components\Textarea::make('description')
                                        ->label('Beschreibung')
                                        ->rows(3)
                                        ->columnSpanFull(),

                                    Forms\Components\DatePicker::make('billing_date')
                                        ->label('Abrechnungsdatum')
                                        ->required()
                                        ->default(now()),

                                    Forms\Components\DatePicker::make('due_date')
                                        ->label('Fälligkeitsdatum')
                                        ->after('billing_date'),

                                    Forms\Components\TextInput::make('total_amount')
                                        ->label('Gesamtbetrag')
                                        ->required()
                                        ->numeric()
                                        ->step(0.01)
                                        ->prefix('€')
                                        ->minValue(0),

                                    Forms\Components\Select::make('currency')
                                        ->label('Währung')
                                        ->options([
                                            'EUR' => 'Euro (€)',
                                            'USD' => 'US-Dollar ($)',
                                            'CHF' => 'Schweizer Franken (CHF)',
                                        ])
                                        ->default('EUR')
                                        ->required(),

                                    Forms\Components\Select::make('status')
                                        ->label('Status')
                                        ->options([
                                            'draft' => 'Entwurf',
                                            'pending' => 'Ausstehend',
                                            'approved' => 'Genehmigt',
                                            'paid' => 'Bezahlt',
                                            'cancelled' => 'Storniert',
                                        ])
                                        ->default('draft')
                                        ->required(),

                                    Forms\Components\Textarea::make('notes')
                                        ->label('Notizen')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ])
                        ->action(function (array $data, $record) {
                            // Erstelle die Abrechnung
                            $billing = \App\Models\SupplierContractBilling::create($data);
                            
                            // Benachrichtigung
                            \Filament\Notifications\Notification::make()
                                ->title('Abrechnung erfolgreich erstellt')
                                ->body("Die Abrechnung wurde erstellt und kann unter Lieferanten → Abrechnungen bearbeitet werden.")
                                ->success()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->label('Anzeigen')
                                        ->url(route('filament.admin.resources.supplier-contract-billings.view', $billing))
                                        ->button(),
                                ])
                                ->send();
                        }),
                    Tables\Actions\Action::make('show_billings')
                        ->label('Abrechnungen anzeigen')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->modalWidth('7xl')
                        ->modalHeading(fn ($record) => "Abrechnungen für Vertrag: {$record->contract_number}")
                        ->modalContent(function ($record) {
                            return view('filament.components.billings-table-modal', [
                                'contract' => $record
                            ]);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Schließen'),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->defaultSort('start_date', 'desc')
            ->emptyStateHeading('Keine Verträge zugeordnet')
            ->emptyStateDescription('Dieser Solaranlage sind noch keine Verträge zugeordnet. Verträge können über die Lieferantenverwaltung erstellt und zugeordnet werden.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->headerActions([
                Tables\Actions\Action::make('create_contract')
                    ->label('Neuen Vertrag erstellen')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->modalWidth('7xl')
                    ->modalHeading('Neuen Vertrag erstellen')
                    ->modalDescription('Erstellen Sie einen neuen Lieferantenvertrag für diese Solaranlage.')
                    ->extraModalWindowAttributes(['class' => 'contract-creation-modal'])
                    ->form($this->getContractForm())
                    ->action(function (array $data, $livewire) {
                        // Erstelle den neuen Vertrag
                        $contract = SupplierContract::create($data);
                        
                        // Erstelle automatisch die Zuordnung zur Solaranlage
                        $contract->solarPlantAssignments()->create([
                            'solar_plant_id' => $this->getOwnerRecord()->id,
                            'percentage' => 100.00,
                            'is_active' => true,
                        ]);
                        
                        // Benachrichtigung
                        Notification::make()
                            ->title('Vertrag erfolgreich erstellt')
                            ->body("Der Vertrag '{$contract->contract_number}' wurde erstellt und der Solaranlage zugeordnet.")
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('Anzeigen')
                                    ->url(route('filament.admin.resources.supplier-contracts.view', $contract))
                                    ->openUrlInNewTab()
                                    ->button(),
                            ])
                            ->send();
                        
                        // Aktualisiere die Tabelle
                        $livewire->dispatch('$refresh');
                    })
                    ->modalSubmitActionLabel('Vertrag erstellen'),
            ])
            ->emptyStateActions([
                Tables\Actions\Action::make('create_contract')
                    ->label('Neuen Vertrag erstellen')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->modalWidth('7xl')
                    ->modalHeading('Neuen Vertrag erstellen')
                    ->modalDescription('Erstellen Sie einen neuen Lieferantenvertrag für diese Solaranlage.')
                    ->extraModalWindowAttributes(['class' => 'contract-creation-modal'])
                    ->form($this->getContractForm())
                    ->action(function (array $data, $livewire) {
                        // Erstelle den neuen Vertrag
                        $contract = SupplierContract::create($data);
                        
                        // Erstelle automatisch die Zuordnung zur Solaranlage
                        $contract->solarPlantAssignments()->create([
                            'solar_plant_id' => $this->getOwnerRecord()->id,
                            'percentage' => 100.00,
                            'is_active' => true,
                        ]);
                        
                        // Benachrichtigung
                        Notification::make()
                            ->title('Vertrag erfolgreich erstellt')
                            ->body("Der Vertrag '{$contract->contract_number}' wurde erstellt und der Solaranlage zugeordnet.")
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('Anzeigen')
                                    ->url(route('filament.admin.resources.supplier-contracts.view', $contract))
                                    ->openUrlInNewTab()
                                    ->button(),
                            ])
                            ->send();
                        
                        // Aktualisiere die Tabelle
                        $livewire->dispatch('$refresh');
                    })
                    ->modalSubmitActionLabel('Vertrag erstellen'),
            ]);
    }

    protected function getContractForm(): array
    {
        return [
            Forms\Components\Section::make('Vertragsdaten')
                ->schema([
                    // Titel in separater Zeile über komplette Breite
                    Forms\Components\TextInput::make('title')
                        ->label('Titel')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Beliebiger Kurztext zur Erkennung in Listen')
                        ->columnSpanFull(),
                    
                    // Beschreibung unter Titel über komplette Breite
                    Forms\Components\Textarea::make('description')
                        ->label('Beschreibung')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    
                    // Restliche Felder im Grid
                    Forms\Components\Select::make('supplier_id')
                        ->label('Lieferant')
                        ->options(Supplier::active()->orderBy('company_name')->pluck('company_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),
                    
                    // Status nach Lieferant
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft' => 'Entwurf',
                            'active' => 'Aktiv',
                            'expired' => 'Abgelaufen',
                            'terminated' => 'Gekündigt',
                            'completed' => 'Abgeschlossen',
                        ])
                        ->default('draft')
                        ->required(),
                    
                    Forms\Components\TextInput::make('creditor_number')
                        ->label('Eigene Kundennummer bei Lieferant')
                        ->maxLength(255)
                        ->placeholder('z.B. KR-12345'),
                    
                    Forms\Components\TextInput::make('contract_number')
                        ->label('Vertragsnummer intern')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    
                    Forms\Components\TextInput::make('external_contract_number')
                        ->label('Vertragsnummer extern')
                        ->maxLength(255)
                        ->placeholder('z.B. EXT-2024-001'),
                    
                    Forms\Components\TextInput::make('malo_id')
                        ->label('MaLo-ID')
                        ->helperText('Marktlokations-ID')
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('ep_id')
                        ->label('EP-ID')
                        ->helperText('Einspeisepunkt-ID')
                        ->maxLength(255),
                    
                    // Dummy Fields in Spalte 2 unten nacheinander
                    ...DummyFieldConfig::getDummyFieldsSchema('supplier_contract'),
                ])->columns(2),

            Forms\Components\Section::make('Laufzeit & Wert')
                ->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Startdatum'),
                    
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Enddatum'),
                    
                    Forms\Components\TextInput::make('contract_value')
                        ->label('Vertragswert')
                        ->numeric()
                        ->step(0.01)
                        ->prefix('€'),
                    
                    Forms\Components\Select::make('currency')
                        ->label('Währung')
                        ->options([
                            'EUR' => 'Euro (EUR)',
                            'USD' => 'US-Dollar (USD)',
                            'CHF' => 'Schweizer Franken (CHF)',
                        ])
                        ->default('EUR'),
                ])->columns(2),

            Forms\Components\Section::make('Vertragserkennung')
                ->description('Diese Informationen werden zur automatischen Vertragserkennung benötigt. Es müssen nicht alle Felder befüllt werden.')
                ->schema([
                    Forms\Components\TextInput::make('contract_recognition_1')
                        ->label('Vertragserkennung 1')
                        ->maxLength(255)
                        ->placeholder('z.B. Erkennungsmerkmal 1'),
                    
                    Forms\Components\TextInput::make('contract_recognition_2')
                        ->label('Vertragserkennung 2')
                        ->maxLength(255)
                        ->placeholder('z.B. Erkennungsmerkmal 2'),
                    
                    Forms\Components\TextInput::make('contract_recognition_3')
                        ->label('Vertragserkennung 3')
                        ->maxLength(255)
                        ->placeholder('z.B. Erkennungsmerkmal 3'),
                ])->columns(2),

            Forms\Components\Section::make('Zusätzliche Informationen')
                ->schema([
                    Forms\Components\Textarea::make('payment_terms')
                        ->label('Zahlungsbedingungen')
                        ->rows(3),
                    
                    Forms\Components\Textarea::make('notes')
                        ->label('Notizen')
                        ->rows(3),
                    
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktiv')
                        ->default(true),
                ]),
        ];
    }
}
