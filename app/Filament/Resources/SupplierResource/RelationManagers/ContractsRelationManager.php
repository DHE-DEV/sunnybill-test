<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Models\SupplierContract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'contracts';

    protected static ?string $modelLabel = 'Vertrag';

    protected static ?string $pluralModelLabel = 'Verträge';

    public function mount(): void
    {
        parent::mount();
        
        $supplierName = $this->ownerRecord->company_name ?? $this->ownerRecord->name ?? 'Unbekannt';
        static::$title = "Verträge - {$supplierName}";
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vertragsdaten')
                    ->schema([
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('Vertragsnummer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('solarPlants')
                    ->label('Zugeordnete Solaranlagen')
                    ->getStateUsing(function ($record) {
                        $solarPlants = $record->activeSolarPlants;
                        if ($solarPlants->isEmpty()) {
                            return '-';
                        }
                        return $solarPlants->pluck('name')->implode(', ');
                    })
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('solarPlants', function (Builder $subQuery) use ($search) {
                            $subQuery->where('name', 'like', "%{$search}%")
                                     ->orWhere('location', 'like', "%{$search}%");
                        });
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
                    ->toggleable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Ende')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('formatted_contract_value')
                    ->label('Wert')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('contract_value', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contractNotes_count')
                    ->label('Notizen')
                    ->counts('contractNotes')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Dokumente')
                    ->counts('documents')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SupplierContract::getStatusOptions()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                Tables\Filters\Filter::make('solar_plant_search')
                    ->form([
                        Forms\Components\TextInput::make('solar_plant_name')
                            ->label('Solaranlage suchen')
                            ->placeholder('Name oder Standort der Solaranlage'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['solar_plant_name'],
                            fn (Builder $query, $search): Builder => $query->whereHas('solarPlants', function (Builder $subQuery) use ($search) {
                                $subQuery->where('name', 'like', "%{$search}%")
                                         ->orWhere('location', 'like', "%{$search}%");
                            })
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['solar_plant_name'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Solaranlage: ' . $data['solar_plant_name'])
                                ->removeField('solar_plant_name');
                        }
                        return $indicators;
                    }),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Vertrag anlegen')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->size('md')
                    ->button()
                    ->modalHeading('Neuen Vertrag anlegen')
                    ->modalDescription('Erstellen Sie einen neuen Vertrag für diesen Lieferanten.')
                    ->modalSubmitActionLabel('Vertrag erstellen')
                    ->modalCancelActionLabel('Abbrechen')
                    ->successNotificationTitle('Vertrag erfolgreich erstellt')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Automatisch die Supplier-ID setzen
                        $data['supplier_id'] = $this->ownerRecord->getKey();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->url(fn ($record): string =>
                            route('filament.admin.resources.supplier-contracts.view', ['record' => $record->id])
                        ),
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
            ->modifyQueryUsing(function (Builder $query) {
                return $query->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ])->with(['solarPlants']);
            })
            ->persistSearchInSession()
            ->defaultSort('created_at', 'desc');
    }
}
