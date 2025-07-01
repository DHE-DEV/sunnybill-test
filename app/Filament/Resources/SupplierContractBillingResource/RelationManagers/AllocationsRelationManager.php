<?php

namespace App\Filament\Resources\SupplierContractBillingResource\RelationManagers;

use App\Models\SolarPlant;
use App\Models\SupplierContractBillingAllocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AllocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'allocations';

    protected static ?string $title = 'Kostenträger-Aufteilungen';

    protected static ?string $modelLabel = 'Aufteilung';

    protected static ?string $pluralModelLabel = 'Aufteilungen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Aufteilungsdetails')
                    ->schema([
                        Forms\Components\Select::make('solar_plant_id')
                            ->label('Kostenträger (Solaranlage)')
                            ->options(function () {
                                // Hole alle verfügbaren Solaranlagen, die dem Vertrag zugeordnet sind
                                $billing = $this->getOwnerRecord();
                                if (!$billing || !$billing->supplierContract) {
                                    return [];
                                }
                                
                                // Prüfe zuerst ob es Solaranlagen-Zuordnungen gibt
                                $assignedPlants = $billing->supplierContract->activeSolarPlants();
                                
                                if ($assignedPlants->count() > 0) {
                                    // Verwende zugeordnete Solaranlagen
                                    return $assignedPlants->get()
                                        ->filter(fn($plant) => !empty($plant->name) && is_string($plant->name))
                                        ->mapWithKeys(fn($plant) => [$plant->id => (string) $plant->name])
                                        ->toArray();
                                } else {
                                    // Fallback: Alle aktiven Solaranlagen
                                    return \App\Models\SolarPlant::where('is_active', true)
                                        ->whereNotNull('name')
                                        ->where('name', '!=', '')
                                        ->orderBy('name')
                                        ->get()
                                        ->filter(fn($plant) => !empty($plant->name) && is_string($plant->name))
                                        ->mapWithKeys(fn($plant) => [$plant->id => (string) $plant->name])
                                        ->toArray();
                                }
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Verfügbare Solaranlagen für die Kostenaufteilung.')
                            ->disableOptionWhen(fn ($value, $label) => empty($label) || !is_string($label)),

                        Forms\Components\TextInput::make('percentage')
                            ->label('Prozentsatz (%)')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                // Berechne automatisch den Betrag basierend auf dem Prozentsatz
                                $billing = $this->getOwnerRecord();
                                if ($billing && $billing->total_amount && $state) {
                                    $amount = ($billing->total_amount * $state) / 100;
                                    $set('amount', round($amount, 2));
                                }
                            })
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $billing = $this->getOwnerRecord();
                                        $currentAllocationId = $this->getRecord()?->id;
                                        
                                        // Berechne die Summe aller anderen Aufteilungen
                                        $existingPercentage = $billing->allocations()
                                            ->when($currentAllocationId, function ($query) use ($currentAllocationId) {
                                                return $query->where('id', '!=', $currentAllocationId);
                                            })
                                            ->sum('percentage');
                                        
                                        if (($existingPercentage + $value) > 100) {
                                            $available = 100 - $existingPercentage;
                                            $fail("Der Prozentsatz darf nicht größer als {$available}% sein. Bereits vergeben: {$existingPercentage}%");
                                        }
                                    };
                                },
                            ]),

                        Forms\Components\TextInput::make('amount')
                            ->label('Betrag')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->prefix('€')
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                // Berechne automatisch den Prozentsatz basierend auf dem Betrag
                                $billing = $this->getOwnerRecord();
                                if ($billing && $billing->total_amount && $state && $billing->total_amount > 0) {
                                    $percentage = ($state / $billing->total_amount) * 100;
                                    $set('percentage', round($percentage, 2));
                                }
                            }),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Abrechnungsübersicht')
                    ->schema([
                        Forms\Components\View::make('filament.forms.components.billing-summary')
                            ->viewData(function () {
                                $billing = $this->getOwnerRecord();
                                if (!$billing) {
                                    return ['error' => 'Keine Abrechnung verfügbar'];
                                }
                                
                                $totalAmount = $billing->total_amount ?? 0;
                                $allocatedPercentage = $billing->allocations()->sum('percentage');
                                $allocatedAmount = $billing->allocations()->sum('amount');
                                $remainingPercentage = max(0, 100 - $allocatedPercentage);
                                $remainingAmount = max(0, $totalAmount - $allocatedAmount);
                                
                                $percentageColor = $allocatedPercentage > 100 ? 'text-red-600' : 'text-green-600';
                                $amountColor = $allocatedAmount > $totalAmount ? 'text-red-600' : 'text-green-600';
                                
                                return [
                                    'billing' => $billing,
                                    'totalAmount' => $totalAmount,
                                    'allocatedPercentage' => $allocatedPercentage,
                                    'allocatedAmount' => $allocatedAmount,
                                    'remainingPercentage' => $remainingPercentage,
                                    'remainingAmount' => $remainingAmount,
                                    'percentageColor' => $percentageColor,
                                    'amountColor' => $amountColor,
                                ];
                            }),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('solarPlant.name')
            ->columns([
                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Kostenträger')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('solarPlant.location')
                    ->label('Standort')
                    ->searchable()
                    ->toggleable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('solarPlant.peak_power')
                    ->label('Leistung')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, ',', '.') . ' kWp' : '-')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('percentage')
                    ->label('Prozentsatz')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                    ->sortable()
                    ->alignEnd()
                    ->weight('medium')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Betrag')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('medium')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notizen')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Keine Notizen'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
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

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Neue Aufteilung')
                    ->icon('heroicon-o-plus')
                    ->disabled(function () {
                        $billing = $this->getOwnerRecord();
                        if (!$billing || !$billing->supplierContract) {
                            return true;
                        }
                        
                        // Prüfe ob Solaranlagen verfügbar sind (entweder zugeordnet oder alle aktiven)
                        $assignedPlants = $billing->supplierContract->activeSolarPlants();
                        if ($assignedPlants->count() > 0) {
                            return false; // Zugeordnete Solaranlagen verfügbar
                        }
                        
                        // Fallback: Prüfe ob überhaupt aktive Solaranlagen existieren
                        return \App\Models\SolarPlant::where('is_active', true)->count() === 0;
                    })
                    ->tooltip(function () {
                        $billing = $this->getOwnerRecord();
                        if (!$billing) return 'Keine Abrechnung verfügbar';
                        if (!$billing->supplierContract) return 'Kein Vertrag zugeordnet';
                        
                        $assignedPlants = $billing->supplierContract->activeSolarPlants();
                        if ($assignedPlants->count() === 0 && \App\Models\SolarPlant::where('is_active', true)->count() === 0) {
                            return 'Keine aktiven Solaranlagen verfügbar';
                        }
                        return null;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Bearbeiten'),
                Tables\Actions\DeleteAction::make()
                    ->label('Löschen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Ausgewählte löschen'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('Endgültig löschen'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Wiederherstellen'),
                ]),
            ])
            ->defaultSort('percentage', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])->with(['solarPlant']))
            ->emptyStateHeading('Keine Aufteilungen vorhanden')
            ->emptyStateDescription('Erstellen Sie eine neue Aufteilung, um die Abrechnung auf Kostenträger zu verteilen.')
            ->emptyStateIcon('heroicon-o-calculator')
            ->striped();
    }
}