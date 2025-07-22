<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CostResource\Pages;
use App\Models\Cost;
use App\Models\CostCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class CostResource extends Resource
{
    protected static ?string $model = Cost::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';
    
    protected static ?string $navigationGroup = 'Fakturierung';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $navigationLabel = 'Kosten';
    
    protected static ?string $modelLabel = 'Kosten';
    
    protected static ?string $pluralModelLabel = 'Kosten';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Titel')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('cost_category_id')
                                    ->label('Kategorie')
                                    ->options(CostCategory::active()->ordered()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Betrag')
                                    ->numeric()
                                    ->required()
                                    ->prefix('€')
                                    ->inputMode('decimal'),
                                Forms\Components\DatePicker::make('date')
                                    ->label('Datum')
                                    ->required()
                                    ->default(now()),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('supplier')
                                    ->label('Lieferant')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('reference_number')
                                    ->label('Referenznummer')
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Ausstehend',
                                        'paid' => 'Bezahlt',
                                        'cancelled' => 'Storniert',
                                    ])
                                    ->required()
                                    ->default('pending'),
                                Forms\Components\DatePicker::make('paid_at')
                                    ->label('Bezahlt am')
                                    ->visible(fn (Forms\Get $get): bool => $get('status') === 'paid'),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategorie')
                    ->badge()
                    ->color(fn ($record) => $record->category->color ?? 'gray'),
                Tables\Columns\TextColumn::make('supplier')
                    ->label('Lieferant')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Betrag')
                    ->money('EUR', locale: 'de')
                    ->sortable()
                    ->alignRight(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Ausstehend',
                        'paid' => 'Bezahlt',
                        'cancelled' => 'Storniert',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Referenznummer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('cost_category_id')
                    ->label('Kategorie')
                    ->options(CostCategory::active()->ordered()->pluck('name', 'id'))
                    ->searchable(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'paid' => 'Bezahlt',
                        'cancelled' => 'Storniert',
                    ]),
                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Von Datum'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Bis Datum'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCosts::route('/'),
            'create' => Pages\CreateCost::route('/create'),
            'edit' => Pages\EditCost::route('/{record}/edit'),
        ];
    }
}