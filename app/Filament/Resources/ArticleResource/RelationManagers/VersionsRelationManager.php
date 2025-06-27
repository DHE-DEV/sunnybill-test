<?php

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Versionshistorie';

    protected static ?string $modelLabel = 'Version';

    protected static ?string $pluralModelLabel = 'Versionen';

    protected $listeners = ['refresh-versions-table' => '$refresh'];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Versionen sind nur lesbar, nicht editierbar
                Forms\Components\Placeholder::make('info')
                    ->content('Versionen werden automatisch erstellt und können nicht bearbeitet werden.')
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version_number')
            ->columns([
                Tables\Columns\TextColumn::make('version_number')
                    ->label('Version')
                    ->badge()
                    ->color(fn ($record) => $record->is_current ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state, $record) => 'v' . $state . ($record->is_current ? ' (aktuell)' : ''))
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'SERVICE' => 'Dienstleistung',
                        'PRODUCT' => 'Produkt',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'SERVICE' => 'info',
                        'PRODUCT' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->label('Preis (netto)')
                    ->formatStateUsing(fn ($state) => rtrim(rtrim(number_format($state, 6, ',', '.'), '0'), ',') . ' €')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_rate')
                    ->label('Steuersatz')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Einheit')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('decimal_places')
                    ->label('Einzelpreis NK')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state . ' NK')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_decimal_places')
                    ->label('Gesamtpreis NK')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => $state . ' NK')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('changed_by')
                    ->label('Geändert von')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('change_reason')
                    ->label('Änderungsgrund')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_current')
                    ->label('Aktuelle Version')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_current', true),
                        false: fn (Builder $query) => $query->where('is_current', false),
                    ),
            ])
            ->headerActions([
                // Keine Create-Action, da Versionen automatisch erstellt werden
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->form([
                        Forms\Components\Section::make('Versionsinformationen')
                            ->schema([
                                Forms\Components\TextInput::make('version_number')
                                    ->label('Versionsnummer')
                                    ->disabled(),
                                Forms\Components\Toggle::make('is_current')
                                    ->label('Aktuelle Version')
                                    ->disabled(),
                                Forms\Components\TextInput::make('changed_by')
                                    ->label('Geändert von')
                                    ->disabled(),
                                Forms\Components\Textarea::make('change_reason')
                                    ->label('Änderungsgrund')
                                    ->disabled()
                                    ->rows(2),
                            ])->columns(2),
                        
                        Forms\Components\Section::make('Artikeldaten')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->disabled(),
                                Forms\Components\TextInput::make('type')
                                    ->label('Typ')
                                    ->disabled(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Beschreibung')
                                    ->disabled()
                                    ->rows(3),
                                Forms\Components\TextInput::make('price')
                                    ->label('Preis (netto)')
                                    ->disabled()
                                    ->suffix('€'),
                                Forms\Components\TextInput::make('tax_rate')
                                    ->label('Steuersatz')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%'),
                                Forms\Components\TextInput::make('unit')
                                    ->label('Einheit')
                                    ->disabled(),
                                Forms\Components\TextInput::make('decimal_places')
                                    ->label('Einzelpreis Nachkommastellen')
                                    ->disabled(),
                                Forms\Components\TextInput::make('total_decimal_places')
                                    ->label('Gesamtpreis Nachkommastellen')
                                    ->disabled(),
                            ])->columns(2),
                        
                        Forms\Components\Section::make('Geänderte Felder')
                            ->schema([
                                Forms\Components\KeyValue::make('changed_fields')
                                    ->label('Änderungen')
                                    ->disabled()
                                    ->keyLabel('Feld')
                                    ->valueLabel('Alt → Neu'),
                            ])
                            ->visible(fn ($record) => !empty($record->changed_fields)),
                    ]),
            ])
            ->bulkActions([
                // Keine Bulk-Actions für Versionen
            ])
            ->defaultSort('version_number', 'desc')
            ->paginated([10, 25, 50])
            ->poll('2s'); // Auto-refresh alle 2 Sekunden für schnellere Updates
    }

    public function isReadOnly(): bool
    {
        return true; // Versionen sind nur lesbar
    }
}
