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
use App\Exports\SolarPlantBillingsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Mail\SingleBillingMail;
use App\Services\SolarPlantBillingPdfService;
use Illuminate\Support\Facades\Mail;

class SolarPlantBillingResource extends Resource
{
    protected static ?string $model = SolarPlantBilling::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Abrechnungen';

    protected static ?string $modelLabel = 'Solaranlagen-Abrechnung';

    protected static ?string $pluralModelLabel = 'Solaranlagen-Abrechnungen';

    protected static ?string $navigationGroup = 'Solar Management';

    protected static ?int $navigationSort = 3;

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
                            ->options(function ($record) {
                                $allOptions = SolarPlantBilling::getStatusOptions();

                                // Wenn finalisiert, nur noch Versendet, Bezahlt und Storniert erlauben
                                if ($record && $record->status === 'finalized') {
                                    return [
                                        'finalized' => $allOptions['finalized'],
                                        'sent' => $allOptions['sent'],
                                        'paid' => $allOptions['paid'],
                                        'cancelled' => $allOptions['cancelled'],
                                    ];
                                }

                                return $allOptions;
                            })
                            ->default('draft')
                            ->required()
                            ->disabled(fn ($record) => $record && $record->status === 'cancelled')
                            ->dehydrated(fn ($record) => !$record || $record->status !== 'cancelled')
                            ->live(),

                        Forms\Components\Hidden::make('cancellation_reason_temp')
                            ->default(null),

