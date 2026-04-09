<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class ArticlesRelationManager extends RelationManager
{
    protected static string $relationship = 'articles';

    protected static ?string $title = 'Artikel';

    protected static ?string $modelLabel = 'Artikel';

    protected static ?string $pluralModelLabel = 'Artikel';

    protected static ?string $icon = 'heroicon-o-cube';

    public static function getBadge(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->articles()->count();
        return $count > 0 ? (string) $count : null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Artikel hinzufügen')
                    ->description('Fügen Sie diesem Kunden einen Artikel aus der Artikelverwaltung hinzu.')
                    ->schema([
                        Forms\Components\Select::make('id')
                            ->label('Artikel')
                            ->options(Article::query()
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(function ($article) {
                                    return [$article->id => $article->name . ' (' . $article->formatted_price . ')'];
                                })
                                ->toArray())
                            ->searchable()
                            ->required()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $article = Article::find($state);
                                    if ($article) {
                                        $set('unit_price', $article->price);
                                        $set('article_info', [
                                            'name' => $article->name,
                                            'description' => $article->description,
                                            'price' => $article->formatted_price,
                                            'tax_rate' => $article->tax_rate_percent,
                                        ]);
                                    }
                                }
                            }),
                        
                        Forms\Components\Placeholder::make('article_info')
                            ->label('Artikel-Info')
                            ->content(function ($get) {
                                $articleId = $get('id');
                                if (!$articleId) return 'Kein Artikel ausgewählt';
                                
                                $article = Article::find($articleId);
                                if (!$article) return 'Artikel nicht gefunden';
                                
                                return "Name: {$article->name}\n" .
                                       "Beschreibung: " . ($article->description ?? 'Keine Beschreibung') . "\n" .
                                       "Preis: {$article->formatted_price}\n" .
                                       "Steuersatz: {$article->tax_rate_percent}";
                            })
                            ->visible(fn ($get) => $get('id')),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Menge')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->required()
                            ->default(1.00)
                            ->suffix('Stk.')
                            ->helperText('Anzahl der Artikel für diesen Kunden'),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Stückpreis')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->prefix('€')
                            ->helperText('Leer lassen um den Standard-Artikelpreis zu verwenden')
                            ->placeholder('Wird automatisch gesetzt'),

                        Forms\Components\Textarea::make('customer_notes')
                            ->label('Notizen')
                            ->rows(2)
                            ->maxLength(1000)
                            ->placeholder('Zusätzliche Informationen zu diesem Artikel...'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Ausführliche Erklärung')
                            ->rows(4)
                            ->maxLength(5000)
                            ->placeholder('Zusätzliche Details und Erklärungen zum Artikel...')
                            ->helperText('Umfassende Informationen und Erklärungen für diesen Kundenartikel (max. 5000 Zeichen)')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Nur aktive Artikel werden bei Berechnungen berücksichtigt.'),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Artikelname')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(30),
                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Ausführliche Erklärung')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->label('Artikeltyp')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'service' => 'Dienstleistung',
                        'product' => 'Produkt',
                        'subscription' => 'Abonnement',
                        'maintenance' => 'Wartung',
                        'other' => 'Sonstiges',
                        default => $state,
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pivot.quantity')
                    ->label('Menge')
                    ->numeric(2)
                    ->alignRight()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Einheit')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Preis (Netto)')
                    ->alignRight()
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('formatted_unit_price')
                    ->label('Stückpreis')
                    ->alignRight()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        $unitPrice = $record->pivot->unit_price ?? $record->price;
                        $decimalPlaces = $record->decimal_places ?? 2;
                        return number_format($unitPrice, $decimalPlaces, ',', '.') . ' €';
                    }),
                Tables\Columns\TextColumn::make('formatted_total_price')
                    ->label('Gesamtpreis netto')
                    ->alignRight()
                    ->badge()
                    ->color('success')
                    ->getStateUsing(function ($record) {
                        $unitPrice = $record->pivot->unit_price ?? $record->price;
                        $quantity = $record->pivot->quantity ?? 1;
                        $total = $unitPrice * $quantity;
                        $decimalPlaces = $record->total_decimal_places ?? 2;
                        return number_format($total, $decimalPlaces, ',', '.') . ' €';
                    }),
                Tables\Columns\TextColumn::make('tax_rate_percent')
                    ->label('MwSt.')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('formatted_total_price_gross')
                    ->label('Gesamtpreis brutto')
                    ->alignRight()
                    ->badge()
                    ->color('info')
                    ->getStateUsing(function ($record) {
                        $unitPrice = $record->pivot->unit_price ?? $record->price;
                        $quantity = $record->pivot->quantity ?? 1;
                        $netTotal = $unitPrice * $quantity;
                        $taxRate = $record->getCurrentTaxRate();
                        $grossTotal = $netTotal * (1 + $taxRate);
                        $decimalPlaces = $record->total_decimal_places ?? 2;
                        return number_format($grossTotal, $decimalPlaces, ',', '.') . ' €';
                    })
                    ->toggleable(),
                Tables\Columns\IconColumn::make('pivot.is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('pivot.billing_requirement')
                    ->label('Abrechnung')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'optional' => 'gray',
                        'mandatory' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'optional' => 'Optional',
                        'mandatory' => 'Pflicht',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('solar_plant_name')
                    ->label('Solaranlage')
                    ->getStateUsing(fn ($record) => $record->pivot->solar_plant_id
                        ? \App\Models\SolarPlant::find($record->pivot->solar_plant_id)?->name
                        : '-')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('supplier_name')
                    ->label('Lieferant')
                    ->getStateUsing(fn ($record) => $record->pivot->supplier_id
                        ? \App\Models\Supplier::find($record->pivot->supplier_id)?->name
                        : '-')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pivot.billing_type')
                    ->label('Berechnungstyp')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'invoice' => 'Rechnung',
                        'credit' => 'Gutschrift',
                        default => $state ?? '-',
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'invoice' => 'success',
                        'credit' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pivot.valid_from')
                    ->label('Gültig von')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pivot.valid_to')
                    ->label('Gültig bis')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pivot.price_increase_percentage')
                    ->label('Preiserhöhung %')
                    ->numeric(2)
                    ->suffix(' %')
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pivot.price_increase_interval_months')
                    ->label('Erhöhungsintervall')
                    ->suffix(' Monate')
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pivot.price_increase_start_date')
                    ->label('Erste Erhöhung ab')
                    ->date('d.m.Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pivot.notes')
                    ->label('Kundenspezifische Notizen')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('decimal_places')
                    ->label('Nachkommastellen (Preis)')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_decimal_places')
                    ->label('Nachkommastellen (Gesamt)')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('taxRate.name')
                    ->label('Steuersatz')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('Hinzugefügt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('pivot.is_active')
                    ->label('Status')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive')
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Artikel neu anlegen')
                    ->icon('heroicon-o-plus-circle')
                    ->modalWidth('4xl')
                    ->form([
                        Forms\Components\Section::make('Neuen Artikel erstellen')
                            ->description('Erstellen Sie einen neuen Artikel und fügen Sie ihn automatisch zu diesem Kunden hinzu.')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Artikelname')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('z.B. Solarmodul XYZ')
                                    ->columnSpanFull(),
                                
                                Forms\Components\Textarea::make('description')
                                    ->label('Beschreibung')
                                    ->rows(2)
                                    ->maxLength(1000)
                                    ->placeholder('Detaillierte Beschreibung des Artikels...')
                                    ->columnSpanFull(),
                                
                                Forms\Components\Textarea::make('notes')
                                    ->label('Ausführliche Erklärung')
                                    ->rows(4)
                                    ->maxLength(5000)
                                    ->placeholder('Zusätzliche Details und Erklärungen zum Artikel...')
                                    ->helperText('Umfassende Informationen und Erklärungen für diesen Kundenartikel (max. 5000 Zeichen)')
                                    ->columnSpanFull(),
                                
                                Forms\Components\Select::make('type')
                                    ->label('Artikeltyp')
                                    ->options([
                                        'service' => 'Dienstleistung',
                                        'product' => 'Produkt',
                                        'subscription' => 'Abonnement',
                                        'maintenance' => 'Wartung',
                                        'other' => 'Sonstiges',
                                    ])
                                    ->default('product')
                                    ->live()
                                    ->required(),
                                
                                Forms\Components\TextInput::make('unit')
                                    ->label('Einheit')
                                    ->maxLength(50)
                                    ->default('Stk.')
                                    ->placeholder('z.B. Stk., Std., m²')
                                    ->visible(fn (Forms\Get $get) => !empty($get('type'))),
                                
                                Forms\Components\TextInput::make('price')
                                    ->label('Preis (Netto)')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->minValue(0)
                                    ->required()
                                    ->prefix('€')
                                    ->placeholder('0,000000')
                                    ->helperText('Bis zu 6 Nachkommastellen möglich'),
                                
                                Forms\Components\Select::make('tax_rate_id')
                                    ->label('Steuersatz')
                                    ->options(\App\Models\TaxRate::active()->get()->mapWithKeys(function ($taxRate) {
                                        return [$taxRate->id => $taxRate->name];
                                    }))
                                    ->default(function () {
                                        $defaultTaxRate = \App\Models\TaxRate::getCurrentDefault();
                                        return $defaultTaxRate?->id;
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                
                                Forms\Components\TextInput::make('decimal_places')
                                    ->label('Nachkommastellen (Preis)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(6)
                                    ->default(2)
                                    ->helperText('Anzahl der Nachkommastellen für die Preisanzeige'),
                                
                                Forms\Components\TextInput::make('total_decimal_places')
                                    ->label('Nachkommastellen (Gesamtpreis)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(6)
                                    ->default(2)
                                    ->helperText('Anzahl der Nachkommastellen für Gesamtpreise'),
                            ])->columns(2),
                        
                        Forms\Components\Section::make('Kundenverknüpfung')
                            ->description('Konfigurieren Sie, wie dieser Artikel mit dem Kunden verknüpft wird.')
                            ->schema([
                                Forms\Components\Select::make('solar_plant_id')
                                    ->label('Zuordnung Solaranlage')
                                    ->searchable()
                                    ->getSearchResultsUsing(fn (string $search): array =>
                                        \App\Models\SolarPlant::where('name', 'like', "%{$search}%")
                                            ->orderBy('name')
                                            ->limit(50)
                                            ->pluck('name', 'id')
                                            ->toArray()
                                    )
                                    ->getOptionLabelUsing(fn ($value): ?string =>
                                        \App\Models\SolarPlant::find($value)?->name
                                    )
                                    ->placeholder('Solaranlage suchen...')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('supplier_id')
                                    ->label('Zuordnung Lieferant')
                                    ->searchable()
                                    ->getSearchResultsUsing(fn (string $search): array =>
                                        \App\Models\Supplier::where('name', 'like', "%{$search}%")
                                            ->orderBy('name')
                                            ->limit(50)
                                            ->pluck('name', 'id')
                                            ->toArray()
                                    )
                                    ->getOptionLabelUsing(fn ($value): ?string =>
                                        \App\Models\Supplier::find($value)?->name
                                    )
                                    ->placeholder('Lieferant suchen...')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('billing_type')
                                    ->label('Berechnungstyp')
                                    ->options([
                                        'invoice' => 'Rechnung',
                                        'credit' => 'Gutschrift',
                                    ])
                                    ->default('invoice')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\DatePicker::make('valid_from')
                                    ->label('Gültig von'),

                                Forms\Components\DatePicker::make('valid_to')
                                    ->label('Gültig bis')
                                    ->afterOrEqual('valid_from'),

                                Forms\Components\Fieldset::make('Automatische Preiserhöhung')
                                    ->schema([
                                        Forms\Components\Placeholder::make('price_increase_notice')
                                            ->label('')
                                            ->content('Diese Funktion ist nicht aktiv. Sie können die Einstellungen bereits speichern. Sie werden jedoch nicht weiter verarbeitet. Eine automatische Preiserhöhung findet nicht statt. Diese Funktion wurde vom Kunden ausdrücklich nicht gewünscht.')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('price_increase_percentage')
                                            ->label('Preiserhöhung')
                                            ->numeric()
                                            ->step(0.01)
                                            ->minValue(0)
                                            ->suffix('%')
                                            ->placeholder('z.B. 2,5'),

                                        Forms\Components\TextInput::make('price_increase_interval_months')
                                            ->label('Intervall')
                                            ->numeric()
                                            ->minValue(1)
                                            ->suffix('Monate')
                                            ->placeholder('z.B. 12'),

                                        Forms\Components\DatePicker::make('price_increase_start_date')
                                            ->label('Erste Erhöhung ab'),
                                    ])->columns(3)->columnSpanFull(),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Menge')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0.01)
                                    ->required()
                                    ->default(1.00)
                                    ->suffix('Stk.')
                                    ->helperText('Anzahl der Artikel für diesen Kunden'),

                                Forms\Components\TextInput::make('unit_price_override')
                                    ->label('Abweichender Stückpreis (optional)')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->minValue(0)
                                    ->prefix('€')
                                    ->helperText('Leer lassen um den Standard-Artikelpreis zu verwenden. Bis zu 6 Nachkommastellen möglich.'),

                                Forms\Components\Textarea::make('customer_notes')
                                    ->label('Kundenspezifische Notizen')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->placeholder('Spezielle Notizen für diesen Artikel bei diesem Kunden...')
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true)
                                    ->helperText('Nur aktive Artikel werden bei Berechnungen berücksichtigt.'),
                                Forms\Components\Radio::make('billing_requirement')
                                    ->label('Anforderung bei Abrechnung')
                                    ->options([
                                        'optional' => 'Optional',
                                        'mandatory' => 'Pflichtartikel',
                                    ])
                                    ->default('optional')
                                    ->required()
                                    ->helperText('Festlegen, ob dieser Artikel bei der Abrechnung für diesen Kunden obligatorisch ist.'),
                            ])->columns(2),
                    ])
                    ->action(function (array $data) {
                        $taxRate = \App\Models\TaxRate::find($data['tax_rate_id']);
                        
                        $articleData = [
                            'name' => $data['name'],
                            'description' => $data['description'],
                            'notes' => $data['notes'] ?? null,
                            'type' => $data['type'],
                            'price' => $data['price'],
                            'tax_rate_id' => $data['tax_rate_id'],
                            'tax_rate' => $taxRate ? $taxRate->rate : 0.19,
                            'unit' => $data['unit'],
                            'decimal_places' => $data['decimal_places'],
                            'total_decimal_places' => $data['total_decimal_places'],
                        ];
                        
                        $article = Article::create($articleData);
                        
                        $pivotData = [
                            'quantity' => $data['quantity'],
                            'unit_price' => $data['unit_price_override'] ?? $article->price,
                            'notes' => $data['customer_notes'],
                            'is_active' => $data['is_active'],
                            'billing_requirement' => $data['billing_requirement'],
                            'valid_from' => $data['valid_from'] ?? null,
                            'valid_to' => $data['valid_to'] ?? null,
                            'billing_type' => $data['billing_type'] ?? 'invoice',
                            'solar_plant_id' => $data['solar_plant_id'] ?? null,
                            'supplier_id' => $data['supplier_id'] ?? null,
                            'price_increase_percentage' => $data['price_increase_percentage'] ?? null,
                            'price_increase_interval_months' => $data['price_increase_interval_months'] ?? null,
                            'price_increase_start_date' => $data['price_increase_start_date'] ?? null,
                        ];

                        $this->getOwnerRecord()->articles()->attach($article->id, $pivotData);
                        
                        Notification::make()
                            ->title('Artikel erstellt und hinzugefügt')
                            ->body("Der Artikel '{$article->name}' wurde erfolgreich erstellt und zum Kunden hinzugefügt.")
                            ->success()
                            ->send();
                    })
                    ->after(function ($livewire) {
                        $livewire->dispatch('refresh');
                    }),
                
                // AttachAction ausgeblendet
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-m-eye')
                        ->modalWidth('4xl')
                        ->fillForm(function ($record): array {
                            return [
                                'article_name' => $record->name,
                                'article_description' => $record->description,
                                'article_notes' => $record->notes,
                                'article_type' => $record->type,
                                'article_unit' => $record->unit,
                                'article_price' => $record->price,
                                'article_tax_rate_id' => $record->tax_rate_id,
                                'article_decimal_places' => $record->decimal_places,
                                'article_total_decimal_places' => $record->total_decimal_places,
                                'solar_plant_id' => $record->pivot->solar_plant_id,
                                'supplier_id' => $record->pivot->supplier_id,
                                'billing_type' => $record->pivot->billing_type,
                                'valid_from' => $record->pivot->valid_from,
                                'valid_to' => $record->pivot->valid_to,
                                'price_increase_percentage' => $record->pivot->price_increase_percentage,
                                'price_increase_interval_months' => $record->pivot->price_increase_interval_months,
                                'price_increase_start_date' => $record->pivot->price_increase_start_date,
                                'quantity' => $record->pivot->quantity,
                                'unit_price_override' => $record->pivot->unit_price,
                                'customer_notes' => $record->pivot->notes,
                                'is_active' => $record->pivot->is_active,
                                'billing_requirement' => $record->pivot->billing_requirement,
                                'created_at' => $record->pivot->created_at,
                            ];
                        })
                        ->form([
                            Forms\Components\Section::make('Artikeldaten')
                                ->schema([
                                    Forms\Components\TextInput::make('article_name')
                                        ->label('Artikelname')
                                        ->disabled()
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('article_description')
                                        ->label('Beschreibung')
                                        ->disabled()
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('article_notes')
                                        ->label('Ausführliche Erklärung')
                                        ->disabled()
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('article_type')
                                        ->label('Artikeltyp')
                                        ->disabled()
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'service' => 'Dienstleistung',
                                            'product' => 'Produkt',
                                            'subscription' => 'Abonnement',
                                            'maintenance' => 'Wartung',
                                            'other' => 'Sonstiges',
                                            default => $state,
                                        }),

                                    Forms\Components\TextInput::make('article_unit')
                                        ->label('Einheit')
                                        ->disabled(),

                                    Forms\Components\TextInput::make('article_price')
                                        ->label('Preis (Netto)')
                                        ->prefix('€')
                                        ->disabled(),

                                    Forms\Components\Select::make('article_tax_rate_id')
                                        ->label('Steuersatz')
                                        ->options(\App\Models\TaxRate::active()->get()->mapWithKeys(function ($taxRate) {
                                            return [$taxRate->id => $taxRate->name];
                                        }))
                                        ->disabled(),

                                    Forms\Components\TextInput::make('article_decimal_places')
                                        ->label('Nachkommastellen (Preis)')
                                        ->disabled(),

                                    Forms\Components\TextInput::make('article_total_decimal_places')
                                        ->label('Nachkommastellen (Gesamtpreis)')
                                        ->disabled(),
                                ])->columns(2),

                            Forms\Components\Section::make('Kundenverknüpfung')
                                ->schema([
                                    Forms\Components\TextInput::make('solar_plant_id')
                                        ->label('Zuordnung Solaranlage')
                                        ->disabled()
                                        ->formatStateUsing(fn ($state) => $state ? \App\Models\SolarPlant::find($state)?->name : '-')
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('supplier_id')
                                        ->label('Zuordnung Lieferant')
                                        ->disabled()
                                        ->formatStateUsing(fn ($state) => $state ? \App\Models\Supplier::find($state)?->name : '-')
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('billing_type')
                                        ->label('Berechnungstyp')
                                        ->disabled()
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'invoice' => 'Rechnung',
                                            'credit' => 'Gutschrift',
                                            default => $state ?? '-',
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\DatePicker::make('valid_from')
                                        ->label('Gültig von')
                                        ->disabled(),

                                    Forms\Components\DatePicker::make('valid_to')
                                        ->label('Gültig bis')
                                        ->disabled(),

                                    Forms\Components\Fieldset::make('Automatische Preiserhöhung')
                                        ->schema([
                                            Forms\Components\Placeholder::make('price_increase_notice')
                                                ->label('')
                                                ->content('Diese Funktion ist nicht aktiv. Sie können die Einstellungen bereits speichern. Sie werden jedoch nicht weiter verarbeitet. Eine automatische Preiserhöhung findet nicht statt. Diese Funktion wurde vom Kunden ausdrücklich nicht gewünscht.')
                                                ->columnSpanFull(),

                                            Forms\Components\TextInput::make('price_increase_percentage')
                                                ->label('Preiserhöhung')
                                                ->disabled()
                                                ->suffix('%')
                                                ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, ',', '.') : '-'),

                                            Forms\Components\TextInput::make('price_increase_interval_months')
                                                ->label('Intervall')
                                                ->disabled()
                                                ->suffix('Monate')
                                                ->formatStateUsing(fn ($state) => $state ?? '-'),

                                            Forms\Components\DatePicker::make('price_increase_start_date')
                                                ->label('Erste Erhöhung ab')
                                                ->disabled(),
                                        ])->columns(3)->columnSpanFull(),

                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Menge')
                                        ->disabled()
                                        ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.')),

                                    Forms\Components\TextInput::make('unit_price_override')
                                        ->label('Stückpreis')
                                        ->disabled()
                                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, ',', '.') . ' €' : '-'),

                                    Forms\Components\Textarea::make('customer_notes')
                                        ->label('Kundenspezifische Notizen')
                                        ->disabled()
                                        ->columnSpanFull(),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Aktiv')
                                        ->disabled(),

                                    Forms\Components\TextInput::make('billing_requirement')
                                        ->label('Anforderung bei Abrechnung')
                                        ->disabled()
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'optional' => 'Optional',
                                            'mandatory' => 'Pflichtartikel',
                                            default => $state,
                                        }),

                                    Forms\Components\TextInput::make('created_at')
                                        ->label('Hinzugefügt am')
                                        ->disabled()
                                        ->formatStateUsing(fn ($state) => $state instanceof \Carbon\Carbon ? $state->format('d.m.Y H:i') : $state)
                                        ->columnSpanFull(),
                                ])->columns(2),
                        ]),
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-m-pencil-square')
                        ->modalWidth('4xl')
                        ->fillForm(function ($record): array {
                            return [
                                'article_name' => $record->name,
                                'article_description' => $record->description,
                                'article_notes' => $record->notes,
                                'article_type' => $record->type,
                                'article_unit' => $record->unit,
                                'article_price' => $record->price,
                                'article_tax_rate_id' => $record->tax_rate_id,
                                'article_decimal_places' => $record->decimal_places,
                                'article_total_decimal_places' => $record->total_decimal_places,
                                'solar_plant_id' => $record->pivot->solar_plant_id,
                                'supplier_id' => $record->pivot->supplier_id,
                                'billing_type' => $record->pivot->billing_type,
                                'valid_from' => $record->pivot->valid_from,
                                'valid_to' => $record->pivot->valid_to,
                                'price_increase_percentage' => $record->pivot->price_increase_percentage,
                                'price_increase_interval_months' => $record->pivot->price_increase_interval_months,
                                'price_increase_start_date' => $record->pivot->price_increase_start_date,
                                'quantity' => $record->pivot->quantity,
                                'unit_price_override' => $record->pivot->unit_price,
                                'customer_notes' => $record->pivot->notes,
                                'is_active' => $record->pivot->is_active,
                                'billing_requirement' => $record->pivot->billing_requirement,
                            ];
                        })
                        ->form([
                            Forms\Components\Section::make('Artikeldaten')
                                ->schema([
                                    Forms\Components\TextInput::make('article_name')
                                        ->label('Artikelname')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('article_description')
                                        ->label('Beschreibung')
                                        ->rows(2)
                                        ->maxLength(1000)
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('article_notes')
                                        ->label('Ausführliche Erklärung')
                                        ->rows(4)
                                        ->maxLength(5000)
                                        ->helperText('Umfassende Informationen und Erklärungen für diesen Kundenartikel (max. 5000 Zeichen)')
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('article_type')
                                        ->label('Artikeltyp')
                                        ->options([
                                            'service' => 'Dienstleistung',
                                            'product' => 'Produkt',
                                            'subscription' => 'Abonnement',
                                            'maintenance' => 'Wartung',
                                            'other' => 'Sonstiges',
                                        ])
                                        ->required(),

                                    Forms\Components\TextInput::make('article_unit')
                                        ->label('Einheit')
                                        ->maxLength(50),

                                    Forms\Components\TextInput::make('article_price')
                                        ->label('Preis (Netto)')
                                        ->numeric()
                                        ->step(0.000001)
                                        ->minValue(0)
                                        ->required()
                                        ->prefix('€')
                                        ->helperText('Bis zu 6 Nachkommastellen möglich'),

                                    Forms\Components\Select::make('article_tax_rate_id')
                                        ->label('Steuersatz')
                                        ->options(\App\Models\TaxRate::active()->get()->mapWithKeys(function ($taxRate) {
                                            return [$taxRate->id => $taxRate->name];
                                        }))
                                        ->required()
                                        ->searchable()
                                        ->preload(),

                                    Forms\Components\TextInput::make('article_decimal_places')
                                        ->label('Nachkommastellen (Preis)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(6)
                                        ->helperText('Anzahl der Nachkommastellen für die Preisanzeige'),

                                    Forms\Components\TextInput::make('article_total_decimal_places')
                                        ->label('Nachkommastellen (Gesamtpreis)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(6)
                                        ->helperText('Anzahl der Nachkommastellen für Gesamtpreise'),
                                ])->columns(2),

                            Forms\Components\Section::make('Kundenverknüpfung')
                                ->description('Konfigurieren Sie, wie dieser Artikel mit dem Kunden verknüpft wird.')
                                ->schema([
                                    Forms\Components\Select::make('solar_plant_id')
                                        ->label('Zuordnung Solaranlage')
                                        ->searchable()
                                        ->getSearchResultsUsing(fn (string $search): array =>
                                            \App\Models\SolarPlant::where('name', 'like', "%{$search}%")
                                                ->orderBy('name')
                                                ->limit(50)
                                                ->pluck('name', 'id')
                                                ->toArray()
                                        )
                                        ->getOptionLabelUsing(fn ($value): ?string =>
                                            \App\Models\SolarPlant::find($value)?->name
                                        )
                                        ->placeholder('Solaranlage suchen...')
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('supplier_id')
                                        ->label('Zuordnung Lieferant')
                                        ->searchable()
                                        ->getSearchResultsUsing(fn (string $search): array =>
                                            \App\Models\Supplier::where('name', 'like', "%{$search}%")
                                                ->orderBy('name')
                                                ->limit(50)
                                                ->pluck('name', 'id')
                                                ->toArray()
                                        )
                                        ->getOptionLabelUsing(fn ($value): ?string =>
                                            \App\Models\Supplier::find($value)?->name
                                        )
                                        ->placeholder('Lieferant suchen...')
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('billing_type')
                                        ->label('Berechnungstyp')
                                        ->options([
                                            'invoice' => 'Rechnung',
                                            'credit' => 'Gutschrift',
                                        ])
                                        ->default('invoice')
                                        ->required()
                                        ->columnSpanFull(),

                                    Forms\Components\DatePicker::make('valid_from')
                                        ->label('Gültig von'),

                                    Forms\Components\DatePicker::make('valid_to')
                                        ->label('Gültig bis')
                                        ->afterOrEqual('valid_from'),

                                    Forms\Components\Fieldset::make('Automatische Preiserhöhung')
                                        ->schema([
                                            Forms\Components\Placeholder::make('price_increase_notice')
                                                ->label('')
                                                ->content('Diese Funktion ist nicht aktiv. Sie können die Einstellungen bereits speichern. Sie werden jedoch nicht weiter verarbeitet. Eine automatische Preiserhöhung findet nicht statt. Diese Funktion wurde vom Kunden ausdrücklich nicht gewünscht.')
                                                ->columnSpanFull(),

                                            Forms\Components\TextInput::make('price_increase_percentage')
                                                ->label('Preiserhöhung')
                                                ->numeric()
                                                ->step(0.01)
                                                ->minValue(0)
                                                ->suffix('%')
                                                ->placeholder('z.B. 2,5'),

                                            Forms\Components\TextInput::make('price_increase_interval_months')
                                                ->label('Intervall')
                                                ->numeric()
                                                ->minValue(1)
                                                ->suffix('Monate')
                                                ->placeholder('z.B. 12'),

                                            Forms\Components\DatePicker::make('price_increase_start_date')
                                                ->label('Erste Erhöhung ab'),
                                        ])->columns(3)->columnSpanFull(),

                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Menge')
                                        ->numeric()
                                        ->step(0.01)
                                        ->minValue(0.01)
                                        ->required()
                                        ->suffix('Stk.')
                                        ->helperText('Anzahl der Artikel für diesen Kunden'),

                                    Forms\Components\TextInput::make('unit_price_override')
                                        ->label('Abweichender Stückpreis (optional)')
                                        ->numeric()
                                        ->step(0.000001)
                                        ->minValue(0)
                                        ->prefix('€')
                                        ->helperText('Leer lassen um den Standard-Artikelpreis zu verwenden. Bis zu 6 Nachkommastellen möglich.'),

                                    Forms\Components\Textarea::make('customer_notes')
                                        ->label('Kundenspezifische Notizen')
                                        ->rows(3)
                                        ->maxLength(1000)
                                        ->placeholder('Spezielle Notizen für diesen Artikel bei diesem Kunden...')
                                        ->columnSpanFull(),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Aktiv')
                                        ->default(true)
                                        ->helperText('Nur aktive Artikel werden bei Berechnungen berücksichtigt.'),

                                    Forms\Components\Radio::make('billing_requirement')
                                        ->label('Anforderung bei Abrechnung')
                                        ->options([
                                            'optional' => 'Optional',
                                            'mandatory' => 'Pflichtartikel',
                                        ])
                                        ->required()
                                        ->helperText('Festlegen, ob dieser Artikel bei der Abrechnung für diesen Kunden obligatorisch ist.'),
                                ])->columns(2),
                        ])
                        ->using(function ($record, array $data): void {
                            $taxRate = \App\Models\TaxRate::find($data['article_tax_rate_id']);

                            $record->update([
                                'name' => $data['article_name'],
                                'description' => $data['article_description'],
                                'notes' => $data['article_notes'] ?? null,
                                'type' => $data['article_type'],
                                'price' => $data['article_price'],
                                'tax_rate_id' => $data['article_tax_rate_id'],
                                'tax_rate' => $taxRate ? $taxRate->rate : 0.19,
                                'unit' => $data['article_unit'],
                                'decimal_places' => $data['article_decimal_places'],
                                'total_decimal_places' => $data['article_total_decimal_places'],
                            ]);

                            $record->pivot->update([
                                'quantity' => $data['quantity'],
                                'unit_price' => $data['unit_price_override'] ?? $record->pivot->unit_price,
                                'notes' => $data['customer_notes'],
                                'is_active' => $data['is_active'],
                                'billing_requirement' => $data['billing_requirement'],
                                'valid_from' => $data['valid_from'] ?? null,
                                'valid_to' => $data['valid_to'] ?? null,
                                'billing_type' => $data['billing_type'] ?? 'invoice',
                                'solar_plant_id' => $data['solar_plant_id'] ?? null,
                                'supplier_id' => $data['supplier_id'] ?? null,
                                'price_increase_percentage' => $data['price_increase_percentage'] ?? null,
                                'price_increase_interval_months' => $data['price_increase_interval_months'] ?? null,
                                'price_increase_start_date' => $data['price_increase_start_date'] ?? null,
                            ]);
                        })
                        ->after(function ($record, $livewire) {
                            Notification::make()
                                ->title('Artikel aktualisiert')
                                ->body('Die Artikel-Zuordnung wurde erfolgreich aktualisiert.')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DetachAction::make()
                        ->label('Entfernen')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Artikel entfernen')
                        ->modalDescription('Möchten Sie diesen Artikel wirklich vom Kunden entfernen? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, entfernen')
                        ->after(function ($livewire) {
                            Notification::make()
                                ->title('Artikel entfernt')
                                ->body('Der Artikel wurde erfolgreich vom Kunden entfernt.')
                                ->success()
                                ->send();
                        }),
                ])
                ->label('Aktionen')
                ->button()
                ->color('gray')
                ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Ausgewählte entfernen'),
                ]),
            ])
            ->defaultSort('customer_article.created_at', 'desc')
            ->emptyStateHeading('Keine Artikel zugeordnet')
            ->emptyStateDescription('Fügen Sie diesem Kunden Artikel aus der Artikelverwaltung hinzu.')
            ->emptyStateIcon('heroicon-o-cube');
    }
    
    public function isReadOnly(): bool
    {
        return false;
    }
}
