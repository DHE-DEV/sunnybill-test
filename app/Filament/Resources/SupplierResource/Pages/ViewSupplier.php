<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use App\Models\Address;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Firmendaten')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('company_name')
                                    ->label('Firmenname')
                                    ->size('lg')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('contact_person')
                                    ->label('Ansprechpartner'),
                                Infolists\Components\TextEntry::make('email')
                                    ->label('E-Mail')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('website')
                                    ->label('Website')
                                    ->url(fn ($record) => $record->website)
                                    ->openUrlInNewTab(),
                            ]),
                    ]),

                Infolists\Components\Section::make('Rechnungsadresse')
                    ->collapsible()
                    ->collapsed(fn ($record) => !$record->hasSeparateBillingAddress())
                    ->schema([
                        Infolists\Components\TextEntry::make('billing_address_display')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                $billingAddress = $record->getBillingAddressForInvoice();
                                if ($billingAddress) {
                                    return $billingAddress->street_address . "\n" .
                                           $billingAddress->postal_code . ' ' . $billingAddress->city . "\n" .
                                           $billingAddress->country;
                                }
                                return 'Verwendet Standard-Adresse aus Firmendaten';
                            })
                            ->prose()
                            ->placeholder('Keine separate Rechnungsadresse vorhanden'),
                        Infolists\Components\Actions::make([
                            Infolists\Components\Actions\Action::make('add_billing_address')
                                ->label('Rechnungsadresse hinzufügen')
                                ->icon('heroicon-o-plus')
                                ->color('success')
                                ->url(fn ($record) => SupplierResource::getUrl('edit', ['record' => $record->id]) . '#addresses')
                                ->visible(fn ($record) => !$record->hasSeparateBillingAddress()),
                            Infolists\Components\Actions\Action::make('edit_billing_address')
                                ->label('Rechnungsadresse bearbeiten')
                                ->icon('heroicon-o-pencil')
                                ->color('warning')
                                ->url(fn ($record) => SupplierResource::getUrl('edit', ['record' => $record->id]) . '#addresses')
                                ->visible(fn ($record) => $record->hasSeparateBillingAddress()),
                        ]),
                    ]),

                Infolists\Components\Section::make('Lieferadresse')
                    ->collapsible()
                    ->collapsed(fn ($record) => !$record->hasSeparateShippingAddress())
                    ->schema([
                        Infolists\Components\TextEntry::make('shipping_address_display')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                $shippingAddress = $record->getShippingAddress();
                                if ($shippingAddress) {
                                    return $shippingAddress->street_address . "\n" .
                                           $shippingAddress->postal_code . ' ' . $shippingAddress->city . "\n" .
                                           $shippingAddress->country;
                                }
                                return 'Verwendet Standard-Adresse aus Firmendaten';
                            })
                            ->prose()
                            ->placeholder('Keine separate Lieferadresse vorhanden'),
                        Infolists\Components\Actions::make([
                            Infolists\Components\Actions\Action::make('add_shipping_address')
                                ->label('Lieferadresse hinzufügen')
                                ->icon('heroicon-o-plus')
                                ->color('success')
                                ->url(fn ($record) => SupplierResource::getUrl('edit', ['record' => $record->id]) . '#addresses')
                                ->visible(fn ($record) => !$record->hasSeparateShippingAddress()),
                            Infolists\Components\Actions\Action::make('edit_shipping_address')
                                ->label('Lieferadresse bearbeiten')
                                ->icon('heroicon-o-pencil')
                                ->color('warning')
                                ->url(fn ($record) => SupplierResource::getUrl('edit', ['record' => $record->id]) . '#addresses')
                                ->visible(fn ($record) => $record->hasSeparateShippingAddress()),
                        ]),
                    ]),

                Infolists\Components\Section::make('Steuerliche Daten')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('tax_number')
                                    ->label('Steuernummer'),
                                Infolists\Components\TextEntry::make('vat_id')
                                    ->label('Umsatzsteuer-ID'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->tax_number || $record->vat_id),

                Infolists\Components\Section::make('Sonstiges')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notizen')
                            ->prose()
                            ->markdown(),
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Aktiv')
                            ->boolean(),
                    ])
                    ->visible(fn ($record) => $record->notes || $record->is_active !== null),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}