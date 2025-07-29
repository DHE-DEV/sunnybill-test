<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolarPlantBillingResource\Pages;
use App\Models\SolarPlantBilling;
use App\Models\SolarPlant;
use App\Models\Customer;
use Filament\Forms;
use App\Models\Document;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Facades\Storage;

class SolarPlantBillingResource extends Resource
{
    protected static ?string $model = SolarPlantBilling::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Abrechnung Solaranlagen';

    protected static ?string $modelLabel = 'Solaranlagen-Abrechnung';

    protected static ?string $pluralModelLabel = 'Solaranlagen-Abrechnungen';

    protected static ?string $navigationGroup = 'Fakturierung';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Abrechnungsdaten')
                    ->schema([
                        Forms\Components\Select::make('solar_plant_id')
                            ->label('Solaranlage')
                            ->options(SolarPlant::orderBy('plant_number')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $plant = SolarPlant::find($state);
                                    if ($plant) {
                                        $set('plant_info', [
                                            'plant_number' => $plant->plant_number,
                                            'location' => $plant->location,
                                        ]);
                                    }
                                }
                            }),
                        
                        Forms\Components\Placeholder::make('plant_info')
                            ->label('Anlagen-Info')
                            ->content(function ($get) {
                                $plantId = $get('solar_plant_id');
                                if (!$plantId) return 'Keine Anlage ausgewählt';
                                
                                $plant = SolarPlant::find($plantId);
                                if (!$plant) return 'Anlage nicht gefunden';
                                
                                $plantUrl = \App\Filament\Resources\SolarPlantResource::getUrl('view', ['record' => $plant->id]);
                                $plantNumberLink = '<a href="' . $plantUrl . '" target="_blank" class="text-primary-600 hover:text-primary-500 underline font-medium">' . htmlspecialchars($plant->plant_number) . '</a>';
                                
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="space-y-1">' .
                                    '<div>Nummer: ' . $plantNumberLink . '</div>' .
                                    '<div>Standort: ' . htmlspecialchars($plant->location ?? 'Nicht angegeben') . '</div>' .
                                    '</div>'
                                );
                            })
                            ->visible(fn ($get) => $get('solar_plant_id')),

                        Forms\Components\Select::make('customer_id')
                            ->label('Kunde')
                            ->options(Customer::all()->mapWithKeys(function ($customer) {
                                $displayName = $customer->customer_type === 'business' && $customer->company_name
                                    ? $customer->company_name
                                    : $customer->name;
                                return [$customer->id => $displayName];
                            }))
                            ->searchable()
                            ->required(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('billing_year')
                                    ->label('Abrechnungsjahr')
                                    ->options(function () {
                                        $currentYear = now()->year;
                                        $years = [];
                                        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                                            $years[$i] = $i;
                                        }
                                        return $years;
                                    })
                                    ->default(now()->year)
                                    ->required(),

                                Forms\Components\Select::make('billing_month')
                                    ->label('Abrechnungsmonat')
                                    ->options([
                                        1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                                        5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                                    ])
                                    ->default(now()->month)
                                    ->required(),
                            ]),

                        Forms\Components\TextInput::make('participation_percentage')
                            ->label('Beteiligungsprozentsatz')
                            ->suffix('%')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),

                        Forms\Components\TextInput::make('produced_energy_kwh')
                            ->label('Produzierte Energie (kWh)')
                            ->suffix('kWh')
                            ->numeric()
                            ->step(0.001)
                            ->minValue(0)
                            ->placeholder('z.B. 2500.000'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(SolarPlantBilling::getStatusOptions())
                            ->default('draft')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Zusätzliche Informationen')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3),
                    ])
                    ->collapsed(),

                Forms\Components\Section::make('Kostenaufschlüsselung')
                    ->schema([
                        Forms\Components\Placeholder::make('energy_distribution')
                            ->label('')
                            ->content(function ($get, $record) {
                                if (!$record || !$record->produced_energy_kwh || !$record->participation_percentage) {
                                    return '';
                                }
                                
                                // Berechne anteilige kWh für den Kunden
                                $customerEnergyKwh = ($record->produced_energy_kwh * $record->participation_percentage) / 100;
                                
                                // Hole EEG-Vergütung aus der Beteiligung
                                $eegCompensation = null;
                                $participation = $record->solarPlant->participations()
                                    ->where('customer_id', $record->customer_id)
                                    ->first();
                                if ($participation) {
                                    $eegCompensation = $participation->eeg_compensation_per_kwh;
                                }
                                
                                // HTML für Energieverteilungs-Block
                                $html = '<div style="margin-bottom: 1rem; padding: 0.75rem; background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 0.375rem;">';
                                $html .= '<div style="font-weight: 600; color: #1e40af; margin-bottom: 0.25rem;">Energieverteilung</div>';
                                $html .= '<div style="font-size: 0.875rem; color: #1e40af;">';
                                $html .= 'Gesamte produzierte Energie: <strong>' . number_format($record->produced_energy_kwh, 3, ',', '.') . ' kWh</strong><br>';
                                $html .= 'Ihr Beteiligungsanteil: <strong>' . number_format($record->participation_percentage, 4, ',', '.') . '%</strong><br>';
                                $html .= 'Ihre anteilige Energie: <strong>' . number_format($customerEnergyKwh, 3, ',', '.') . ' kWh</strong><br>';
                                if ($eegCompensation && $eegCompensation > 0) {
                                    $html .= 'Vertraglich zugesicherte EEG-Vergütung: <strong>' . number_format($eegCompensation, 6, ',', '.') . ' €/kWh</strong>';
                                } else {
                                    $html .= 'Vertraglich zugesicherte EEG-Vergütung: <strong>Nicht hinterlegt</strong>';
                                }
                                $html .= '</div>';
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->visible(fn ($record) => $record && $record->produced_energy_kwh && $record->participation_percentage),

                        Forms\Components\Placeholder::make('cost_breakdown_table')
                            ->label('Kostenpositionen')
                            ->content(function ($get, $record) {
                                if (!$record || !$record->cost_breakdown || empty($record->cost_breakdown)) {
                                    return 'Keine Kostenpositionen verfügbar';
                                }
                                
                                $breakdown = $record->cost_breakdown;
                                
                                // HTML-Tabelle für Kostenpositionen
                                $html = '<div style="overflow-x: auto;">';
                                
                                $html .= '<table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem;">';
                                $html .= '<thead>';
                                $html .= '<tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">';
                                $html .= '<th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151;">Bezeichnung</th>';
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #374151; vertical-align: top;">Anteil</th>';
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #374151; vertical-align: top;">Netto (€)</th>';
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #374151; vertical-align: top;">MwSt. %</th>';
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #374151; vertical-align: top;">Brutto (€)</th>';
                                $html .= '</tr>';
                                $html .= '</thead>';
                                $html .= '<tbody>';
                                
                                foreach ($breakdown as $item) {
                                    $contractId = $item['contract_id'] ?? null;
                                    $docs = $contractId ? $record->documents()->where('description', 'like', '%' . $contractId . '%')->get() : collect();

                                    $supplierName = htmlspecialchars($item['supplier_name'] ?? 'Unbekannt');
                                    if (isset($item['supplier_id'])) {
                                        $supplierUrl = route('filament.admin.resources.suppliers.view', $item['supplier_id']);
                                        $supplierName = '<a href="' . $supplierUrl . '" target="_blank" class="text-primary-600 hover:underline">' . $supplierName . '</a>';
                                    }

                                    $billingNumberText = htmlspecialchars($item['billing_number'] ?? 'N/A');
                                    if (isset($item['contract_billing_id'])) {
                                        $billingUrl = \App\Filament\Resources\SupplierContractBillingResource::getUrl('view', ['record' => $item['contract_billing_id']]);
                                        $billingNumber = '<a href="' . $billingUrl . '" target="_blank" class="text-primary-600 hover:underline">' . $billingNumberText . '</a>';
                                    } else {
                                        $billingNumber = $billingNumberText;
                                    }

                                    $html .= '<tr style="border-bottom: 1px solid #e2e8f0;">';
                                    $html .= '<td style="padding: 0.75rem; color: #374151; vertical-align: top;">';
                                    $html .= '<div style="font-weight: 500;">' . htmlspecialchars($item['contract_title']) . '</div>';
                                    $html .= '<div style="font-size: 0.875rem; color: #6b7280;">';
                                    $html .= 'Lieferant: ' . $supplierName . ' | Abrechnungsnr.: ' . $billingNumber;
                                    $html .= '</div>';
                                    
                                    // Artikel-Details anzeigen
                                    if (isset($item['articles']) && !empty($item['articles'])) {
                                        $html .= '<div style="margin-top: 0.5rem; padding: 0.5rem; background-color: #f9fafb; border-radius: 0.25rem; border: 1px solid #e5e7eb;">';
                                        $html .= '<div style="font-weight: 500; font-size: 0.875rem; color: #374151; margin-bottom: 0.25rem;">Details:</div>';
                                        
                                        foreach ($item['articles'] as $article) {
                                            $html .= '<div style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.25rem;">';
                                            $html .= '<div style="font-weight: 500;">' . htmlspecialchars($article['article_name']) . '</div>';
                                            
                                            // Berechne anteilige Beträge
                                            $customerPercentage = $item['customer_percentage'] ?? 0;
                                            $anteiligeMenge = ($article['quantity'] * $customerPercentage) / 100;
                                            $anteiligenNetto = ($article['total_price_net'] * $customerPercentage) / 100;
                                            $anteiligeSteuer = ($article['tax_amount'] * $customerPercentage) / 100;
                                            $anteiligeBrutto = ($article['total_price_gross'] * $customerPercentage) / 100;
                                            
                                            // Tabellarische Darstellung
                                            $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 0.25rem; font-size: 0.75rem;">';
                                            $html .= '<thead>';
                                            $html .= '<tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">';
                                            $html .= '<th style="padding: 0.25rem; text-align: left; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Typ</th>';
                                            $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Menge</th>';
                                            $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Preis</th>';
                                            $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Netto</th>';
                                            $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Steuer</th>';
                                            $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151;">Brutto</th>';
                                            $html .= '</tr>';
                                            $html .= '</thead>';
                                            $html .= '<tbody>';
                                            
                                            // Anlage Gesamt Zeile
                                            $html .= '<tr style="border-bottom: 1px solid #e2e8f0;">';
                                            $html .= '<td style="padding: 0.25rem; color: #374151; border-right: 1px solid #e2e8f0;">Anlage Gesamt</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151; border-right: 1px solid #e2e8f0;">' . number_format($article['quantity'], 4, ',', '.') . ' ' . htmlspecialchars($article['unit']) . '</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151; border-right: 1px solid #e2e8f0;">' . number_format($article['unit_price'], 6, ',', '.') . ' €</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151; border-right: 1px solid #e2e8f0;">' . number_format($article['total_price_net'], 2, ',', '.') . ' €</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151; border-right: 1px solid #e2e8f0;">' . number_format($article['tax_rate'] * 100, 1, ',', '.') . '% = ' . number_format($article['tax_amount'], 2, ',', '.') . ' €</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151;">' . number_format($article['total_price_gross'], 2, ',', '.') . ' €</td>';
                                            $html .= '</tr>';
                                            
                                            // Anteilig Zeile (blau)
                                            $html .= '<tr>';
                                            $html .= '<td style="padding: 0.25rem; color: #2563eb; border-right: 1px solid #e2e8f0;">Anteilig ' . number_format($customerPercentage, 2, ',', '.') . '%</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb; border-right: 1px solid #e2e8f0;">' . number_format($anteiligeMenge, 3, ',', '.') . ' ' . htmlspecialchars($article['unit']) . '</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb; border-right: 1px solid #e2e8f0;">' . number_format($article['unit_price'], 6, ',', '.') . ' €</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb; border-right: 1px solid #e2e8f0;">' . number_format($anteiligenNetto, 2, ',', '.') . ' €</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb; border-right: 1px solid #e2e8f0;">' . number_format($article['tax_rate'] * 100, 1, ',', '.') . '% = ' . number_format($anteiligeSteuer, 2, ',', '.') . ' €</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb;">' . number_format($anteiligeBrutto, 2, ',', '.') . ' €</td>';
                                            $html .= '</tr>';
                                            
                                            $html .= '</tbody>';
                                            $html .= '</table>';
                                            
                                            $html .= '</div>';
                                        }
                                        $html .= '</div>';
                                    }
                                    
                                    $html .= '</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; color: #374151; vertical-align: top;">' . number_format($item['customer_percentage'], 2, ',', '.') . '%</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; color: #374151; vertical-align: top;">' . number_format($item['customer_share_net'] ?? $item['customer_share'], 2, ',', '.') . '</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; color: #374151; vertical-align: top;">' . number_format((($item['vat_rate'] ?? 0.19) <= 1 ? ($item['vat_rate'] ?? 0.19) * 100 : ($item['vat_rate'] ?? 19)), 0, ',', '.') . '%</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; font-weight: 500; color: #374151; vertical-align: top;">' . number_format($item['customer_share'], 2, ',', '.') . '</td>';
                                    $html .= '</tr>';
                                }
                                
                                $html .= '</tbody>';
                                $html .= '</table>';
                                
                                // Gesamtbetrag für Kostenpositionen
                                $totalCosts = array_sum(array_column($breakdown, 'customer_share'));
                                $totalCostsNet = array_sum(array_column($breakdown, 'customer_share_net'));
                                $html .= '<div style="margin-top: 0.5rem; padding: 0.75rem; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.375rem;">';
                                $html .= '<table style="width: 100%; border-collapse: collapse;">';
                                $html .= '<tr>';
                                $html .= '<td style="font-weight: 600; color: #374151;">Gesamtkosten:</td>';
                                $html .= '<td style="text-align: right; color: #374151;">Netto: <strong>' . number_format($totalCostsNet, 2, ',', '.') . ' €</strong></td>';
                                $html .= '<td style="text-align: right; color: #374151;">Brutto: <strong>' . number_format($totalCosts, 2, ',', '.') . ' €</strong></td>';
                                $html .= '</tr>';
                                $html .= '</table>';
                                $html .= '</div>';
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->visible(fn ($record) => $record && $record->cost_breakdown && !empty($record->cost_breakdown)),

                        Forms\Components\Placeholder::make('credit_breakdown_table')
                            ->label('Gutschriftenpositionen')
                            ->content(function ($get, $record) {
                                if (!$record || !$record->credit_breakdown || empty($record->credit_breakdown)) {
                                    return 'Keine Gutschriftenpositionen verfügbar';
                                }
                                
                                $breakdown = $record->credit_breakdown;
                                
                                // HTML-Tabelle für Gutschriftenpositionen
                                $html = '<div style="overflow-x: auto;">';
                                $html .= '<table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem;">';
                                $html .= '<thead>';
                                $html .= '<tr style="background-color: #f0fdf4; border-bottom: 2px solid #bbf7d0;">';
                                $html .= '<th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #166534;">Bezeichnung</th>';
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #166534; vertical-align: top;">Anteil</th>';
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #166534; vertical-align: top;">Netto (€)</th>';
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #166534; vertical-align: top;">MwSt. %</th>';
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #166534; vertical-align: top;">Brutto (€)</th>';
                                $html .= '</tr>';
                                $html .= '</thead>';
                                $html .= '<tbody>';
                                
                                foreach ($breakdown as $item) {
                                    $contractId = $item['contract_id'] ?? null;
                                    $docs = $contractId ? $record->documents()->where('description', 'like', '%' . $contractId . '%')->get() : collect();

                                    $supplierName = htmlspecialchars($item['supplier_name'] ?? 'Unbekannt');
                                    if (isset($item['supplier_id'])) {
                                        $supplierUrl = route('filament.admin.resources.suppliers.view', $item['supplier_id']);
                                        $supplierName = '<a href="' . $supplierUrl . '" target="_blank" class="text-primary-600 hover:underline">' . $supplierName . '</a>';
                                    }

                                    $billingNumberText = htmlspecialchars($item['billing_number'] ?? 'N/A');
                                    if (isset($item['contract_billing_id'])) {
                                        $billingUrl = \App\Filament\Resources\SupplierContractBillingResource::getUrl('view', ['record' => $item['contract_billing_id']]);
                                        $billingNumber = '<a href="' . $billingUrl . '" target="_blank" class="text-primary-600 hover:underline">' . $billingNumberText . '</a>';
                                    } else {
                                        $billingNumber = $billingNumberText;
                                    }

                                    $html .= '<tr style="border-bottom: 1px solid #bbf7d0;">';
                                    $html .= '<td style="padding: 0.75rem; color: #166534;">';
                                    $html .= '<div style="font-weight: 500;">' . htmlspecialchars($item['contract_title']) . '</div>';
                                    $html .= '<div style="font-size: 0.875rem; color: #6b7280;">';
                                    $html .= 'Lieferant: ' . $supplierName . ' | Abrechnungsnr.: ' . $billingNumber;
                                    $html .= '</div>';
                                    
                                    // Artikel-Details immer anzeigen (nicht nur wenn articles vorhanden sind)
                                    $html .= '<div style="margin-top: 0.5rem; padding: 0.5rem; background-color: #f9fafb; border-radius: 0.25rem; border: 1px solid #e5e7eb;">';
                                    $html .= '<div style="font-weight: 500; font-size: 0.875rem; color: #374151; margin-bottom: 0.25rem;">Details:</div>';
                                    
                                    if (isset($item['articles']) && !empty($item['articles'])) {
                                        foreach ($item['articles'] as $article) {
                                            $html .= '<div style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.25rem;">';
                                            $html .= '<div style="font-weight: 500;">' . htmlspecialchars($article['article_name']) . '</div>';
                                            
                                            // Berechne anteilige Beträge
                                            $customerPercentage = $item['customer_percentage'] ?? 0;
                                            $anteiligeMenge = ($article['quantity'] * $customerPercentage) / 100;
                                            $anteiligenNetto = ($article['total_price_net'] * $customerPercentage) / 100;
                                            $anteiligeSteuer = ($article['tax_amount'] * $customerPercentage) / 100;
                                            $anteiligeBrutto = ($article['total_price_gross'] * $customerPercentage) / 100;
                                            
                                            // Tabellarische Darstellung
                                            $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 0.25rem; font-size: 0.75rem;">';
                                            $html .= '<thead>';
                                            $html .= '<tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">';
                                            $html .= '<th style="padding: 0.25rem; text-align: left; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Typ</th>';
                                            $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Menge</th>';
                                            $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Preis</th>';
                                            $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Netto</th>';
                                            $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Steuer</th>';
                                            $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151;">Brutto</th>';
                                            $html .= '</tr>';
                                            $html .= '</thead>';
                                            $html .= '<tbody>';
                                            
                                            // Anlage Gesamt Zeile
                                            $html .= '<tr style="border-bottom: 1px solid #e2e8f0;">';
                                            $html .= '<td style="padding: 0.25rem; color: #374151; border-right: 1px solid #e2e8f0;">Anlage Gesamt</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151; border-right: 1px solid #e2e8f0;">' . number_format($article['quantity'], 4, ',', '.') . ' ' . htmlspecialchars($article['unit']) . '</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151; border-right: 1px solid #e2e8f0;">' . number_format($article['unit_price'], 6, ',', '.') . ' €</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151; border-right: 1px solid #e2e8f0;">' . number_format($article['total_price_net'], 2, ',', '.') . ' €</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151; border-right: 1px solid #e2e8f0;">' . number_format($article['tax_rate'] * 100, 1, ',', '.') . '% = ' . number_format($article['tax_amount'], 2, ',', '.') . ' €</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151;">' . number_format($article['total_price_gross'], 2, ',', '.') . ' €</td>';
                                            $html .= '</tr>';
                                            
                                            // Anteilig Zeile (blau)
                                            $html .= '<tr>';
                                            $html .= '<td style="padding: 0.25rem; color: #2563eb; border-right: 1px solid #e2e8f0;">Anteilig ' . number_format($customerPercentage, 2, ',', '.') . '%</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb; border-right: 1px solid #e2e8f0;">' . number_format($anteiligeMenge, 3, ',', '.') . ' ' . htmlspecialchars($article['unit']) . '</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb; border-right: 1px solid #e2e8f0;">' . number_format($article['unit_price'], 6, ',', '.') . ' €</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb; border-right: 1px solid #e2e8f0;">' . number_format($anteiligenNetto, 2, ',', '.') . ' €</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb; border-right: 1px solid #e2e8f0;">' . number_format($article['tax_rate'] * 100, 1, ',', '.') . '% = ' . number_format($anteiligeSteuer, 2, ',', '.') . ' €</td>';
                                            $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb;">' . number_format($anteiligeBrutto, 2, ',', '.') . ' €</td>';
                                            $html .= '</tr>';
                                            
                                            $html .= '</tbody>';
                                            $html .= '</table>';
                                            
                                            $html .= '</div>';
                                        }
                                    } else {
                                        // Zeige Tabelle basierend auf Hauptposition (für Marktprämie ohne Artikel-Details)
                                        $customerPercentage = $item['customer_percentage'] ?? 0;
                                        $gesamtbetrag = $item['customer_share'] ?? 0; // Gesamtbetrag aus Abrechnung
                                        
                                        // Verwende kWh-Werte wie bei Direktvermarktung
                                        $anlageMengeKwh = $record->produced_energy_kwh ?? 0; // Gesamte produzierte Energie
                                        $anteiligeMengeKwh = ($anlageMengeKwh * $customerPercentage) / 100; // Anteilige kWh
                                        
                                        // Einzelpreis berechnen: Gesamtbetrag ÷ anteilige kWh
                                        $einzelpreisGesamt = $anteiligeMengeKwh > 0 ? ($gesamtbetrag / $anteiligeMengeKwh) : 0;
                                        $einzelpreisAnteilig = $einzelpreisGesamt; // Derselbe Preis
                                        
                                        // Für Marktprämie: Steuer ist immer 0%
                                        $steuerrate = 0.0;
                                        $steuerbetragGesamt = 0;
                                        $steuerbetragAnteilig = 0;
                                        
                                        // Brutto = Netto (da keine Steuer)
                                        $bruttoGesamt = $anlageMengeKwh * $einzelpreisGesamt; // Hochrechnung auf 100%
                                        $bruttoAnteilig = $gesamtbetrag;
                                        
                                        $html .= '<div style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.25rem;">';
                                        $html .= '<div style="font-weight: 500;">Einspeisung Marktwert - ' . $record->billing_year . '/' . sprintf('%02d', $record->billing_month) . '</div>';
                                        
                                        // Tabellarische Darstellung für Marktprämie
                                        $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 0.25rem; font-size: 0.75rem;">';
                                        $html .= '<thead>';
                                        $html .= '<tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">';
                                        $html .= '<th style="padding: 0.25rem; text-align: left; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Typ</th>';
                                        $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Menge</th>';
                                        $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Preis</th>';
                                        $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Netto</th>';
                                        $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151; border-right: 1px solid #e2e8f0;">Steuer</th>';
                                        $html .= '<th style="padding: 0.25rem; text-align: right; font-weight: 500; color: #374151;">Brutto</th>';
                                        $html .= '</tr>';
                                        $html .= '</thead>';
                                        $html .= '<tbody>';
                                        
                                        // Anlage Gesamt Zeile
                                        $html .= '<tr style="border-bottom: 1px solid #e2e8f0;">';
                                        $html .= '<td style="padding: 0.25rem; color: #374151; border-right: 1px solid #e2e8f0;">Anlage Gesamt</td>';
                                        $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151; border-right: 1px solid #e2e8f0;">' . number_format($anlageMengeKwh, 4, ',', '.') . ' kWh</td>';
                                        $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151; border-right: 1px solid #e2e8f0;">' . number_format($einzelpreisGesamt, 6, ',', '.') . ' €</td>';
                                        $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151; border-right: 1px solid #e2e8f0;">' . number_format($bruttoGesamt, 2, ',', '.') . ' €</td>';
                                        $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151; border-right: 1px solid #e2e8f0;">0,0% = 0,00 €</td>';
                                        $html .= '<td style="padding: 0.25rem; text-align: right; color: #374151;">' . number_format($bruttoGesamt, 2, ',', '.') . ' €</td>';
                                        $html .= '</tr>';
                                        
                                        // Anteilig Zeile (blau)
                                        $html .= '<tr>';
                                        $html .= '<td style="padding: 0.25rem; color: #2563eb; border-right: 1px solid #e2e8f0;">Anteilig ' . number_format($customerPercentage, 2, ',', '.') . '%</td>';
                                        $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb; border-right: 1px solid #e2e8f0;">' . number_format($anteiligeMengeKwh, 3, ',', '.') . ' kWh</td>';
                                        $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb; border-right: 1px solid #e2e8f0;">' . number_format($einzelpreisAnteilig, 6, ',', '.') . ' €</td>';
                                        $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb; border-right: 1px solid #e2e8f0;">' . number_format($gesamtbetrag, 2, ',', '.') . ' €</td>';
                                        $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb; border-right: 1px solid #e2e8f0;">0,0% = 0,00 €</td>';
                                        $html .= '<td style="padding: 0.25rem; text-align: right; color: #2563eb;">' . number_format($gesamtbetrag, 2, ',', '.') . ' €</td>';
                                        $html .= '</tr>';
                                        
                                        $html .= '</tbody>';
                                        $html .= '</table>';
                                        
                                        $html .= '</div>';
                                    }
                                    
                                    // Schließe Details-Kasten
                                    $html .= '</div>';
                                    
                                    $html .= '</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; color: #166534; vertical-align: top;">' . number_format($item['customer_percentage'], 2, ',', '.') . '%</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; color: #166534; vertical-align: top;">' . number_format($item['customer_share_net'] ?? $item['customer_share'], 2, ',', '.') . '</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; color: #166534; vertical-align: top;">' . number_format((($item['vat_rate'] ?? 0.0) <= 1 ? ($item['vat_rate'] ?? 0.0) * 100 : ($item['vat_rate'] ?? 0.0)), 1, ',', '.') . '%</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; font-weight: 500; color: #166534; vertical-align: top;">' . number_format($item['customer_share'], 2, ',', '.') . '</td>';
                                    $html .= '</tr>';
                                }
                                
                                $html .= '</tbody>';
                                $html .= '</table>';
                                
                                // Gesamtbetrag für Gutschriftenpositionen
                                $totalCredits = array_sum(array_column($breakdown, 'customer_share'));
                                $totalCreditsNet = array_sum(array_column($breakdown, 'customer_share_net'));
                                $html .= '<div style="margin-top: 0.5rem; padding: 0.75rem; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.375rem;">';
                                $html .= '<table style="width: 100%; border-collapse: collapse;">';
                                $html .= '<tr>';
                                $html .= '<td style="font-weight: 600; color: #166534;">Gesamtgutschriften:</td>';
                                $html .= '<td style="text-align: right; color: #166534;">Netto: <strong>' . number_format($totalCreditsNet, 2, ',', '.') . ' €</strong></td>';
                                $html .= '<td style="text-align: right; color: #166534;">Brutto: <strong>' . number_format($totalCredits, 2, ',', '.') . ' €</strong></td>';
                                $html .= '</tr>';
                                $html .= '</table>';
                                $html .= '</div>';
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->visible(fn ($record) => $record && $record->credit_breakdown && !empty($record->credit_breakdown)),
                                
                        Forms\Components\Placeholder::make('price_overview')
                            ->label('')
                            ->content(function ($get, $record) {
                                if (!$record || !$record->credit_breakdown || empty($record->credit_breakdown)) {
                                    return '';
                                }
                                
                                $breakdown = $record->credit_breakdown;
                                
                                // Oranger Kasten mit Preis-Übersicht
                                $marktpraemiePreis = 0;
                                $direktvermarktungPreis = 0;
                                $hasMarktpraemie = false;
                                $hasDirektvermarktung = false;
                                
                                foreach ($breakdown as $item) {
                                    $contractTitle = strtolower($item['contract_title'] ?? '');
                                    $customerPercentage = $item['customer_percentage'] ?? 0;
                                    $gesamtbetrag = $item['customer_share'] ?? 0;
                                    
                                    // Preis für Marktprämie extrahieren
                                    if (strpos($contractTitle, 'marktprämie') !== false || strpos($contractTitle, 'marktwert') !== false) {
                                        $anteiligeMengeKwh = ($record->produced_energy_kwh * $customerPercentage) / 100;
                                        $marktpraemiePreis = $anteiligeMengeKwh > 0 ? ($gesamtbetrag / $anteiligeMengeKwh) : 0;
                                        $hasMarktpraemie = true;
                                    }
                                    
                                    // Preis für Direktvermarktung extrahieren (aus Artikel-Details wenn vorhanden)
                                    if (strpos($contractTitle, 'direktvermarktung') !== false) {
                                        $hasDirektvermarktung = true;
                                        if (isset($item['articles']) && !empty($item['articles'])) {
                                            $article = $item['articles'][0]; // Nehme ersten Artikel
                                            $direktvermarktungPreis = $article['unit_price'] ?? 0;
                                        } else {
                                            // Fallback: Berechne aus Gesamtbetrag
                                            $anteiligeMengeKwh = ($record->produced_energy_kwh * $customerPercentage) / 100;
                                            $direktvermarktungPreis = $anteiligeMengeKwh > 0 ? ($gesamtbetrag / $anteiligeMengeKwh) : 0;
                                        }
                                    }
                                }
                                
                                // Hole EEG-Vergütung aus der Beteiligung
                                $eegCompensation = 0;
                                $participation = $record->solarPlant->participations()
                                    ->where('customer_id', $record->customer_id)
                                    ->first();
                                if ($participation) {
                                    $eegCompensation = $participation->eeg_compensation_per_kwh ?? 0;
                                }
                                
                                // Berechne Summe und Differenz
                                $summeEinzelpreise = $marktpraemiePreis + $direktvermarktungPreis;
                                $differenzEEG = $eegCompensation - $summeEinzelpreise;
                                
                                // Oranger Kasten nur anzeigen wenn relevante Daten vorhanden sind
                                if (!$hasMarktpraemie && !$hasDirektvermarktung) {
                                    return '';
                                }
                                
                                $html = '<div style="padding: 0.75rem; background-color: #fff7ed; border: 1px solid #fed7aa; border-radius: 0.375rem;">';
                                $html .= '<div style="font-weight: 600; color: #ea580c; margin-bottom: 0.5rem;">Preis-Übersicht (pro kWh)</div>';
                                
                                // Tabellenlayout für bessere Ausrichtung
                                $html .= '<table style="width: 100%; border-collapse: collapse;">';
                                
                                if ($hasMarktpraemie) {
                                    $html .= '<tr>';
                                    $html .= '<td style="font-size: 0.875rem; color: #ea580c; padding: 0.125rem 0;">Einzelpreis Marktprämie:</td>';
                                    $html .= '<td style="font-size: 0.875rem; color: #ea580c; padding: 0.125rem 0; text-align: right; font-weight: bold;">' . number_format($marktpraemiePreis, 6, ',', '.') . ' €/kWh</td>';
                                    $html .= '</tr>';
                                }
                                
                                if ($hasDirektvermarktung) {
                                    $html .= '<tr>';
                                    $html .= '<td style="font-size: 0.875rem; color: #ea580c; padding: 0.125rem 0;">Einzelpreis Direktvermarktung:</td>';
                                    $html .= '<td style="font-size: 0.875rem; color: #ea580c; padding: 0.125rem 0; text-align: right; font-weight: bold;">' . number_format($direktvermarktungPreis, 6, ',', '.') . ' €/kWh</td>';
                                    $html .= '</tr>';
                                }
                                
                                if ($hasMarktpraemie || $hasDirektvermarktung) {
                                    $html .= '<tr style="border-top: 1px solid #fed7aa;">';
                                    $html .= '<td style="font-size: 0.875rem; color: #ea580c; padding: 0.25rem 0 0.125rem 0;">Summe Einzelpreise:</td>';
                                    $html .= '<td style="font-size: 0.875rem; color: #ea580c; padding: 0.25rem 0 0.125rem 0; text-align: right; font-weight: bold;">' . number_format($summeEinzelpreise, 6, ',', '.') . ' €/kWh</td>';
                                    $html .= '</tr>';
                                    
                                    if ($eegCompensation > 0) {
                                        $html .= '<tr>';
                                        $html .= '<td style="font-size: 0.875rem; color: #ea580c; padding: 0.125rem 0;">Vertraglich zugesicherte EEG-Vergütung:</td>';
                                        $html .= '<td style="font-size: 0.875rem; color: #ea580c; padding: 0.125rem 0; text-align: right; font-weight: bold;">' . number_format($eegCompensation, 6, ',', '.') . ' €/kWh</td>';
                                        $html .= '</tr>';
                                        
                                        $differenzColor = $differenzEEG >= 0 ? '#dc2626' : '#059669'; // Rot für negativ, Grün für positiv
                                        $differenzText = $differenzEEG >= 0 ? 'Differenz (Verlust)' : 'Differenz (Gewinn)';
                                        
                                        // Berechne anteilige kWh des Kunden für absoluten Betrag
                                        $customerEnergyKwh = ($record->produced_energy_kwh * $record->participation_percentage) / 100;
                                        $differenzAbsolut = abs($differenzEEG) * $customerEnergyKwh;
                                        
                                        $html .= '<tr style="border-top: 1px solid #fed7aa;">';
                                        $html .= '<td style="font-size: 0.875rem; color: ' . $differenzColor . '; padding: 0.25rem 0 0.125rem 0; font-weight: 600;">' . $differenzText . ':</td>';
                                        $html .= '<td style="font-size: 0.875rem; color: ' . $differenzColor . '; padding: 0.25rem 0 0.125rem 0; text-align: right; font-weight: bold;">' . number_format(abs($differenzEEG), 6, ',', '.') . ' €/kWh</td>';
                                        $html .= '</tr>';
                                        
                                        $html .= '<tr>';
                                        $html .= '<td style="font-size: 0.875rem; color: ' . $differenzColor . '; padding: 0.125rem 0; font-weight: 600;">Absoluter Betrag:</td>';
                                        $html .= '<td style="font-size: 0.875rem; color: ' . $differenzColor . '; padding: 0.125rem 0; text-align: right; font-weight: bold;">' . number_format($differenzAbsolut, 2, ',', '.') . ' €</td>';
                                        $html .= '</tr>';
                                    } else {
                                        $html .= '<tr>';
                                        $html .= '<td colspan="2" style="font-size: 0.875rem; color: #ea580c; padding: 0.125rem 0; font-style: italic;">EEG-Vergütung nicht hinterlegt</td>';
                                        $html .= '</tr>';
                                    }
                                }
                                
                                $html .= '</table>';
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->visible(fn ($record) => $record && $record->credit_breakdown && !empty($record->credit_breakdown)),

                        Forms\Components\Placeholder::make('final_summary')
                            ->label('')
                            ->content(function ($get, $record) {
                                if (!$record) return '';
                                
                                $hasCosts = $record->cost_breakdown && !empty($record->cost_breakdown);
                                $hasCredits = $record->credit_breakdown && !empty($record->credit_breakdown);
                                
                                if (!$hasCosts && !$hasCredits) return '';
                                
                                // Berechne Gesamtwerte
                                $totalCosts = $hasCosts ? array_sum(array_column($record->cost_breakdown, 'customer_share')) : 0;
                                $totalCostsNet = $hasCosts ? array_sum(array_column($record->cost_breakdown, 'customer_share_net')) : 0;
                                $totalCredits = $hasCredits ? array_sum(array_column($record->credit_breakdown, 'customer_share')) : 0;
                                $totalCreditsNet = $hasCredits ? array_sum(array_column($record->credit_breakdown, 'customer_share_net')) : 0;
                                
                                // Berechne MwSt.-Beträge
                                $totalCostVat = $totalCosts - $totalCostsNet;
                                $totalCreditVat = $totalCredits - $totalCreditsNet;
                                $totalVat = $totalCostVat - $totalCreditVat; // Credits reduzieren die MwSt.
                                
                                // Berechne Endbetrag
                                $finalNetAmount = $totalCostsNet - $totalCreditsNet;
                                $finalAmount = $totalCosts - $totalCredits;
                                
                                $html = '<div style="margin-top: 1.5rem; padding: 1rem; background-color: #f8fafc; border: 2px solid #e2e8f0; border-radius: 0.5rem;">';
                                $html .= '<div style="font-weight: 700; font-size: 1.125rem; color: #374151; margin-bottom: 1rem; text-align: center; border-bottom: 1px solid #d1d5db; padding-bottom: 0.5rem;">Gesamtübersicht</div>';
                                
                                $html .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">';
                                
                                // Kostenseite (blau)
                                if ($hasCosts) {
                                    $html .= '<div style="padding: 0.75rem; background-color: #eff6ff; border-radius: 0.375rem;">';
                                    $html .= '<div style="font-weight: 600; color: #1d4ed8; margin-bottom: 0.5rem; text-align: center; font-size: 1rem;">Rechnungsbeträge</div>';
                                    $html .= '<table style="width: 100%; border-collapse: collapse;">';
                                    $html .= '<tr><td style="color: #1d4ed8; padding: 0.25rem 0;">Netto:</td><td style="text-align: right; color: #1d4ed8; font-weight: bold;">' . number_format($totalCostsNet, 2, ',', '.') . ' €</td></tr>';
                                    $html .= '<tr><td style="color: #1d4ed8; padding: 0.25rem 0;">MwSt.:</td><td style="text-align: right; color: #1d4ed8; font-weight: bold;">' . number_format($totalCostVat, 2, ',', '.') . ' €</td></tr>';
                                    $html .= '<tr style="border-top: 1px solid #3b82f6;"><td style="color: #1d4ed8; padding: 0.25rem 0; font-weight: bold;">Brutto:</td><td style="text-align: right; color: #1d4ed8; font-weight: bold; font-size: 1.125rem;">' . number_format($totalCosts, 2, ',', '.') . ' €</td></tr>';
                                    $html .= '</table>';
                                    $html .= '</div>';
                                } else {
                                    $html .= '<div style="padding: 0.75rem; background-color: #f9fafb; border: 1px dashed #d1d5db; border-radius: 0.375rem; display: flex; align-items: center; justify-content: center; color: #6b7280; font-style: italic;">Keine Kostenpositionen</div>';
                                }
                                
                                // Gutschriftenseite (grün)
                                if ($hasCredits) {
                                    $html .= '<div style="padding: 0.75rem; background-color: #f0fdf4; border-radius: 0.375rem;">';
                                    $html .= '<div style="font-weight: 600; color: #15803d; margin-bottom: 0.5rem; text-align: center; font-size: 1rem;">Gutschriftsbeträge</div>';
                                    $html .= '<table style="width: 100%; border-collapse: collapse;">';
                                    $html .= '<tr><td style="color: #15803d; padding: 0.25rem 0;">Netto:</td><td style="text-align: right; color: #15803d; font-weight: bold;">' . number_format($totalCreditsNet, 2, ',', '.') . ' €</td></tr>';
                                    $html .= '<tr><td style="color: #15803d; padding: 0.25rem 0;">MwSt.:</td><td style="text-align: right; color: #15803d; font-weight: bold;">' . number_format($totalCreditVat, 2, ',', '.') . ' €</td></tr>';
                                    $html .= '<tr style="border-top: 1px solid #22c55e;"><td style="color: #15803d; padding: 0.25rem 0; font-weight: bold;">Brutto:</td><td style="text-align: right; color: #15803d; font-weight: bold; font-size: 1.125rem;">' . number_format($totalCredits, 2, ',', '.') . ' €</td></tr>';
                                    $html .= '</table>';
                                    $html .= '</div>';
                                } else {
                                    $html .= '<div style="padding: 0.75rem; background-color: #f9fafb; border: 1px dashed #d1d5db; border-radius: 0.375rem; display: flex; align-items: center; justify-content: center; color: #6b7280; font-style: italic;">Keine Gutschriftenpositionen</div>';
                                }
                                
                                $html .= '</div>';
                                
                                // Finale Berechnung
                                $html .= '<div style="margin-top: 1rem; padding: 1rem; background-color: #fff; border-radius: 0.5rem;">';
                                $html .= '<div style="font-weight: 700; color: #374151; margin-bottom: 0.75rem; text-align: center; font-size: 1.125rem;">Endergebnis</div>';
                                
                                $html .= '<table style="width: 100%; border-collapse: collapse; font-size: 1rem;">';
                                
                                // Netto-Berechnung
                                $html .= '<tr>';
                                $html .= '<td style="color: #374151; padding: 0.375rem 0;">Netto-Gesamtbetrag:</td>';
                                $html .= '<td style="text-align: right; color: #374151; font-weight: bold;">' . number_format($finalNetAmount, 2, ',', '.') . ' €</td>';
                                $html .= '</tr>';
                                
                                // MwSt.-Berechnung
                                $html .= '<tr>';
                                $html .= '<td style="color: #374151; padding: 0.375rem 0;">MwSt.-Gesamtbetrag:</td>';
                                $html .= '<td style="text-align: right; color: #374151; font-weight: bold;">' . number_format($totalVat, 2, ',', '.') . ' €</td>';
                                $html .= '</tr>';
                                
                                // Finale Summe
                                $finalAmountColor = $finalAmount >= 0 ? '#dc2626' : '#059669'; // Rot für Rechnung, Grün für Guthaben
                                $finalAmountText = $finalAmount >= 0 ? 'Rechnungsbetrag' : 'Guthabenbetrag';
                                
                                $html .= '<tr style="border-top: 2px solid #374151; border-bottom: 2px solid #374151;">';
                                $html .= '<td style="color: ' . $finalAmountColor . '; padding: 0.75rem 0; font-weight: bold; font-size: 1.125rem;">' . $finalAmountText . ':</td>';
                                $html .= '<td style="text-align: right; color: ' . $finalAmountColor . '; font-weight: bold; font-size: 1.25rem;">' . number_format(abs($finalAmount), 2, ',', '.') . ' €</td>';
                                $html .= '</tr>';
                                
                                $html .= '</table>';
                                $html .= '</div>';
                                
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->visible(fn ($record) => $record && (
                                ($record->cost_breakdown && !empty($record->cost_breakdown)) ||
                                ($record->credit_breakdown && !empty($record->credit_breakdown))
                            )),
                    ])
                    ->visible(fn ($record) => $record && (
                        ($record->cost_breakdown && !empty($record->cost_breakdown)) ||
                        ($record->credit_breakdown && !empty($record->credit_breakdown))
                    )),

                // Beträge-Sektion ausgeblendet da Informationen bereits in Kostenaufschlüsselung enthalten sind
                Forms\Components\Section::make('Beträge')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_costs')
                                    ->label('Gesamtkosten (Brutto)')
                                    ->prefix('€')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('total_costs_net')
                                    ->label('Gesamtkosten (Netto)')
                                    ->prefix('€')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('total_credits')
                                    ->label('Gesamtgutschriften (Brutto)')
                                    ->prefix('€')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('total_credits_net')
                                    ->label('Gesamtgutschriften (Netto)')
                                    ->prefix('€')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('total_vat_amount')
                                    ->label('MwSt.-Betrag')
                                    ->prefix('€')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('net_amount')
                                    ->label('Gesamtbetrag (Brutto)')
                                    ->prefix('€')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->hidden(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('solarPlant.plant_number')
                    ->label('Anlagen-Nr.')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Anlagenname')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->getStateUsing(function (SolarPlantBilling $record): string {
                        $customer = $record->customer;
                        return $customer->customer_type === 'business' && $customer->company_name 
                            ? $customer->company_name 
                            : $customer->name;
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_month')
                    ->label('Abrechnungsmonat')
                    ->sortable(['billing_year', 'billing_month']),

                Tables\Columns\TextColumn::make('produced_energy_kwh')
                    ->label('Produzierte Energie')
                    ->suffix(' kWh')
                    ->numeric(3)
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('participation_percentage')
                    ->label('Beteiligung')
                    ->suffix('%')
                    ->numeric(2)
                    ->alignRight()
                    ->getStateUsing(function (SolarPlantBilling $record): ?float {
                        // Hole die aktuelle Beteiligung aus der participations Tabelle
                        $participation = $record->solarPlant->participations()
                            ->where('customer_id', $record->customer_id)
                            ->first();
                        
                        return $participation ? $participation->percentage : $record->participation_percentage;
                    }),

                Tables\Columns\TextColumn::make('formatted_total_costs')
                    ->label('Kosten')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('formatted_total_credits')
                    ->label('Gutschriften')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('formatted_net_amount')
                    ->label('Gesamtbetrag')
                    ->alignRight()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('breakdown_summary')
                    ->label('Aufschlüsselung')
                    ->getStateUsing(function (SolarPlantBilling $record): string {
                        $summary = '';
                        
                        if ($record->cost_breakdown && count($record->cost_breakdown) > 0) {
                            $costCount = count($record->cost_breakdown);
                            $summary .= "{$costCount} Kostenposition" . ($costCount > 1 ? 'en' : '');
                        }
                        
                        if ($record->credit_breakdown && count($record->credit_breakdown) > 0) {
                            $creditCount = count($record->credit_breakdown);
                            if ($summary) $summary .= ', ';
                            $summary .= "{$creditCount} Gutschrift" . ($creditCount > 1 ? 'en' : '');
                        }
                        
                        return $summary ?: 'Keine Details';
                    })
                    ->limit(30)
                    ->tooltip(function (SolarPlantBilling $record): ?string {
                        $tooltip = '';
                        
                        if ($record->cost_breakdown) {
                            $tooltip .= "Kosten:\n";
                            foreach ($record->cost_breakdown as $item) {
                                $tooltip .= "• {$item['contract_title']}: " . number_format($item['customer_share'], 2, ',', '.') . " €\n";
                            }
                        }
                        
                        if ($record->credit_breakdown) {
                            if ($tooltip) $tooltip .= "\n";
                            $tooltip .= "Gutschriften:\n";
                            foreach ($record->credit_breakdown as $item) {
                                $tooltip .= "• {$item['contract_title']}: " . number_format($item['customer_share'], 2, ',', '.') . " €\n";
                            }
                        }
                        
                        return $tooltip ?: null;
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'finalized' => 'warning',
                        'sent' => 'info',
                        'paid' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => SolarPlantBilling::getStatusOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('solar_plant_id')
                    ->label('Solaranlage')
                    ->options(SolarPlant::orderBy('plant_number')->pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SolarPlantBilling::getStatusOptions()),

                Tables\Filters\SelectFilter::make('billing_year')
                    ->label('Jahr')
                    ->options(function () {
                        $currentYear = now()->year;
                        $years = [];
                        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    }),

                Tables\Filters\SelectFilter::make('billing_month')
                    ->label('Monat')
                    ->options([
                        1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                        5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_monthly_billings')
                    ->label('Monatliche Abrechnungen erstellen')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('solar_plant_id')
                            ->label('Solaranlage')
                            ->options(SolarPlant::orderBy('plant_number')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('billing_year')
                            ->label('Abrechnungsjahr')
                            ->options(function () {
                                $currentYear = now()->year;
                                $years = [];
                                for ($i = $currentYear - 1; $i <= $currentYear; $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            })
                            ->default(now()->year)
                            ->required(),

                        Forms\Components\Select::make('billing_month')
                            ->label('Abrechnungsmonat')
                            ->options([
                                1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                                5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                            ])
                            ->default(now()->subMonth()->month)
                            ->required(),

                        Forms\Components\TextInput::make('produced_energy_kwh')
                            ->label('Produzierte Energie (kWh)')
                            ->suffix('kWh')
                            ->numeric()
                            ->step(0.001)
                            ->minValue(0)
                            ->placeholder('z.B. 2500.000')
                            ->helperText('Gesamte produzierte Energie der Solaranlage für diesen Monat'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $billings = SolarPlantBilling::createBillingsForMonth(
                                $data['solar_plant_id'],
                                $data['billing_year'],
                                $data['billing_month'],
                                $data['produced_energy_kwh'] ?? null
                            );

                            $count = count($billings);
                            $monthName = Carbon::createFromDate($data['billing_year'], $data['billing_month'], 1)
                                ->locale('de')
                                ->translatedFormat('F Y');

                            $energyText = '';
                            if (isset($data['produced_energy_kwh']) && $data['produced_energy_kwh'] > 0) {
                                $energyText = " (Produzierte Energie: " . number_format($data['produced_energy_kwh'], 3, ',', '.') . " kWh)";
                            }

                            Notification::make()
                                ->title('Abrechnungen erfolgreich erstellt')
                                ->body("{$count} Abrechnungen für {$monthName} wurden erstellt.{$energyText}")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Fehler beim Erstellen der Abrechnungen')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalWidth('md'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('generate_pdfs')
                        ->label('PDF Abrechnungen generieren')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('primary')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            try {
                                $pdfService = new \App\Services\SolarPlantBillingPdfService();
                                $successCount = 0;
                                $errorCount = 0;
                                $errors = [];
                                $downloadUrls = [];
                                
                                // Lade notwendige Beziehungen
                                $records->load(['solarPlant', 'customer']);
                                
                                // Erstelle Session-ID für diese Batch
                                $batchId = uniqid('pdf_batch_');
                                
                                foreach ($records as $billing) {
                                    try {
                                        // Verwende den PDF-Service für konsistente PDF-Generierung (inkl. Logo)
                                        $pdfContent = $pdfService->generateBillingPdf($billing);
                                        
                                        // Erstelle Dateiname
                                        $customer = $billing->customer;
                                        $solarPlant = $billing->solarPlant;
                                        
                                        // Solaranlagen-Namen bereinigen
                                        $plantName = preg_replace('/[^a-zA-Z0-9\-äöüÄÖÜß]/', '', str_replace(' ', '-', trim($solarPlant->name)));
                                        $plantName = preg_replace('/-+/', '-', $plantName);
                                        $plantName = trim($plantName, '-');
                                        
                                        // Kundennamen bereinigen
                                        $customerName = $customer->customer_type === 'business' && $customer->company_name 
                                            ? $customer->company_name 
                                            : $customer->name;
                                        $customerName = preg_replace('/[^a-zA-Z0-9\-äöüÄÖÜß]/', '', str_replace(' ', '-', trim($customerName)));
                                        $customerName = preg_replace('/-+/', '-', $customerName);
                                        $customerName = trim($customerName, '-');
                                        
                                        $filename = sprintf(
                                            '%04d-%02d_%s_%s.pdf',
                                            $billing->billing_year,
                                            $billing->billing_month,
                                            $plantName,
                                            $customerName
                                        );
                                        
                                        // Speichere PDF temporär für individuellen Download
                                        $tempPath = "temp/bulk-pdfs/{$batchId}/{$filename}";
                                        Storage::disk('public')->put($tempPath, $pdfContent);
                                        
                                        // Erstelle Download-URL
                                        $downloadUrls[] = [
                                            'filename' => $filename,
                                            'url' => Storage::disk('public')->url($tempPath),
                                            'customer_name' => $customerName,
                                            'period' => sprintf('%04d-%02d', $billing->billing_year, $billing->billing_month),
                                            'status' => 'success'
                                        ];
                                        
                                        $successCount++;
                                        
                                    } catch (\Exception $e) {
                                        $errorCount++;
                                        $errors[] = "Fehler bei Abrechnung {$billing->id}: " . $e->getMessage();
                                    }
                                }
                                
                                // Wenn PDFs erfolgreich generiert wurden, starte automatische Downloads
                                if ($successCount > 0) {
                                    // Speichere Download-URLs in Session für Download-Seite
                                    session([
                                        'bulk_pdf_downloads' => $downloadUrls,
                                        'bulk_pdf_batch_id' => $batchId,
                                        'bulk_pdf_success_count' => $successCount,
                                        'bulk_pdf_error_count' => $errorCount
                                    ]);
                                    
                                    // Erfolgsmeldung
                                    $message = "{$successCount} PDF" . ($successCount !== 1 ? 's' : '') . " wurden erstellt und werden jetzt heruntergeladen";
                                    if ($errorCount > 0) {
                                        $message .= " ({$errorCount} Fehler aufgetreten)";
                                    }
                                    
                                    $notification = Notification::make()
                                        ->title('PDFs werden heruntergeladen')
                                        ->body($message);
                                    
                                    if ($errorCount === 0) {
                                        $notification->success();
                                    } else {
                                        $notification->warning();
                                    }
                                    
                                    $notification->send();
                                    
                                    // Redirect zur Download-Seite, die automatische Downloads startet
                                    return redirect()->route('admin.download-bulk-pdfs');
                                    
                                } else {
                                    // Nur Fehlermeldung wenn keine PDFs generiert wurden
                                    $notification = Notification::make()
                                        ->title('Keine PDFs generiert')
                                        ->body('Es konnten keine PDFs erstellt werden. Siehe Fehlerdetails.');
                                    
                                    if ($errorCount > 0) {
                                        $notification->danger();
                                    } else {
                                        $notification->warning();
                                    }
                                    
                                    $notification->send();
                                }
                                
                                // Bei Fehlern Details anzeigen
                                if ($errorCount > 0 && count($errors) > 0) {
                                    $errorDetails = implode("\n", array_slice($errors, 0, 5));
                                    if (count($errors) > 5) {
                                        $errorDetails .= "\n... und " . (count($errors) - 5) . " weitere Fehler";
                                    }
                                    
                                    Notification::make()
                                        ->title('Fehlerdetails')
                                        ->body($errorDetails)
                                        ->danger()
                                        ->send();
                                }
                                
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler bei Bulk-PDF-Generierung')
                                    ->body('Ein unerwarteter Fehler ist aufgetreten: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('PDF Abrechnungen generieren')
                        ->modalDescription(function (\Illuminate\Database\Eloquent\Collection $records): string {
                            $count = $records->count();
                            return "Möchten Sie für alle {$count} ausgewählten Abrechnungen PDF-Dateien generieren? Die PDFs werden erstellt und können dann heruntergeladen werden.";
                        })
                        ->modalSubmitActionLabel('PDFs generieren')
                        ->modalIcon('heroicon-o-document-arrow-down'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSolarPlantBillings::route('/'),
            'create' => Pages\CreateSolarPlantBilling::route('/create'),
            'view' => Pages\ViewSolarPlantBilling::route('/{record}'),
            'edit' => Pages\EditSolarPlantBilling::route('/{record}/edit'),
        ];
    }
}
