<?php

namespace App\Livewire;

use App\Models\SolarPlantBilling;
use App\Models\SolarPlant;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class CustomerBillingsTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public SolarPlant $solarPlant;

    public function mount(SolarPlant $solarPlant): void
    {
        $this->solarPlant = $solarPlant;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SolarPlantBilling::query()
                    ->where('solar_plant_id', $this->solarPlant->id)
                    ->with(['customer'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Rechnungsnummer')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('primary')
                    ->copyable()
                    ->placeholder('Noch nicht generiert'),
                
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->description(fn ($record) => $record->customer?->customer_type === 'business' ? $record->customer?->company_name : null)
                    ->searchable(['customers.name', 'customers.company_name'])
                    ->sortable()
                    ->weight('medium')
                    ->color('primary')
                    ->url(fn ($record) => $record->customer ? route('filament.admin.resources.customers.view', $record->customer) : null)
                    ->openUrlInNewTab(false),
                
                Tables\Columns\TextColumn::make('billing_period')
                    ->label('Abrechnungszeitraum')
                    ->state(function ($record) {
                        $monthNames = [
                            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                        ];
                        return $monthNames[$record->billing_month] . ' ' . $record->billing_year;
                    })
                    ->sortable(['billing_year', 'billing_month'])
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('participation_kwp')
                    ->label('Beteiligung in kWp')
                    ->state(function ($record) {
                        $solarPlant = $this->solarPlant;
                        $totalCapacity = $solarPlant->capacity_kwp ?? $solarPlant->total_capacity_kw ?? 0;
                        
                        if ($totalCapacity && $record->participation_percentage) {
                            $participationKwp = ($totalCapacity * $record->participation_percentage) / 100;
                            return number_format($participationKwp, 3, ',', '.');
                        }
                        
                        return null;
                    })
                    ->suffix(' kWp')
                    ->alignCenter()
                    ->sortable(false)
                    ->badge()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('participation_percentage')
                    ->label('Beteiligung')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('produced_energy_kwh')
                    ->label('Erzeugte Energie')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' kWh' : 'Keine Daten')
                    ->sortable()
                    ->color('success')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('total_costs')
                    ->label('Gesamtkosten')
                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 2, ',', '.'))
                    ->sortable()
                    ->color('danger')
                    ->alignCenter()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('total_credits')
                    ->label('Gesamterlös')
                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 2, ',', '.'))
                    ->sortable()
                    ->color('success')
                    ->alignCenter()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Nettobetrag')
                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 2, ',', '.'))
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Entwurf',
                        'finalized' => 'Finalisiert',
                        'sent' => 'Versendet',
                        'paid' => 'Bezahlt',
                        default => $state,
                    })
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'draft' => 'gray',
                        'finalized' => 'warning',
                        'sent' => 'info',
                        'paid' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('finalized_at')
                    ->label('Finalisiert am')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter()
                    ->placeholder('Noch nicht finalisiert')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Versendet am')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter()
                    ->placeholder('Noch nicht versendet')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Bezahlt am')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter()
                    ->placeholder('Noch nicht bezahlt')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->date('d.m.Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Entwurf',
                        'finalized' => 'Finalisiert',
                        'sent' => 'Versendet',
                        'paid' => 'Bezahlt',
                    ]),
                
                Tables\Filters\SelectFilter::make('billing_year')
                    ->label('Abrechnungsjahr')
                    ->options(function () {
                        $years = SolarPlantBilling::where('solar_plant_id', $this->solarPlant->id)
                            ->distinct()
                            ->orderBy('billing_year', 'desc')
                            ->pluck('billing_year')
                            ->mapWithKeys(fn ($year) => [$year => $year]);
                        return $years;
                    }),
                
                Tables\Filters\SelectFilter::make('billing_month')
                    ->label('Abrechnungsmonat')
                    ->options([
                        1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                        5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                    ]),
                
                Tables\Filters\SelectFilter::make('customer_type')
                    ->label('Kundentyp')
                    ->options([
                        'individual' => 'Privatperson',
                        'business' => 'Unternehmen',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => $query->whereHas('customer', 
                                fn (Builder $query) => $query->where('customer_type', $value)
                            ),
                        );
                    }),
                
                Tables\Filters\Filter::make('amount_range')
                    ->label('Betragsspanne')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_from')
                                    ->label('Von (€)')
                                    ->numeric()
                                    ->placeholder('0')
                                    ->step(0.01),
                                Forms\Components\TextInput::make('amount_to')
                                    ->label('Bis (€)')
                                    ->numeric()
                                    ->placeholder('1000')
                                    ->step(0.01),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $value): Builder => $query->where('net_amount', '>=', $value),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $value): Builder => $query->where('net_amount', '<=', $value),
                            );
                    }),
                
                Tables\Filters\Filter::make('energy_range')
                    ->label('Energiebereich')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('energy_from')
                                    ->label('Von (kWh)')
                                    ->numeric()
                                    ->placeholder('0')
                                    ->step(1),
                                Forms\Components\TextInput::make('energy_to')
                                    ->label('Bis (kWh)')
                                    ->numeric()
                                    ->placeholder('10000')
                                    ->step(1),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['energy_from'],
                                fn (Builder $query, $value): Builder => $query->where('produced_energy_kwh', '>=', $value),
                            )
                            ->when(
                                $data['energy_to'],
                                fn (Builder $query, $value): Builder => $query->where('produced_energy_kwh', '<=', $value),
                            );
                    }),
                
                Tables\Filters\Filter::make('created_at')
                    ->label('Erstellungsdatum')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Von')
                            ->placeholder('Startdatum'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Bis')
                            ->placeholder('Enddatum'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                
                Tables\Filters\TernaryFilter::make('has_invoice_number')
                    ->label('Hat Rechnungsnummer')
                    ->placeholder('Alle Abrechnungen')
                    ->trueLabel('Mit Rechnungsnummer')
                    ->falseLabel('Ohne Rechnungsnummer')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('invoice_number'),
                        false: fn (Builder $query) => $query->whereNull('invoice_number'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => route('filament.admin.resources.solar-plant-billings.view', $record))
                        ->openUrlInNewTab(false),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->url(fn ($record) => route('filament.admin.resources.solar-plant-billings.edit', $record))
                        ->openUrlInNewTab(false),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Ausgewählte löschen')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Abrechnungen löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Abrechnungen löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, löschen')
                        ->successNotificationTitle('Abrechnungen gelöscht'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->searchOnBlur()
            ->deferLoading()
            ->emptyStateHeading('Keine Abrechnungen vorhanden')
            ->emptyStateDescription('Es wurden noch keine Abrechnungen für diese Solaranlage erstellt.')
            ->emptyStateIcon('heroicon-o-document-currency-euro')
            ->poll('30s'); // Automatische Aktualisierung alle 30 Sekunden
    }

    protected function getTableQuery(): Builder
    {
        return SolarPlantBilling::query()
            ->where('solar_plant_id', $this->solarPlant->id)
            ->with(['customer']);
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    protected function getTableName(): string
    {
        return 'customer-billings-table-' . $this->solarPlant->id;
    }

    public function render(): View
    {
        return view('livewire.customer-billings-table');
    }
}
