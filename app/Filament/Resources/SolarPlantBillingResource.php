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
                                
                                return "Nummer: {$plant->plant_number}\nStandort: {$plant->location}";
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

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(SolarPlantBilling::getStatusOptions())
                            ->default('draft')
                            ->required(),
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
                                $html .= '<th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #374151;">Dok.</th>';
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #374151;">Anteil</th>';
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #374151;">Gesamtbetrag</th>';
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
                                    $html .= '<td style="padding: 0.75rem; color: #374151;">';
                                    $html .= '<div style="font-weight: 500;">' . htmlspecialchars($item['contract_title']) . '</div>';
                                    $html .= '<div style="font-size: 0.875rem; color: #6b7280;">';
                                    $html .= 'Lieferant: ' . $supplierName . ' | Abrechnungsnr.: ' . $billingNumber;
                                    $html .= '</div>';
                                    $html .= '</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: center;">';
                                    if ($docs->isNotEmpty()) {
                                        $html .= '<a href="#" onclick="window.Livewire.dispatch(\'openModal\', { component: \'filament.modals.document-list-modal\', arguments: { documents: ' . htmlspecialchars(json_encode($docs->toArray())) . ' } })" class="text-primary-600 hover:text-primary-500">';
                                        $html .= '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                                        $html .= '</a>';
                                    }
                                    $html .= '</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; color: #374151;">' . number_format($item['solar_plant_percentage'], 2, ',', '.') . '%</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; font-weight: 500; color: #374151;">' . number_format($item['customer_share'], 2, ',', '.') . ' €</td>';
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
                                $html .= '<th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #166534;">Dok.</th>';
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #166534;">Anteil</th>';
                                $html .= '<th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #166534;">Gesamtbetrag</th>';
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
                                    $html .= '</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: center;">';
                                    if ($docs->isNotEmpty()) {
                                        $html .= '<a href="#" onclick="window.Livewire.dispatch(\'openModal\', { component: \'filament.modals.document-list-modal\', arguments: { documents: ' . htmlspecialchars(json_encode($docs->toArray())) . ' } })" class="text-primary-600 hover:text-primary-500">';
                                        $html .= '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                                        $html .= '</a>';
                                    }
                                    $html .= '</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; color: #166534;">' . number_format($item['solar_plant_percentage'], 2, ',', '.') . '%</td>';
                                    $html .= '<td style="padding: 0.75rem; text-align: right; font-weight: 500; color: #166534;">' . number_format($item['customer_share'], 2, ',', '.') . ' €</td>';
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
                        Forms\Components\TextInput::make('total_costs')
                            ->label('Gesamtkosten')
                            ->prefix('€')
                            ->numeric()
                            ->step(0.01)
                            ->default(0),

                        Forms\Components\TextInput::make('total_credits')
                            ->label('Gesamtgutschriften')
                            ->prefix('€')
                            ->numeric()
                            ->step(0.01)
                            ->default(0),

                        Forms\Components\TextInput::make('net_amount')
                            ->label('Gesamtbetrag')
                            ->prefix('€')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Forms\Components\Section::make('Zusätzliche Informationen')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3),
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
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_month')
                    ->label('Abrechnungsmonat')
                    ->sortable(['billing_year', 'billing_month']),

                Tables\Columns\TextColumn::make('participation_percentage')
                    ->label('Beteiligung')
                    ->suffix('%')
                    ->numeric(2)
                    ->alignRight(),

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
                    ])
                    ->action(function (array $data) {
                        try {
                            $billings = SolarPlantBilling::createBillingsForMonth(
                                $data['solar_plant_id'],
                                $data['billing_year'],
                                $data['billing_month']
                            );

                            $count = count($billings);
                            $monthName = Carbon::createFromDate($data['billing_year'], $data['billing_month'], 1)
                                ->locale('de')
                                ->translatedFormat('F Y');

                            Notification::make()
                                ->title('Abrechnungen erfolgreich erstellt')
                                ->body("{$count} Abrechnungen für {$monthName} wurden erstellt.")
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
