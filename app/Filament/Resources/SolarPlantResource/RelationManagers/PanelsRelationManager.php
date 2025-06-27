<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class PanelsRelationManager extends RelationManager
{
    protected static string $relationship = 'panels';

    protected static ?string $title = 'Solarpanels';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->panels()->count();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('manufacturer')
                    ->label('Hersteller')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('z.B. JinkoSolar, Canadian Solar, Trina Solar'),
                Forms\Components\TextInput::make('model')
                    ->label('Modell')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('z.B. JKM540M-7RL4-V'),
                Forms\Components\TextInput::make('serial_number')
                    ->label('Seriennummer')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->placeholder('Eindeutige Seriennummer'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('model')
            ->columns([
                Tables\Columns\TextColumn::make('manufacturer')
                    ->label('Hersteller')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model')
                    ->label('Modell')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Seriennummer')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Klicken zum Kopieren'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Vollständige Bezeichnung')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Hinzugefügt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('manufacturer')
                    ->label('Hersteller')
                    ->options(function () {
                        return \App\Models\Panel::distinct()
                            ->pluck('manufacturer', 'manufacturer')
                            ->toArray();
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Panel hinzufügen'),
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
            ->defaultSort('created_at', 'desc');
    }
}