                        Forms\Components\Textarea::make('cancellation_reason_input')
                            ->label('Stornierungsgrund')
                            ->rows(3)
                            ->visible(fn (Forms\Get $get, $record) => $get('status') === 'cancelled' && (!$record || $record->status !== 'cancelled'))
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('cancellation_reason_temp', $state);
                            })
                            ->live(onBlur: true)
                            ->dehydrated(false),

                        Forms\Components\DatePicker::make('cancellation_date')
                            ->label('Stornierungsdatum')
                            ->helperText('Diese Abrechnung wurde storniert. Status und Stornierungsdatum können nicht mehr geändert werden.')
                            ->nullable()
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record && $record->status === 'cancelled'),

                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Stornierungsgrund')
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record && $record->status === 'cancelled' && $record->cancellation_reason),
                    ])->columns(2),

                Forms\Components\Section::make('Kostenaufschlüsselung')
                    ->schema([
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
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #374151; vertical-align: top;">Gesamtbetrag</th>';
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
                                            $html .= '<div style="display: flex; gap: 1rem; flex-wrap: wrap;">';
                                            $html .= '<span>Menge: ' . number_format($article['quantity'], 4, ',', '.') . ' ' . htmlspecialchars($article['unit']) . '</span>';
                                            $html .= '<span>Preis: ' . number_format($article['unit_price'], 6, ',', '.') . ' €</span>';
                                            $html .= '<span>Gesamt netto: ' . number_format($article['total_price_net'], 2, ',', '.') . ' €</span>';
                                            $html .= '<span>Steuer: ' . number_format($article['tax_rate'] * 100, 1, ',', '.') . '% = ' . number_format($article['tax_amount'], 2, ',', '.') . ' €</span>';
                                            $html .= '<span>Gesamt brutto: ' . number_format($article['total_price_gross'], 2, ',', '.') . ' €</span>';
                                            $html .= '</div>';
                                            $html .= '</div>';
                                        }
                                        $html .= '</div>';
                                    }
                                    
                                    $html .= '</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; color: #374151; vertical-align: top;">' . number_format($item['customer_percentage'], 2, ',', '.') . '%</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; font-weight: 500; color: #374151; vertical-align: top;">' . number_format($item['customer_share'], 2, ',', '.') . ' €</td>';
                                    $html .= '</tr>';
                                }
                                
                                $html .= '</tbody>';
                                $html .= '</table>';
                                
                                // Gesamtbetrag für Kostenpositionen
                                $totalCosts = array_sum(array_column($breakdown, 'customer_share'));
                                $html .= '<div style="margin-top: 0.5rem; padding: 0.75rem; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.375rem; display: flex; justify-content: space-between; align-items: center;">';
                                $html .= '<div style="font-weight: 600; color: #374151;">Gesamtkosten:</div>';
                                $html .= '<div style="font-weight: 600; color: #374151;">' . number_format($totalCosts, 2, ',', '.') . ' €</div>';
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
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #166534; vertical-align: top;">Gesamtbetrag</th>';
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
                                    
                                    // Artikel-Details anzeigen
                                    if (isset($item['articles']) && !empty($item['articles'])) {
                                        $html .= '<div style="margin-top: 0.5rem; padding: 0.5rem; background-color: #f9fafb; border-radius: 0.25rem; border: 1px solid #e5e7eb;">';
                                        $html .= '<div style="font-weight: 500; font-size: 0.875rem; color: #374151; margin-bottom: 0.25rem;">Details:</div>';
                                        
                                        foreach ($item['articles'] as $article) {
                                            $html .= '<div style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.25rem;">';
                                            $html .= '<div style="font-weight: 500;">' . htmlspecialchars($article['article_name']) . '</div>';
                                            $html .= '<div style="display: flex; gap: 1rem; flex-wrap: wrap;">';
                                            $html .= '<span>Menge: ' . number_format($article['quantity'], 4, ',', '.') . ' ' . htmlspecialchars($article['unit']) . '</span>';
                                            $html .= '<span>Preis: ' . number_format($article['unit_price'], 6, ',', '.') . ' €</span>';
                                            $html .= '<span>Gesamt netto: ' . number_format($article['total_price_net'], 2, ',', '.') . ' €</span>';
                                            $html .= '<span>Steuer: ' . number_format($article['tax_rate'] * 100, 1, ',', '.') . '% = ' . number_format($article['tax_amount'], 2, ',', '.') . ' €</span>';
                                            $html .= '<span>Gesamt brutto: ' . number_format($article['total_price_gross'], 2, ',', '.') . ' €</span>';
                                            $html .= '</div>';
                                            $html .= '</div>';
                                        }
                                        $html .= '</div>';
                                    }
                                    
                                    $html .= '</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; color: #166534; vertical-align: top;">' . number_format($item['customer_percentage'], 2, ',', '.') . '%</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; font-weight: 500; color: #166534; vertical-align: top;">' . number_format($item['customer_share'], 2, ',', '.') . ' €</td>';
                                    $html .= '</tr>';
                                }
                                
                                $html .= '</tbody>';
                                $html .= '</table>';
                                
                                // Gesamtbetrag für Gutschriftenpositionen
                                $totalCredits = array_sum(array_column($breakdown, 'customer_share'));
                                $html .= '<div style="margin-top: 0.5rem; padding: 0.75rem; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.375rem; display: flex; justify-content: space-between; align-items: center;">';
                                $html .= '<div style="font-weight: 600; color: #166534;">Gesamtgutschriften:</div>';
                                $html .= '<div style="font-weight: 600; color: #166534;">' . number_format($totalCredits, 2, ',', '.') . ' €</div>';
                                $html .= '</div>';
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->visible(fn ($record) => $record && $record->credit_breakdown && !empty($record->credit_breakdown)),
                    ])
                    ->visible(fn ($record) => $record && (
                        ($record->cost_breakdown && !empty($record->cost_breakdown)) ||
                        ($record->credit_breakdown && !empty($record->credit_breakdown))
                    )),

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
                    ]),

                Forms\Components\Section::make('Zusätzliche Informationen')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Bemerkung')
                            ->helperText('Wird auf der PDF Abrechnung unter der Gesamtsumme angezeigt.')
                            ->rows(3)
                            ->default('Diese Rechnung / Gutschrift, wurde maschinell erstellt und bedarf keiner Unterschrift. Wir legen höchsten Wert auf Transparenz und hoffen, dass wir ihnen die abrechnungsrelevanten Positionen klar und einfach verständlich erläutern konnten. Sollten Sie noch weitere Informationen zu Ihrer Abrechnung wünschen, rufen Sie uns gerne unter 02234-4300614 an oder schreiben Sie uns  eine Mail mit Ihrem Anliegen an: abrechnung@prosoltec-anlagenbetreiber.de'),

                        Forms\Components\Toggle::make('show_hints')
                            ->label('Hinweistext auf PDF anzeigen')
                            ->default(false)
                            ->helperText('Wenn deaktiviert, wird der Hinweistext am Ende der PDF nicht angezeigt'),
                    ]),
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
                    ->url(fn (SolarPlantBilling $record): string =>
                        \App\Filament\Resources\CustomerResource::getUrl('view', ['record' => $record->customer_id])
                    )
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_month')
                    ->label('Abrechnungsmonat')
                    ->sortable(['billing_year', 'billing_month']),

                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Rechnungsnummer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('produced_energy_kwh')
                    ->label('Produzierte Energie')
                    ->suffix(' kWh')
                    ->numeric(3)
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('participation_kwp')
                    ->label('Beteiligung (kWp)')
                    ->suffix(' kWp')
                    ->numeric(2)
                    ->alignRight()
                    ->getStateUsing(function (SolarPlantBilling $record): ?float {
                        // Hole die aktuelle kWp-Beteiligung aus der participations Tabelle
                        $participation = $record->solarPlant->participations()
                            ->where('customer_id', $record->customer_id)
                            ->first();
                        
                        return $participation ? $participation->participation_kwp : null;
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('participation_percentage')
                    ->label('Beteiligung (%)')
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
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'finalized' => 'warning',
                        'sent' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => SolarPlantBilling::getStatusOptions()[$state] ?? $state)
                    ->tooltip(fn ($record) => $record->status === 'cancelled' && $record->cancellation_reason
                        ? 'Stornierungsgrund: ' . $record->cancellation_reason
                        : null),

                Tables\Columns\TextColumn::make('cancellation_date')
                    ->label('Storniert am')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('danger')
                    ->badge(fn ($record) => $record->cancellation_date ? true : false),

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

                        Forms\Components\Textarea::make('notes')
                            ->label('Bemerkung')
                            ->helperText('Wird auf allen PDF-Abrechnungen unter der Gesamtsumme angezeigt.')
                            ->rows(4)
                            ->maxLength(2000)
                            ->default('Diese Rechnung / Gutschrift, wurde maschinell erstellt und bedarf keiner Unterschrift. Wir legen höchsten Wert auf Transparenz und hoffen, dass wir ihnen die abrechnungsrelevanten Positionen klar und einfach verständlich erläutern konnten. Sollten Sie noch weitere Informationen zu Ihrer Abrechnung wünschen, rufen Sie uns gerne unter 02234-4300614 an oder schreiben Sie uns  eine Mail mit Ihrem Anliegen an: abrechnung@prosoltec-anlagenbetreiber.de')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('show_hints')
                            ->label('Hinweistext auf PDF anzeigen')
                            ->default(false)
                            ->helperText('Wenn deaktiviert, wird der Hinweistext am Ende der PDF nicht angezeigt')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data) {
                        try {
                            $billings = SolarPlantBilling::createBillingsForMonth(
                                $data['solar_plant_id'],
                                $data['billing_year'],
                                $data['billing_month'],
                                $data['produced_energy_kwh'] ?? null
                            );

                            // Füge Bemerkung und show_hints zu allen erstellten Abrechnungen hinzu
                            if (!empty($data['notes']) || isset($data['show_hints'])) {
                                foreach ($billings as $billing) {
                                    $updateData = [];
                                    if (!empty($data['notes'])) {
                                        $updateData['notes'] = $data['notes'];
                                    }
                                    if (isset($data['show_hints'])) {
                                        $updateData['show_hints'] = $data['show_hints'];
                                    }
                                    $billing->update($updateData);
                                }
                            }

                            $count = count($billings);
                            $monthName = Carbon::createFromDate($data['billing_year'], $data['billing_month'], 1)
                                ->locale('de')
                                ->translatedFormat('F Y');

                            $energyText = '';
                            if (isset($data['produced_energy_kwh']) && $data['produced_energy_kwh'] > 0) {
                                $energyText = " (Produzierte Energie: " . number_format($data['produced_energy_kwh'], 3, ',', '.') . " kWh)";
                            }

                            $notesText = '';
                            if (!empty($data['notes'])) {
                                $notesText = " (Bemerkung hinzugefügt)";
                            }

                            Notification::make()
                                ->title('Abrechnungen erfolgreich erstellt')
                                ->body("{$count} Abrechnungen für {$monthName} wurden erstellt.{$energyText}{$notesText}")
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
                    ->modalWidth('xl'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\Action::make('cancel')
                        ->label('Stornieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Abrechnung stornieren')
                        ->modalDescription('Möchten Sie diese Abrechnung wirklich stornieren? Das Stornierungsdatum wird auf heute gesetzt.')
                        ->modalSubmitActionLabel('Stornieren')
                        ->modalCancelActionLabel('Abbrechen')
                        ->action(function (SolarPlantBilling $record): void {
                            $record->update([
                                'cancellation_date' => now(),
                                'status' => 'cancelled',
                            ]);

                            Notification::make()
                                ->title('Abrechnung storniert')
                                ->body('Die Abrechnung wurde erfolgreich storniert.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (SolarPlantBilling $record): bool => $record->cancellation_date === null),
                    Tables\Actions\Action::make('edit_notes')
                        ->label('Bemerkung')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->form([
                            Forms\Components\Textarea::make('notes')
                                ->label('Bemerkung')
                                ->helperText('Wird auf der PDF Abrechnung unter der Gesamtsumme angezeigt.')
                                ->rows(8)
                                ->maxLength(2000)
                                ->columnSpanFull(),

                            Forms\Components\Toggle::make('show_hints')
                                ->label('Hinweistext auf PDF anzeigen')
                                ->default(false)
                                ->helperText('Wenn deaktiviert, wird der Hinweistext am Ende der PDF nicht angezeigt')
                                ->columnSpanFull(),
                        ])
                        ->fillForm(fn (SolarPlantBilling $record): array => [
                            'notes' => $record->notes,
                            'show_hints' => $record->show_hints,
                        ])
                        ->action(function (array $data, SolarPlantBilling $record): void {
                            $record->update([
                                'notes' => $data['notes'],
                                'show_hints' => $data['show_hints'],
                            ]);
                            
                            Notification::make()
                                ->title('Bemerkung und Einstellungen aktualisiert')
                                ->body('Die Bemerkung und Hinweistext-Einstellung wurden erfolgreich gespeichert.')
                                ->success()
                                ->send();
                        })
                        ->modalHeading('Bemerkung bearbeiten')
                        ->modalDescription('Bearbeiten Sie die Bemerkung für diese Abrechnung. Sie wird auf der PDF unter der Gesamtsumme angezeigt.')
                        ->modalSubmitActionLabel('Speichern')
                        ->modalCancelActionLabel('Abbrechen')
                        ->modalWidth('2xl'),
                    Tables\Actions\Action::make('email_billing')
                        ->label('E-Mail Abrechnung')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('email_recipient')
                                ->label('E-Mail-Empfänger (An)')
                                ->email()
                                ->required()
                                ->placeholder('kunde@example.com')
                                ->helperText('E-Mail-Adresse des Empfängers')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('email_cc')
                                ->label('CC (Kopie)')
                                ->placeholder('email1@example.com, email2@example.com')
                                ->helperText('Mehrere E-Mail-Adressen mit Komma trennen')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('email_bcc')
                                ->label('BCC (Blindkopie)')
                                ->placeholder('email1@example.com, email2@example.com')
                                ->helperText('Mehrere E-Mail-Adressen mit Komma trennen')
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('email_message')
                                ->label('E-Mail-Text')
                                ->rows(6)
                                ->placeholder('Geben Sie hier Ihren individuellen E-Mail-Text ein...')
                                ->helperText('Lassen Sie das Feld leer für eine Standard-Nachricht')
                                ->columnSpanFull(),
                        ])
                        ->fillForm(function (SolarPlantBilling $record): array {
                            $customer = $record->customer;
                            $defaultEmail = '';

                            // Versuche E-Mail-Adresse aus Kundendaten zu ermitteln
                            if ($customer) {
                                $defaultEmail = $customer->email ?? '';
                            }

                            return [
                                'email_recipient' => $defaultEmail,
                                'email_cc' => '',
                                'email_bcc' => 't.kubitzek@jt-solarbau.de',
                                'email_message' => '',
                            ];
                        })
                        ->action(function (array $data, SolarPlantBilling $record): void {
                            try {
                                // PDF generieren
                                $pdfService = new SolarPlantBillingPdfService();
                                $pdfContent = $pdfService->generateBillingPdf($record);

                                // Dateiname erstellen
                                $customer = $record->customer;
                                $solarPlant = $record->solarPlant;

                                $plantName = preg_replace('/[^a-zA-Z0-9\-äöüÄÖÜß]/', '', str_replace(' ', '-', trim($solarPlant->name)));
                                $plantName = preg_replace('/-+/', '-', $plantName);
                                $plantName = trim($plantName, '-');

                                $customerName = $customer->customer_type === 'business' && $customer->company_name
                                    ? $customer->company_name
                                    : $customer->name;
                                $customerName = preg_replace('/[^a-zA-Z0-9\-äöüÄÖÜß]/', '', str_replace(' ', '-', trim($customerName)));
                                $customerName = preg_replace('/-+/', '-', $customerName);
                                $customerName = trim($customerName, '-');

                                $fileName = sprintf(
                                    'Abrechnung_%04d-%02d_%s_%s.pdf',
                                    $record->billing_year,
                                    $record->billing_month,
                                    $plantName,
                                    $customerName
                                );

                                // Temporäre Datei speichern
                                $tempPath = 'temp/email/' . uniqid() . '_' . $fileName;
                                \Storage::disk('public')->put($tempPath, $pdfContent);
                                $fullPath = \Storage::disk('public')->path($tempPath);

                                // Parse CC und BCC E-Mail-Adressen (Komma-getrennt)
                                $ccEmails = [];
                                if (!empty($data['email_cc'])) {
                                    $ccEmails = array_map('trim', explode(',', $data['email_cc']));
                                    $ccEmails = array_filter($ccEmails, function($email) {
                                        return filter_var($email, FILTER_VALIDATE_EMAIL);
                                    });
                                }

                                $bccEmails = [];
                                if (!empty($data['email_bcc'])) {
                                    $bccEmails = array_map('trim', explode(',', $data['email_bcc']));
                                    $bccEmails = array_filter($bccEmails, function($email) {
                                        return filter_var($email, FILTER_VALIDATE_EMAIL);
                                    });
                                }

                                // E-Mail senden
                                $customMessage = !empty($data['email_message']) ? $data['email_message'] : null;

                                $mail = Mail::to($data['email_recipient']);

                                // CC hinzufügen
                                if (!empty($ccEmails)) {
                                    $mail->cc($ccEmails);
                                }

                                // BCC hinzufügen
                                if (!empty($bccEmails)) {
                                    $mail->bcc($bccEmails);
                                }

                                $mail->send(new SingleBillingMail($record, $customMessage, $fullPath, $fileName));

                                // Temporäre Datei löschen
                                \Storage::disk('public')->delete($tempPath);

                                // Erfolgsmeldung mit CC/BCC Info
                                $recipients = $data['email_recipient'];
                                if (!empty($ccEmails)) {
                                    $recipients .= ' (CC: ' . implode(', ', $ccEmails) . ')';
                                }
                                if (!empty($bccEmails)) {
                                    $recipients .= ' (BCC: ' . implode(', ', $bccEmails) . ')';
                                }

                                Notification::make()
                                    ->title('E-Mail erfolgreich versendet')
                                    ->body("Die Abrechnung wurde per E-Mail gesendet an: {$recipients}")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler beim E-Mail-Versand')
                                    ->body('Die E-Mail konnte nicht gesendet werden: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->modalHeading('E-Mail Abrechnung senden')
                        ->modalDescription('Senden Sie die PDF-Abrechnung per E-Mail an den gewünschten Empfänger.')
                        ->modalSubmitActionLabel('E-Mail senden')
                        ->modalCancelActionLabel('Abbrechen')
                        ->modalWidth('2xl'),
                    Tables\Actions\EditAction::make(),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('change_status')
                        ->label('Status ändern')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Status auf Finalisiert ändern')
                        ->modalDescription(function (\Illuminate\Database\Eloquent\Collection $records): string {
                            $draftCount = $records->where('status', 'draft')->count();
                            $totalCount = $records->count();
                            $nonDraftCount = $totalCount - $draftCount;

                            $message = "Sie haben {$totalCount} Abrechnung(en) ausgewählt.\n\n";

                            if ($draftCount > 0) {
                                $message .= "{$draftCount} Abrechnung(en) mit Status 'Entwurf' können auf 'Finalisiert' geändert werden.\n";
                            }

                            if ($nonDraftCount > 0) {
                                $message .= "\n{$nonDraftCount} Abrechnung(en) haben einen anderen Status und werden übersprungen.\n";
                            }

                            $message .= "\nWichtig: Nur Abrechnungen mit Status 'Entwurf' werden auf 'Finalisiert' geändert.";

                            return $message;
                        })
                        ->modalSubmitActionLabel('Status ändern')
                        ->modalIcon('heroicon-o-arrow-path')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $updatedCount = 0;
                            $skippedCount = 0;

                            foreach ($records as $billing) {
                                if ($billing->status === 'draft') {
                                    $billing->update([
                                        'status' => 'finalized',
                                        'finalized_at' => now(),
                                    ]);
                                    $updatedCount++;
                                } else {
                                    $skippedCount++;
                                }
                            }

                            if ($updatedCount > 0) {
                                $message = "{$updatedCount} Abrechnung(en) wurden auf 'Finalisiert' gesetzt";
                                if ($skippedCount > 0) {
                                    $message .= " ({$skippedCount} übersprungen)";
                                }

                                Notification::make()
                                    ->title('Status erfolgreich geändert')
                                    ->body($message)
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Keine Änderungen')
                                    ->body('Keine der ausgewählten Abrechnungen hatte den Status "Entwurf".')
                                    ->warning()
                                    ->send();
                            }
                        }),

                    Tables\Actions\BulkAction::make('generate_qr_codes')
                        ->label('QR-Codes drucken')
                        ->icon('heroicon-o-qr-code')
                        ->color('info')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            try {
                                $epcQrCodeService = new \App\Services\EpcQrCodeService();
                                $successCount = 0;
                                $errorCount = 0;
                                $errors = [];
                                $billingIds = [];

                                // Lade notwendige Beziehungen
                                $records->load(['solarPlant', 'customer']);

                                foreach ($records as $billing) {
                                    try {
                                        // Prüfe ob QR-Code generiert werden kann
                                        if (!$epcQrCodeService->canGenerateQrCode($billing)) {
                                            $errorCount++;
                                            $errors[] = "Abrechnung {$billing->invoice_number}: " . $epcQrCodeService->getQrCodeErrorMessage($billing);
                                            continue;
                                        }

                                        // Speichere Billing ID für späteren Abruf
                                        $billingIds[] = $billing->id;
                                        $successCount++;

                                    } catch (\Exception $e) {
                                        $errorCount++;
                                        $errors[] = "Fehler bei Abrechnung {$billing->invoice_number}: " . $e->getMessage();
                                    }
                                }

                                // Wenn QR-Codes erfolgreich generiert wurden, öffne Druckansicht
                                if ($successCount > 0) {
                                    // Speichere Billing IDs in Session für Druckseite
                                    session([
                                        'bulk_qr_billing_ids' => $billingIds,
                                        'bulk_qr_success_count' => $successCount,
                                        'bulk_qr_error_count' => $errorCount
                                    ]);

                                    // Erfolgsmeldung
                                    $message = "{$successCount} QR-Code" . ($successCount !== 1 ? 's' : '') . " wurden erstellt und werden zum Drucken vorbereitet";
                                    if ($errorCount > 0) {
                                        $message .= " ({$errorCount} Fehler aufgetreten)";
                                    }

                                    $notification = Notification::make()
                                        ->title('QR-Codes werden zum Drucken geöffnet')
                                        ->body($message);

                                    if ($errorCount === 0) {
                                        $notification->success();
                                    } else {
                                        $notification->warning();
                                    }

                                    $notification->send();

                                    // Redirect zur Druckseite
                                    return redirect()->route('admin.print-qr-codes');

                                } else {
                                    // Nur Fehlermeldung wenn keine QR-Codes generiert wurden
                                    $notification = Notification::make()
                                        ->title('Keine QR-Codes generiert')
                                        ->body('Es konnten keine QR-Codes erstellt werden. Siehe Fehlerdetails.');

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
                                    ->title('Fehler bei QR-Code-Generierung')
                                    ->body('Ein unerwarteter Fehler ist aufgetreten: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('QR-Codes drucken')
                        ->modalDescription(function (\Illuminate\Database\Eloquent\Collection $records) use (&$epcQrCodeService): string {
                            $epcQrCodeService = new \App\Services\EpcQrCodeService();
                            $count = $records->count();
                            $validCount = 0;
                            $invalidReasons = [];

                            foreach ($records as $billing) {
                                if ($epcQrCodeService->canGenerateQrCode($billing)) {
                                    $validCount++;
                                } else {
                                    $invalidReasons[] = "• {$billing->invoice_number}: " . $epcQrCodeService->getQrCodeErrorMessage($billing);
                                }
                            }

                            $message = "Sie haben {$count} Abrechnung(en) ausgewählt.\n\n";

                            if ($validCount > 0) {
                                $message .= "{$validCount} QR-Code(s) können generiert werden.\n";
                            }

                            if ($count - $validCount > 0) {
                                $message .= "\n" . ($count - $validCount) . " Abrechnung(en) können nicht verarbeitet werden:\n";
                                $message .= implode("\n", array_slice($invalidReasons, 0, 5));
                                if (count($invalidReasons) > 5) {
                                    $message .= "\n... und " . (count($invalidReasons) - 5) . " weitere";
                                }
                            }

                            return $message;
                        })
                        ->modalSubmitActionLabel('QR-Codes generieren')
                        ->modalIcon('heroicon-o-qr-code'),

                    Tables\Actions\BulkAction::make('print_billings')
                        ->label('Abrechnungen drucken')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            try {
                                $billingIds = [];

                                // Sammle alle Billing IDs
                                foreach ($records as $billing) {
                                    $billingIds[] = $billing->id;
                                }

                                if (count($billingIds) > 0) {
                                    // Speichere Billing IDs in Session für Druckseite
                                    session([
                                        'bulk_billing_ids' => $billingIds,
                                        'bulk_billing_success_count' => count($billingIds),
                                    ]);

                                    // Erfolgsmeldung
                                    $message = count($billingIds) . " Abrechnung(en) werden zum Drucken vorbereitet";

                                    Notification::make()
                                        ->title('Abrechnungen werden zum Drucken geöffnet')
                                        ->body($message)
                                        ->success()
                                        ->send();

                                    // Redirect zur Druckseite
                                    return redirect()->route('admin.print-billings');
                                } else {
                                    Notification::make()
                                        ->title('Keine Abrechnungen ausgewählt')
                                        ->body('Bitte wählen Sie mindestens eine Abrechnung aus.')
                                        ->warning()
                                        ->send();
                                }

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler beim Drucken')
                                    ->body('Ein unerwarteter Fehler ist aufgetreten: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Abrechnungen drucken')
                        ->modalDescription(function (\Illuminate\Database\Eloquent\Collection $records): string {
                            $count = $records->count();
                            return "Sie haben {$count} Abrechnung(en) ausgewählt.\n\nDiese werden in einer Druckansicht geöffnet (eine Abrechnung pro Seite).";
                        })
                        ->modalSubmitActionLabel('Abrechnungen drucken')
                        ->modalIcon('heroicon-o-printer'),

                    Tables\Actions\BulkAction::make('export_csv')
                        ->label('CSV Export')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $selectedIds = $records->pluck('id')->toArray();

                            try {
                                // Prüfe ob Datensätze vorhanden sind
                                if (empty($selectedIds)) {
                                    Notification::make()
                                        ->title('Keine Datensätze ausgewählt')
                                        ->body('Bitte wählen Sie mindestens eine Abrechnung aus.')
                                        ->warning()
                                        ->send();
                                    return;
                                }

                                // Stelle sicher, dass die Relationen geladen sind
                                $billings = SolarPlantBilling::whereIn('id', $selectedIds)
                                    ->with(['solarPlant.participations', 'customer'])
                                    ->orderBy('created_at', 'desc')
                                    ->get();

                                if ($billings->isEmpty()) {
                                    Notification::make()
                                        ->title('Keine Abrechnungen gefunden')
                                        ->body('Die ausgewählten Abrechnungen konnten nicht gefunden werden.')
                                        ->warning()
                                        ->send();
                                    return;
                                }

                                // Erstelle CSV-Daten direkt (schnell, ohne Library)
                                $csv = [];

                                // Header
                                $csv[] = [
                                    'Anlagen-Nr.', 'Anlagenname', 'Anlagen-Standort', 'Anlagen-kWp',
                                    'Kunde', 'Kundentyp', 'Abrechnungsmonat', 'Abrechnungsjahr',
                                    'Rechnungsnummer', 'Produzierte Energie (kWh)', 'Beteiligung (%)',
                                    'Beteiligung (kWp)', 'Kosten (Brutto)', 'Kosten (Netto)',
                                    'Gutschriften (Brutto)', 'Gutschriften (Netto)', 'MwSt.-Betrag',
                                    'Gesamtbetrag (Brutto)', 'Anzahl Kostenpositionen', 'Anzahl Gutschriftspositionen',
                                    'Status', 'Bemerkung', 'Hinweistext anzeigen', 'Erstellt am'
                                ];

                                // Daten
                                $monthNames = [
                                    1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                                    5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                                ];

                                foreach ($billings as $billing) {
                                    $solarPlant = $billing->solarPlant;
                                    $customer = $billing->customer;

                                    // Beteiligung
                                    $currentPercentage = $billing->participation_percentage ?? 0;
                                    $currentKwp = null;
                                    if ($solarPlant && $customer && $solarPlant->participations) {
                                        $participation = $solarPlant->participations->where('customer_id', $customer->id)->first();
                                        if ($participation) {
                                            $currentPercentage = $participation->percentage ?? $currentPercentage;
                                            $currentKwp = $participation->participation_kwp;
                                        }
                                    }

                                    $customerName = $customer ? (($customer->customer_type === 'business' && $customer->company_name) ? $customer->company_name : $customer->name) : 'Unbekannt';
                                    $customerType = $customer ? ($customer->customer_type === 'business' ? 'Unternehmen' : 'Privatperson') : 'Unbekannt';
                                    $monthName = $monthNames[$billing->billing_month] ?? $billing->billing_month;

                                    $statusOptions = SolarPlantBilling::getStatusOptions();
                                    $status = $statusOptions[$billing->status] ?? $billing->status;

                                    $csv[] = [
                                        $solarPlant->plant_number ?? '',
                                        $solarPlant->name ?? '',
                                        $solarPlant->location ?? '',
                                        $solarPlant->total_kwp ?? '',
                                        $customerName,
                                        $customerType,
                                        $monthName,
                                        $billing->billing_year ?? '',
                                        $billing->invoice_number ?? '',
                                        $billing->produced_energy_kwh ?? '',
                                        $currentPercentage,
                                        $currentKwp ?? '',
                                        $billing->total_costs ?? 0,
                                        $billing->total_costs_net ?? 0,
                                        $billing->total_credits ?? 0,
                                        $billing->total_credits_net ?? 0,
                                        $billing->total_vat_amount ?? 0,
                                        $billing->net_amount ?? 0,
                                        is_array($billing->cost_breakdown) ? count($billing->cost_breakdown) : 0,
                                        is_array($billing->credit_breakdown) ? count($billing->credit_breakdown) : 0,
                                        $status,
                                        $billing->notes ?? '',
                                        $billing->show_hints ? 'Ja' : 'Nein',
                                        $billing->created_at ? $billing->created_at->format('d.m.Y H:i') : '',
                                    ];
                                }

                                // Speichere CSV
                                $filename = 'solaranlagen-abrechnungen-' . now()->format('Y-m-d_H-i-s') . '.csv';
                                $tempPath = 'temp/csv-exports/' . $filename;

                                // Stelle sicher, dass das Verzeichnis existiert
                                \Storage::disk('public')->makeDirectory('temp/csv-exports');

                                // Erstelle CSV-String mit UTF-8 BOM für Excel
                                $output = fopen('php://temp', 'r+');
                                fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM
                                foreach ($csv as $row) {
                                    fputcsv($output, $row, ';'); // Semikolon für Excel
                                }
                                rewind($output);
                                $csvContent = stream_get_contents($output);
                                fclose($output);

                                \Storage::disk('public')->put($tempPath, $csvContent);

                                // Speichere Download-Info in Session
                                session([
                                    'csv_download_path' => $tempPath,
                                    'csv_download_filename' => $filename,
                                ]);

                                // Erfolgsmeldung mit Download-Link
                                Notification::make()
                                    ->title('CSV-Export erfolgreich')
                                    ->body('Klicken Sie auf den Button, um die Datei herunterzuladen.')
                                    ->success()
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('download')
                                            ->label('Datei herunterladen')
                                            ->url(route('admin.download-csv'))
                                            ->openUrlInNewTab()
                                            ->button()
                                    ])
                                    ->persistent()
                                    ->send();

                            } catch (\Throwable $e) {
                                \Log::error('CSV Export Error', [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                    'selectedIds' => $selectedIds ?? [],
                                ]);

                                Notification::make()
                                    ->title('Fehler beim CSV-Export')
                                    ->body('Ein Fehler ist aufgetreten: ' . $e->getMessage())
                                    ->danger()
                                    ->duration(10000)
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('CSV Export')
                        ->modalDescription(function (\Illuminate\Database\Eloquent\Collection $records): string {
                            $count = $records->count();
                            return "Möchten Sie die {$count} ausgewählten Abrechnungen als CSV-Datei exportieren?\n\nDie CSV-Datei kann in Excel geöffnet werden.";
                        })
                        ->modalSubmitActionLabel('CSV exportieren')
                        ->modalIcon('heroicon-o-document-arrow-down'),
                    
                    Tables\Actions\BulkAction::make('generate_pdfs')
                        ->label('PDF Abrechnungen generieren')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('primary')
                        ->form([
                            Forms\Components\Checkbox::make('include_qr_code')
                                ->label('Inklusive QR-Code Deckblatt')
                                ->helperText('Erstellt automatisch ein QR-Code Deckblatt für jede Abrechnung, wenn Banking-Daten verfügbar sind.')
                                ->default(false),
                            
                            Forms\Components\Checkbox::make('send_emails')
                                ->label('Automatischer E-Mail Versand')
                                ->helperText('Sendet die generierten PDFs automatisch an die konfigurierten E-Mail-Adressen.')
                                ->default(false)
                                ->live()
                                ->columnSpanFull(),
                            
                            Forms\Components\Section::make('E-Mail-Adressen')
                                ->schema([
                                    Forms\Components\TextInput::make('email_1')
                                        ->label('E-Mail-Adresse 1')
                                        ->email()
                                        ->default(env('MAIL_1_PDF_ABRECHNUNG'))
                                        ->placeholder('erste@email.de'),
                                    
                                    Forms\Components\TextInput::make('email_2')
                                        ->label('E-Mail-Adresse 2')
                                        ->email()
                                        ->default(env('MAIL_2_PDF_ABRECHNUNG'))
                                        ->placeholder('zweite@email.de'),
                                    
                                    Forms\Components\TextInput::make('email_3')
                                        ->label('E-Mail-Adresse 3')
                                        ->email()
                                        ->default(env('MAIL_3_PDF_ABRECHNUNG'))
                                        ->placeholder('dritte@email.de'),
                                ])
                                ->columns(3)
                                ->visible(fn ($get) => $get('send_emails')),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            try {
                                $pdfService = new \App\Services\SolarPlantBillingPdfService();
                                $epcQrCodeService = new \App\Services\EpcQrCodeService();
                                $successCount = 0;
                                $errorCount = 0;
                                $errors = [];
                                $downloadUrls = [];
                                
                                // Lade notwendige Beziehungen
                                $records->load(['solarPlant', 'customer']);
                                
                                // Erstelle Session-ID für diese Batch
                                $batchId = uniqid('pdf_batch_');
                                
                                // Prüfe ob QR-Code-Option aktiviert ist
                                $includeQrCode = $data['include_qr_code'] ?? false;
                                
                                foreach ($records as $billing) {
                                    try {
                                        // Verwende exakt dieselbe Logik und denselben Service wie der Einzeldownload
                                        $billing->load(['solarPlant', 'customer']);
                                        
                                        // Verwende den gleichen Service wie bei der Einzelgenerierung für konsistente Ergebnisse
                                        $pdfContent = $pdfService->generateBillingPdf($billing);
                                        
                        // Erstelle Dateiname - hole Daten für aktuelle Abrechnung
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
                                        
                                        // Erstelle Download-URL für Hauptabrechnung
                                        $downloadUrls[] = [
                                            'filename' => $filename,
                                            'url' => Storage::disk('public')->url($tempPath),
                                            'customer_name' => $customerName,
                                            'period' => sprintf('%04d-%02d', $billing->billing_year, $billing->billing_month),
                                            'status' => 'success'
                                        ];
                                        
                        // QR-Code PDF generieren falls gewünscht und möglich
                        if ($includeQrCode && $epcQrCodeService->canGenerateQrCode($billing)) {
                            try {
                                // Hole aktuellen Kunden für diese spezifische Abrechnung
                                $currentCustomer = $billing->customer;
                                
                                // Generate QR code (base64 encoded image)
                                $qrCodeImage = $epcQrCodeService->generateEpcQrCode($billing);
                                
                                // Get customer and billing data for QR code
                                $amount = abs($billing->net_amount);
                                
                                // Generate reference/purpose
                                $reference = $epcQrCodeService->getDefaultReference($billing);
                                
                                // Prepare data structure that the template expects
                                $qrCodeData = [
                                    'qrCode' => $qrCodeImage,
                                    'data' => [
                                        'beneficiaryName' => $currentCustomer->account_holder,
                                        'beneficiaryAccount' => strtoupper(str_replace(' ', '', $currentCustomer->iban)),
                                        'beneficiaryBIC' => $currentCustomer->bic ?: '',
                                        'remittanceInformation' => $reference,
                                        'amount' => $amount,
                                    ]
                                ];
                                                
                                // Generate QR-Code PDF using exakt the same view and settings as individual QR-Code print
                                // to ensure identical design and layout
                                $qrPdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('print.qr-code-banking', [
                                    'solarPlantBilling' => $billing,
                                    'customer' => $currentCustomer,
                                    'qrCodeData' => $qrCodeData,
                                ])
                                                ->setPaper('a4', 'portrait')
                                                ->setOptions([
                                                    'defaultFont' => '-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif',
                                                    'isHtml5ParserEnabled' => true,
                                                    'isPhpEnabled' => true,
                                                    'debugKeepTemp' => false,
                                                    'isRemoteEnabled' => false,
                                                    'chroot' => public_path(),
                                                ]);
                                                
                                                $qrPdfContent = $qrPdf->output();
                                                
                                                // QR-Code Dateiname mit Prefix
                                                $qrFilename = sprintf(
                                                    '%04d-%02d_%s_%s_QR-Code.pdf',
                                                    $billing->billing_year,
                                                    $billing->billing_month,
                                                    $plantName,
                                                    $customerName
                                                );
                                                
                                                // Speichere QR-Code PDF temporär
                                                $qrTempPath = "temp/bulk-pdfs/{$batchId}/{$qrFilename}";
                                                Storage::disk('public')->put($qrTempPath, $qrPdfContent);
                                                
                                                // Erstelle Download-URL für QR-Code PDF
                                                $downloadUrls[] = [
                                                    'filename' => $qrFilename,
                                                    'url' => Storage::disk('public')->url($qrTempPath),
                                                    'customer_name' => $customerName,
                                                    'period' => sprintf('%04d-%02d', $billing->billing_year, $billing->billing_month),
                                                    'status' => 'success',
                                                    'type' => 'qr-code'
                                                ];
                                                
                                            } catch (\Exception $qrError) {
                                                // QR-Code Fehler sammeln, aber nicht abbrechen
                                                $errors[] = "QR-Code für Abrechnung {$billing->id} konnte nicht erstellt werden: " . $qrError->getMessage();
                                            }
                                        }
                                        
                                        $successCount++;
                                        
                                    } catch (\Exception $e) {
                                        $errorCount++;
                                        $errors[] = "Fehler bei Abrechnung {$billing->id}: " . $e->getMessage();
                                    }
                                }
                                
                                // Email-Versand verarbeiten falls aktiviert
                                $emailSent = false;
                                if ($data['send_emails'] ?? false) {
                                    try {
                                        // Sammle E-Mail-Adressen aus dem Formular
                                        $emailAddresses = array_filter([
                                            $data['email_1'] ?? null,
                                            $data['email_2'] ?? null,
                                            $data['email_3'] ?? null,
                                        ]);
                                        
                                        if (!empty($emailAddresses)) {
                                            // Konvertiere Download-URLs zu PDF-Files Array für E-Mail
                                            $pdfFiles = [];
                                            foreach ($downloadUrls as $downloadUrl) {
                                                if ($downloadUrl['status'] === 'success') {
                                                    // Konvertiere URL zurück zum lokalen Pfad
                                                    $relativePath = str_replace(Storage::disk('public')->url(''), '', $downloadUrl['url']);
                                                    $fullPath = Storage::disk('public')->path($relativePath);
                                                    
                                                    if (file_exists($fullPath)) {
                                                        $pdfFiles[] = [
                                                            'path' => $fullPath,
                                                            'name' => $downloadUrl['filename']
                                                        ];
                                                    }
                                                }
                                            }
                                            
                                            if (!empty($pdfFiles)) {
                                                // Versende E-Mails an alle konfigurierten Adressen
                                                $totalCount = count($pdfFiles);
                                                $bulkPdfMail = new \App\Mail\BulkPdfMail($pdfFiles, $totalCount);
                                                
                                                foreach ($emailAddresses as $email) {
                                                    try {
                                                        \Mail::to($email)->send($bulkPdfMail);
                                                    } catch (\Exception $mailError) {
                                                        \Log::error('Email sending failed', [
                                                            'email' => $email,
                                                            'error' => $mailError->getMessage()
                                                        ]);
                                                        $errors[] = "E-Mail an {$email} konnte nicht gesendet werden: " . $mailError->getMessage();
                                                    }
                                                }
                                                
                                                $emailSent = true;
                                                
                                                Notification::make()
                                                    ->title('E-Mails versendet')
                                                    ->body("PDFs wurden an " . count($emailAddresses) . " E-Mail-Adresse(n) gesendet.")
                                                    ->success()
                                                    ->send();
                                            }
                                        }
                                    } catch (\Exception $emailError) {
                                        $errors[] = "E-Mail-Versand fehlgeschlagen: " . $emailError->getMessage();
                                        
                                        Notification::make()
                                            ->title('E-Mail-Versand fehlgeschlagen')
                                            ->body($emailError->getMessage())
                                            ->danger()
                                            ->send();
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
                                    if ($emailSent) {
                                        $message .= " und per E-Mail versendet";
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
            ->defaultSort('created_at', 'desc')
            ->poll('10s')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->selectCurrentPageOnly()
            ->checkIfRecordIsSelectableUsing(
                fn (\App\Models\SolarPlantBilling $record): bool => true,
            );
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
