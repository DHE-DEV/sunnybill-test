<?php

namespace App\Filament\Resources\SupplierContractBillingResource\RelationManagers;

use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticlesRelationManager extends RelationManager
{
    protected static string $relationship = 'articles';

    protected static ?string $title = 'Artikel';

    protected static ?string $modelLabel = 'Artikel';

    protected static ?string $pluralModelLabel = 'Artikel';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('article_id')
                    ->label('Artikel')
                    ->relationship('article', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $get, callable $set, $state) {
                        if ($state) {
                            $article = Article::find($state);
                            if ($article) {
                                $set('unit_price', $article->price);
                                $set('description', $article->name);
                            }
                        }
                    }),

                Forms\Components\TextInput::make('quantity')
                    ->label('Menge')
                    ->required()
                    ->numeric()
                    ->step(0.01)
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
                    ->step(0.01)
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
            ->recordTitleAttribute('description')
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
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Einzelpreis')
                    ->money('EUR')
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
