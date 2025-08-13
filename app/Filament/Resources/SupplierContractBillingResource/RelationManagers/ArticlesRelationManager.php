<?php

namespace App\Filament\Resources\SupplierContractBillingResource\RelationManagers;

use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticlesRelationManager extends RelationManager
{
    protected static string $relationship = 'articles';

    protected static ?string $title = 'Artikel';

    protected static ?string $modelLabel = 'Artikel';

    protected static ?string $pluralModelLabel = 'Artikel';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->articles()->count();
        return $count > 0 ? (string) $count : null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('article_group')
                    ->label('Artikelgruppe')
                    ->options([
                        'supplier' => 'Lieferantengebundene Artikel',
                        'contract' => 'Vertragsgebundene Artikel',
                        'customer' => 'Kundengebundene Artikel', 
                        'solar_plant' => 'Solaranlagengebundene Artikel',
                    ])
                    ->placeholder('Wählen Sie eine Artikelgruppe')
                    ->reactive()
                    ->afterStateUpdated(function (callable $get, callable $set, $state) {
                        // Reset article selection when group changes
                        $set('article_id', null);
                        $set('unit_price', null);
                        $set('description', null);
                        $set('total_price', null);
                    })
                    ->columnSpanFull(),

                Forms\Components\Select::make('article_id')
                    ->label('Artikel')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->visible(fn (callable $get) => $get('article_group'))
                    ->options(function (callable $get) {
                        $group = $get('article_group');
                        $ownerRecord = $this->getOwnerRecord();
                        
                        if (!$group || !$ownerRecord) {
                            return [];
                        }

                        $supplierId = $ownerRecord->supplierContract->supplier_id;
                        $contractId = $ownerRecord->supplier_contract_id;
                        
                        return match ($group) {
                            'supplier' => Article::whereHas('suppliers', function ($query) use ($supplierId) {
                                $query->where('supplier_article.supplier_id', $supplierId)
                                      ->where('supplier_article.billing_requirement', 'mandatory');
                            })->pluck('name', 'id'),
                            
                            'contract' => Article::whereHas('supplierContracts', function ($query) use ($contractId) {
                                $query->where('supplier_contract_articles.supplier_contract_id', $contractId)
                                      ->where('supplier_contract_articles.billing_requirement', 'mandatory');
                            })->pluck('name', 'id'),
                            
                            'customer' => Article::whereHas('customers', function ($query) use ($ownerRecord) {
                                // Get all customers related to this supplier contract's solar plants
                                $customerIds = $ownerRecord->supplierContract->solarPlants()
                                    ->with('customers')
                                    ->get()
                                    ->pluck('customers')
                                    ->flatten()
                                    ->pluck('id')
                                    ->unique();
                                
                                $query->whereIn('customer_article.customer_id', $customerIds)
                                      ->where('customer_article.billing_requirement', 'mandatory');
                            })->pluck('name', 'id'),
                            
                            'solar_plant' => Article::whereHas('solarPlants', function ($query) use ($ownerRecord) {
                                $solarPlantIds = $ownerRecord->supplierContract->solarPlants()->pluck('id');
                                $query->whereIn('solar_plant_article.solar_plant_id', $solarPlantIds)
                                      ->where('solar_plant_article.billing_requirement', 'mandatory');
                            })->pluck('name', 'id'),
                            
                            default => [],
                        };
                    })
                    ->afterStateUpdated(function (callable $get, callable $set, $state, $record) {
                        // Nur bei neuen Artikeln (nicht beim Bearbeiten) die Werte überschreiben
                        if ($state && !$record) {
                            $article = Article::find($state);
                            if ($article) {
                                // Nur setzen wenn noch keine Werte vorhanden sind
                                if (!$get('unit_price')) {
                                    $set('unit_price', $article->price);
                                }
                                if (!$get('description')) {
                                    $set('description', $article->description ?? $article->name);
                                }
                            }
                        }
                    }),

                Forms\Components\TextInput::make('quantity')
                    ->label('Menge')
                    ->required()
                    ->numeric()
                    ->step(0.0001)
                    ->default(1)
                    ->minValue(0)
                    ->reactive()
                    ->afterStateUpdated(function (callable $get, callable $set, $state) {
                        $unitPrice = $get('unit_price');
                        if ($unitPrice && $state) {
                            $set('total_price', $unitPrice * $state);
                        }
                    }),

                Forms\Components\TextInput::make('unit_price')
                    ->label('Einzelpreis')
                    ->required()
                    ->numeric()
                    ->step(0.000001)
                    ->prefix('€')
                    ->minValue(0)
                    ->reactive()
                    ->afterStateUpdated(function (callable $get, callable $set, $state) {
                        $quantity = $get('quantity');
                        if ($quantity && $state) {
                            $set('total_price', $quantity * $state);
                        }
                    }),

                Forms\Components\TextInput::make('total_price')
                    ->label('Gesamtpreis')
                    ->numeric()
                    ->step(0.01)
                    ->prefix('€')
                    ->disabled()
                    ->dehydrated(true),

                Forms\Components\TextInput::make('description')
                    ->label('Beschreibung')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notizen')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('article.name')
            ->columns([
                Tables\Columns\TextColumn::make('article.name')
                    ->label('Artikel')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Menge')
                    ->numeric(decimalPlaces: 4)
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Einzelpreis')
                    ->numeric(decimalPlaces: 6)
                    ->suffix(' €')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Gesamtpreis')
                    ->money('EUR')
                    ->sortable()
                    ->weight('medium')
                    ->alignEnd(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('article_id')
                    ->label('Artikel')
                    ->relationship('article', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('is_active')
                    ->label('Nur aktive')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->default(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Artikel hinzufügen')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Berechne den Gesamtpreis beim Erstellen
                        $data['total_price'] = $data['quantity'] * $data['unit_price'];
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Bearbeiten')
                    ->fillForm(function ($record): array {
                        // Bestimme die Artikelgruppe basierend auf der Herkunft
                        $articleGroup = null;
                        $ownerRecord = $this->getOwnerRecord();
                        
                        if ($ownerRecord && $record->article) {
                            $supplierId = $ownerRecord->supplierContract->supplier_id;
                            $contractId = $ownerRecord->supplier_contract_id;
                            
                            // Prüfe ob der Artikel vom Lieferanten kommt
                            if ($record->article->suppliers()->where('supplier_article.supplier_id', $supplierId)->exists()) {
                                $articleGroup = 'supplier';
                            }
                            // Prüfe ob der Artikel vom Vertrag kommt
                            elseif ($record->article->supplierContracts()->where('supplier_contract_articles.supplier_contract_id', $contractId)->exists()) {
                                $articleGroup = 'contract';
                            }
                            // Prüfe ob der Artikel von einem Kunden kommt
                            elseif ($record->article->customers()->exists()) {
                                $articleGroup = 'customer';
                            }
                            // Prüfe ob der Artikel von einer Solaranlage kommt
                            elseif ($record->article->solarPlants()->exists()) {
                                $articleGroup = 'solar_plant';
                            }
                        }
                        
                        return [
                            'article_group' => $articleGroup,
                            'article_id' => $record->article_id,
                            'quantity' => $record->quantity,
                            'unit_price' => $record->unit_price,
                            'total_price' => $record->total_price,
                            'description' => $record->description,
                            'notes' => $record->notes,
                            'is_active' => $record->is_active,
                        ];
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        // Berechne den Gesamtpreis beim Bearbeiten
                        $data['total_price'] = $data['quantity'] * $data['unit_price'];
                        return $data;
                    }),
                
                Tables\Actions\DeleteAction::make()
                    ->label('Löschen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Ausgewählte löschen'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
