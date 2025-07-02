<?php

namespace App\Filament\Resources\SupplierContractResource\RelationManagers;

use App\Models\SupplierContractBilling;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BillingsRelationManager extends RelationManager
{
    protected static string $relationship = 'billings';

    protected static ?string $title = 'Abrechnungen';

    protected static ?string $modelLabel = 'Abrechnung';

    protected static ?string $pluralModelLabel = 'Abrechnungen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Abrechnungsdetails')
                    ->schema([
                        Forms\Components\TextInput::make('billing_number')
                            ->label('Abrechnungsnummer')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Wird automatisch generiert'),

                        Forms\Components\TextInput::make('supplier_invoice_number')
                            ->label('Anbieter-Rechnungsnummer')
                            ->maxLength(255)
                            ->placeholder('Rechnungsnummer des Anbieters'),

                        Forms\Components\Select::make('billing_type')
                            ->label('Abrechnungstyp')
                            ->options(SupplierContractBilling::getBillingTypeOptions())
                            ->default('invoice')
                            ->required(),

                        Forms\Components\Select::make('billing_year')
                            ->label('Abrechnungsjahr')
                            ->options(function () {
                                $currentYear = now()->year;
                                $years = [];
                                for ($year = $currentYear - 5; $year <= $currentYear + 2; $year++) {
                                    $years[$year] = $year;
                                }
                                return $years;
                            })
                            ->default(function () {
                                $lastMonth = now()->subMonth();
                                return $lastMonth->year;
                            })
                            ->searchable(),

                        Forms\Components\Select::make('billing_month')
                            ->label('Abrechnungsmonat')
                            ->options(SupplierContractBilling::getMonthOptions())
                            ->default(function () {
                                $lastMonth = now()->subMonth();
                                return $lastMonth->month;
                            })
                            ->searchable(),

                        Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('billing_date')
                            ->label('Abrechnungsdatum')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Fälligkeitsdatum')
                            ->after('billing_date'),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Gesamtbetrag')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->prefix('€')
                            ->minValue(0),

                        Forms\Components\Select::make('currency')
                            ->label('Währung')
                            ->options([
                                'EUR' => 'Euro (€)',
                                'USD' => 'US-Dollar ($)',
                                'CHF' => 'Schweizer Franken (CHF)',
                            ])
                            ->default('EUR')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(SupplierContractBilling::getStatusOptions())
                            ->default('draft')
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('billing_number')
                    ->label('Abrechnungsnummer')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('supplier_invoice_number')
                    ->label('Anbieter-Rechnung')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('billing_type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => SupplierContractBilling::getBillingTypeOptions()[$state] ?? $state)
                    ->colors([
                        'primary' => 'invoice',
                        'warning' => 'credit_note',
                    ]),

                Tables\Columns\TextColumn::make('billing_period')
                    ->label('Abrechnungsperiode')
                    ->getStateUsing(function (SupplierContractBilling $record): ?string {
                        return $record->billing_period;
                    })
                    ->sortable(['billing_year', 'billing_month'])
                    ->searchable(false)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('billing_date')
                    ->label('Abrechnungsdatum')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Gesamtbetrag')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => SupplierContractBilling::getStatusOptions()[$state] ?? $state)
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending',
                        'success' => 'approved',
                        'primary' => 'paid',
                        'danger' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('allocations_count')
                    ->label('Aufteilungen')
                    ->counts('allocations')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SupplierContractBilling::getStatusOptions()),

                Tables\Filters\SelectFilter::make('billing_type')
                    ->label('Abrechnungstyp')
                    ->options(SupplierContractBilling::getBillingTypeOptions()),

                Tables\Filters\SelectFilter::make('billing_year')
                    ->label('Abrechnungsjahr')
                    ->options(function () {
                        $currentYear = now()->year;
                        $years = [];
                        for ($year = $currentYear - 5; $year <= $currentYear + 2; $year++) {
                            $years[$year] = $year;
                        }
                        return $years;
                    }),

                Tables\Filters\SelectFilter::make('billing_month')
                    ->label('Abrechnungsmonat')
                    ->options(SupplierContractBilling::getMonthOptions()),

                Tables\Filters\Filter::make('billing_date')
                    ->label('Abrechnungsdatum')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Von'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('billing_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('billing_date', '<=', $date),
                            );
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Abrechnung erfassen')
                    ->icon('heroicon-o-plus')
                    ->button()
                    ->color('primary'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Anzeigen')
                    ->url(fn (SupplierContractBilling $record): string =>
                        \App\Filament\Resources\SupplierContractBillingResource::getUrl('view', ['record' => $record])
                    ),
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
            ->defaultSort('billing_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
    
    public function isReadOnly(): bool
    {
        return false; // Erlaubt Aktionen auch im View-Modus
    }
}