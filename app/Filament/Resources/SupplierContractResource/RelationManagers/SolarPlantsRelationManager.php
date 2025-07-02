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
use Filament\Notifications\Notification;

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

                        Forms\Components\Toggle::make('auto_calculate')
                            ->label('Prozentsatz automatisch berechnen')
                            ->helperText('Berechnet den Prozentsatz basierend auf der Anlagenkapazität')
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state && $get('solar_plant_id')) {
                                    // Berechne den vorgeschlagenen Prozentsatz
                                    $plantId = $get('solar_plant_id');
                                    $plant = SolarPlant::find($plantId);
                                    if ($plant && $plant->total_capacity_kw > 0) {
                                        // Hole alle anderen aktiven Zuordnungen
                                        $contractId = $this->getOwnerRecord()->id;
                                        $otherAssignments = SupplierContractSolarPlant::with('solarPlant')
                                            ->where('supplier_contract_id', $contractId)
                                            ->where('is_active', true)
                                            ->get();
                                        
                                        // Berechne die Gesamtkapazität (inkl. der neuen Anlage)
                                        $totalCapacity = $otherAssignments->sum(function ($assignment) {
                                            return $assignment->solarPlant->total_capacity_kw ?? 0;
                                        }) + $plant->total_capacity_kw;
                                        
                                        if ($totalCapacity > 0) {
                                            $suggestedPercentage = round(($plant->total_capacity_kw / $totalCapacity) * 100, 2);
                                            $set('percentage', $suggestedPercentage);
                                        }
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('percentage')
                            ->label('Prozentsatz')
                            ->suffix('%')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(fn (Forms\Get $get) => $get('auto_calculate') !== true)
                            ->default(0)
                            ->live()
                            ->disabled(fn (Forms\Get $get) => $get('auto_calculate') === true)
                            ->dehydrated()
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
                            ->helperText(function ($livewire, Forms\Get $get) {
                                if ($get('auto_calculate')) {
                                    return 'Der Prozentsatz wird automatisch basierend auf der Anlagenkapazität berechnet.';
                                }
                                
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
                Tables\Actions\Action::make('recalculate_percentages')
                    ->label('Prozentsätze neu berechnen')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Prozentsätze neu berechnen')
                    ->modalDescription('Möchten Sie die Prozentsätze aller Kostenträger basierend auf ihrer Kapazität neu berechnen? Dies überschreibt alle manuell eingegebenen Werte.')
                    ->modalSubmitActionLabel('Neu berechnen')
                    ->action(function ($livewire) {
                        SupplierContractSolarPlant::recalculatePercentagesBasedOnCapacity($this->getOwnerRecord()->id);
                        
                        Notification::make()
                            ->title('Prozentsätze neu berechnet')
                            ->body('Die Prozentsätze aller Kostenträger wurden basierend auf ihrer Kapazität neu berechnet.')
                            ->success()
                            ->send();
                            
                        $this->dispatch('refresh-stats');
                        $livewire->resetTable();
                    })
                    ->visible(fn () => $this->getOwnerRecord()->solarPlantAssignments()->count() > 0),
                    
                Tables\Actions\CreateAction::make()
                    ->label('Kostenträger hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('4xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['supplier_contract_id'] = $this->getOwnerRecord()->id;
                        
                        // Wenn auto_calculate aktiviert ist und kein Prozentsatz angegeben wurde, setze auf 0
                        if (isset($data['auto_calculate']) && $data['auto_calculate'] && empty($data['percentage'])) {
                            $data['percentage'] = 0;
                        }
                        
                        // Entferne das auto_calculate Feld aus den Daten
                        unset($data['auto_calculate']);
                        return $data;
                    })
                    ->after(function ($record, $livewire) {
                        // Immer alle Prozentsätze neu berechnen nach dem Hinzufügen
                        SupplierContractSolarPlant::recalculatePercentagesBasedOnCapacity($this->getOwnerRecord()->id);
                        
                        Notification::make()
                            ->title('Kostenträger hinzugefügt')
                            ->body('Der Kostenträger wurde hinzugefügt und die Prozentsätze aller Kostenträger wurden basierend auf ihrer Kapazität neu berechnet.')
                            ->success()
                            ->send();
                        
                        // Aktualisiere die Statistiken und die Tabelle
                        $this->dispatch('refresh-stats');
                        $livewire->resetTable();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-m-eye')
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
                    Tables\Actions\DeleteAction::make()
                        ->label('Löschen')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Kostenträger entfernen')
                        ->modalDescription('Möchten Sie diesen Kostenträger wirklich entfernen? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, entfernen')
                        ->after(function ($livewire) {
                            // Optional: Prozentsätze neu berechnen nach dem Löschen
                            if ($this->getOwnerRecord()->solarPlantAssignments()->count() > 0) {
                                SupplierContractSolarPlant::recalculatePercentagesBasedOnCapacity($this->getOwnerRecord()->id);
                                
                                Notification::make()
                                    ->title('Kostenträger entfernt')
                                    ->body('Der Kostenträger wurde entfernt und die Prozentsätze wurden neu berechnet.')
                                    ->success()
                                    ->send();
                            }
                            
                            $this->dispatch('refresh-stats');
                            $livewire->resetTable();
                        }),
                ])
                ->label('Aktionen')
                ->button()
                ->color('gray')
                ->icon('heroicon-m-ellipsis-vertical'),
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
    
    public function isReadOnly(): bool
    {
        return false; // Erlaubt Aktionen auch im View-Modus
    }
}