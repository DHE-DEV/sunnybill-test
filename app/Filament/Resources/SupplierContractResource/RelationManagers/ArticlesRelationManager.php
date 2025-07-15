<?php

namespace App\Filament\Resources\SupplierContractResource\RelationManagers;

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
                    ->description('Fügen Sie diesem Vertrag einen Artikel aus der Artikelverwaltung hinzu.')
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
                            ->helperText('Anzahl der Artikel für diesen Vertrag'),

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
                            ->description('Erstellen Sie einen neuen Artikel und fügen Sie ihn automatisch zu diesem Vertrag hinzu.')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Artikelname')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('z.B. Wartungsvertrag Solaranlage'),
                                
                                Forms\Components\Textarea::make('description')
                                    ->label('Beschreibung')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->placeholder('Detaillierte Beschreibung des Artikels...'),
                                
                                Forms\Components\Select::make('type')
                                    ->label('Artikeltyp')
                                    ->options([
                                        'service' => 'Dienstleistung',
                                        'product' => 'Produkt',
                                        'subscription' => 'Abonnement',
                                        'maintenance' => 'Wartung',
                                        'other' => 'Sonstiges',
                                    ])
                                    ->default('service')
                                    ->required(),
                                
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
                                
                                Forms\Components\TextInput::make('unit')
                                    ->label('Einheit')
                                    ->maxLength(50)
                                    ->default('Stk.')
                                    ->placeholder('z.B. Stk., Std., m²'),
                                
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
                        
                        Forms\Components\Section::make('Vertragsverknüpfung')
                            ->description('Konfigurieren Sie, wie dieser Artikel mit dem Vertrag verknüpft wird.')
                            ->schema([
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Menge')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0.01)
                                    ->required()
                                    ->default(1.00)
                                    ->suffix('Stk.')
                                    ->helperText('Anzahl der Artikel für diesen Vertrag'),
                                
                                Forms\Components\TextInput::make('unit_price_override')
                                    ->label('Abweichender Stückpreis (optional)')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->minValue(0)
                                    ->prefix('€')
                                    ->helperText('Leer lassen um den Standard-Artikelpreis zu verwenden. Bis zu 6 Nachkommastellen möglich.'),
                                
                                Forms\Components\Textarea::make('contract_notes')
                                    ->label('Vertragsnotizen')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->placeholder('Spezielle Notizen für diesen Artikel in diesem Vertrag...'),
                                
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true)
                                    ->helperText('Nur aktive Artikel werden bei Berechnungen berücksichtigt.'),
                            ])->columns(2),
                    ])
                    ->action(function (array $data) {
                        // Hole den Steuersatz für das alte tax_rate Feld
                        $taxRate = \App\Models\TaxRate::find($data['tax_rate_id']);
                        
                        // Erstelle den neuen Artikel
                        $articleData = [
                            'name' => $data['name'],
                            'description' => $data['description'],
                            'type' => $data['type'],
                            'price' => $data['price'],
                            'tax_rate_id' => $data['tax_rate_id'],
                            'tax_rate' => $taxRate ? $taxRate->rate : 0.19, // Fallback auf 19%
                            'unit' => $data['unit'],
                            'decimal_places' => $data['decimal_places'],
                            'total_decimal_places' => $data['total_decimal_places'],
                        ];
                        
                        $article = Article::create($articleData);
                        
                        // Verknüpfe den Artikel mit dem Vertrag
                        $pivotData = [
                            'quantity' => $data['quantity'],
                            'unit_price' => $data['unit_price_override'] ?? $article->price,
                            'notes' => $data['contract_notes'],
                            'is_active' => $data['is_active'],
                        ];
                        
                        $this->getOwnerRecord()->articles()->attach($article->id, $pivotData);
                        
                        Notification::make()
                            ->title('Artikel erstellt und hinzugefügt')
                            ->body("Der Artikel '{$article->name}' wurde erfolgreich erstellt und zum Vertrag hinzugefügt.")
                            ->success()
                            ->send();
                    })
                    ->after(function ($livewire) {
                        // Aktualisiere die Tabelle
                        $livewire->dispatch('refresh');
                    }),
                
                Tables\Actions\AttachAction::make()
                    ->label('Artikel hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('4xl')
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        // Schließe bereits verknüpfte Artikel aus
                        $ownerRecord = $this->getOwnerRecord();
                        $attachedArticleIds = $ownerRecord->articles()->pluck('articles.id')->toArray();
                        
                        return $query->whereNotIn('id', $attachedArticleIds)->orderBy('name');
                    })
                    ->recordSelectSearchColumns(['name', 'description'])
                    ->preloadRecordSelect()
                    ->form([
                        Forms\Components\Select::make('recordId')
                            ->label('Artikel')
                            ->options(function () {
                                // Schließe bereits verknüpfte Artikel aus
                                $ownerRecord = $this->getOwnerRecord();
                                $attachedArticleIds = $ownerRecord->articles()->pluck('articles.id')->toArray();
                                
                                return Article::query()
                                    ->whereNotIn('id', $attachedArticleIds)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($article) {
                                        return [$article->id => $article->name . ' (' . $article->formatted_price . ')'];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $article = Article::find($state);
                                    if ($article) {
                                        $set('unit_price', $article->price);
                                    }
                                }
                            }),
                        
                        Forms\Components\Placeholder::make('article_info')
                            ->label('Artikel-Info')
                            ->content(function ($get) {
                                $articleId = $get('recordId');
                                if (!$articleId) return 'Kein Artikel ausgewählt';
                                
                                $article = Article::find($articleId);
                                if (!$article) return 'Artikel nicht gefunden';
                                
                                return "Name: {$article->name}\n" .
                                       "Beschreibung: " . ($article->description ?? 'Keine Beschreibung') . "\n" .
                                       "Preis: {$article->formatted_price}\n" .
                                       "Steuersatz: {$article->tax_rate_percent}";
                            })
                            ->visible(fn ($get) => $get('recordId')),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Menge')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->required()
                            ->default(1.00)
                            ->suffix('Stk.')
                            ->helperText('Anzahl der Artikel für diesen Vertrag'),

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
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        // Wenn kein unit_price angegeben wurde, verwende den Artikel-Preis
                        if (empty($data['unit_price']) && !empty($data['recordId'])) {
                            $article = Article::find($data['recordId']);
                            if ($article) {
                                $data['unit_price'] = $article->price;
                            }
                        }
                        
                        // Setze Standardwerte
                        $data['quantity'] = $data['quantity'] ?? 1.00;
                        $data['is_active'] = $data['is_active'] ?? true;
                        
                        return $data;
                    })
                    ->after(function ($record, $livewire) {
                        Notification::make()
                            ->title('Artikel hinzugefügt')
                            ->body('Der Artikel wurde erfolgreich zum Vertrag hinzugefügt.')
                            ->success()
                            ->send();
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
                                ])->columns(2),
                        ])
                        ->using(function ($record, array $data): void {
                            // Aktualisiere die Pivot-Daten
                            $record->pivot->update([
                                'quantity' => $data['quantity'],
                                'unit_price' => $data['unit_price'],
                                'notes' => $data['notes'],
                                'is_active' => $data['is_active'],
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
                        ->modalDescription('Möchten Sie diesen Artikel wirklich vom Vertrag entfernen? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, entfernen')
                        ->after(function ($livewire) {
                            Notification::make()
                                ->title('Artikel entfernt')
                                ->body('Der Artikel wurde erfolgreich vom Vertrag entfernt.')
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
            ->defaultSort('supplier_contract_articles.created_at', 'desc')
            ->emptyStateHeading('Keine Artikel zugeordnet')
            ->emptyStateDescription('Fügen Sie diesem Vertrag Artikel aus der Artikelverwaltung hinzu.')
            ->emptyStateIcon('heroicon-o-cube');
    }
    
    public function isReadOnly(): bool
    {
        return false; // Erlaubt Aktionen auch im View-Modus
    }
}