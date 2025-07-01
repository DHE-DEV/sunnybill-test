<?php

namespace App\Filament\Resources\SupplierContractResource\RelationManagers;

use App\Models\SolarPlant;
use App\Models\SupplierContractBillingAllocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BillingAllocationsRelationManager extends RelationManager
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
                                $contract = $this->getOwnerRecord();
                                return $contract->activeSolarPlants()
                                    ->get()
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->preload(),

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
                                if ($billing && $billing->total_amount && $state) {
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

                Forms\Components\Section::make('Übersicht')
                    ->schema([
                        Forms\Components\Placeholder::make('total_info')
                            ->label('Abrechnungsübersicht')
                            ->content(function () {
                                $billing = $this->getOwnerRecord();
                                if (!$billing) return 'Keine Abrechnung verfügbar';
                                
                                $totalAmount = $billing->total_amount ?? 0;
                                $allocatedPercentage = $billing->allocations()->sum('percentage');
                                $allocatedAmount = $billing->allocations()->sum('amount');
                                $remainingPercentage = 100 - $allocatedPercentage;
                                $remainingAmount = $totalAmount - $allocatedAmount;
                                
                                return "
                                    <div class='space-y-2'>
                                        <div><strong>Gesamtbetrag:</strong> " . number_format($totalAmount, 2, ',', '.') . " €</div>
                                        <div><strong>Bereits verteilt:</strong> " . number_format($allocatedPercentage, 2, ',', '.') . "% (" . number_format($allocatedAmount, 2, ',', '.') . " €)</div>
                                        <div><strong>Noch verfügbar:</strong> " . number_format($remainingPercentage, 2, ',', '.') . "% (" . number_format($remainingAmount, 2, ',', '.') . " €)</div>
                                    </div>
                                ";
                            }),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('solar_plant.name')
            ->columns([
                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Kostenträger')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('solarPlant.location')
                    ->label('Standort')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('percentage')
                    ->label('Prozentsatz')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Betrag')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd(),

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
                    ->toggleable(isToggledHiddenByDefault: true),

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
                        return !$billing || $billing->activeSolarPlants()->count() === 0;
                    })
                    ->tooltip(function () {
                        $billing = $this->getOwnerRecord();
                        if (!$billing) return 'Keine Abrechnung verfügbar';
                        if ($billing->activeSolarPlants()->count() === 0) {
                            return 'Keine aktiven Kostenträger für diesen Vertrag verfügbar';
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
            ]))
            ->emptyStateHeading('Keine Aufteilungen vorhanden')
            ->emptyStateDescription('Erstellen Sie eine neue Aufteilung, um die Abrechnung auf Kostenträger zu verteilen.')
            ->emptyStateIcon('heroicon-o-calculator');
    }
}