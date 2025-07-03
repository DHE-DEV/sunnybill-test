<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierContractResource\Pages;
use App\Filament\Resources\SupplierContractResource\RelationManagers;
use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\SolarPlant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierContractResource extends Resource
{
    protected static ?string $model = SupplierContract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Verträge';

    protected static ?string $modelLabel = 'Lieferantenvertrag';

    protected static ?string $pluralModelLabel = 'Lieferantenverträge';

    protected static ?string $navigationGroup = 'Lieferanten';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vertragsdaten')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Lieferant')
                            ->options(Supplier::active()->orderBy('company_name')->pluck('company_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('contract_number')
                            ->label('Vertragsnummer')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->maxLength(1000),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(SupplierContract::getStatusOptions())
                            ->default('draft')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Laufzeit & Wert')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Startdatum'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Enddatum'),
                        Forms\Components\TextInput::make('contract_value')
                            ->label('Vertragswert')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('€'),
                        Forms\Components\Select::make('currency')
                            ->label('Währung')
                            ->options([
                                'EUR' => 'Euro (EUR)',
                                'USD' => 'US-Dollar (USD)',
                                'CHF' => 'Schweizer Franken (CHF)',
                            ])
                            ->default('EUR'),
                    ])->columns(2),

                Forms\Components\Section::make('Zusätzliche Informationen')
                    ->schema([
                        Forms\Components\Textarea::make('payment_terms')
                            ->label('Zahlungsbedingungen')
                            ->rows(3),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('Vertragsnummer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.company_name')
                    ->label('Lieferant')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function (SupplierContract $record): string {
                        $supplierName = $record->supplier?->company_name ?? $record->supplier?->name ?? 'Unbekannter Lieferant';
                        
                        $assignments = $record->activeSolarPlantAssignments()
                            ->with('solarPlant')
                            ->get();
                        
                        if ($assignments->isEmpty()) {
                            return '<div class="space-y-1"><div><strong>' . e($supplierName) . '</strong></div><div><span class="text-gray-500 text-sm">(Keine Kostenträger zugeordnet)</span></div></div>';
                        }
                        
                        $plantList = $assignments->map(function ($assignment) {
                            $plant = $assignment->solarPlant;
                            if (!$plant) {
                                return '<div class="text-gray-500 text-sm">• Unbekannte Anlage</div>';
                            }
                            
                            $plantNumber = e($plant->plant_number ?? 'Keine Nr.');
                            $plantName = e($plant->name ?? 'Kein Name');
                            $percentage = $assignment->percentage ? ' (' . number_format($assignment->percentage, 2) . '%)' : '';
                            
                            return '<div class="text-gray-500 text-sm">• ' . $plantNumber . ' - ' . $plantName . $percentage . '</div>';
                        })->toArray();
                        
                        return '<div class="space-y-1"><div><strong>' . e($supplierName) . '</strong></div>' . implode('', $plantList) . '</div>';
                    })
                    ->html()
                    ->wrap()
                    ->tooltip(function (SupplierContract $record): ?string {
                        $supplierName = $record->supplier?->company_name ?? $record->supplier?->name ?? 'Unbekannter Lieferant';
                        
                        $assignments = $record->activeSolarPlantAssignments()
                            ->with('solarPlant')
                            ->get();
                        
                        if ($assignments->isEmpty()) {
                            return $supplierName . "\n(Keine Kostenträger zugeordnet)";
                        }
                        
                        $plantList = $assignments->map(function ($assignment) {
                            $plant = $assignment->solarPlant;
                            if (!$plant) {
                                return '• Unbekannte Anlage';
                            }
                            
                            $plantNumber = $plant->plant_number ?? 'Keine Nr.';
                            $plantName = $plant->name ?? 'Kein Name';
                            $percentage = $assignment->percentage ? number_format($assignment->percentage, 2) : '0.00';
                            
                            return "• {$plantNumber} - {$plantName} ({$percentage}%)";
                        })->toArray();
                        
                        return $supplierName . "\nKostenträger:\n" . implode("\n", $plantList);
                    }),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'expired' => 'warning',
                        'terminated' => 'danger',
                        'completed' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Entwurf',
                        'active' => 'Aktiv',
                        'expired' => 'Abgelaufen',
                        'terminated' => 'Gekündigt',
                        'completed' => 'Abgeschlossen',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Ende')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('formatted_contract_value')
                    ->label('Wert')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('contract_value', $direction);
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('contractNotes_count')
                    ->label('Notizen')
                    ->counts('contractNotes')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Dokumente')
                    ->counts('documents')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('solarPlantAssignments_count')
                    ->label('Solaranlagen')
                    ->counts('solarPlantAssignments')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('total_solar_plant_percentage')
                    ->label('Gesamt %')
                    ->suffix('%')
                    ->numeric(2)
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->badge()
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 50 ? 'warning' : 'gray')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Lieferant')
                    ->options(Supplier::active()->orderBy('company_name')->pluck('company_name', 'id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('solar_plant_id')
                    ->label('Kostenträger')
                    ->options(function () {
                        return SolarPlant::whereHas('supplierContractAssignments', function ($query) {
                            $query->where('is_active', true);
                        })
                        ->orderBy('plant_number')
                        ->get()
                        ->mapWithKeys(function ($plant) {
                            $displayName = ($plant->plant_number ?? 'Keine Nr.') . ' - ' . ($plant->name ?? 'Kein Name');
                            return [$plant->id => $displayName];
                        });
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['value'])) {
                            return $query->whereHas('solarPlantAssignments', function ($q) use ($data) {
                                $q->where('solar_plant_id', $data['value'])
                                  ->where('is_active', true);
                            });
                        }
                        return $query;
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SupplierContract::getStatusOptions()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Läuft bald ab')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('end_date', '<=', now()->addDays(30))
                              ->where('end_date', '>=', now())
                              ->where('status', 'active')
                    ),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SolarPlantsRelationManager::class,
            RelationManagers\BillingsRelationManager::class,
            RelationManagers\FavoriteNotesRelationManager::class,
            RelationManagers\StandardNotesRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierContracts::route('/'),
            'create' => Pages\CreateSupplierContract::route('/create'),
            'view' => Pages\ViewSupplierContract::route('/{record}'),
            'edit' => Pages\EditSupplierContract::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}