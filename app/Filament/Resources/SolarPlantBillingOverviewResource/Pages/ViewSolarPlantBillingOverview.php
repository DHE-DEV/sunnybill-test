<?php

namespace App\Filament\Resources\SolarPlantBillingOverviewResource\Pages;

use App\Filament\Resources\SolarPlantBillingOverviewResource;
use App\Filament\Resources\SupplierContractBillingResource;
use App\Models\SolarPlant;
use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Forms;
use Carbon\Carbon;

class ViewSolarPlantBillingOverview extends ViewRecord
{
    protected static string $resource = SolarPlantBillingOverviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Zurück zur Übersicht')
                ->icon('heroicon-o-arrow-left')
                ->url(SolarPlantBillingOverviewResource::getUrl('index')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Solaranlagen-Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('plant_number')
                            ->label('Anlagennummer'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('Anlagenname'),
                        Infolists\Components\TextEntry::make('location')
                            ->label('Standort'),
                        Infolists\Components\TextEntry::make('active_contracts_count')
                            ->label('Aktive Lieferantenverträge')
                            ->getStateUsing(fn (SolarPlant $record) => $record->activeSupplierContracts()->count())
                            ->badge()
                            ->color('info'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Abrechnungsübersicht ab Januar 2025')
                    ->headerActions([
                        \Filament\Infolists\Components\Actions\Action::make('refresh')
                            ->label('Aktualisieren')
                            ->icon('heroicon-o-arrow-path')
                            ->color('gray')
                            ->action(function () {
                                // Livewire refresh - aktualisiert die gesamte Komponente
                                $this->dispatch('$refresh');
                            })
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('billing_table')
                            ->label('')
                            ->getStateUsing(function (SolarPlant $record) {
                                $html = '<div style="overflow-x: auto;">';
                                $html .= '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
                                $html .= '<thead>';
                                $html .= '<tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">';
                                $html .= '<th style="padding: 12px; text-align: left; font-weight: bold; border: 1px solid #dee2e6;">Monat</th>';
                                $html .= '<th style="padding: 12px; text-align: center; font-weight: bold; border: 1px solid #dee2e6;">Status</th>';
                                $html .= '<th style="padding: 12px; text-align: center; font-weight: bold; border: 1px solid #dee2e6;">Fehlende Abrechnungen</th>';
                                $html .= '<th style="padding: 12px; text-align: left; font-weight: bold; border: 1px solid #dee2e6;">Fehlende Verträge (Lieferant)</th>';
                                $html .= '</tr>';
                                $html .= '</thead>';
                                $html .= '<tbody>';
                                
                                // Start from January 2025 and show months up to current month
                                $startDate = Carbon::create(2025, 1, 1); // Januar 2025
                                $currentDate = now();
                                $monthsToShow = $startDate->diffInMonths($currentDate) + 1; // +1 to include current month
                                
                                // Create array of all months from January 2025 to current month
                                $months = [];
                                for ($i = 0; $i < $monthsToShow; $i++) {
                                    $date = $startDate->copy()->addMonths($i);
                                    // Stop if we've reached future months
                                    if ($date->isAfter($currentDate)) {
                                        break;
                                    }
                                    $months[] = $date;
                                }
                                
                                // Reverse the array so newest month appears first (top)
                                $months = array_reverse($months);
                                
                                // Iterate through months in reversed order (newest first)
                                foreach ($months as $date) {
                                    $month = $date->format('Y-m');
                                    $status = SolarPlantBillingOverviewResource::getBillingStatusForMonth($record, $month);
                                    $missing = SolarPlantBillingOverviewResource::getMissingBillingsForMonth($record, $month);
                                    
                                    $statusIcon = match($status) {
                                        'Vollständig' => '✅',
                                        'Unvollständig' => '⚠️',
                                        'Keine Verträge' => '⚪',
                                        default => '❓'
                                    };
                                    
                                    $statusColor = match($status) {
                                        'Vollständig' => '#d4edda',
                                        'Unvollständig' => '#fff3cd',
                                        'Keine Verträge' => '#f8f9fa',
                                        default => '#f8f9fa'
                                    };
                                    
                                    $html .= '<tr style="border-bottom: 1px solid #dee2e6; background-color: ' . $statusColor . ';">';
                                    $html .= '<td style="padding: 10px; border: 1px solid #dee2e6; font-weight: 500;">' . $date->locale('de')->translatedFormat('F Y') . '</td>';
                                    $html .= '<td style="padding: 10px; border: 1px solid #dee2e6; text-align: center;">';
                                    $html .= '<span style="display: inline-flex; align-items: center; gap: 5px;">';
                                    $html .= $statusIcon . ' ' . $status;
                                    $html .= '</span>';
                                    $html .= '</td>';
                                    $html .= '<td style="padding: 10px; border: 1px solid #dee2e6; text-align: center; font-weight: bold;">';
                                    
                                    if ($missing->count() > 0) {
                                        $html .= '<span style="color: #dc3545;">' . $missing->count() . '</span>';
                                    } else {
                                        $html .= '<span style="color: #28a745;">0</span>';
                                    }
                                    
                                    $html .= '</td>';
                                    $html .= '<td style="padding: 10px; border: 1px solid #dee2e6;">';
                                    
                                    if ($missing->count() > 0) {
                                        $missingDetails = [];
                                        foreach ($missing as $contract) {
                                            $supplierName = $contract->supplier ? $contract->supplier->display_name : 'Unbekannt';
                                            $year = (int) substr($month, 0, 4);
                                            $monthNumber = (int) substr($month, 5, 2);
                                            $monthFormatted = str_pad($monthNumber, 2, '0', STR_PAD_LEFT);
                                            $title = "Abrechnung {$year}-{$monthFormatted} - {$contract->title}";
                                            
                                            // Vereinfachtes JavaScript ohne komplexes Escaping
                                            $contractData = [
                                                'id' => $contract->id,
                                                'year' => $year,
                                                'month' => $monthNumber,
                                                'title' => $title,
                                                'contractTitle' => $contract->title,
                                                'supplierName' => $supplierName
                                            ];
                                            $dataJson = htmlspecialchars(json_encode($contractData), ENT_QUOTES, 'UTF-8');
                                            
                                            // Erstelle direkten Link zu supplier-contracts mit activeRelationManager=1
                                            $contractUrl = '/admin/supplier-contracts/' . $contract->id . '?activeRelationManager=1';
                                            $missingDetails[] = '<a href="' . $contractUrl . '" class="missing-billing-link" style="display: inline-block; margin: 2px 5px 2px 0; padding: 3px 8px; background-color: #f8d7da; color: #721c24; border-radius: 4px; font-size: 12px; text-decoration: none; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor=\'#f5c6cb\'" onmouseout="this.style.backgroundColor=\'#f8d7da\'">' . htmlspecialchars($contract->title) . ' (' . htmlspecialchars($supplierName) . ')</a>';
                                        }
                                        $html .= implode(' ', $missingDetails);
                                    } else {
                                        $html .= '<span style="color: #6c757d; font-style: italic;">Alle Abrechnungen vollständig</span>';
                                    }
                                    
                                    $html .= '</td>';
                                    $html .= '</tr>';
                                }
                                
                                $html .= '</tbody>';
                                $html .= '</table>';
                                $html .= '</div>';
                                
                                // JavaScript nicht mehr benötigt, da wir direkte Links verwenden
                                
                                return $html;
                            })
                            ->html(),
                    ]),

                Infolists\Components\Section::make('Aktive Lieferantenverträge')
                    ->schema([
                        Infolists\Components\TextEntry::make('contracts_overview')
                            ->label('')
                            ->getStateUsing(function (SolarPlant $record) {
                                $contracts = $record->activeSupplierContracts()->with('supplier')->get();

                                if ($contracts->isEmpty()) {
                                    return 'Keine aktiven Verträge vorhanden.';
                                }

                                $html = '<div style="line-height: 1.6;">';

                                foreach ($contracts as $contract) {
                                    $currentMonth = now();

                                    // Prüfe ob Vertrag für aktuellen Monat gültig ist
                                    $isValidForMonth = true;
                                    $validityInfo = '';

                                    if (!$contract->is_active) {
                                        $isValidForMonth = false;
                                        $validityInfo = ' <span style="color: #6c757d; font-size: 12px;">(Inaktiv)</span>';
                                    } else {
                                        if ($contract->start_date && $currentMonth->isBefore($contract->start_date->startOfMonth())) {
                                            $isValidForMonth = false;
                                            $validityInfo = ' <span style="color: #6c757d; font-size: 12px;">(Noch nicht gestartet)</span>';
                                        }

                                        if ($contract->end_date && $currentMonth->isAfter($contract->end_date->endOfMonth())) {
                                            $isValidForMonth = false;
                                            $validityInfo = ' <span style="color: #6c757d; font-size: 12px;">(Beendet)</span>';
                                        }
                                    }

                                    $billing = $contract->billings()
                                        ->where('billing_year', $currentMonth->year)
                                        ->where('billing_month', $currentMonth->month)
                                        ->first();

                                    // Status basierend auf Gültigkeit und Abrechnung
                                    if (!$isValidForMonth) {
                                        $status = '⚪ Nicht relevant' . $validityInfo;
                                    } else {
                                        $status = $billing ? '✅ Vorhanden' : '❌ Fehlt';
                                    }

                                    $contractUrl = '/admin/supplier-contracts/' . $contract->id;

                                    $html .= '<div style="margin-bottom: 8px;">';
                                    $html .= '<a href="' . $contractUrl . '" style="color: #3b82f6; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration=\'underline\'" onmouseout="this.style.textDecoration=\'none\'">';
                                    $html .= htmlspecialchars($contract->contract_number . ' - ' . $contract->title);
                                    $html .= '</a>';
                                    $html .= ' (' . htmlspecialchars($contract->supplier->company_name) . '): ' . $status;
                                    $html .= '</div>';
                                }

                                $html .= '</div>';

                                return $html;
                            })
                            ->html(),
                    ]),
            ]);
    }

    public function getTitle(): string
    {
        return "Abrechnungsübersicht: {$this->record->plant_number} - {$this->record->name}";
    }

}
