<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SolarParticipationsRelationManager extends RelationManager
{
    protected static string $relationship = 'plantParticipations';

    protected static ?string $title = 'Solar-Beteiligungen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('solar_plant_id')
                    ->label('Solaranlage')
                    ->relationship('solarPlant', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('percentage')
                    ->label('Beteiligung (%)')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->suffix('%')
                    ->minValue(0.01)
                    ->maxValue(100)
                    ->placeholder('z.B. 25.50'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('solarPlant.name')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('solarPlant'))
            ->columns([
                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Solaranlage')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('solarPlant.location')
                    ->label('Standort')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('percentage')
                    ->label('Beteiligung')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('solarPlant.total_capacity_kw')
                    ->label('Anlagenleistung')
                    ->formatStateUsing(fn ($state) => number_format($state, 3, ',', '.') . ' kW')
                    ->sortable(),
                Tables\Columns\TextColumn::make('solarPlant.status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'planned' => 'Geplant',
                        'in_planning' => 'In Planung',
                        'under_construction' => 'Im Bau',
                        'awaiting_commissioning' => 'Wartet auf Inbetriebnahme',
                        'active' => 'Aktiv',
                        'maintenance' => 'Wartung',
                        'inactive' => 'Inaktiv',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'planned' => 'gray',
                        'in_planning' => 'gray',
                        'under_construction' => 'warning',
                        'awaiting_commissioning' => 'warning',
                        'active' => 'success',
                        'maintenance' => 'info',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Beteiligung seit')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('solarPlant.status')
                    ->label('Anlagenstatus')
                    ->relationship('solarPlant', 'status')
                    ->options([
                        'planned' => 'Geplant',
                        'in_planning' => 'In Planung',
                        'under_construction' => 'Im Bau',
                        'awaiting_commissioning' => 'Wartet auf Inbetriebnahme',
                        'active' => 'Aktiv',
                        'maintenance' => 'Wartung',
                        'inactive' => 'Inaktiv',
                    ]),
                Tables\Filters\Filter::make('high_participation')
                    ->label('Hohe Beteiligung (>= 25%)')
                    ->query(fn (Builder $query): Builder => $query->where('percentage', '>=', 25)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Beteiligung hinzufügen'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->url(fn ($record) => $record->solarPlant ?
                            route('filament.admin.resources.solar-plants.view', ['record' => $record->solarPlant->id]) :
                            '#'
                        )
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => $record->solarPlant !== null),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->icon('heroicon-o-cog-6-tooth')
                ->tooltip('Aktionen')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine Beteiligungen')
            ->emptyStateDescription('Dieser Kunde hat noch keine Solar-Beteiligungen.')
            ->emptyStateIcon('heroicon-o-sun');
    }
}