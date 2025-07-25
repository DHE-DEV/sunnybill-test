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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AllocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'allocations';

    protected static ?string $title = 'Kostenträger-Aufteilungen';

    protected static ?string $modelLabel = 'Aufteilung';

    protected static ?string $pluralModelLabel = 'Aufteilungen';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->allocations()->count();
        return $count > 0 ? (string) $count : null;
    }

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

                Tables\Columns\TextColumn::make('solarPlant.total_capacity_kw')
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
                Tables\Actions\Action::make('help')
                    ->label('Hilfe')
                    ->icon('heroicon-o-question-mark-circle')
                    ->button()
                    ->color('info')
                    ->modalHeading('Hilfe: Prozentsatz-Berechnung')
                    ->modalContent(function () {
                        $billing = $this->getOwnerRecord();
                        $totalAmount = $billing ? $billing->total_amount : 0;
                        $allocations = $billing ? $billing->allocations : collect();
                        
                        $content = '<div class="space-y-4">';
                        
                        // Aktuelle Berechnung anzeigen
                        if ($billing && $totalAmount > 0) {
                            $content .= '<div class="bg-green-50 p-4 rounded-lg border border-green-200">';
                            $content .= '<h3 class="font-semibold text-green-900 mb-2">📊 Aktuelle Abrechnung</h3>';
                            $content .= '<p class="text-green-800 text-sm mb-2"><strong>Gesamtbetrag:</strong> ' . number_format($totalAmount, 2, ',', '.') . ' €</p>';
                            
                            if ($allocations->count() > 0) {
                                // Berechne Gesamtkapazität für Prozentsatz-Erklärung
                                $totalCapacity = 0;
                                $capacityData = [];
                                
                                foreach ($allocations as $allocation) {
                                    if ($allocation->solarPlant && $allocation->solarPlant->total_capacity_kw) {
                                        $capacity = $allocation->solarPlant->total_capacity_kw;
                                        $totalCapacity += $capacity;
                                        $capacityData[$allocation->id] = $capacity;
                                    }
                                }
                                
                                // Zeige Kapazitäts-basierte Berechnung wenn verfügbar
                                if ($totalCapacity > 0) {
                                    $content .= '<div class="bg-blue-100 p-3 rounded mb-3">';
                                    $content .= '<h4 class="font-medium text-blue-900 mb-2">🔋 Kapazitäts-basierte Prozentsätze:</h4>';
                                    $content .= '<p class="text-blue-800 text-sm mb-2"><strong>Gesamtkapazität:</strong> ' . number_format($totalCapacity, 2, ',', '.') . ' kWp</p>';
                                    $content .= '</div>';
                                }
                                
                                $content .= '<div class="space-y-2">';
                                $totalPercentage = 0;
                                $totalAllocated = 0;
                                
                                foreach ($allocations as $allocation) {
                                    $percentage = round($allocation->percentage, 2);
                                    $amount = round($allocation->amount, 2);
                                    $calculatedAmount = round(($totalAmount * $percentage) / 100, 2);
                                    
                                    $totalPercentage += $percentage;
                                    $totalAllocated += $amount;
                                    
                                    $plantName = $allocation->solarPlant ? $allocation->solarPlant->name : 'Unbekannt';
                                    $plantCapacity = isset($capacityData[$allocation->id]) ? $capacityData[$allocation->id] : null;
                                    
                                    $content .= '<div class="bg-white p-3 rounded border">';
                                    $content .= '<p class="font-medium text-gray-900">' . htmlspecialchars($plantName) . '</p>';
                                    
                                    // Zeige Kapazität wenn verfügbar
                                    if ($plantCapacity && $totalCapacity > 0) {
                                        $capacityPercentage = round(($plantCapacity / $totalCapacity) * 100, 2);
                                        $content .= '<div class="text-xs text-blue-600 mb-1">';
                                        $content .= 'Kapazität: ' . number_format($plantCapacity, 2, ',', '.') . ' kWp ';
                                        $content .= '→ Kapazitäts-Prozentsatz: (' . number_format($plantCapacity, 2, ',', '.') . ' ÷ ' . number_format($totalCapacity, 2, ',', '.') . ') × 100 = ' . number_format($capacityPercentage, 2, ',', '.') . '%';
                                        $content .= '</div>';
                                    }
                                    
                                    $content .= '<div class="text-sm text-gray-600 mt-1">';
                                    $content .= '<p>• Zugewiesener Prozentsatz: <span class="font-mono">' . number_format($percentage, 2, ',', '.') . '%</span></p>';
                                    $content .= '<p>• Betrag: <span class="font-mono">' . number_format($amount, 2, ',', '.') . ' €</span></p>';
                                    $content .= '<p class="text-xs text-gray-500 mt-1">';
                                    $content .= 'Kostenberechnung: ' . number_format($totalAmount, 2, ',', '.') . ' € × ' . number_format($percentage, 2, ',', '.') . '% = ' . number_format($calculatedAmount, 2, ',', '.') . ' €';
                                    $content .= '</p>';
                                    $content .= '</div>';
                                    $content .= '</div>';
                                }
                                
                                // Zusammenfassung
                                $content .= '<div class="bg-gray-50 p-3 rounded border-t-2 border-gray-300 mt-3">';
                                $content .= '<p class="font-semibold text-gray-900">Zusammenfassung:</p>';
                                if ($totalCapacity > 0) {
                                    $content .= '<p class="text-sm text-gray-700">• Gesamtkapazität: <span class="font-mono">' . number_format($totalCapacity, 2, ',', '.') . ' kWp</span></p>';
                                }
                                $content .= '<p class="text-sm text-gray-700">• Gesamt Prozentsatz: <span class="font-mono">' . number_format($totalPercentage, 2, ',', '.') . '%</span></p>';
                                $content .= '<p class="text-sm text-gray-700">• Gesamt zugewiesen: <span class="font-mono">' . number_format($totalAllocated, 2, ',', '.') . ' €</span></p>';
                                $content .= '<p class="text-sm text-gray-700">• Verbleibt: <span class="font-mono">' . number_format($totalAmount - $totalAllocated, 2, ',', '.') . ' €</span> (' . number_format(100 - $totalPercentage, 2, ',', '.') . '%)</p>';
                                $content .= '</div>';
                                
                                $content .= '</div>';
                            } else {
                                $content .= '<p class="text-green-700 text-sm">Noch keine Aufteilungen vorhanden.</p>';
                            }
                            $content .= '</div>';
                        }
                        
                        // Hinweise
                        $content .= '<div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">';
                        $content .= '<h3 class="font-semibold text-yellow-900 mb-2">💡 Wichtige Hinweise</h3>';
                        $content .= '<ul class="text-yellow-800 text-sm space-y-1 ml-4">';
                        $content .= '<li>• Die Summe aller Prozentsätze sollte 100% nicht überschreiten</li>';
                        $content .= '<li>• Änderungen am Prozentsatz berechnen automatisch den Betrag neu</li>';
                        $content .= '<li>• Änderungen am Betrag berechnen automatisch den Prozentsatz neu</li>';
                        $content .= '<li>• Bei der Neuberechnung werden bestehende Aufteilungen durch die Vertragszuordnungen ersetzt</li>';
                        $content .= '</ul>';
                        $content .= '</div>';
                        
                        $content .= '</div>';
                        
                        return new \Illuminate\Support\HtmlString($content);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen'),
                    
                Tables\Actions\Action::make('recalculate')
                    ->label('Neuberechnung')
                    ->icon('heroicon-o-calculator')
                    ->button()
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Kostenträger-Aufteilung neu berechnen')
                    ->modalDescription('Möchten Sie die Kostenträger-Aufteilung basierend auf den aktuellen Vertragszuordnungen neu berechnen? Bestehende Aufteilungen werden gelöscht und neu erstellt.')
                    ->modalSubmitActionLabel('Ja, neu berechnen')
                    ->action(function () {
                        $billing = $this->getOwnerRecord();
                        
                        if (!$billing || !$billing->supplierContract) {
                            \Filament\Notifications\Notification::make()
                                ->title('Fehler')
                                ->body('Keine gültige Abrechnung oder Vertrag gefunden.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Lösche bestehende Allocations endgültig (force delete)
                        $billing->allocations()->forceDelete();
                        
                        // Erstelle neue Allocations basierend auf Vertragszuordnungen
                        $billing->createAllocationsFromContract();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Neuberechnung abgeschlossen')
                            ->body('Die Kostenträger-Aufteilung wurde erfolgreich neu berechnet.')
                            ->success()
                            ->send();
                    })
                    ->visible(function () {
                        $billing = $this->getOwnerRecord();
                        return $billing && $billing->supplierContract &&
                               $billing->supplierContract->activeSolarPlantAssignments()->count() > 0;
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
    
    public function isReadOnly(): bool
    {
        return false; // Erlaubt Aktionen auch im View-Modus
    }
}
