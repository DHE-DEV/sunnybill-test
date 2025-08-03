<?php

namespace App\Livewire;

use App\Models\Supplier;
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

class SuppliersTable extends Component implements HasForms, HasTable
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
        // Hole alle Supplier-IDs, die zu dieser SolarPlant gehören
        $supplierIds = $this->solarPlant->suppliers()->pluck('suppliers.id');
        
        return $table
            ->query(
                Supplier::query()
                    ->whereIn('id', $supplierIds)
                    ->with(['supplierType'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('supplier_number')
                    ->label('Lieferantennummer')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('primary')
                    ->copyable()
                    ->placeholder('Nicht vergeben')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Name')
                    ->state(fn ($record) => $record->company_name ?: $record->name)
                    ->searchable(['name', 'company_name'])
                    ->sortable()
                    ->weight('medium')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.admin.resources.suppliers.view', $record))
                    ->openUrlInNewTab(false),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->url(fn ($record) => $record->email ? 'mailto:' . $record->email : null)
                    ->openUrlInNewTab(false)
                    ->placeholder('Keine E-Mail')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->url(fn ($record) => $record->phone ? 'tel:' . $record->phone : null)
                    ->openUrlInNewTab(false)
                    ->placeholder('Keine Telefonnummer')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('supplierType.name')
                    ->label('Lieferantentyp')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => match($record->supplierType?->name) {
                        'Installateur' => 'warning',
                        'Gerätehändler' => 'success',
                        'Wartung' => 'info',
                        'Planung' => 'primary',
                        'Finanzierung' => 'danger',
                        'Versicherung' => 'gray',
                        'Rechtsberatung' => 'purple',
                        'Beratung' => 'orange',
                        default => 'gray',
                    })
                    ->placeholder('Nicht definiert'),

                Tables\Columns\TextColumn::make('city')
                    ->label('Ort')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->placeholder('Nicht angegeben')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('postal_code')
                    ->label('PLZ')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->placeholder('Nicht angegeben')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('country')
                    ->label('Land')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->placeholder('Nicht angegeben')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tax_number')
                    ->label('Steuernummer')
                    ->searchable()
                    ->color('gray')
                    ->placeholder('Nicht angegeben')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('vat_number')
                    ->label('USt-IdNr.')
                    ->searchable()
                    ->color('gray')
                    ->placeholder('Nicht angegeben')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->date('d.m.Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Zuletzt geändert')
                    ->date('d.m.Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_type')
                    ->label('Lieferantentyp')
                    ->options([
                        'installer' => 'Installateur',
                        'equipment_supplier' => 'Gerätehändler',
                        'maintenance' => 'Wartung',
                        'planning' => 'Planung',
                        'financing' => 'Finanzierung',
                        'insurance' => 'Versicherung',
                        'legal' => 'Rechtsberatung',
                        'consulting' => 'Beratung',
                        'other' => 'Sonstiges',
                    ]),

                Tables\Filters\SelectFilter::make('company_type')
                    ->label('Unternehmenstyp')
                    ->options([
                        'sole_proprietorship' => 'Einzelunternehmen',
                        'partnership' => 'Personengesellschaft',
                        'corporation' => 'Kapitalgesellschaft',
                        'cooperative' => 'Genossenschaft',
                        'association' => 'Verein',
                        'other' => 'Sonstiges',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle Lieferanten')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_active', true),
                        false: fn (Builder $query) => $query->where('is_active', false),
                        blank: fn (Builder $query) => $query,
                    ),

                Tables\Filters\SelectFilter::make('city')
                    ->label('Ort')
                    ->options(function () {
                        $supplierIds = $this->solarPlant->suppliers()->pluck('suppliers.id');
                        return Supplier::whereIn('id', $supplierIds)
                            ->whereNotNull('city')
                            ->distinct()
                            ->orderBy('city')
                            ->pluck('city', 'city');
                    })
                    ->searchable(),

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
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => route('filament.admin.resources.suppliers.view', $record))
                        ->openUrlInNewTab(false),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->url(fn ($record) => route('filament.admin.resources.suppliers.edit', $record))
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
                        ->modalHeading('Lieferanten löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Lieferanten löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, löschen')
                        ->successNotificationTitle('Lieferanten gelöscht'),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->searchOnBlur()
            ->deferLoading()
            ->emptyStateHeading('Keine Lieferanten zugeordnet')
            ->emptyStateDescription('Es wurden noch keine Lieferanten zu dieser Solaranlage zugeordnet.')
            ->emptyStateIcon('heroicon-o-building-office-2')
            ->poll('30s'); // Automatische Aktualisierung alle 30 Sekunden
    }


    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    protected function getTableName(): string
    {
        return 'suppliers-table-' . $this->solarPlant->id;
    }

    public function render(): View
    {
        return view('livewire.suppliers-table');
    }
}
