<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxRateResource\Pages;
use App\Filament\Resources\TaxRateResource\RelationManagers;
use App\Models\TaxRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxRateResource extends Resource
{
    protected static ?string $model = TaxRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Steuersatz';

    protected static ?string $pluralModelLabel = 'Steuersätze';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('z.B. Standard, Ermäßigt, Befreit'),
                
                Forms\Components\TextInput::make('rate')
                    ->label('Steuersatz (%)')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->placeholder('19.00')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state * 100, 2) : '')
                    ->dehydrateStateUsing(fn ($state) => $state ? $state / 100 : null),
                
                Forms\Components\DatePicker::make('valid_from')
                    ->label('Gültig ab')
                    ->required()
                    ->default(now())
                    ->native(false),
                
                Forms\Components\DatePicker::make('valid_until')
                    ->label('Gültig bis')
                    ->nullable()
                    ->native(false)
                    ->after('valid_from'),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true)
                    ->helperText('Nur aktive Steuersätze können für neue Artikel verwendet werden.'),
                
                Forms\Components\Textarea::make('description')
                    ->label('Beschreibung')
                    ->nullable()
                    ->maxLength(500)
                    ->rows(3)
                    ->placeholder('Optionale Beschreibung des Steuersatzes'),
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
                
                Tables\Columns\TextColumn::make('rate')
                    ->label('Steuersatz')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%'),
                
                Tables\Columns\TextColumn::make('valid_from')
                    ->label('Gültig ab')
                    ->date('d.m.Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Gültig bis')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('Unbegrenzt'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('current_version.version')
                    ->label('Version')
                    ->sortable()
                    ->placeholder('1'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),
                
                Tables\Filters\Filter::make('valid_now')
                    ->label('Aktuell gültig')
                    ->query(fn (Builder $query): Builder => $query->currentlyValid()),
                
                Tables\Filters\Filter::make('expired')
                    ->label('Abgelaufen')
                    ->query(fn (Builder $query): Builder => $query->expired()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TaxRateVersionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxRates::route('/'),
            'create' => Pages\CreateTaxRate::route('/create'),
            'view' => Pages\ViewTaxRate::route('/{record}'),
            'edit' => Pages\EditTaxRate::route('/{record}/edit'),
        ];
    }
}