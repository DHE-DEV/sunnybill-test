<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierContractBillingResource\Pages;
use App\Filament\Resources\SupplierContractBillingResource\RelationManagers;
use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierContractBillingResource extends Resource
{
    protected static ?string $model = SupplierContractBilling::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Abrechnungen';

    protected static ?string $modelLabel = 'Abrechnung';

    protected static ?string $pluralModelLabel = 'Abrechnungen';

    protected static ?string $navigationGroup = 'Lieferanten';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Abrechnungsdetails')
                    ->schema([
                        Forms\Components\Select::make('supplier_contract_id')
                            ->label('Lieferantenvertrag')
                            ->relationship('supplierContract', 'title')
                            ->getOptionLabelFromRecordUsing(function (SupplierContract $record): string {
                                $supplierNumber = $record->supplier && !empty($record->supplier->supplier_number)
                                    ? (string) $record->supplier->supplier_number
                                    : 'Unbekannt';
                                return "{$record->contract_number} - {$record->title} ({$supplierNumber})";
                            })
                            ->searchable(['contract_number', 'title'])
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('billing_number')
                            ->label('Abrechnungsnummer')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Wird automatisch generiert'),

                        Forms\Components\TextInput::make('supplier_invoice_number')
                            ->label('Anbieter-Rechnungsnummer')
                            ->maxLength(255)
                            ->placeholder('Rechnungsnummer des Anbieters'),

                        Forms\Components\Select::make('billing_type')
                            ->label('Abrechnungstyp')
                            ->options(SupplierContractBilling::getBillingTypeOptions())
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
                            ->options(SupplierContractBilling::getMonthOptions())
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
                            ->after('billing_date')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Betragsangaben')
                    ->schema([
                        Forms\Components\Select::make('currency')
                            ->label('Währung')
                            ->options([
                                'EUR' => 'Euro (€)',
                                'USD' => 'US-Dollar ($)',
                                'CHF' => 'Schweizer Franken (CHF)',
                            ])
                            ->default('EUR')
                            ->required(),

                        Forms\Components\TextInput::make('net_amount')
                            ->label('Betrag Netto')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('€')
                            ->minValue(0)
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                // Berechne Gesamtbetrag wenn Nettobetrag und MwSt. vorhanden sind
                                $vatRate = floatval($get('vat_rate') ?? 0);
                                if ($state && $vatRate >= 0) {
                                    $totalAmount = $state * (1 + ($vatRate / 100));
                                    $set('total_amount', round($totalAmount, 2));
                                }
                            }),

                        Forms\Components\TextInput::make('vat_rate')
                            ->label('MwSt. (%)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(19.00)
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                // Berechne Gesamtbetrag wenn Nettobetrag und MwSt. vorhanden sind
                                $netAmount = floatval($get('net_amount') ?? 0);
                                if ($netAmount && $state >= 0) {
                                    $totalAmount = $netAmount * (1 + ($state / 100));
                                    $set('total_amount', round($totalAmount, 2));
                                }
                            }),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Gesamtbetrag')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->prefix('€')
                            ->minValue(0)
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                // Aktualisiere alle Aufteilungsbeträge basierend auf dem neuen Gesamtbetrag
                                $allocations = $get('allocations') ?? [];
                                $updatedAllocations = [];
                                
                                foreach ($allocations as $index => $allocation) {
                                    $percentage = floatval($allocation['percentage'] ?? 0);
                                    if ($percentage > 0 && $state) {
                                        $allocation['amount'] = round(($state * $percentage) / 100, 2);
                                    }
                                    $updatedAllocations[] = $allocation;
                                }
                                
                                $set('allocations', $updatedAllocations);
                                
                                // Berechne Nettobetrag wenn Gesamtbetrag und MwSt. vorhanden sind
                                $vatRate = floatval($get('vat_rate') ?? 0);
                                if ($state && $vatRate >= 0) {
                                    $netAmount = $state / (1 + ($vatRate / 100));
                                    $set('net_amount', round($netAmount, 2));
                                }
                            }),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Sonstige Angaben')
                    ->schema([

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(SupplierContractBilling::getStatusOptions())
                            ->default('draft')
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                /*    
                Forms\Components\Section::make('Kostenträger-Aufteilungen')
                    ->schema([
                        Forms\Components\Repeater::make('allocations')
                            ->label('')
                            ->relationship('allocations')
                            ->schema([
                                Forms\Components\Select::make('solar_plant_id')
                                    ->label('Kostenträger (Solaranlage)')
                                    ->options(function (callable $get) {
                                        $contractId = $get('../../supplier_contract_id');
                                        if (!$contractId) {
                                            return [];
                                        }
                                        
                                        $contract = \App\Models\SupplierContract::find($contractId);
                                        if (!$contract) {
                                            return [];
                                        }
                                        
                                        // Prüfe zuerst ob es Solaranlagen-Zuordnungen gibt
                                        $assignedPlants = $contract->activeSolarPlants();
                                        
                                        if ($assignedPlants->count() > 0) {
                                            // Verwende zugeordnete Solaranlagen
                                            return $assignedPlants->get()
                                                ->filter(fn($plant) => !empty($plant->name) && is_string($plant->name))
                                                ->mapWithKeys(fn($plant) => [$plant->id => (string) $plant->name])
                                                ->toArray();
                                        } else {
                                            // Fallback: Alle aktiven Solaranlagen
                                            return \App\Models\SolarPlant::where('is_active', true)
                                                ->whereNotNull('name')
                                                ->where('name', '!=', '')
                                                ->orderBy('name')
                                                ->get()
                                                ->filter(fn($plant) => !empty($plant->name) && is_string($plant->name))
                                                ->mapWithKeys(fn($plant) => [$plant->id => (string) $plant->name])
                                                ->toArray();
                                        }
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->helperText('Verfügbare Solaranlagen für die Kostenaufteilung.')
                                    ->disableOptionWhen(fn ($value, $label) => empty($label) || !is_string($label)),

                                Forms\Components\TextInput::make('percentage')
                                    ->label('Prozentsatz (%)')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                        // Berechne automatisch den Betrag basierend auf dem Prozentsatz
                                        $totalAmount = $get('../../total_amount');
                                        if ($totalAmount && $state) {
                                            $amount = ($totalAmount * $state) / 100;
                                            $set('amount', round($amount, 2));
                                        }
                                    }),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Betrag')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('€')
                                    ->minValue(0)
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                        // Berechne automatisch den Prozentsatz basierend auf dem Betrag
                                        $totalAmount = $get('../../total_amount');
                                        if ($totalAmount && $state && $totalAmount > 0) {
                                            $percentage = ($state / $totalAmount) * 100;
                                            $set('percentage', round($percentage, 2));
                                        }
                                    }),

                                Forms\Components\Textarea::make('notes')
                                    ->label('Notizen')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true),
                            ])
                            ->columns(3)
                            ->defaultItems(fn ($record) => $record ? 0 : 1)
                            ->addActionLabel('Neue Aufteilung hinzufügen')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                if (!isset($state['solar_plant_id']) || !isset($state['percentage'])) {
                                    return 'Neue Aufteilung';
                                }
                                
                                $solarPlant = \App\Models\SolarPlant::find($state['solar_plant_id']);
                                $plantName = $solarPlant && !empty($solarPlant->name) ? $solarPlant->name : 'Unbekannte Anlage';
                                $percentage = $state['percentage'] ?? 0;
                                
                                return "{$plantName} - {$percentage}%";
                            })
                            ->live()
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                // Berechne die Gesamtsumme aller Prozentsätze
                                $allocations = $get('allocations') ?? [];
                                $totalPercentage = 0;
                                
                                foreach ($allocations as $allocation) {
                                    $totalPercentage += floatval($allocation['percentage'] ?? 0);
                                }
                                
                                // Setze eine versteckte Validierung für die Gesamtsumme
                                $set('total_allocation_percentage', $totalPercentage);
                            }),

                        Forms\Components\Hidden::make('total_allocation_percentage')
                            ->default(0)
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if ($value > 100) {
                                            $fail('Die Gesamtsumme aller Prozentsätze darf 100% nicht überschreiten. Aktuell: ' . number_format($value, 2, ',', '.') . '%');
                                        }
                                    };
                                },
                            ]),

                        Forms\Components\View::make('filament.forms.components.allocation-summary')
                            ->viewData(function (callable $get): array {
                                $allocations = $get('allocations') ?? [];
                                $totalAmount = floatval($get('total_amount') ?? 0);
                                $totalPercentage = 0;
                                $totalAllocatedAmount = 0;
                                
                                foreach ($allocations as $allocation) {
                                    $percentage = floatval($allocation['percentage'] ?? 0);
                                    $amount = floatval($allocation['amount'] ?? 0);
                                    $totalPercentage += $percentage;
                                    $totalAllocatedAmount += $amount;
                                }
                                
                                $remainingPercentage = max(0, 100 - $totalPercentage);
                                $remainingAmount = max(0, $totalAmount - $totalAllocatedAmount);
                                
                                $percentageColor = $totalPercentage > 100 ? 'text-red-600' : ($totalPercentage == 100 ? 'text-green-600' : 'text-yellow-600');
                                $amountColor = $totalAllocatedAmount > $totalAmount ? 'text-red-600' : 'text-green-600';
                                
                                return [
                                    'totalAmount' => $totalAmount,
                                    'totalAllocatedAmount' => $totalAllocatedAmount,
                                    'remainingAmount' => $remainingAmount,
                                    'totalPercentage' => $totalPercentage,
                                    'remainingPercentage' => $remainingPercentage,
                                    'allocationsCount' => count($allocations),
                                    'percentageColor' => $percentageColor,
                                    'amountColor' => $amountColor,
                                ];
                            })
                            ->live(),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => $record === null), // Collapsed when creating new record

                Forms\Components\Section::make('Dokumente')
                    ->schema([
                        Forms\Components\FileUpload::make('invoice_document')
                            ->label('Anbieter-Rechnung (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->directory(function (callable $get) {
                                $contractId = $get('supplier_contract_id');
                                if ($contractId) {
                                    $contract = \App\Models\SupplierContract::find($contractId);
                                    if ($contract && $contract->supplier) {
                                        $supplierName = \Illuminate\Support\Str::slug($contract->supplier->name);
                                        return "suppliers/{$supplierName}/invoices";
                                    }
                                }
                                return 'suppliers/invoices';
                            })
                            ->maxSize(51200) // 50MB
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->columnSpanFull()
                            ->helperText('Laden Sie die PDF-Rechnung des Anbieters hoch (max. 10MB)'),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => $record === null), // Collapsed when creating new record
                    */
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('billing_number')
                    ->label('Abrechnungsnummer')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('solar_plant_name')
                    ->label('Solaranlage')
                    ->getStateUsing(function (SupplierContractBilling $record): ?string {
                        $contract = $record->supplierContract;
                        if ($contract) {
                            $solarPlant = $contract->solarPlants()->first();
                            return $solarPlant?->name ?? 'Keine Zuordnung';
                        }
                        return 'Keine Zuordnung';
                    })
                    ->color('primary')
                    ->weight('medium')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('supplierContract.solarPlants', function (Builder $query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(false)
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('supplier_invoice_number')
                    ->label('Anbieter-Rechnung')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('billing_type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => SupplierContractBilling::getBillingTypeOptions()[$state] ?? $state)
                    ->colors([
                        'primary' => 'invoice',
                        'success' => 'credit_note',
                    ]),

                Tables\Columns\TextColumn::make('billing_period')
                    ->label('Abrechnungsperiode')
                    ->getStateUsing(function (SupplierContractBilling $record): ?string {
                        return $record->billing_period;
                    })
                    ->sortable(['billing_year', 'billing_month'])
                    ->searchable(false)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('supplierContract.contract_number')
                    ->label('Vertragsnummer')
                    ->searchable()
                    ->sortable()
                    ->url(fn (SupplierContractBilling $record): string => 
                        route('filament.admin.resources.supplier-contracts.view', $record->supplier_contract_id)
                    )
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('supplier_display_name')
                    ->label('Lieferant')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('supplierContract.supplier', function (Builder $query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                  ->orWhere('company_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->join('supplier_contracts', 'supplier_contract_billings.supplier_contract_id', '=', 'supplier_contracts.id')
                                    ->join('suppliers', 'supplier_contracts.supplier_id', '=', 'suppliers.id')
                                    ->orderBy('suppliers.name', $direction)
                                    ->select('supplier_contract_billings.*');
                    })
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->placeholder('Kein Lieferant')
                    ->getStateUsing(function (SupplierContractBilling $record): ?string {
                        $supplier = $record->supplierContract?->supplier;
                        if (!$supplier) {
                            return 'Kein Lieferant';
                        }
                        
                        // Verwende company_name oder fallback zu name
                        return $supplier->company_name ?? $supplier->name ?? 'Unbekannt';
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->weight('medium')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('billing_date')
                    ->label('Abrechnungsdatum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Fälligkeitsdatum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Gesamtbetrag')
                    ->money('EUR')
                    ->sortable()
                    ->weight('medium')
                    ->alignEnd(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => SupplierContractBilling::getStatusOptions()[$state] ?? $state)
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending',
                        'primary' => 'approved',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('allocations_count')
                    ->label('Aufteilungen')
                    ->counts('allocations')
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('allocated_percentage')
                    ->label('Verteilt (%)')
                    ->getStateUsing(function (SupplierContractBilling $record): string {
                        $percentage = $record->allocations()->sum('percentage');
                        return number_format($percentage, 1, ',', '.') . '%';
                    })
                    ->color(function (SupplierContractBilling $record): string {
                        $percentage = $record->allocations()->sum('percentage');
                        if ($percentage > 100) return 'danger';
                        if ($percentage == 100) return 'success';
                        if ($percentage > 0) return 'warning';
                        return 'gray';
                    })
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SupplierContractBilling::getStatusOptions()),

                Tables\Filters\SelectFilter::make('billing_type')
                    ->label('Abrechnungstyp')
                    ->options(SupplierContractBilling::getBillingTypeOptions()),

                Tables\Filters\SelectFilter::make('billing_year')
                    ->label('Abrechnungsjahr')
                    ->options(function () {
                        $currentYear = now()->year;
                        $years = [];
                        for ($year = $currentYear - 5; $year <= $currentYear + 2; $year++) {
                            $years[$year] = $year;
                        }
                        return $years;
                    }),

                Tables\Filters\SelectFilter::make('billing_month')
                    ->label('Abrechnungsmonat')
                    ->options(SupplierContractBilling::getMonthOptions()),

                Tables\Filters\SelectFilter::make('supplier')
                    ->label('Lieferant')
                    ->relationship('supplierContract.supplier', 'name', function ($query) {
                        return $query->where(function ($q) {
                            $q->whereNotNull('name')->where('name', '!=', '')
                              ->orWhere(function ($q2) {
                                  $q2->whereNotNull('company_name')->where('company_name', '!=', '');
                              });
                        });
                    })
                    ->getOptionLabelFromRecordUsing(function (\App\Models\Supplier $record): string {
                        // Verwende company_name oder fallback zu name
                        return $record->company_name ?? $record->name ?? 'Unbekannt';
                    })
                    ->searchable(['name', 'company_name'])
                    ->preload(),

                Tables\Filters\Filter::make('billing_date')
                    ->label('Abrechnungsdatum')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Von'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('billing_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('billing_date', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('overdue')
                    ->label('Überfällig')
                    ->query(fn (Builder $query): Builder => $query->where('due_date', '<', now())->where('status', '!=', 'paid'))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('created_period')
                    ->label('Erfasst')
                    ->options([
                        'today' => 'Heute',
                        'this_week' => 'Diese Woche',
                        'this_month' => 'Dieser Monat',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value']) || empty($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'today' => $query->whereDate('created_at', now()->toDateString()),
                            'this_week' => $query->whereBetween('created_at', [
                                now()->startOfWeek()->toDateTimeString(),
                                now()->endOfWeek()->toDateTimeString()
                            ]),
                            'this_month' => $query->whereBetween('created_at', [
                                now()->startOfMonth()->toDateTimeString(),
                                now()->endOfMonth()->toDateTimeString()
                            ]),
                            default => $query,
                        };
                    }),

                Tables\Filters\SelectFilter::make('solar_plant')
                    ->label('Kostenträger (Solaranlage)')
                    ->relationship('allocations.solarPlant', 'name')
                    ->getOptionLabelFromRecordUsing(function (\App\Models\SolarPlant $record): string {
                        $plantNumber = $record->plant_number ?? 'Keine Nr.';
                        $plantName = $record->name ?? 'Unbenannt';
                        return "{$plantNumber} - {$plantName}";
                    })
                    ->searchable(['plant_number', 'name'])
                    ->preload()
                    ->multiple(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->url(fn (SupplierContractBilling $record): string =>
                            static::getUrl('view', ['record' => $record])
                        ),
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten'),
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
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('Endgültig löschen'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Wiederherstellen'),
                ]),
            ])
            ->defaultSort('billing_date', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ArticlesRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\AllocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierContractBillings::route('/'),
            'create' => Pages\CreateSupplierContractBilling::route('/create'),
            'view' => Pages\ViewSupplierContractBilling::route('/{record}'),
            'edit' => Pages\EditSupplierContractBilling::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['supplierContract.supplier', 'allocations']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}
