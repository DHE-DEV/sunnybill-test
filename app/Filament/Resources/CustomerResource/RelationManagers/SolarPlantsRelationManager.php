<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\SolarPlant;
use App\Models\PlantParticipation;
use App\Filament\Resources\SolarPlantResource;

class SolarPlantsRelationManager extends RelationManager
{
    protected static string $relationship = 'plantParticipations';

    protected static ?string $title = 'Zugeordnete Solaranlagen';

    protected static ?string $modelLabel = 'Solaranlage';

    protected static ?string $pluralModelLabel = 'Solaranlagen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('solar_plant_id')
                    ->label('Solaranlage')
                    ->options(SolarPlant::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),
                
                Forms\Components\TextInput::make('percentage')
                    ->label('Beteiligung (%)')
                    ->numeric()
                    ->min(0)
                    ->max(100)
                    ->step(0.01)
                    ->required()
                    ->suffix('%'),
                
                Forms\Components\TextInput::make('eeg_compensation_per_kwh')
                    ->label('Vertraglich zugesicherte EEG-Vergütung')
                    ->numeric()
                    ->step(0.000001)
                    ->minValue(0)
                    ->suffix('€/kWh')
                    ->placeholder('0,000000')
                    ->helperText('Vergütung pro kWh in EUR mit bis zu 6 Nachkommastellen'),
                
                Forms\Components\DatePicker::make('start_date')
                    ->label('Startdatum')
                    ->required(),
                
                Forms\Components\DatePicker::make('end_date')
                    ->label('Enddatum')
                    ->nullable(),
                
                Forms\Components\Textarea::make('notes')
                    ->label('Notizen')
                    ->nullable()
                    ->rows(3),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('solar_plant.name')
            ->columns([
                Tables\Columns\TextColumn::make('solarPlant.plant_number')
                    ->label('Anlagen-Nr.')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('solarPlant.location')
                    ->label('Standort')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('solarPlant.total_capacity_kw')
                    ->label('Leistung')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, ',', '.') . ' kW' : '-')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('percentage')
                    ->label('Beteiligung')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('eeg_compensation_per_kwh')
                    ->label('EEG-Vergütung')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 6, ',', '.') . ' €/kWh' : '-')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Startdatum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Enddatum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('Unbegrenzt')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive')
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Solaranlage zuordnen')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['customer_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_solar_plant')
                    ->label('Solaranlage öffnen')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => SolarPlantResource::getUrl('view', ['record' => $record->solarPlant]))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->label('Bearbeiten'),
                Tables\Actions\DeleteAction::make()
                    ->label('Entfernen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Ausgewählte entfernen'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
