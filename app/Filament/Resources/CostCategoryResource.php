<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CostCategoryResource\Pages;
use App\Models\CostCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CostCategoryResource extends Resource
{
    protected static ?string $model = CostCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $navigationLabel = 'Kostenkategorien';
    
    protected static ?string $modelLabel = 'Kostenkategorie';
    
    protected static ?string $pluralModelLabel = 'Kostenkategorien';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->teams()->exists() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\ColorPicker::make('color')
                                    ->label('Farbe'),
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sortierreihenfolge')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true),
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Farbe')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('costs_count')
                    ->label('Anzahl Kosten')
                    ->counts('costs')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sortierung')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive')
                    ->placeholder('Alle'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, CostCategory $record) {
                        if ($record->costs()->exists()) {
                            $action->cancel();
                            $action->failure();
                            $action->failureNotificationTitle('Kategorie kann nicht gelöscht werden');
                            $action->sendFailureNotification();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, $records) {
                            $hasRelatedCosts = false;
                            foreach ($records as $record) {
                                if ($record->costs()->exists()) {
                                    $hasRelatedCosts = true;
                                    break;
                                }
                            }
                            if ($hasRelatedCosts) {
                                $action->cancel();
                                $action->failure();
                                $action->failureNotificationTitle('Einige Kategorien können nicht gelöscht werden, da sie noch Kosten enthalten');
                                $action->sendFailureNotification();
                            }
                        }),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
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
            'index' => Pages\ListCostCategories::route('/'),
            'create' => Pages\CreateCostCategory::route('/create'),
            'edit' => Pages\EditCostCategory::route('/{record}/edit'),
        ];
    }
}
