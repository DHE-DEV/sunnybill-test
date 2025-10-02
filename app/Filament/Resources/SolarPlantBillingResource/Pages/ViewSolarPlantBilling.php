<?php

namespace App\Filament\Resources\SolarPlantBillingResource\Pages;

use App\Filament\Resources\SolarPlantBillingResource;
use App\Services\SolarPlantBillingPdfService;
use App\Services\EpcQrCodeService;
use App\Models\SolarPlantBillingPayment;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Forms;

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
                                        'cancelled' => 'Storniert',
                                        default => $state,
                                    })
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn ($state) => match($state) {
                                        'draft' => 'gray',
                                        'finalized' => 'info',
                                        'sent' => 'warning',
                                        'paid' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('formatted_month')
                                    ->label('Abrechnungszeitraum')
                                    ->state(fn ($record) => \Carbon\Carbon::createFromDate($record->billing_year, $record->billing_month, 1)->locale('de')->translatedFormat('F Y'))
                                    ->badge()
                                    ->size('lg')
                                    ->color('info'),
                            ]),
                        Infolists\Components\TextEntry::make('cancellation_reason')
                            ->label('Stornierungsgrund')
                            ->prose()
                            ->color('danger')
                            ->visible(fn ($record) => $record->status === 'cancelled' && $record->cancellation_reason),
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

                Infolists\Components\Section::make('Abrechnungsdetails')
                    ->icon('heroicon-o-document-text')
                    ->description('Detaillierte Aufschlüsselung der Abrechnungsperiode entsprechend der PDF-Abrechnung')
                    ->schema([
                        // Abrechnungsperiode Info
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('billing_period')
                                    ->label('Abrechnungsperiode')
                                    ->state(function ($record) {
                                        $monthNames = [
                                            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                                            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August', 
                                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                                        ];
                                        return $monthNames[$record->billing_month] . ' ' . $record->billing_year;
                                    })
                                    ->badge()
                                    ->size('xl')
                                    ->color('primary'),
                                Infolists\Components\TextEntry::make('current_participation_percentage')
                                    ->label('Aktueller Beteiligungsanteil')
                                    ->state(function ($record) {
                                        $solarPlant = $record->solarPlant;
                                        $customer = $record->customer;
                                        $participation = $solarPlant->participations()
                                            ->where('customer_id', $customer->id)
                                            ->first();
                                        $currentPercentage = $participation ? $participation->percentage : $record->participation_percentage;
                                        $currentParticipationKwp = $participation ? $participation->participation_kwp : null;
                                        
                                        return $currentParticipationKwp 
                                            ? number_format($currentParticipationKwp, 2, ',', '.') . ' kWp (' . number_format($currentPercentage, 2, ',', '.') . '%)'
                                            : number_format($currentPercentage, 2, ',', '.') . '%';
                                    })
                                    ->badge()
                                    ->size('xl')
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('energy_production')
                                    ->label('Produzierte Energie')
                                    ->state(function ($record) {
                                        if (!$record->produced_energy_kwh) {
                                            return 'Nicht erfasst';
                                        }
                                        
                                        $solarPlant = $record->solarPlant;
                                        $customer = $record->customer;
                                        $participation = $solarPlant->participations()
                                            ->where('customer_id', $customer->id)
                                            ->first();
                                        $currentPercentage = $participation ? $participation->percentage : $record->participation_percentage;
                                        
                                        $totalEnergy = number_format($record->produced_energy_kwh, 3, ',', '.') . ' kWh';
                                        $customerShare = number_format(($record->produced_energy_kwh * $currentPercentage / 100), 3, ',', '.') . ' kWh';
                                        
                                        return "Total: {$totalEnergy}\nIhr Anteil: {$customerShare}";
                                    })
                                    ->badge()
                                    ->size('xl')
                                    ->color('warning'),
                            ]),

                        // Gutschriftenpositionen Details
                        Infolists\Components\Section::make('Gutschriftenpositionen')
                            ->icon('heroicon-o-plus-circle')
                            ->schema([
                                Infolists\Components\TextEntry::make('credit_breakdown_table')
                                    ->label('')
                                    ->state(function ($record) {
                                        if (empty($record->credit_breakdown)) {
                                            return '<div class="text-center py-4 text-gray-500">Keine Gutschriftenpositionen verfügbar</div>';
                                        }

                                        $breakdown = $record->credit_breakdown;
                                        
                                        // HTML-Tabelle für Gutschriftenpositionen
                                        $html = '<div class="overflow-x-auto">';
                                        $html .= '<table class="w-full border-collapse border border-gray-300 text-sm">';
                                        $html .= '<thead>';
                                        $html .= '<tr class="bg-green-50 border-b-2 border-green-200">';
                                        $html .= '<th class="px-3 py-2 text-left font-semibold text-green-800 border-r border-gray-300">Bezeichnung</th>';
                                        $html .= '<th class="px-3 py-2 text-right font-semibold text-green-800 border-r border-gray-300" style="width: 100px;">Anteil</th>';
                                        $html .= '<th class="px-3 py-2 text-right font-semibold text-green-800" style="width: 120px;">Gesamtbetrag</th>';
                                        $html .= '</tr>';
                                        $html .= '</thead>';
                                        $html .= '<tbody>';
                                        
                                        foreach ($breakdown as $item) {
                                            $supplierName = htmlspecialchars($item['supplier_name'] ?? 'Unbekannt');
                                            $billingNumber = htmlspecialchars($item['billing_number'] ?? 'N/A');

                                            $html .= '<tr class="border-b border-green-100">';
                                            $html .= '<td class="px-3 py-6 border-r border-gray-300" style="vertical-align: top;">';
                                            $html .= '<div class="font-medium text-gray-900">' . htmlspecialchars($item['contract_title']) . '</div>';
                                            $html .= '<div class="text-sm text-gray-600 mt-1">';
                                            
                                            // Lieferanten-Link
                                            $supplierUrl = route('filament.admin.resources.suppliers.view', $item['supplier_id']);
                                            $html .= 'Lieferant: <a href="' . $supplierUrl . '" target="_blank" class="text-blue-600 font-medium hover:text-blue-800 hover:underline">' . $supplierName . ' <svg class="inline-block w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"></path><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-1a1 1 0 10-2 0v1H5V7h1a1 1 0 000-2H5z"></path></svg></a>';
                                            
                                            // Abrechnungs-Link  
                                            $billingUrl = route('filament.admin.resources.supplier-contract-billings.view', $item['contract_billing_id']);
                                            $html .= ' | Abrechnungsnr.: <a href="' . $billingUrl . '" target="_blank" class="text-orange-600 font-medium hover:text-orange-800 hover:underline">' . $billingNumber . ' <svg class="inline-block w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"></path><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-1a1 1 0 10-2 0v1H5V7h1a1 1 0 000-2H5z"></path></svg></a>';
                                            
                                            $html .= '</div>';
                                            
                                            // Artikel-Details anzeigen
                                            if (isset($item['articles']) && !empty($item['articles'])) {
                                                $html .= '<div class="mt-2 p-2 bg-gray-50 rounded border">';
                                                $html .= '<div class="font-medium text-xs text-gray-700 mb-1">Details:</div>';
                                                
                                                foreach ($item['articles'] as $article) {
                                                    $html .= '<div class="text-xs text-gray-600 mb-0 mt-3">';
                                                    
                                                    // Artikel-Name mit Links - sowohl zum Artikel als auch zum Vertrag
                                                    $html .= '<div class="font-medium flex items-center gap-1">';
                                                    
                                                    // Artikel-Name als Link zum Artikel (ursprüngliche Verlinkung)
                                                    if (isset($article['article_id']) && $article['article_id']) {
                                                        $articleUrl = route('filament.admin.resources.articles.edit', $article['article_id']);
                                                        $html .= '<a href="' . $articleUrl . '" target="_blank" class="text-purple-600 hover:text-purple-800 hover:underline" title="Artikel bearbeiten">' . htmlspecialchars($article['article_name']) . '</a>';
                                                    } else {
                                                        $html .= htmlspecialchars($article['article_name']);
                                                    }
                                                    
                                                    // Text "info_artikel_xxx" nach dem Artikel-Namen hinzufügen
                                                    $html .= ' <span class="text-gray-500 text-xs">info_artikel_xxx</span>';
                                                    
                                                    // Info-Symbol zum Artikel
                                                    if (isset($article['article_id']) && $article['article_id']) {
                                                        $articleViewUrl = route('filament.admin.resources.articles.edit', $article['article_id']);
                                                        $html .= ' <a href="' . $articleViewUrl . '" target="_blank" class="text-gray-600 hover:text-gray-800" title="Artikel-Info anzeigen"><svg class="inline-block w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"></path></svg></a>';
                                                    }
                                                    
                                                    // Zusätzliches Symbol zum Vertragsartikel
                                                    if (isset($item['contract_id'])) {
                                                        $contractUrl = route('filament.admin.resources.supplier-contracts.view', $item['contract_id']);
                                                        $html .= ' <a href="' . $contractUrl . '" target="_blank" class="text-blue-600 hover:text-blue-800" title="Vertrag anzeigen"><svg class="inline-block w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></a>';
                                                    }
                                                    
                                                    $html .= '</div>';
                                                    $html .= '<div class="flex flex-wrap gap-4 mt-1">';
                                                    $html .= '<span>Menge: ' . number_format($article['quantity'], 4, ',', '.') . ' ' . htmlspecialchars($article['unit']) . '</span>';
                                                    $html .= '<span>Preis: ' . number_format($article['unit_price'], 6, ',', '.') . ' €</span>';
                                                    $html .= '<span>Gesamt netto: ' . number_format($article['total_price_net'], 2, ',', '.') . ' €</span>';
                                                    $html .= '<span>Steuer: ' . number_format($article['tax_rate'] * 100, 1, ',', '.') . '% = ' . number_format($article['tax_amount'], 2, ',', '.') . ' €</span>';
                                                    $html .= '<span>Gesamt brutto: ' . number_format($article['total_price_gross'], 2, ',', '.') . ' €</span>';
                                                    $html .= '</div>';
                                                    
                                                    // Artikel-Hinweis anzeigen falls vorhanden
                                                    if (!empty($article['detailed_description'])) {
                                                        $html .= '<div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs">';
                                                        $html .= '<div class="font-medium text-blue-800 mb-1">Hinweis:</div>';
                                                        $html .= '<div class="text-blue-700">' . nl2br(htmlspecialchars($article['detailed_description'])) . '</div>';
                                                        $html .= '</div>';
                                                    }
                                                    
                                                    $html .= '</div>';
                                                }
                                                $html .= '</div>';
                                            }
                                            
                                            $html .= '</td>';
                                            $html .= '<td class="px-3 py-6 text-right text-green-800 font-medium border-r border-gray-300" style="vertical-align: top;">' . number_format($item['customer_percentage'], 2, ',', '.') . '%</td>';
                                            $html .= '<td class="px-3 py-6 text-right text-green-800 font-semibold " style="vertical-align: top;">' . number_format($item['customer_share'], 2, ',', '.') . ' €</td>';
                                            $html .= '</tr>';
                                        }
                                        
                                        $html .= '</tbody>';
                                        $html .= '</table>';
                                        
                                        // Gesamtbetrag für Gutschriftenpositionen
                                        $totalCredits = array_sum(array_column($breakdown, 'customer_share'));
                                        $html .= '<div class="mt-3 p-3 bg-green-50 border border-green-200 rounded flex justify-between items-center">';
                                        $html .= '<div class="font-semibold text-green-800">Gesamtgutschriften:</div>';
                                        $html .= '<div class="font-bold text-green-800 ">' . number_format($totalCredits, 2, ',', '.') . ' €</div>';
                                        $html .= '</div>';
                                        $html .= '</div>';
                                        
                                        return $html;
                                    })
                                    ->html()
                                    ->visible(fn ($record) => !empty($record->credit_breakdown)),
                            ])
                            ->compact()
                            ->collapsible()
                            ->collapsed(false)
                            ->visible(fn ($record) => !empty($record->credit_breakdown)),

                        // Kostenpositionen Details
                        Infolists\Components\Section::make('Kostenpositionen')
                            ->icon('heroicon-o-minus-circle')
                            ->schema([
                                Infolists\Components\TextEntry::make('cost_breakdown_table')
                                    ->label('')
                                    ->state(function ($record) {
                                        if (empty($record->cost_breakdown)) {
                                            return '<div class="text-center py-4 text-gray-500">Keine Kostenpositionen verfügbar</div>';
                                        }

                                        $breakdown = $record->cost_breakdown;
                                        
                                        // HTML-Tabelle für Kostenpositionen
                                        $html = '<div class="overflow-x-auto">';
                                        $html .= '<table class="w-full border-collapse border border-gray-300 text-sm">';
                                        $html .= '<thead>';
                                        $html .= '<tr class="bg-gray-50 border-b-2 border-gray-300">';
                                        $html .= '<th class="px-3 py-2 text-left font-semibold text-gray-800 border-r border-gray-300">Bezeichnung</th>';
                                        $html .= '<th class="px-3 py-2 text-right font-semibold text-gray-800 border-r border-gray-300" style="width: 100px;">Anteil</th>';
                                        $html .= '<th class="px-3 py-2 text-right font-semibold text-gray-800" style="width: 120px;">Gesamtbetrag</th>';
                                        $html .= '</tr>';
                                        $html .= '</thead>';
                                        $html .= '<tbody>';
                                        
                                        foreach ($breakdown as $item) {
                                            $supplierName = htmlspecialchars($item['supplier_name'] ?? 'Unbekannt');
                                            $billingNumber = htmlspecialchars($item['billing_number'] ?? 'N/A');

                                            $html .= '<tr class="border-b border-gray-200">';
                                            $html .= '<td class="px-3 py-6 border-r border-gray-300" style="vertical-align: top;">';
                                            $html .= '<div class="font-medium text-gray-900">' . htmlspecialchars($item['contract_title']) . '</div>';
                                            $html .= '<div class="text-sm text-gray-600 mt-1">';
                                            
                                            // Lieferanten-Link
                                            $supplierUrl = route('filament.admin.resources.suppliers.view', $item['supplier_id']);
                                            $html .= 'Lieferant: <a href="' . $supplierUrl . '" target="_blank" class="text-blue-600 font-medium hover:text-blue-800 hover:underline">' . $supplierName . ' <svg class="inline-block w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"></path><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-1a1 1 0 10-2 0v1H5V7h1a1 1 0 000-2H5z"></path></svg></a>';
                                            
                                            // Abrechnungs-Link  
                                            $billingUrl = route('filament.admin.resources.supplier-contract-billings.view', $item['contract_billing_id']);
                                            $html .= ' | Abrechnungsnr.: <a href="' . $billingUrl . '" target="_blank" class="text-orange-600 font-medium hover:text-orange-800 hover:underline">' . $billingNumber . ' <svg class="inline-block w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"></path><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-1a1 1 0 10-2 0v1H5V7h1a1 1 0 000-2H5z"></path></svg></a>';
                                            
                                            $html .= '</div>';
                                            
                                            // Artikel-Details anzeigen
                                            if (isset($item['articles']) && !empty($item['articles'])) {
                                                $html .= '<div class="mt-2 p-2 bg-gray-50 rounded border">';
                                                $html .= '<div class="font-medium text-xs text-gray-700 mb-1">Details:</div>';
                                                
                                                foreach ($item['articles'] as $article) {
                                                    $html .= '<div class="text-xs text-gray-600 mb-1">';
                                                    
                                                    // Artikel-Name mit Links - sowohl zum Artikel als auch zum Vertrag
                                                    $html .= '<div class="font-medium flex items-center gap-1">';
                                                    
                                                    // Artikel-Name als Link zum Artikel (ursprüngliche Verlinkung)
                                                    if (isset($article['article_id']) && $article['article_id']) {
                                                        $articleUrl = route('filament.admin.resources.articles.edit', $article['article_id']);
                                                        $html .= '<a href="' . $articleUrl . '" target="_blank" class="text-purple-600 hover:text-purple-800 hover:underline" title="Artikel bearbeiten">' . htmlspecialchars($article['article_name']) . '</a>';
                                                    } else {
                                                        $html .= htmlspecialchars($article['article_name']);
                                                    }
                                                    
                                                    // Text "info_artikel_xxx" nach dem Artikel-Namen hinzufügen
                                                    $html .= ' <span class="text-gray-500 text-xs">info_artikel_xxx_2</span> ('. $article['article_id'].')';
                                                    
                                                    // Info-Symbol zum Artikel
                                                    if (isset($article['article_id']) && $article['article_id']) {
                                                        $articleViewUrl = route('filament.admin.resources.articles.edit', $article['article_id']);
                                                        $html .= ' <a href="' . $articleViewUrl . '" target="_blank" class="text-gray-600 hover:text-gray-800" title="Artikel-Info anzeigen"><svg class="inline-block w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"></path></svg></a>';
                                                    }
                                                    
                                                    // Zusätzliches Symbol zum Vertragsartikel
                                                    if (isset($item['contract_id'])) {
                                                        $contractUrl = route('filament.admin.resources.supplier-contracts.view', $item['contract_id']);
                                                        $html .= ' <a href="' . $contractUrl . '" target="_blank" class="text-blue-600 hover:text-blue-800" title="Vertrag anzeigen"><svg class="inline-block w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></a>';
                                                    }
                                                    
                                                    $html .= '</div>';
                                                    $html .= '<div class="flex flex-wrap gap-4 mt-1">';
                                                    $html .= '<span>Menge: ' . number_format($article['quantity'], 4, ',', '.') . ' ' . htmlspecialchars($article['unit']) . '</span>';
                                                    $html .= '<span>Preis: ' . number_format($article['unit_price'], 6, ',', '.') . ' €</span>';
                                                    $html .= '<span>Gesamt netto: ' . number_format($article['total_price_net'], 2, ',', '.') . ' €</span>';
                                                    $html .= '<span>Steuer: ' . number_format($article['tax_rate'] * 100, 1, ',', '.') . '% = ' . number_format($article['tax_amount'], 2, ',', '.') . ' €</span>';
                                                    $html .= '<span>Gesamt brutto: ' . number_format($article['total_price_gross'], 2, ',', '.') . ' €</span>';
                                                    $html .= '</div>';
                                                    
                                                    // Artikel-Hinweis anzeigen falls vorhanden
                                                    if (!empty($article['detailed_description'])) {
                                                        $html .= '<div class="mt-2 p-2 bg-orange-50 border border-orange-200 rounded text-xs">';
                                                        $html .= '<div class="font-medium text-orange-800 mb-1">Hinweis:</div>';
                                                        $html .= '<div class="text-orange-700">' . nl2br(htmlspecialchars($article['detailed_description'])) . '</div>';
                                                        $html .= '</div>';
                                                    }
                                                    
                                                    $html .= '</div>';
                                                }
                                                $html .= '</div>';
                                            }
                                            
                                            $html .= '</td>';
                                            $html .= '<td class="px-3 py-6 text-right text-gray-800 font-medium border-r border-gray-300" style="vertical-align: top;">' . number_format($item['customer_percentage'], 2, ',', '.') . '%</td>';
                                            $html .= '<td class="px-3 py-6 text-right text-gray-800 font-semibold " style="vertical-align: top;">' . number_format($item['customer_share'], 2, ',', '.') . ' €</td>';
                                            $html .= '</tr>';
                                        }
                                        
                                        $html .= '</tbody>';
                                        $html .= '</table>';
                                        
                                        // Gesamtbetrag für Kostenpositionen
                                        $totalCosts = array_sum(array_column($breakdown, 'customer_share'));
                                        $html .= '<div class="mt-3 p-3 bg-gray-50 border border-gray-300 rounded flex justify-between items-center">';
                                        $html .= '<div class="font-semibold text-gray-800">Gesamtkosten:</div>';
                                        $html .= '<div class="font-bold text-gray-800 ">' . number_format($totalCosts, 2, ',', '.') . ' €</div>';
                                        $html .= '</div>';
                                        $html .= '</div>';
                                        
                                        return $html;
                                    })
                                    ->html()
                                    ->visible(fn ($record) => !empty($record->cost_breakdown)),
                            ])
                            ->compact()
                            ->collapsible()
                            ->collapsed(false)
                            ->visible(fn ($record) => !empty($record->cost_breakdown)),

                        // MwSt. Aufschlüsselung
                        Infolists\Components\Section::make('Steuerliche Aufschlüsselung')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('total_costs_net')
                                            ->label('Gesamtkosten (Netto)')
                                            ->formatStateUsing(fn ($state) => '€ ' . number_format($state ?? 0, 2, ',', '.'))
                                            ->badge()
                                            ->color('danger'),
                                        Infolists\Components\TextEntry::make('total_credits_net')
                                            ->label('Gesamtgutschriften (Netto)')
                                            ->formatStateUsing(fn ($state) => '€ ' . number_format($state ?? 0, 2, ',', '.'))
                                            ->badge()
                                            ->color('success'),
                                        Infolists\Components\TextEntry::make('net_amount_before_vat')
                                            ->label('Nettobetrag')
                                            ->state(function ($record) {
                                                $netBeforeVat = ($record->total_credits_net ?? 0) - ($record->total_costs_net ?? 0);
                                                return '€ ' . number_format($netBeforeVat, 2, ',', '.');
                                            })
                                            ->badge()
                                            ->color('info'),
                                    ]),
                                Infolists\Components\TextEntry::make('vat_calculation')
                                    ->label('MwSt. Berechnung')
                                    ->state(function ($record) {
                                        $netBeforeVat = ($record->total_credits_net ?? 0) - ($record->total_costs_net ?? 0);
                                        $vatAmount = $record->total_vat_amount ?? 0;
                                        $grossAmount = $record->net_amount ?? 0;
                                        
                                        return "Nettobetrag: € " . number_format(abs($netBeforeVat), 2, ',', '.') . "\n" .
                                               "zzgl. 19% MwSt.: € " . number_format(abs($vatAmount), 2, ',', '.') . "\n" .
                                               "**Bruttobetrag: € " . number_format(abs($grossAmount), 2, ',', '.') . "**";
                                    })
                                    ->prose()
                                    ->markdown()
                                    ->color('primary'),
                            ])
                            ->compact()
                            ->collapsible()
                            ->collapsed(true),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(false),

                Infolists\Components\Section::make('Finanzübersicht')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Infolists\Components\Grid::make(3)
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
                            ]),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('net_amount')
                                    ->label('Rechnungsbetrag')
                                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 2, ',', '.'))
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color(fn ($state) => $state >= 0 ? 'warning' : 'success'),
                                Infolists\Components\TextEntry::make('total_paid')
                                    ->label('Gesamt gezahlt')
                                    ->state(function ($record) {
                                        $totalPaid = $record->payments()->sum('amount');
                                        return '€ ' . number_format($totalPaid, 2, ',', '.');
                                    })
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('primary'),
                                Infolists\Components\TextEntry::make('remaining_amount')
                                    ->label('Restbetrag')
                                    ->state(function ($record) {
                                        $totalPaid = $record->payments()->sum('amount');
                                        
                                        if ($record->net_amount < 0) {
                                            // Bei Gutschriften: negativer Betrag + gezahlter Betrag = verbleibendes Guthaben
                                            $remainingAmount = $record->net_amount + $totalPaid;
                                            $label = $remainingAmount < 0 ? 'Noch auszuzahlen' : 'Überzahlt';
                                        } else {
                                            // Bei normalen Rechnungen: Rechnungsbetrag - gezahlter Betrag = offener Betrag
                                            $remainingAmount = $record->net_amount - $totalPaid;
                                            $label = $remainingAmount > 0 ? 'Noch offen' : 'Überzahlt';
                                        }
                                        
                                        return '€ ' . number_format(abs($remainingAmount), 2, ',', '.') . ' (' . $label . ')';
                                    })
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color(function ($record) {
                                        $totalPaid = $record->payments()->sum('amount');
                                        $remainingAmount = $record->net_amount < 0 
                                            ? $record->net_amount + $totalPaid 
                                            : $record->net_amount - $totalPaid;
                                        
                                        if ($remainingAmount == 0) {
                                            return 'success'; // Vollständig bezahlt
                                        } elseif (($record->net_amount >= 0 && $remainingAmount > 0) || ($record->net_amount < 0 && $remainingAmount < 0)) {
                                            return 'warning'; // Noch offen/auszuzahlen
                                        } else {
                                            return 'info'; // Überzahlt
                                        }
                                    }),
                            ]),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(false),

                Infolists\Components\Section::make('Erfasste Zahlungen')
                    ->icon('heroicon-o-banknotes')
                    ->description('Übersicht aller erfassten Zahlungen zu dieser Abrechnung.')
                    ->schema([
                        Infolists\Components\TextEntry::make('payments_table')
                            ->label('Zahlungsdetails')
                            ->state(function ($record) {
                                $payments = $record->payments()->orderBy('payment_date', 'desc')->get();
                                
                                if ($payments->isEmpty()) {
                                    return 'Keine Zahlungen erfasst.';
                                }
                                
                                $table = '<div class="overflow-x-auto">';
                                $table .= '<table class="min-w-full border border-gray-300">';
                                $table .= '<thead class="bg-gray-50">';
                                $table .= '<tr>';
                                $table .= '<th class="px-4 py-2 border-b border-gray-300 text-left font-medium text-gray-900">Datum</th>';
                                $table .= '<th class="px-4 py-2 border-b border-gray-300 text-left font-medium text-gray-900">Betrag</th>';
                                $table .= '<th class="px-4 py-2 border-b border-gray-300 text-left font-medium text-gray-900">Zahlungsart</th>';
                                $table .= '<th class="px-4 py-2 border-b border-gray-300 text-left font-medium text-gray-900">Referenz</th>';
                                $table .= '<th class="px-4 py-2 border-b border-gray-300 text-left font-medium text-gray-900">Erfasst von</th>';
                                $table .= '</tr>';
                                $table .= '</thead>';
                                $table .= '<tbody>';
                                
                                foreach ($payments as $payment) {
                                    $table .= '<tr class="hover:bg-gray-50">';
                                    $table .= '<td class="px-4 py-2 border-b border-gray-200">' . $payment->payment_date->format('d.m.Y') . '</td>';
                                    $table .= '<td class="px-4 py-2 border-b border-gray-200 font-medium">' . number_format($payment->amount, 2, ',', '.') . '</td>';
                                    $table .= '<td class="px-4 py-2 border-b border-gray-200">' . $payment->formatted_payment_type . '</td>';
                                    
                                    // Referenz-Spalte mit Notizen kombiniert
                                    $referenceContent = '';
                                    if ($payment->reference) {
                                        $referenceContent .= $payment->reference;
                                    } else {
                                        $referenceContent .= '<em class="text-gray-500">Keine Referenz</em>';
                                    }
                                    
                                    if ($payment->notes) {
                                        $referenceContent .= '<br><small class="text-gray-600"><strong>Notiz:</strong> ' . $payment->notes . '</small>';
                                    }
                                    
                                    $table .= '<td class="px-4 py-2 border-b border-gray-200">' . $referenceContent . '</td>';
                                    $table .= '<td class="px-4 py-2 border-b border-gray-200">' . ($payment->recordedByUser->name ?? '<em class="text-gray-500">Unbekannt</em>') . '</td>';
                                    $table .= '</tr>';
                                }
                                
                                $table .= '</tbody>';
                                $table .= '</table>';
                                $table .= '</div>';
                                
                                return $table;
                            })
                            ->html()
                            ->visible(function ($record) {
                                return $record->payments()->exists();
                            }),
                            
                        Infolists\Components\Actions::make([
                            Infolists\Components\Actions\Action::make('recordPayment')
                                ->label('Zahlung erfassen')
                                ->icon('heroicon-o-plus')
                                ->color('success')
                                ->modalHeading('Zahlung erfassen')
                                ->modalDescription('Erfassen Sie eine neue Zahlung zu dieser Abrechnung.')
                                ->modalWidth('4xl')
                                ->form([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Select::make('payment_type')
                                                ->label('Zahlungsart')
                                                ->options([
                                                    'bank_transfer' => 'Überweisung',
                                                    'instant_transfer' => 'Sofortüberweisung',
                                                    'direct_debit' => 'Lastschrift/Abbuchung',
                                                    'cash' => 'Barzahlung',
                                                    'check' => 'Scheck',
                                                    'credit_card' => 'Kreditkarte',
                                                    'paypal' => 'PayPal',
                                                    'other' => 'Sonstiges',
                                                ])
                                                ->required()
                                                ->native(false)
                                                ->default('bank_transfer')
                                                ->live(),
                                            Forms\Components\TextInput::make('amount')
                                                ->label('Zahlungsbetrag')
                                                ->numeric()
                                                ->step(0.01)
                                                ->prefix('€')
                                                ->required()
                                                ->default(fn ($livewire) => abs($livewire->record->net_amount))
                                                ->minValue(0.01)
                                                ->live(debounce: 500), // Live-Updates mit Debounce
                                        ]),
                                    Forms\Components\DatePicker::make('payment_date')
                                        ->label('Zahlungsdatum')
                                        ->required()
                                        ->default(now())
                                        ->maxDate(now()),
                                    Forms\Components\Textarea::make('reference')
                                        ->label('Referenz/Verwendungszweck')
                                        ->placeholder('z.B. Überweisungsreferenz')
                                        ->default(function ($livewire) {
                                            $qrService = new EpcQrCodeService();
                                            return $qrService->getDefaultReference($livewire->record);
                                        })
                                        ->rows(3)
                                        ->live(debounce: 500), // Live-Updates mit Debounce
                                    Forms\Components\Textarea::make('notes')
                                        ->label('Notizen')
                                        ->placeholder('Zusätzliche Informationen zur Zahlung...')
                                        ->rows(3),
                                        
                                    // QR-Code Section - nur bei Überweisung/Sofortüberweisung
                                    Forms\Components\Section::make('QR-Code für Banking-App')
                                        ->icon('heroicon-o-qr-code')
                                        ->description('Scannen Sie den QR-Code mit Ihrer Banking-App für eine schnelle Überweisung.')
                                        ->schema([
                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    Forms\Components\Placeholder::make('qr_code_image')
                                                        ->label('')
                                                        ->content(function ($livewire, Forms\Get $get) {
                                                            $qrService = new EpcQrCodeService();
                                                            $record = $livewire->record;
                                                            
                                                            if (!$qrService->canGenerateQrCode($record)) {
                                                                return new \Illuminate\Support\HtmlString('<div class="text-gray-500 text-center p-4 border-2 border-dashed border-gray-300 rounded-lg">' . $qrService->getQrCodeErrorMessage($record) . '</div>');
                                                            }
                                                            
                                                            try {
                                                                // Dynamische Werte aus dem Formular verwenden
                                                                $amount = $get('amount') ?: abs($record->net_amount);
                                                                $reference = $get('reference') ?: $qrService->getDefaultReference($record);
                                                                
                                                                $base64QrCode = $qrService->generateDynamicEpcQrCode($record, (float)$amount, $reference);
                                                                return new \Illuminate\Support\HtmlString('<div class="text-center"><img src="data:image/png;base64,' . $base64QrCode . '" alt="QR-Code" style="width: 200px; height: 200px; border: 2px solid #e5e7eb; border-radius: 8px; padding: 10px; margin: 0 auto;" /></div>');
                                                            } catch (\Exception $e) {
                                                                \Log::error('QR-Code generation failed: ' . $e->getMessage());
                                                                return new \Illuminate\Support\HtmlString('<div class="text-red-500 text-center p-4 border-2 border-dashed border-red-300 rounded-lg">QR-Code konnte nicht generiert werden: ' . $e->getMessage() . '</div>');
                                                            }
                                                        }),
                                                        
                                                    Forms\Components\Placeholder::make('qr_code_info')
                                                        ->label('')
                                                        ->content(function ($livewire, Forms\Get $get) {
                                                            $record = $livewire->record;
                                                            $customer = $record->customer;
                                                            $qrService = new EpcQrCodeService();
                                                            
                                                            // Dynamische Werte aus dem Formular verwenden
                                                            $amount = $get('amount') ?: abs($record->net_amount);
                                                            $reference = $get('reference') ?: $qrService->getDefaultReference($record);
                                                            
                                                            $info = [];
                                                            $info[] = '<strong>Banking-App QR-Code - Überweisung</strong>';
                                                            $info[] = '<strong>Empfänger:</strong> ' . ($customer->account_holder ?? 'Nicht hinterlegt');
                                                            if ($customer->iban) {
                                                                $info[] = '<strong>IBAN:</strong> ' . chunk_split($customer->iban, 4, ' ');
                                                            }
                                                            if ($customer->bic) {
                                                                $info[] = '<strong>BIC:</strong> ' . $customer->bic;
                                                            }
                                                            $info[] = '<strong>Betrag: €</strong> ' . number_format($amount, 2, ',', '.');
                                                            $info[] = '<strong>Verwendungszweck:</strong> ' . $reference;
                                                            $info[] = '';
                                                            if ($record->net_amount < 0) {
                                                                $info[] = '<em>Scannen Sie den QR-Code mit Ihrer Banking-App für eine schnelle Überweisung.</em>';
                                                            } else {
                                                                $info[] = '<em>Scannen Sie den QR-Code mit Ihrer Banking-App für eine schnelle Überweisung.</em>';
                                                            }
                                                            
                                                            return new \Illuminate\Support\HtmlString(implode('<br>', $info));
                                                        }),
                                                ]),
                                        ])
                                        ->visible(function (Forms\Get $get, $livewire) {
                                            $paymentType = $get('payment_type');
                                            $qrService = new EpcQrCodeService();
                                            return in_array($paymentType, ['bank_transfer', 'instant_transfer']) && 
                                                   $qrService->canGenerateQrCode($livewire->record);
                                        })
                                        ->compact(),
                                ])
                                ->action(function (array $data, $livewire) {
                                    try {
                                        SolarPlantBillingPayment::create([
                                            'solar_plant_billing_id' => $livewire->record->id,
                                            'recorded_by_user_id' => auth()->id(),
                                            'payment_type' => $data['payment_type'],
                                            'amount' => $data['amount'],
                                            'payment_date' => $data['payment_date'],
                                            'reference' => $data['reference'],
                                            'notes' => $data['notes'],
                                        ]);

                                        // Prüfe ob die Abrechnung vollständig bezahlt ist
                                        $totalPaid = $livewire->record->payments()->sum('amount');
                                        if ($totalPaid >= $livewire->record->net_amount && $livewire->record->net_amount > 0) {
                                            $livewire->record->update([
                                                'status' => 'paid',
                                                'paid_at' => now(),
                                            ]);
                                        }

                                        Notification::make()
                                            ->title('Zahlung erfasst')
                                            ->body('Die Zahlung wurde erfolgreich erfasst.')
                                            ->success()
                                            ->send();

                                        // Seite neu laden um die aktualisierten Daten anzuzeigen
                                        return redirect()->to($livewire->getUrl());
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Fehler beim Erfassen der Zahlung')
                                            ->body('Die Zahlung konnte nicht erfasst werden: ' . $e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ])
                        ->alignment('center'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(false)
                    ->visible(fn ($record) => $record->net_amount != 0), // Nur anzeigen wenn Betrag != 0

                Infolists\Components\Section::make('Hinweise')
                    ->icon('heroicon-o-information-circle')
                    ->description('Wichtige Informationen zu Ihrer Abrechnung')
                    ->schema([
                        Infolists\Components\TextEntry::make('billing_hints')
                            ->label('')
                            ->state(function ($record) {
                                $solarPlant = $record->solarPlant;
                                $customer = $record->customer;
                                $participation = $solarPlant->participations()
                                    ->where('customer_id', $customer->id)
                                    ->first();
                                $currentPercentage = $participation ? $participation->percentage : $record->participation_percentage;

                                $hints = [];
                                $hints[] = "• Diese Abrechnung zeigt Ihren Anteil an den Einnahmen und Kosten der Solaranlage {$solarPlant->name}.";
                                $hints[] = "• Ihr aktueller Beteiligungsanteil beträgt " . number_format($currentPercentage, 2, ',', '.') . "%.";
                                $hints[] = "• Die Abrechnung der Marktprämie erfolgt Umsatzsteuerfrei.";
                                
                                if ($record->total_credits > 0) {
                                    $hints[] = "• Die Einnahmen/Gutschriften stammen aus Vertragsabrechnungen unserer Lieferanten für diese Solaranlage.";
                                }
                                
                                $hints[] = "• Bei Fragen zu dieser Abrechnung wenden Sie sich bitte an uns.";

                                return implode("\n", $hints);
                            })
                            ->prose()
                            ->markdown()
                            ->color('info'),
                    ])
                    ->compact()
                    ->collapsible()
                    ->collapsed(false)
                    ->visible(fn ($record) => $record->show_hints ?? true),

                Infolists\Components\Section::make('Zahlungsinformationen')
                    ->icon('heroicon-o-credit-card')
                    ->description('Informationen zum Zahlungsstatus und wichtigen Terminen dieser Abrechnung.')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\Group::make([
                                    Infolists\Components\TextEntry::make('finalized_at')
                                        ->label('Finalisiert am')
                                        ->dateTime('d.m.Y H:i')
                                        ->placeholder('Noch nicht finalisiert')
                                        ->icon('heroicon-o-check-circle')
                                        ->color('info'),
                                    Infolists\Components\Actions::make([
                                        Infolists\Components\Actions\Action::make('finalize_today')
                                            ->label('Heute')
                                            ->icon('heroicon-o-calendar')
                                            ->color('info')
                                            ->size('sm')
                                            ->visible(fn ($record) => !$record->finalized_at)
                                            ->requiresConfirmation()
                                            ->modalHeading('Abrechnung als finalisiert markieren')
                                            ->modalDescription('Möchten Sie die Abrechnung mit dem heutigen Datum als finalisiert markieren?')
                                            ->action(function ($livewire) {
                                                $livewire->record->update([
                                                    'finalized_at' => now(),
                                                    'status' => 'finalized'
                                                ]);
                                                
                                                Notification::make()
                                                    ->title('Abrechnung finalisiert')
                                                    ->body('Die Abrechnung wurde als finalisiert markiert.')
                                                    ->success()
                                                    ->send();
                                            })
                                    ])
                                ]),
                                Infolists\Components\Group::make([
                                    Infolists\Components\TextEntry::make('sent_at')
                                        ->label('Versendet am')
                                        ->dateTime('d.m.Y H:i')
                                        ->placeholder('Noch nicht versendet')
                                        ->icon('heroicon-o-paper-airplane')
                                        ->color('warning'),
                                    Infolists\Components\Actions::make([
                                        Infolists\Components\Actions\Action::make('sent_today')
                                            ->label('Heute')
                                            ->icon('heroicon-o-calendar')
                                            ->color('warning')
                                            ->size('sm')
                                            ->visible(fn ($record) => !$record->sent_at)
                                            ->requiresConfirmation()
                                            ->modalHeading('Abrechnung als versendet markieren')
                                            ->modalDescription('Möchten Sie die Abrechnung mit dem heutigen Datum als versendet markieren?')
                                            ->action(function ($livewire) {
                                                $livewire->record->update([
                                                    'sent_at' => now(),
                                                    'status' => 'sent'
                                                ]);
                                                
                                                Notification::make()
                                                    ->title('Abrechnung versendet')
                                                    ->body('Die Abrechnung wurde als versendet markiert.')
                                                    ->success()
                                                    ->send();
                                            })
                                    ])
                                ]),
                                Infolists\Components\Group::make([
                                    Infolists\Components\TextEntry::make('paid_at')
                                        ->label('Bezahlt am')
                                        ->dateTime('d.m.Y H:i')
                                        ->placeholder('Noch nicht bezahlt')
                                        ->icon('heroicon-o-currency-euro')
                                        ->color('success'),
                                    Infolists\Components\Actions::make([
                                        Infolists\Components\Actions\Action::make('paid_today')
                                            ->label('Heute')
                                            ->icon('heroicon-o-calendar')
                                            ->color('success')
                                            ->size('sm')
                                            ->visible(fn ($record) => !$record->paid_at)
                                            ->requiresConfirmation()
                                            ->modalHeading('Abrechnung als bezahlt markieren')
                                            ->modalDescription('Möchten Sie die Abrechnung mit dem heutigen Datum als bezahlt markieren?')
                                            ->action(function ($livewire) {
                                                $livewire->record->update([
                                                    'paid_at' => now(),
                                                    'status' => 'paid'
                                                ]);
                                                
                                                Notification::make()
                                                    ->title('Abrechnung bezahlt')
                                                    ->body('Die Abrechnung wurde als bezahlt markiert.')
                                                    ->success()
                                                    ->send();
                                            })
                                    ])
                                ]),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\Group::make([
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
                                    Infolists\Components\Actions::make([
                                        Infolists\Components\Actions\Action::make('change_status')
                                            ->label('Status ändern')
                                            ->icon('heroicon-o-pencil')
                                            ->color('gray')
                                            ->size('sm')
                                            ->visible(fn ($livewire) => $livewire->record->status !== 'cancelled')
                                            ->modalHeading('Zahlungsstatus ändern')
                                            ->modalDescription('Wählen Sie den neuen Status für diese Abrechnung.')
                                            ->form([
                                                Forms\Components\Select::make('status')
                                                    ->label('Status')
                                                    ->options(function ($livewire) {
                                                        $currentStatus = $livewire->record->status;
                                                        $allOptions = \App\Models\SolarPlantBilling::getStatusOptions();

                                                        // Status-Hierarchie: draft -> finalized -> sent -> paid -> cancelled
                                                        $statusHierarchy = ['draft', 'finalized', 'sent', 'paid', 'cancelled'];
                                                        $currentIndex = array_search($currentStatus, $statusHierarchy);

                                                        $availableOptions = [];

                                                        // Aktueller Status ist immer verfügbar
                                                        $availableOptions[$currentStatus] = $allOptions[$currentStatus];

                                                        // Alle höheren Status sind verfügbar
                                                        for ($i = $currentIndex + 1; $i < count($statusHierarchy); $i++) {
                                                            $status = $statusHierarchy[$i];
                                                            if (isset($allOptions[$status])) {
                                                                $availableOptions[$status] = $allOptions[$status];
                                                            }
                                                        }

                                                        return $availableOptions;
                                                    })
                                                    ->default(fn ($livewire) => $livewire->record->status)
                                                    ->required()
                                                    ->native(false)
                                                    ->live(),
                                                Forms\Components\Textarea::make('cancellation_reason')
                                                    ->label('Stornierungsgrund')
                                                    ->rows(3)
                                                    ->visible(fn (Forms\Get $get) => $get('status') === 'cancelled')
                                                    ->required(fn (Forms\Get $get) => $get('status') === 'cancelled'),
                                                Forms\Components\Toggle::make('update_dates')
                                                    ->label('Entsprechende Datumsfelder automatisch setzen')
                                                    ->helperText('Setzt automatisch die passenden Datumsfelder (finalized_at, sent_at, paid_at) auf das heutige Datum')
                                                    ->default(true),
                                            ])
                                            ->action(function (array $data, $livewire) {
                                                $updateData = ['status' => $data['status']];

                                                // Stornierungsgrund hinzufügen wenn Status = cancelled
                                                if ($data['status'] === 'cancelled') {
                                                    $updateData['cancellation_reason'] = $data['cancellation_reason'] ?? null;
                                                }

                                                if ($data['update_dates']) {
                                                    $now = now();
                                                    switch ($data['status']) {
                                                        case 'finalized':
                                                            if (!$livewire->record->finalized_at) {
                                                                $updateData['finalized_at'] = $now;
                                                            }
                                                            break;
                                                        case 'sent':
                                                            if (!$livewire->record->finalized_at) {
                                                                $updateData['finalized_at'] = $now;
                                                            }
                                                            if (!$livewire->record->sent_at) {
                                                                $updateData['sent_at'] = $now;
                                                            }
                                                            break;
                                                        case 'paid':
                                                            if (!$livewire->record->finalized_at) {
                                                                $updateData['finalized_at'] = $now;
                                                            }
                                                            if (!$livewire->record->sent_at) {
                                                                $updateData['sent_at'] = $now;
                                                            }
                                                            if (!$livewire->record->paid_at) {
                                                                $updateData['paid_at'] = $now;
                                                            }
                                                            break;
                                                        case 'cancelled':
                                                            if (!$livewire->record->cancellation_date) {
                                                                $updateData['cancellation_date'] = $now;
                                                            }
                                                            break;
                                                    }
                                                }

                                                $livewire->record->update($updateData);

                                                Notification::make()
                                                    ->title('Status aktualisiert')
                                                    ->body('Der Zahlungsstatus wurde erfolgreich geändert.')
                                                    ->success()
                                                    ->send();
                                            })
                                    ])
                                ]),
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
                                        
                                        $timeline[] = '<b>Erstellt:</b> ' . $record->created_at->format('d.m.Y H:i');
                                        
                                        if ($record->finalized_at) {
                                            $timeline[] = '<b>Finalisiert:</b> ' . $record->finalized_at->format('d.m.Y H:i');
                                        }
                                        
                                        if ($record->sent_at) {
                                            $timeline[] = '<b>Versendet:</b> ' . $record->sent_at->format('d.m.Y H:i');
                                        }
                                        
                                        if ($record->paid_at) {
                                            $timeline[] = '<b>Bezahlt:</b> ' . $record->paid_at->format('d.m.Y H:i');
                                        }
                                        
                                        return implode(' → ', $timeline);
                                    })
                                    ->prose()
                                    ->color('gray'),
                            ]),
                        
                        // QR-Code für Banking-Apps - Debug Version
                        /*
                        Infolists\Components\Grid::make(1)
                            ->schema([
                                Infolists\Components\TextEntry::make('qr_code_debug')
                                    ->label('QR-Code Debug Information')
                                    ->state(function ($record) {
                                        $qrService = new EpcQrCodeService();
                                        $customer = $record->customer;
                                        
                                        $debug = [];
                                        $debug[] = "**Debug Informationen:**";
                                        $debug[] = "Net Amount: " . $record->net_amount;
                                        $debug[] = "Customer ID: " . ($customer ? $customer->id : 'NULL');
                                        $debug[] = "Customer Number: " . ($customer?->customer_number ?: 'LEER');
                                        $debug[] = "Account Holder: " . ($customer?->account_holder ?: 'LEER');
                                        $debug[] = "IBAN: " . ($customer?->iban ?: 'LEER');
                                        $debug[] = "BIC: " . ($customer?->bic ?: 'LEER');
                                        $debug[] = "Can Generate QR: " . ($qrService->canGenerateQrCode($record) ? 'JA' : 'NEIN');
                                        
                                        if (!$qrService->canGenerateQrCode($record)) {
                                            $debug[] = "Error: " . $qrService->getQrCodeErrorMessage($record);
                                        } else {
                                            try {
                                                $base64QrCode = $qrService->generateEpcQrCode($record);
                                                $debug[] = "QR-Code generiert: " . strlen($base64QrCode) . " Zeichen";
                                                $debug[] = "Base64 Prefix: " . substr($base64QrCode, 0, 50) . "...";
                                            } catch (\Exception $e) {
                                                $debug[] = "QR-Code Fehler: " . $e->getMessage();
                                            }
                                        }
                                        
                                        return implode("\n", $debug);
                                    })
                                    ->prose()
                                    ->markdown()
                                    ->color('info'),
                            ]),
                        */
                        // QR-Code für Banking-Apps
                        Infolists\Components\Grid::make(3)
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
                                            \Log::error('QR-Code generation failed: ' . $e->getMessage());
                                            return null;
                                        }
                                    })
                                    ->size(200)
                                    ->extraAttributes(['style' => 'border: 2px solid #e5e7eb; border-radius: 8px; padding: 10px;'])
                                    ->visible(function ($record) {
                                        $qrService = new EpcQrCodeService();
                                        return $qrService->canGenerateQrCode($record);
                                    })
                                    ->columnSpan(1),
                                
                                Infolists\Components\TextEntry::make('qr_code_info')
                                    ->label('QR-Code Informationen für Überweisung')
                                    ->state(function ($record) {
                                        $qrService = new EpcQrCodeService();
                                        
                                        if (!$qrService->canGenerateQrCode($record)) {
                                            return $qrService->getQrCodeErrorMessage($record);
                                        }
                                        
                                        $customer = $record->customer;
                                        $solarPlant = $record->solarPlant;
                                        
                                        $info = [];
                                        $info[] = "**Empfänger:** {$customer->account_holder}";
                                        $info[] = "<br>**IBAN:** " . chunk_split($customer->iban, 4, ' ');
                                        if ($customer->bic) {
                                            $info[] = "&nbsp;&nbsp;&nbsp;**BIC:** {$customer->bic}";
                                        }
                                        $info[] = "&nbsp;&nbsp;&nbsp;**Betrag: €** " . number_format(abs($record->net_amount), 2, ',', '.');
                                        
                                        $reference = [];
                                        if ($record->invoice_number) {
                                            $reference[] = $record->invoice_number;
                                        }
                                        if ($customer && $customer->customer_number) {
                                            $reference[] = "{$customer->customer_number}";
                                        }
                                        if ($solarPlant && $solarPlant->name) {
                                            $reference[] = $solarPlant->name;
                                        }
                                        $month = \Carbon\Carbon::createFromDate($record->billing_year, $record->billing_month, 1);
                                        $reference[] = "Zeitraum: " . $month->locale('de')->translatedFormat('m-Y');
                                        
                                        $info[] = "<br><br>**Verwendungszweck:**<br>" . implode(' --- ', $reference);
                                        $info[] = "";
                                        if ($record->net_amount < 0) {
                                            $info[] = "**<br>Scannen Sie den QR-Code mit Ihrer Banking-App für eine schnelle Überweisung.**";
                                        } else {
                                            $info[] = "*Scannen Sie den QR-Code mit Ihrer Banking-App für eine schnelle Überweisung.*";
                                        }
                                        
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
                                    })
                                    ->columnSpan(2),
                            ])
                            ->visible(function ($record) {
                                // Zeige gesamtes Grid für alle Beträge außer 0 an (auch Gutschriften)
                                return $record->net_amount != 0;
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
            Actions\Action::make('changeStatus')
                ->label('Status ändern')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->status !== 'cancelled')
                ->modalHeading('Status ändern')
                ->modalDescription('Wählen Sie den neuen Status für diese Abrechnung.')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(function () {
                            $currentStatus = $this->record->status;
                            $allOptions = \App\Models\SolarPlantBilling::getStatusOptions();

                            // Status-Hierarchie: draft -> finalized -> sent -> paid -> cancelled
                            $statusHierarchy = ['draft', 'finalized', 'sent', 'paid', 'cancelled'];
                            $currentIndex = array_search($currentStatus, $statusHierarchy);

                            $availableOptions = [];

                            // Aktueller Status ist immer verfügbar
                            $availableOptions[$currentStatus] = $allOptions[$currentStatus];

                            // Alle höheren Status sind verfügbar
                            for ($i = $currentIndex + 1; $i < count($statusHierarchy); $i++) {
                                $status = $statusHierarchy[$i];
                                if (isset($allOptions[$status])) {
                                    $availableOptions[$status] = $allOptions[$status];
                                }
                            }

                            return $availableOptions;
                        })
                        ->default(fn () => $this->record->status)
                        ->required()
                        ->native(false)
                        ->live(),
                    Forms\Components\Textarea::make('cancellation_reason')
                        ->label('Stornierungsgrund')
                        ->rows(3)
                        ->visible(fn (Forms\Get $get) => $get('status') === 'cancelled')
                        ->required(fn (Forms\Get $get) => $get('status') === 'cancelled'),
                    Forms\Components\Toggle::make('update_dates')
                        ->label('Entsprechende Datumsfelder automatisch setzen')
                        ->helperText('Setzt automatisch die passenden Datumsfelder (finalized_at, sent_at, paid_at, cancellation_date) auf das heutige Datum')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    $updateData = ['status' => $data['status']];

                    // Stornierungsgrund hinzufügen wenn Status = cancelled
                    if ($data['status'] === 'cancelled') {
                        $updateData['cancellation_reason'] = $data['cancellation_reason'] ?? null;
                    }

                    if ($data['update_dates']) {
                        $now = now();
                        switch ($data['status']) {
                            case 'finalized':
                                if (!$this->record->finalized_at) {
                                    $updateData['finalized_at'] = $now;
                                }
                                break;
                            case 'sent':
                                if (!$this->record->finalized_at) {
                                    $updateData['finalized_at'] = $now;
                                }
                                if (!$this->record->sent_at) {
                                    $updateData['sent_at'] = $now;
                                }
                                break;
                            case 'paid':
                                if (!$this->record->finalized_at) {
                                    $updateData['finalized_at'] = $now;
                                }
                                if (!$this->record->sent_at) {
                                    $updateData['sent_at'] = $now;
                                }
                                if (!$this->record->paid_at) {
                                    $updateData['paid_at'] = $now;
                                }
                                break;
                            case 'cancelled':
                                if (!$this->record->cancellation_date) {
                                    $updateData['cancellation_date'] = $now;
                                }
                                break;
                        }
                    }

                    $this->record->update($updateData);

                    Notification::make()
                        ->title('Status aktualisiert')
                        ->body('Der Status wurde erfolgreich geändert.')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('printQrCode')
                ->label('QR-Code drucken')
                ->icon('heroicon-o-qr-code')
                ->color('info')
                ->visible(function () {
                    $qrService = new EpcQrCodeService();
                    return $qrService->canGenerateQrCode($this->record);
                })
                ->url(function () {
                    return route('admin.solar-plant-billing.qr-code-print', $this->record);
                })
                ->openUrlInNewTab(),
            Actions\Action::make('recordPayment')
                ->label('Zahlung erfassen')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->visible(fn () => $this->record->net_amount != 0) // Nur anzeigen wenn Betrag != 0
                ->form([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('payment_type')
                                ->label('Zahlungsart')
                                ->options([
                                    'bank_transfer' => 'Überweisung',
                                    'instant_transfer' => 'Sofortüberweisung',
                                    'direct_debit' => 'Lastschrift/Abbuchung',
                                    'cash' => 'Barzahlung',
                                    'check' => 'Scheck',
                                    'credit_card' => 'Kreditkarte',
                                    'paypal' => 'PayPal',
                                    'other' => 'Sonstiges',
                                ])
                                ->required()
                                ->native(false)
                                ->default('bank_transfer'),
                            Forms\Components\TextInput::make('amount')
                                ->label('Zahlungsbetrag')
                                ->numeric()
                                ->step(0.01)
                                ->prefix('€')
                                ->required()
                                ->default(fn () => abs($this->record->net_amount))
                                ->minValue(0.01),
                        ]),
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\DatePicker::make('payment_date')
                                ->label('Zahlungsdatum')
                                ->required()
                                ->default(now())
                                ->maxDate(now()),
                            Forms\Components\TextInput::make('reference')
                                ->label('Referenz/Verwendungszweck')
                                ->placeholder('z.B. Überweisungsreferenz')
                                ->maxLength(255),
                        ]),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notizen')
                        ->placeholder('Zusätzliche Informationen zur Zahlung...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        SolarPlantBillingPayment::create([
                            'solar_plant_billing_id' => $this->record->id,
                            'recorded_by_user_id' => auth()->id(),
                            'payment_type' => $data['payment_type'],
                            'amount' => $data['amount'],
                            'payment_date' => $data['payment_date'],
                            'reference' => $data['reference'],
                            'notes' => $data['notes'],
                        ]);

                        // Prüfe ob die Abrechnung vollständig bezahlt ist
                        $totalPaid = $this->record->payments()->sum('amount');
                        if ($totalPaid >= $this->record->net_amount && $this->record->net_amount > 0) {
                            $this->record->update([
                                'status' => 'paid',
                                'paid_at' => now(),
                            ]);
                        }

                        Notification::make()
                            ->title('Zahlung erfasst')
                            ->body('Die Zahlung wurde erfolgreich erfasst.')
                            ->success()
                            ->send();

                        // Seite neu laden um die aktualisierten Daten anzuzeigen
                        return redirect()->to($this->getUrl());
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler beim Erfassen der Zahlung')
                            ->body('Die Zahlung konnte nicht erfasst werden: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
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
