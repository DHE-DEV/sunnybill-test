<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'supplierContracts';

    protected static ?string $title = 'Verträge';

    protected static ?string $modelLabel = 'Vertrag';

    protected static ?string $pluralModelLabel = 'Verträge';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->supplierContracts()->count();
        return $count > 0 ? (string) $count : null;
    }

    public function isReadOnly(): bool
    {
        return true; // Nur Anzeige, da Verträge über SupplierContract verwaltet werden
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('contract_number')
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('Vertragsnummer')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('supplier.company_name')
                    ->label('Lieferant')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('malo_id')
                    ->label('MaLo-ID')
                    ->searchable()
                    ->placeholder('-')
                    ->copyable()
                    ->toggleable()
                    ->color('info'),
                Tables\Columns\TextColumn::make('ep_id')
                    ->label('EP-ID')
                    ->searchable()
                    ->placeholder('-')
                    ->copyable()
                    ->toggleable()
                    ->color('info'),
                Tables\Columns\TextColumn::make('contract_type')
                    ->label('Vertragsart')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'installation' => 'Installation',
                        'maintenance' => 'Wartung',
                        'components' => 'Komponenten',
                        'planning' => 'Planung',
                        'monitoring' => 'Überwachung',
                        'insurance' => 'Versicherung',
                        'financing' => 'Finanzierung',
                        'other' => 'Sonstiges',
                        default => $state,
                    })
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(fn ($state) => match($state) {
                        'installation' => 'success',
                        'maintenance' => 'warning',
                        'components' => 'info',
                        'planning' => 'primary',
                        'monitoring' => 'info',
                        'insurance' => 'danger',
                        'financing' => 'warning',
                        'other' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Vertragsbeginn')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Vertragsende')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('Unbefristet')
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contract_value')
                    ->label('Vertragswert')
                    ->formatStateUsing(fn ($state) => $state ? '€ ' . number_format($state, 2, ',', '.') : '-')
                    ->sortable()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        'expired' => 'danger',
                        'terminated' => 'warning',
                        'completed' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('contract_type')
                    ->label('Vertragsart')
                    ->options([
                        'installation' => 'Installation',
                        'maintenance' => 'Wartung',
                        'components' => 'Komponenten',
                        'planning' => 'Planung',
                        'monitoring' => 'Überwachung',
                        'insurance' => 'Versicherung',
                        'financing' => 'Finanzierung',
                        'other' => 'Sonstiges',
                    ]),
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
                    ->label('Aktive Verträge'),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Läuft bald ab (nächste 30 Tage)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('end_date', '>=', now())
                              ->where('end_date', '<=', now()->addDays(30))
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.supplier-contracts.view', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.supplier-contracts.edit', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('start_date', 'desc')
            ->emptyStateHeading('Keine Verträge zugeordnet')
            ->emptyStateDescription('Dieser Solaranlage sind noch keine Verträge zugeordnet. Verträge können über die Lieferantenverwaltung erstellt und zugeordnet werden.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateActions([
                Tables\Actions\Action::make('create_contract')
                    ->label('Neuen Vertrag erstellen')
                    ->icon('heroicon-o-plus')
                    ->url(route('filament.admin.resources.supplier-contracts.create'))
                    ->openUrlInNewTab(),
            ]);
    }
}
