<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

use App\Traits\HasPersistentTableState;
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
    use HasPersistentTableState;
    
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
                    ->description('Fügen Sie dieser Solaranlage einen Artikel aus der Artikelverwaltung hinzu.')
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
                            ->helperText('Anzahl der Artikel für diese Solaranlage'),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Stückpreis')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->prefix('€')
                            ->helperText('Leer lassen um den Standard-Artikelpreis zu verwenden')
                            ->placeholder('Wird automatisch gesetzt'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Zusätzliche Informationen zu diesem Artikel...'),

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
                Tables\Columns\TextColumn::make('pivot.quantity')
                    ->label('Menge')
                    ->numeric(2)
                    ->alignRight()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Einheit')
                    ->toggleable(),
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
                            ->description('Erstellen Sie einen neuen Artikel und fügen Sie ihn automatisch zu dieser Solaranlage hinzu.')
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
                                    ->placeholder('Kurze Beschreibung des Artikels...')
                                    ->columnSpanFull(),
                                
                                Forms\Components\Textarea::make('notes')
                                    ->label('Ausführliche Erklärung')
                                    ->rows(4)
                                    ->maxLength(5000)
                                    ->placeholder('Ausführliche Erklärung zum Artikel, technische Details, Verwendungszweck, etc...')
                                    ->columnSpanFull()
                                    ->helperText('Hier können Sie ausführliche Informationen zum Artikel hinterlegen.'),
                                
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
                        
                        Forms\Components\Section::make('Anlagenverknüpfung')
                            ->description('Konfigurieren Sie, wie dieser Artikel mit der Solaranlage verknüpft wird.')
                            ->schema([
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Menge')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0.01)
                                    ->required()
                                    ->default(1.00)
                                    ->suffix('Stk.')
                                    ->helperText('Anzahl der Artikel für diese Solaranlage'),
                                
                                Forms\Components\TextInput::make('unit_price_override')
                                    ->label('Abweichender Stückpreis (optional)')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->minValue(0)
                                    ->prefix('€')
                                    ->helperText('Leer lassen um den Standard-Artikelpreis zu verwenden. Bis zu 6 Nachkommastellen möglich.'),
                                
                                Forms\Components\Textarea::make('plant_notes')
                                    ->label('Anlagenspezifische Notizen')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->placeholder('Spezielle Notizen für diesen Artikel bei dieser Anlage...')
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
                                    ->helperText('Festlegen, ob dieser Artikel bei der Abrechnung für diese Solaranlage obligatorisch ist.'),
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
                            'notes' => $data['plant_notes'],
                            'is_active' => $data['is_active'],
                            'billing_requirement' => $data['billing_requirement'],
                        ];
                        
                        $this->getOwnerRecord()->articles()->attach($article->id, $pivotData);
                        
                        Notification::make()
                            ->title('Artikel erstellt und hinzugefügt')
                            ->body("Der Artikel '{$article->name}' wurde erfolgreich erstellt und zur Solaranlage hinzugefügt.")
                            ->success()
                            ->send();
                    })
                    ->after(function ($livewire) {
                        $livewire->dispatch('refresh');
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-m-eye')
                        ->modalWidth('4xl')
                        ->fillForm(function ($record): array {
                            return [
                                'name' => $record->name,
                                'description' => $record->description,
                                'formatted_price' => $record->formatted_price,
                                'tax_rate_percent' => $record->tax_rate_percent,
                                'unit' => $record->unit,
                                'quantity' => $record->pivot->quantity,
                                'unit_price' => $record->pivot->unit_price,
                                'notes' => $record->pivot->notes,
                                'is_active' => $record->pivot->is_active,
                                'billing_requirement' => $record->pivot->billing_requirement,
                                'created_at' => $record->pivot->created_at,
                            ];
                        })
                        ->form([
                            Forms\Components\Section::make('Artikel-Details')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Artikelname')
                                        ->disabled()
                                        ->columnSpanFull(),
                                    Forms\Components\Textarea::make('description')
                                        ->label('Beschreibung')
                                        ->disabled()
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Menge')
                                        ->disabled()
                                        ->formatStateUsing(fn ($state) => number_format($state, 2)),
                                    Forms\Components\TextInput::make('unit')
                                        ->label('Einheit')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('formatted_price')
                                        ->label('Preis netto')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('tax_rate_percent')
                                        ->label('zzgl. Steuer')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('unit_price')
                                        ->label('Stückpreis')
                                        ->disabled()
                                        ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . ' €')
                                        ->hiddenOn('view'),
                                    Forms\Components\Textarea::make('notes')
                                        ->label('Notizen')
                                        ->disabled()
                                        ->columnSpanFull(),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Aktiv')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('billing_requirement')
                                        ->label('Abrechnungsanforderung')
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'optional' => 'Optional',
                                            'mandatory' => 'Pflicht',
                                            default => $state,
                                        })
                                        ->disabled(),
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
                                'name' => $record->name,
                                'quantity' => $record->pivot->quantity,
                                'unit_price' => $record->pivot->unit_price,
                                'notes' => $record->pivot->notes,
                                'is_active' => $record->pivot->is_active,
                                'billing_requirement' => $record->pivot->billing_requirement,
                            ];
                        })
                        ->form([
                            Forms\Components\Section::make('Artikel bearbeiten')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Artikelname')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Menge')
                                        ->numeric()
                                        ->step(0.01)
                                        ->minValue(0.01)
                                        ->required()
                                        ->suffix('Stk.'),
                                    Forms\Components\TextInput::make('unit_price')
                                        ->label('Stückpreis')
                                        ->numeric()
                                        ->step(0.000001)
                                        ->minValue(0)
                                        ->required()
                                        ->prefix('€'),
                                    Forms\Components\Textarea::make('notes')
                                        ->label('Notizen')
                                        ->rows(3)
                                        ->maxLength(1000),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Aktiv')
                                        ->required(),
                                    Forms\Components\Radio::make('billing_requirement')
                                        ->label('Anforderung bei Abrechnung')
                                        ->options([
                                            'optional' => 'Optional',
                                            'mandatory' => 'Pflichtartikel',
                                        ])
                                        ->required(),
                                ])->columns(2),
                        ])
                        ->using(function ($record, array $data): void {
                            $record->pivot->update([
                                'quantity' => $data['quantity'],
                                'unit_price' => $data['unit_price'],
                                'notes' => $data['notes'],
                                'is_active' => $data['is_active'],
                                'billing_requirement' => $data['billing_requirement'],
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
                        ->modalDescription('Möchten Sie diesen Artikel wirklich von der Solaranlage entfernen? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, entfernen')
                        ->after(function ($livewire) {
                            Notification::make()
                                ->title('Artikel entfernt')
                                ->body('Der Artikel wurde erfolgreich von der Solaranlage entfernt.')
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
            ->defaultSort('solar_plant_article.created_at', 'desc')
            ->emptyStateHeading('Keine Artikel zugeordnet')
            ->emptyStateDescription('Fügen Sie dieser Solaranlage Artikel aus der Artikelverwaltung hinzu.')
            ->emptyStateIcon('heroicon-o-cube');
    }
    
    public function isReadOnly(): bool
    {
        return false;
    }
}
