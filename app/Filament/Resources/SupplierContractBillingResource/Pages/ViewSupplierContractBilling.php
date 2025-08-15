<?php

namespace App\Filament\Resources\SupplierContractBillingResource\Pages;

use App\Filament\Resources\SupplierContractBillingResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplierContractBilling extends ViewRecord
{
    protected static string $resource = SupplierContractBillingResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $contract = $record->supplierContract;
        
        if ($contract) {
            return "Beleg ansehen - {$contract->contract_number} - {$contract->title}";
        }
        
        return 'Beleg ansehen';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Belegdetails')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('billing_number')
                                    ->label('Belegnummer'),
                                Infolists\Components\TextEntry::make('supplier_invoice_number')
                                    ->label('Anbieter-Rechnungsnummer')
                                    ->placeholder('—'),
                                Infolists\Components\TextEntry::make('supplierContract.contract_number')
                                    ->label('Lieferantenvertrag'),
                                Infolists\Components\TextEntry::make('solar_plant_name')
                                    ->label('Zugeordnete Solaranlage')
                                    ->getStateUsing(function ($record) {
                                        $contract = $record->supplierContract;
                                        if ($contract) {
                                            $solarPlant = $contract->solarPlants()->first();
                                            return $solarPlant?->name ?? 'Keine Zuordnung';
                                        }
                                        return 'Keine Zuordnung';
                                    })
                                    ->color('primary')
                                    ->weight('medium'),
                                Infolists\Components\TextEntry::make('title')
                                    ->label('Titel'),
                                Infolists\Components\TextEntry::make('billing_period')
                                    ->label('Abrechnungsperiode')
                                    ->getStateUsing(function ($record) {
                                        return $record->billing_period ?? '—';
                                    }),
                                Infolists\Components\TextEntry::make('billing_date')
                                    ->label('Abrechnungsdatum')
                                    ->date('d.m.Y'),
                                Infolists\Components\TextEntry::make('due_date')
                                    ->label('Fälligkeitsdatum')
                                    ->date('d.m.Y')
                                    ->placeholder('—'),
                                Infolists\Components\TextEntry::make('total_amount')
                                    ->label('Gesamtbetrag')
                                    ->money('EUR')
                                    ->size('lg')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'secondary',
                                        'pending' => 'warning',
                                        'approved' => 'primary',
                                        'paid' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray'
                                    })
                                    ->formatStateUsing(fn (string $state): string => 
                                        \App\Models\SupplierContractBilling::getStatusOptions()[$state] ?? $state
                                    ),
                            ]),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->label('Beschreibung')
                            ->prose()
                            ->placeholder('Keine Beschreibung')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Notizen')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->prose()
                            ->placeholder('Keine Notizen vorhanden'),
                    ])
                    ->visible(fn ($record) => !empty($record->notes))
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Bearbeiten'),
            Actions\DeleteAction::make()
                ->label('Löschen'),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            SupplierContractBillingResource\RelationManagers\ArticlesRelationManager::class,
            SupplierContractBillingResource\RelationManagers\AllocationsRelationManager::class,
            SupplierContractBillingResource\RelationManagers\DocumentsRelationManager::class,
        ];
    }
}
