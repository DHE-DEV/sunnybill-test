<?php

namespace App\Filament\Resources\SupplierContractResource\RelationManagers;

use App\Models\SolarPlant;
use App\Models\SupplierContractSolarPlant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SolarPlantsRelationManager extends RelationManager
{
    protected static string $relationship = 'solarPlantAssignments';

    protected static ?string $title = 'Kostenträger';

    protected static ?string $modelLabel = 'Kostenträger';

    protected static ?string $pluralModelLabel = 'Kostenträger';

    protected static ?string $icon = 'heroicon-o-sun';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Kostenträger')
                    ->description('Ordnen Sie diesem Vertrag eine Solaranlage als Kostenträger mit einem Prozentsatz zu.')
                    ->schema([
                        Forms\Components\Select::make('solar_plant_id')
                            ->label('Solaranlage')
                            ->options(SolarPlant::query()
                                ->orderBy('plant_number')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->required()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $plant = SolarPlant::find($state);
                                    if ($plant) {
                                        $set('plant_info', [
                                            'plant_number' => $plant->plant_number,
                                            'location' => $plant->location,
                                            'capacity' => $plant->total_capacity_kw . ' kWp',
                                        ]);
                                    }
                                }
                            }),
                        
                        Forms\Components\Placeholder::make('plant_info')
                            ->label('Anlagen-Info')
                            ->content(function ($get) {
                                $plantId = $get('solar_plant_id');
                                if (!$plantId) return 'Keine Anlage ausgewählt';
                                
                                $plant = SolarPlant::find($plantId);
                                if (!$plant) return 'Anlage nicht gefunden';
                                
                                return "Nummer: {$plant->plant_number}\n" .
                                       "Standort: {$plant->location}\n" .
                                       "Kapazität: {$plant->total_capacity_kw} kWp";
                            })
                            ->visible(fn ($get) => $get('solar_plant_id')),

                        Forms\Components\TextInput::make('percentage')
                            ->label('Prozentsatz')
                            ->suffix('%')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->maxValue(100)
                            ->required()
                            ->live()
                            ->rules([
                                function ($livewire) {
                                    return function (string $attribute, $value, \Closure $fail) use ($livewire) {
                                        $contractId = $livewire->getOwnerRecord()->id;
                                        $recordId = null;
                                        
                                        // Für Edit-Operationen: Versuche Record-ID zu bekommen
                                        if (isset($livewire->mountedTableActionRecord)) {
                                            $recordId = $livewire->mountedTableActionRecord;
                                        }
                                        
                                        if (!SupplierContractSolarPlant::validateTotalPercentage($contractId, (float) $value, $recordId)) {
                                            $available = SupplierContractSolarPlant::getAvailablePercentage($contractId, $recordId);
                                            $fail("Die Gesamtsumme aller Prozentsätze darf 100% nicht überschreiten. Verfügbar: {$available}%");
                                        }
                                    };
                                },
                            ])
                            ->helperText(function ($livewire) {
                                $contractId = $livewire->getOwnerRecord()->id;
                                $recordId = null;
                                
                                // Für Edit-Operationen: Versuche Record-ID zu bekommen
                                if (isset($livewire->mountedTableActionRecord)) {
                                    $recordId = $livewire->mountedTableActionRecord;
                                }
                                
                                $available = SupplierContractSolarPlant::getAvailablePercentage($contractId, $recordId);
                                $total = SupplierContractSolarPlant::getTotalPercentage($contractId);
                                
                                return "Bereits zugeordnet: {$total}% | Verfügbar: {$available}%";
                            }),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Zusätzliche Informationen zu diesem Kostenträger...'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Nur aktive Kostenträger werden bei der Prozentsatz-Berechnung berücksichtigt.'),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('solar_plant.name')
            ->columns([
                Tables\Columns\TextColumn::make('solarPlant.plant_number')
                    ->label('Anlagen-Nr.')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Anlagenname')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('solarPlant.location')
                    ->label('Standort')
                    ->searchable()
                    ->limit(25)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('solarPlant.total_capacity_kw')
                    ->label('Kapazität')
                    ->suffix(' kWp')
                    ->numeric(2)
                    ->alignRight()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('formatted_percentage')
                    ->label('Prozentsatz')
                    ->alignRight()
                    ->sortable('percentage')
                    ->badge()
                    ->color(fn ($record) => $record->percentage > 50 ? 'success' : ($record->percentage > 25 ? 'warning' : 'gray')),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
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
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive')
                    ->native(false),
                Tables\Filters\Filter::make('high_percentage')
                    ->label('Hoher Prozentsatz (>50%)')
                    ->query(fn (Builder $query): Builder => $query->where('percentage', '>', 50)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Kostenträger hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('4xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['supplier_contract_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->after(function () {
                        // Aktualisiere die Statistiken nach dem Erstellen
                        $this->dispatch('refresh-stats');
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('4xl')
                    ->form([
                        Forms\Components\Section::make('Kostenträger-Details')
                            ->schema([
                                Forms\Components\TextInput::make('solarPlant.plant_number')
                                    ->label('Anlagen-Nummer')
                                    ->disabled(),
                                Forms\Components\TextInput::make('solarPlant.name')
                                    ->label('Anlagenname')
                                    ->disabled(),
                                Forms\Components\TextInput::make('solarPlant.location')
                                    ->label('Standort')
                                    ->disabled(),
                                Forms\Components\TextInput::make('solarPlant.total_capacity_kw')
                                    ->label('Kapazität (kWp)')
                                    ->disabled(),
                                Forms\Components\TextInput::make('formatted_percentage')
                                    ->label('Prozentsatz')
                                    ->disabled(),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notizen')
                                    ->disabled(),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->disabled(),
                                Forms\Components\TextInput::make('created_at')
                                    ->label('Erstellt am')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => $state?->format('d.m.Y H:i')),
                            ])->columns(2),
                    ]),
                Tables\Actions\EditAction::make()
                    ->modalWidth('4xl')
                    ->after(function () {
                        $this->dispatch('refresh-stats');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        $this->dispatch('refresh-stats');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            $this->dispatch('refresh-stats');
                        }),
                ]),
            ])
            ->defaultSort('percentage', 'desc')
            ->emptyStateHeading('Keine Kostenträger zugeordnet')
            ->emptyStateDescription('Ordnen Sie diesem Vertrag Solaranlagen als Kostenträger mit Prozentsätzen zu.')
            ->emptyStateIcon('heroicon-o-sun');
    }
}