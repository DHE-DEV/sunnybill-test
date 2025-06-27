<?php

namespace App\Filament\Resources\CustomerMonthlyCreditResource\Pages;

use App\Filament\Resources\CustomerMonthlyCreditResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewCustomerMonthlyCredit extends ViewRecord
{
    protected static string $resource = CustomerMonthlyCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Keine Edit/Delete-Actions, da Gutschriften automatisch berechnet werden
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Gutschrift Übersicht')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('month')
                                    ->label('Monat')
                                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('F Y'))
                                    ->badge()
                                    ->color('primary')
                                    ->size('lg'),
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Kunde')
                                    ->size('lg')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('solarPlant.name')
                                    ->label('Solaranlage')
                                    ->size('lg')
                                    ->color('success'),
                            ]),
                    ]),
                Infolists\Components\Section::make('Beteiligung & Energieanteil')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('participation_percentage')
                                    ->label('Beteiligung')
                                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                                    ->badge()
                                    ->color('info')
                                    ->size('lg'),
                                Infolists\Components\TextEntry::make('energy_share_kwh')
                                    ->label('Energieanteil')
                                    ->formatStateUsing(fn ($state) => number_format($state, 6, ',', '.') . ' kWh')
                                    ->badge()
                                    ->color('warning')
                                    ->size('lg'),
                            ]),
                    ]),
                Infolists\Components\Section::make('Finanzielle Aufschlüsselung')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('savings_amount')
                                    ->label('Ersparnis durch Eigenverbrauch')
                                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 6, ',', '.'))
                                    ->badge()
                                    ->color('success')
                                    ->size('lg'),
                                Infolists\Components\TextEntry::make('feed_in_revenue')
                                    ->label('Einspeiseerlös')
                                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 6, ',', '.'))
                                    ->badge()
                                    ->color('warning')
                                    ->size('lg'),
                                Infolists\Components\TextEntry::make('total_credit')
                                    ->label('Gesamtgutschrift')
                                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 6, ',', '.'))
                                    ->badge()
                                    ->color('primary')
                                    ->size('xl')
                                    ->weight('bold'),
                            ]),
                    ]),
                Infolists\Components\Section::make('Solaranlage Details')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('solarPlant.location')
                                    ->label('Standort')
                                    ->icon('heroicon-o-map-pin'),
                                Infolists\Components\TextEntry::make('solarPlant.total_capacity_kw')
                                    ->label('Gesamtleistung')
                                    ->formatStateUsing(fn ($state) => number_format($state, 3, ',', '.') . ' kW')
                                    ->badge()
                                    ->color('success'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('solarPlant.status')
                                    ->label('Anlagenstatus')
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'planned' => 'Geplant',
                                        'under_construction' => 'Im Bau',
                                        'active' => 'Aktiv',
                                        'maintenance' => 'Wartung',
                                        'inactive' => 'Inaktiv',
                                        default => $state,
                                    })
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'planned' => 'gray',
                                        'under_construction' => 'warning',
                                        'active' => 'success',
                                        'maintenance' => 'info',
                                        'inactive' => 'danger',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Berechnet am')
                                    ->dateTime('d.m.Y H:i')
                                    ->icon('heroicon-o-clock'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}