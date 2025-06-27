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

class BatteriesRelationManager extends RelationManager
{
    protected static string $relationship = 'batteries';

    protected static ?string $title = 'Batterien';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->batteries()->count();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('manufacturer')
                    ->label('Hersteller')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('z.B. Tesla, BYD, Huawei'),
                Forms\Components\TextInput::make('model')
                    ->label('Modell')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('z.B. Powerwall 2, Battery-Box Premium'),
                Forms\Components\TextInput::make('serial_number')
                    ->label('Seriennummer')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->placeholder('Eindeutige Seriennummer'),
                Forms\Components\TextInput::make('capacity_kwh')
                    ->label('Kapazität (kWh)')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->suffix('kWh')
                    ->placeholder('z.B. 13.5'),
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
                Tables\Columns\TextColumn::make('capacity_kwh')
                    ->label('Kapazität')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . ' kWh')
                    ->sortable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Seriennummer')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Klicken zum Kopieren'),
                Tables\Columns\TextColumn::make('display_name')
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
                        return \App\Models\Battery::distinct()
                            ->pluck('manufacturer', 'manufacturer')
                            ->toArray();
                    }),
                Tables\Filters\Filter::make('capacity_range')
                    ->form([
                        Forms\Components\TextInput::make('capacity_from')
                            ->label('Kapazität von (kWh)')
                            ->numeric(),
                        Forms\Components\TextInput::make('capacity_to')
                            ->label('Kapazität bis (kWh)')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['capacity_from'],
                                fn (Builder $query, $capacity): Builder => $query->where('capacity_kwh', '>=', $capacity),
                            )
                            ->when(
                                $data['capacity_to'],
                                fn (Builder $query, $capacity): Builder => $query->where('capacity_kwh', '<=', $capacity),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Batterie hinzufügen'),
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