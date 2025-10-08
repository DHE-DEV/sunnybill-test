<?php

namespace App\Exports;

use App\Models\SolarPlantBilling;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Database\Eloquent\Builder;

class SolarPlantBillingsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $selectedIds;

    public function __construct(array $selectedIds = [])
    {
        $this->selectedIds = $selectedIds;
    }

    public function query()
    {
        $query = SolarPlantBilling::query()
            ->with([
                'solarPlant.participations',
                'customer'
            ])
            ->orderBy('created_at', 'desc');

        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Anlagen-Nr.',
            'Anlagenname', 
            'Anlagen-Standort',
            'Anlagen-kWp',
            'Kunde',
            'Kundentyp',
            'Abrechnungsmonat',
            'Abrechnungsjahr',
            'Rechnungsnummer',
            'Produzierte Energie (kWh)',
            'Beteiligung (%)',
            'Beteiligung (kWp)',
            'Kosten (Brutto)',
            'Kosten (Netto)',
            'Gutschriften (Brutto)',
            'Gutschriften (Netto)',
            'MwSt.-Betrag',
            'Gesamtbetrag (Brutto)',
            'Anzahl Kostenpositionen',
            'Anzahl Gutschriftspositionen',
            'Status',
            'Bemerkung',
            'Hinweistext anzeigen',
            'Erstellt am',
        ];
    }

    public function map($billing): array
    {
        try {
            // Sichere Datenextraktion mit Fallback-Werten
            $solarPlant = $billing->solarPlant;
            $customer = $billing->customer;
            
            // Hole aktuelle Beteiligung aus den bereits geladenen participations (sicher)
            $currentPercentage = $billing->participation_percentage ?? 0;
            $currentKwp = null;

            try {
                if ($solarPlant && $customer && $solarPlant->participations) {
                    // Nutze bereits geladene Relation statt neuer Query
                    $participation = $solarPlant->participations->where('customer_id', $customer->id)->first();

                    if ($participation) {
                        $currentPercentage = $participation->percentage ?? $billing->participation_percentage ?? 0;
                        $currentKwp = $participation->participation_kwp ?? null;
                    }
                }
            } catch (\Exception $e) {
                // Verwende Fallback-Werte bei Fehler
                $currentPercentage = $billing->participation_percentage ?? 0;
                $currentKwp = null;
            }

            // Kundennamen ermitteln (sicher)
            $customerName = 'Unbekannt';
            if ($customer) {
                $customerName = ($customer->customer_type === 'business' && !empty($customer->company_name))
                    ? $customer->company_name 
                    : ($customer->name ?? 'Unbekannt');
            }

            // Kundentyp übersetzen (sicher)
            $customerType = 'Unbekannt';
            if ($customer && $customer->customer_type) {
                $customerType = $customer->customer_type === 'business' ? 'Unternehmen' : 'Privatperson';
            }

            // Monatsnamen (sicher)
            $monthNames = [
                1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
            ];
            $monthName = $monthNames[$billing->billing_month] ?? (string)$billing->billing_month;

            // Status übersetzen (sicher)
            $status = $billing->status ?? 'draft';
            try {
                $statusOptions = SolarPlantBilling::getStatusOptions();
                $status = $statusOptions[$billing->status] ?? $billing->status ?? 'Unbekannt';
            } catch (\Exception $e) {
                $status = $billing->status ?? 'Unbekannt';
            }

            // Kostenpositionen und Gutschriftenpositionen zählen (sicher)
            $costCount = 0;
            $creditCount = 0;
            
            try {
                $costCount = is_array($billing->cost_breakdown) ? count($billing->cost_breakdown) : 0;
                $creditCount = is_array($billing->credit_breakdown) ? count($billing->credit_breakdown) : 0;
            } catch (\Exception $e) {
                // Verwende 0 als Fallback
            }

            // Sichere Formatierung numerischer Werte
            $formatNumber = function($value, $default = '0,00') {
                try {
                    return $value !== null ? number_format((float)$value, 2, ',', '.') : $default;
                } catch (\Exception $e) {
                    return $default;
                }
            };

            return [
                $solarPlant->plant_number ?? '',
                $solarPlant->name ?? '',
                $solarPlant->location ?? '',
                $solarPlant->total_kwp ? $formatNumber($solarPlant->total_kwp) : '',
                $customerName,
                $customerType,
                $monthName,
                $billing->billing_year ?? '',
                $billing->invoice_number ?? '',
                $billing->produced_energy_kwh ? $formatNumber($billing->produced_energy_kwh, '') : '',
                $currentPercentage ? $formatNumber($currentPercentage) : '',
                $currentKwp ? $formatNumber($currentKwp) : '',
                $formatNumber($billing->total_costs),
                $formatNumber($billing->total_costs_net),
                $formatNumber($billing->total_credits),
                $formatNumber($billing->total_credits_net),
                $formatNumber($billing->total_vat_amount),
                $formatNumber($billing->net_amount),
                (string)$costCount,
                (string)$creditCount,
                $status,
                $billing->notes ?? '',
                $billing->show_hints ? 'Ja' : 'Nein',
                $billing->created_at ? $billing->created_at->format('d.m.Y H:i') : '',
            ];
            
        } catch (\Exception $e) {
            // Fallback für kompletten Fehler
            return [
                'Fehler',
                'Daten konnten nicht geladen werden',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '0,00',
                '0,00',
                '0,00',
                '0,00',
                '0,00',
                '0,00',
                '0',
                '0',
                'Fehler',
                'Fehler beim Laden der Daten: ' . $e->getMessage(),
                'Nein',
                '',
            ];
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header-Stil
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FF4472C4',
                    ],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'font' => [
                    'color' => [
                        'argb' => 'FFFFFFFF',
                    ],
                    'bold' => true,
                ],
            ],
            
            // Alle Zeilen - Grundstil
            'A:Z' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFD0D0D0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true,
                ],
            ],

            // Numerische Spalten rechtsbündig
            'H:R' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    'vertical' => Alignment::VERTICAL_TOP,
                ],
            ],

            // Datum-Spalte rechtsbündig  
            'X' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    'vertical' => Alignment::VERTICAL_TOP,
                ],
            ],
        ];
    }
}
