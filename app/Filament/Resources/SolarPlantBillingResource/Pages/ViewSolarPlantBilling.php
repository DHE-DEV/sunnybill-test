<?php

namespace App\Filament\Resources\SolarPlantBillingResource\Pages;

use App\Filament\Resources\SolarPlantBillingResource;
use App\Services\SolarPlantBillingPdfService;
use App\Services\EpcQrCodeService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewSolarPlantBilling extends ViewRecord
{
    protected static string $resource = SolarPlantBillingResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Abrechnungsübersicht')
                    ->icon('heroicon-o-document-currency-euro')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('invoice_number')
                                    ->label('Rechnungsnummer')
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('primary')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'draft' => 'Entwurf',
                                        'finalized' => 'Finalisiert',
                                        'sent' => 'Versendet',
                                        'paid' => 'Bezahlt',
                                        default => $state,
                                    })
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($state) => match($state) {
                                        'draft' => 'gray',
                                        'finalized' => 'info',
                                        'sent' => 'warning',
                                        'paid' => 'success',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('formatted_month')
                                    ->label('Abrechnungszeitraum')
                                    ->state(fn ($record) => \Carbon\Carbon::createFromDate($record->billing_year, $record->billing_month, 1)->locale('de')->translatedFormat('F Y'))
                                    ->badge()
                                    ->size('lg')
                                    ->color('info'),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(false),

                Infolists\Components\Section::make('Kunde & Solaranlage')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Kunde')
                                    ->state(function ($record) {
                                        $customer = $record->customer;
                                        if (!$customer) return 'Kunde nicht gefunden';
                                        
                                        return $customer->customer_type === 'business'
                                            ? ($customer->company_name ?: $customer->name)
                                            : $customer->name;
                                    })
                                    ->weight('medium')
                                    ->size('lg')
                                    ->color('primary')
                                    ->url(fn ($record) => $record->customer ? route('filament.admin.resources.customers.view', $record->customer) : null)
                                    ->openUrlInNewTab(false),
                                Infolists\Components\TextEntry::make('solarPlant.name')
                                    ->label('Solaranlage')
                                    ->weight('medium')
                                    ->size('lg')
                                    ->color('success')
                                    ->url(fn ($record) => $record->solarPlant ? route('filament.admin.resources.solar-plants.view', $record->solarPlant) : null)
                                    ->openUrlInNewTab(false),
                            ]),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('participation_percentage')
                                    ->label('Beteiligung')
                                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                                    ->badge()
                                    ->color('info')
                                    ->size('lg'),
                                Infolists\Components\TextEntry::make('produced_energy_kwh')
                                    ->label('Produzierte Energie')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 3, ',', '.') . ' kWh' : 'Nicht erfasst')
                                    ->badge()
                                    ->color('warning')
                                    ->size('lg'),
                                Infolists\Components\TextEntry::make('solarPlant.total_capacity_kw')
                                    ->label('Anlagenleistung')
                                    ->formatStateUsing(fn ($state) => number_format($state, 3, ',', '.') . ' kWp')
                                    ->badge()
                                    ->color('success')
                                    ->size('lg'),
                            ]),
                        Infolists\Components\Section::make('Bankverbindung')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('customer.payment_method')
                                            ->label('Zahlungsart')
                                            ->formatStateUsing(fn (?string $state): string => match($state) {
                                                'transfer' => 'Überweisung (Einzeln)',
                                                'sepa_bulk_transfer' => 'SEPA Sammelüberweisung',
                                                'direct_debit' => 'Lastschrift (Einzeln)',
                                                'sepa_direct_debit' => 'SEPA Sammellastschrift',
                                                default => $state ?: 'Nicht festgelegt',
                                            })
                                            ->badge()
                                            ->color(fn (?string $state): string => match($state) {
                                                'transfer' => 'info',
                                                'sepa_bulk_transfer' => 'primary',
                                                'direct_debit' => 'warning', 
                                                'sepa_direct_debit' => 'success',
                                                default => 'gray',
                                            })
                                            ->icon('heroicon-o-credit-card'),
                                        Infolists\Components\TextEntry::make('customer.account_holder')
                                            ->label('Kontoinhaber')
                                            ->placeholder('Nicht hinterlegt')
                                            ->icon('heroicon-o-user')
                                            ->color('warning'),
                                        Infolists\Components\TextEntry::make('customer.bank_name')
                                            ->label('Bank')
                                            ->placeholder('Nicht hinterlegt')
                                            ->icon('heroicon-o-building-office')
                                            ->color('gray'),
                                    ]),
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('customer.iban')
                                            ->label('IBAN')
                                            ->placeholder('Nicht hinterlegt')
                                            ->formatStateUsing(function ($state) {
                                                if (!$state) return 'Nicht hinterlegt';
                                                // IBAN formatieren: DE89 3704 0044 0532 0130 00
                                                return chunk_split($state, 4, ' ');
                                            })
                                            ->copyable()
                                            ->icon('heroicon-o-identification')
                                            ->color('primary'),
                                        Infolists\Components\TextEntry::make('customer.bic')
                                            ->label('BIC')
                                            ->placeholder('Nicht hinterlegt')
                                            ->copyable()
                                            ->icon('heroicon-o-globe-europe-africa')
                                            ->color('info'),
                                    ]),
                            ])
                            ->compact()
                            ->collapsible()
                            ->collapsed(false),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(true),

                Infolists\Components\Section::make('Finanzübersicht')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_costs')
                                    ->label('Gesamtkosten')
                                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 2, ',', '.'))
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('danger'),
                                Infolists\Components\TextEntry::make('total_credits')
                                    ->label('Gesamtgutschriften')
                                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 2, ',', '.'))
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('total_vat_amount')
                                    ->label('MwSt.-Betrag')
                                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 2, ',', '.'))
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('net_amount')
                                    ->label('Rechnungsbetrag')
                                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 2, ',', '.'))
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color(fn ($state) => $state >= 0 ? 'warning' : 'success'),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(false),

                Infolists\Components\Section::make('Zusätzliche Informationen')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notizen')
                            ->placeholder('Keine Notizen vorhanden')
                            ->prose()
                            ->markdown(),
                        Infolists\Components\TextEntry::make('createdBy.name')
                            ->label('Erstellt von')
                            ->placeholder('Unbekannt'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(true),

                Infolists\Components\Section::make('Zahlungsinformationen')
                    ->icon('heroicon-o-credit-card')
                    ->description('Informationen zum Zahlungsstatus und wichtigen Terminen dieser Abrechnung.')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('finalized_at')
                                    ->label('Finalisiert am')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Noch nicht finalisiert')
                                    ->icon('heroicon-o-check-circle')
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('sent_at')
                                    ->label('Versendet am')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Noch nicht versendet')
                                    ->icon('heroicon-o-paper-airplane')
                                    ->color('warning'),
                                Infolists\Components\TextEntry::make('paid_at')
                                    ->label('Bezahlt am')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Noch nicht bezahlt')
                                    ->icon('heroicon-o-currency-euro')
                                    ->color('success'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('payment_status')
                                    ->label('Zahlungsstatus')
                                    ->state(function ($record) {
                                        if ($record->paid_at) {
                                            return 'Bezahlt';
                                        } elseif ($record->sent_at) {
                                            return 'Ausstehend';
                                        } elseif ($record->finalized_at) {
                                            return 'Bereit zum Versand';
                                        } else {
                                            return 'In Bearbeitung';
                                        }
                                    })
                                    ->badge()
                                    ->color(function ($record) {
                                        if ($record->paid_at) {
                                            return 'success';
                                        } elseif ($record->sent_at) {
                                            return 'warning';
                                        } elseif ($record->finalized_at) {
                                            return 'info';
                                        } else {
                                            return 'gray';
                                        }
                                    })
                                    ->size('xl'),
                                Infolists\Components\TextEntry::make('payment_amount')
                                    ->label('Zahlungsbetrag')
                                    ->state(fn ($record) => $record->net_amount >= 0 
                                        ? 'Forderung: € ' . number_format($record->net_amount, 2, ',', '.') 
                                        : 'Guthaben: € ' . number_format(abs($record->net_amount), 2, ',', '.'))
                                    ->badge()
                                    ->color(fn ($record) => $record->net_amount >= 0 ? 'danger' : 'success')
                                    ->size('xl'),
                            ]),
                        Infolists\Components\Grid::make(1)
                            ->schema([
                                Infolists\Components\TextEntry::make('payment_timeline')
                                    ->label('Zahlungsverlauf')
                                    ->state(function ($record) {
                                        $timeline = [];
                                        
                                        $timeline[] = '📝 Erstellt: ' . $record->created_at->format('d.m.Y H:i');
                                        
                                        if ($record->finalized_at) {
                                            $timeline[] = '✅ Finalisiert: ' . $record->finalized_at->format('d.m.Y H:i');
                                        }
                                        
                                        if ($record->sent_at) {
                                            $timeline[] = '📧 Versendet: ' . $record->sent_at->format('d.m.Y H:i');
                                        }
                                        
                                        if ($record->paid_at) {
                                            $timeline[] = '💰 Bezahlt: ' . $record->paid_at->format('d.m.Y H:i');
                                        }
                                        
                                        return implode(' → ', $timeline);
                                    })
                                    ->prose()
                                    ->color('gray'),
                            ]),
                        
                        // QR-Code für Banking-Apps
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\ImageEntry::make('epc_qr_code')
                                    ->label('QR-Code für Banking-App')
                                    ->state(function ($record) {
                                        $qrService = new EpcQrCodeService();
                                        
                                        if (!$qrService->canGenerateQrCode($record)) {
                                            return null;
                                        }
                                        
                                        try {
                                            $base64QrCode = $qrService->generateEpcQrCode($record);
                                            return 'data:image/png;base64,' . $base64QrCode;
                                        } catch (\Exception $e) {
                                            return null;
                                        }
                                    })
                                    ->size(200)
                                    ->extraAttributes(['style' => 'border: 2px solid #e5e7eb; border-radius: 8px; padding: 10px;'])
                                    ->visible(function ($record) {
                                        $qrService = new EpcQrCodeService();
                                        return $qrService->canGenerateQrCode($record);
                                    }),
                                
                                Infolists\Components\TextEntry::make('qr_code_info')
                                    ->label('QR-Code Informationen')
                                    ->state(function ($record) {
                                        $qrService = new EpcQrCodeService();
                                        
                                        if (!$qrService->canGenerateQrCode($record)) {
                                            return $qrService->getQrCodeErrorMessage($record);
                                        }
                                        
                                        $customer = $record->customer;
                                        $solarPlant = $record->solarPlant;
                                        
                                        $info = [];
                                        $info[] = "📱 **Banking-App QR-Code**";
                                        $info[] = "Empfänger: {$customer->account_holder}";
                                        $info[] = "IBAN: " . chunk_split($customer->iban, 4, ' ');
                                        if ($customer->bic) {
                                            $info[] = "BIC: {$customer->bic}";
                                        }
                                        $info[] = "Betrag: € " . number_format($record->net_amount, 2, ',', '.');
                                        
                                        $reference = [];
                                        if ($solarPlant && $solarPlant->name) {
                                            $reference[] = $solarPlant->name;
                                        }
                                        if ($record->invoice_number) {
                                            $reference[] = "Rechnung: {$record->invoice_number}";
                                        }
                                        $month = \Carbon\Carbon::createFromDate($record->billing_year, $record->billing_month, 1);
                                        $reference[] = "Zeitraum: " . $month->locale('de')->translatedFormat('m/Y');
                                        
                                        $info[] = "Verwendungszweck: " . implode(' | ', $reference);
                                        $info[] = "";
                                        $info[] = "*Scannen Sie den QR-Code mit Ihrer Banking-App für eine schnelle Überweisung.*";
                                        
                                        return implode("\n", $info);
                                    })
                                    ->prose()
                                    ->markdown()
                                    ->color(function ($record) {
                                        $qrService = new EpcQrCodeService();
                                        return $qrService->canGenerateQrCode($record) ? 'success' : 'warning';
                                    })
                                    ->visible(function ($record) {
                                        // Zeige Info immer an, aber Inhalt abhängig von QR-Code Verfügbarkeit
                                        return true;
                                    }),
                            ])
                            ->visible(function ($record) {
                                // Zeige gesamtes Grid nur bei positiven Beträgen an
                                return $record->net_amount > 0;
                            }),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generatePdf')
                ->label('PDF Abrechnung generieren')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(function () {
                    try {
                        $pdfService = new SolarPlantBillingPdfService();
                        
                        return $pdfService->downloadBillingPdf($this->record);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler beim PDF-Export')
                            ->body('Die PDF-Abrechnung konnte nicht erstellt werden: ' . $e->getMessage())
                            ->danger()
                            ->send();
                        
                        return null;
                    }
                }),
            Actions\EditAction::make(),
        ];
    }
}
