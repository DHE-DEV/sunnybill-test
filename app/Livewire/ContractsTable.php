<?php

namespace App\Livewire;

use App\Models\SupplierContract;
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

class ContractsTable extends Component implements HasForms, HasTable
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
                SupplierContract::query()
                    ->whereHas('solarPlants', function ($query) {
                        $query->where('solar_plants.id', $this->solarPlant->id);
                    })
                    ->with(['supplier'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('Vertragsnummer')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('primary')
                    ->copyable()
                    ->placeholder('Nicht vergeben'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.admin.resources.supplier-contracts.view', $record))
                    ->openUrlInNewTab(false)
                    ->limit(50),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Lieferant')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->url(fn ($record) => $record->supplier ? route('filament.admin.resources.suppliers.view', $record->supplier) : null)
                    ->openUrlInNewTab(false),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Entwurf',
                        'active' => 'Aktiv',
                        'expired' => 'Abgelaufen',
                        'terminated' => 'Gekündigt',
                        'completed' => 'Abgeschlossen',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'expired' => 'warning',
                        'terminated' => 'danger',
                        'completed' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Startdatum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Enddatum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color('gray')
                    ->placeholder('Unbegrenzt'),

                Tables\Columns\TextColumn::make('contract_value')
                    ->label('Vertragswert')
                    ->formatStateUsing(fn ($state, $record) => $state ? '€ ' . number_format($state, 2, ',', '.') : 'Nicht angegeben')
                    ->sortable()
                    ->color('success')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('solar_plant_percentage')
                    ->label('Anteil (%)')
                    ->state(function ($record) {
                        $assignment = $record->solarPlants()
                            ->where('solar_plants.id', $this->solarPlant->id)
                            ->first();
                        return $assignment ? number_format($assignment->pivot->percentage, 2, ',', '.') . '%' : '0%';
                    })
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter()
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
                        'active' => 'Aktiv',
                        'expired' => 'Abgelaufen',
                        'terminated' => 'Gekündigt',
                        'completed' => 'Abgeschlossen',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->placeholder('Alle Verträge')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_active', true),
                        false: fn (Builder $query) => $query->where('is_active', false),
                        blank: fn (Builder $query) => $query,
                    ),

                Tables\Filters\Filter::make('start_date')
                    ->label('Startdatum')
                    ->form([
                        Forms\Components\DatePicker::make('start_from')
                            ->label('Von')
                            ->placeholder('Startdatum von'),
                        Forms\Components\DatePicker::make('start_until')
                            ->label('Bis')
                            ->placeholder('Startdatum bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Läuft bald ab')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotNull('end_date')
                              ->where('end_date', '>', now())
                              ->where('end_date', '<=', now()->addDays(30))
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => route('filament.admin.resources.supplier-contracts.view', $record))
                        ->openUrlInNewTab(false),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->url(fn ($record) => route('filament.admin.resources.supplier-contracts.edit', $record))
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
                        ->modalHeading('Verträge löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Verträge löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, löschen')
                        ->successNotificationTitle('Verträge gelöscht'),
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
            ->emptyStateHeading('Keine Verträge zugeordnet')
            ->emptyStateDescription('Es wurden noch keine Verträge zu dieser Solaranlage zugeordnet.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->poll('30s'); // Automatische Aktualisierung alle 30 Sekunden
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    protected function getTableName(): string
    {
        return 'contracts-table-' . $this->solarPlant->id;
    }

    public function render(): View
    {
        return view('livewire.contracts-table');
    }
}
