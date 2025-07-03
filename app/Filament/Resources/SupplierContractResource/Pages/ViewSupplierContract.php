<?php

namespace App\Filament\Resources\SupplierContractResource\Pages;

use App\Filament\Resources\SupplierContractResource;
use App\Models\Supplier;
use App\Models\SupplierContract;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplierContract extends ViewRecord
{
    protected static string $resource = SupplierContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Vertragsdaten')
                    ->schema([
                        Components\TextEntry::make('supplier.company_name')
                            ->label('Lieferant'),
                        Components\TextEntry::make('contract_number')
                            ->label('Vertragsnummer'),
                        Components\TextEntry::make('title')
                            ->label('Titel'),
                        Components\TextEntry::make('description')
                            ->label('Beschreibung')
                            ->columnSpanFull(),
                        Components\TextEntry::make('status')
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
                    ])
                    ->columns(2)
                    ->collapsible(),

                Components\Section::make('Laufzeit & Wert')
                    ->schema([
                        Components\TextEntry::make('start_date')
                            ->label('Startdatum')
                            ->date(),
                        Components\TextEntry::make('end_date')
                            ->label('Enddatum')
                            ->date(),
                        Components\TextEntry::make('formatted_contract_value')
                            ->label('Vertragswert'),
                        Components\TextEntry::make('currency')
                            ->label('Währung')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'EUR' => 'Euro (EUR)',
                                'USD' => 'US-Dollar (USD)',
                                'CHF' => 'Schweizer Franken (CHF)',
                                default => $state,
                            }),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Components\Section::make('Zusätzliche Informationen')
                    ->schema([
                        Components\TextEntry::make('payment_terms')
                            ->label('Zahlungsbedingungen')
                            ->columnSpanFull(),
                        Components\TextEntry::make('notes')
                            ->label('Notizen')
                            ->columnSpanFull(),
                        Components\IconEntry::make('is_active')
                            ->label('Aktiv')
                            ->boolean(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}