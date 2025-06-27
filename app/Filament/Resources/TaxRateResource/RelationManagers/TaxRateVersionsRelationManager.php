<?php

namespace App\Filament\Resources\TaxRateResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxRateVersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Versionshistorie';

    protected static ?string $modelLabel = 'Version';

    protected static ?string $pluralModelLabel = 'Versionen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('version_number')
                    ->label('Version')
                    ->required()
                    ->numeric()
                    ->disabled(),
                
                Forms\Components\TextInput::make('rate')
                    ->label('Steuersatz (%)')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
                
                Forms\Components\DateTimePicker::make('valid_from')
                    ->label('Gültig ab')
                    ->required()
                    ->native(false),
                
                Forms\Components\DateTimePicker::make('valid_until')
                    ->label('Gültig bis')
                    ->nullable()
                    ->native(false)
                    ->after('valid_from'),
                
                Forms\Components\Textarea::make('change_reason')
                    ->label('Änderungsgrund')
                    ->nullable()
                    ->maxLength(500)
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version_number')
            ->columns([
                Tables\Columns\TextColumn::make('version_number')
                    ->label('Version')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('rate')
                    ->label('Steuersatz')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . '%'),
                
                Tables\Columns\TextColumn::make('valid_from')
                    ->label('Gültig ab')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Gültig bis')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Unbegrenzt'),
                
                Tables\Columns\TextColumn::make('change_reason')
                    ->label('Änderungsgrund')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }
                        
                        return $state;
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('current')
                    ->label('Aktuell gültig')
                    ->query(fn (Builder $query): Builder => $query->currentlyValid()),
                
                Tables\Filters\Filter::make('future')
                    ->label('Zukünftig gültig')
                    ->query(fn (Builder $query): Builder => $query->futureValid()),
                
                Tables\Filters\Filter::make('expired')
                    ->label('Abgelaufen')
                    ->query(fn (Builder $query): Builder => $query->expired()),
            ])
            ->headerActions([
                // Versionen werden automatisch erstellt, daher keine Create-Action
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Versionen sollten nicht editiert oder gelöscht werden können
            ])
            ->bulkActions([
                // Keine Bulk-Actions für Versionen
            ])
            ->defaultSort('version_number', 'desc')
            ->poll('30s'); // Automatische Aktualisierung alle 30 Sekunden
    }
}