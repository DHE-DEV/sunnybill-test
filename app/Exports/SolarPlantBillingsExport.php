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
            ->with(['solarPlant', 'customer'])
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
        // Hole aktuelle Beteiligung aus der participations Tabelle
        $participation = $billing->solarPlant->participations()
            ->where('customer_id', $billing->customer_id)
            ->first();

        $currentPercentage = $participation ? $participation->percentage : $billing->participation_percentage;
        $currentKwp = $participation ? $participation->participation_kwp : null;

        // Kundennamen ermitteln
        $customerName = $billing->customer->customer_type === 'business' && $billing->customer->company_name 
            ? $billing->customer->company_name 
            : $billing->customer->name;

        // Kundentyp übersetzen
        $customerType = $billing->customer->customer_type === 'business' ? 'Unternehmen' : 'Privatperson';

        // Monatsnamen
        $monthNames = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
        ];
        $monthName = $monthNames[$billing->billing_month] ?? $billing->billing_month;

        // Status übersetzen
        $statusOptions = SolarPlantBilling::getStatusOptions();
        $status = $statusOptions[$billing->status] ?? $billing->status;

        // Kostenpositionen und Gutschriftenpositionen zählen
        $costCount = $billing->cost_breakdown ? count($billing->cost_breakdown) : 0;
        $creditCount = $billing->credit_breakdown ? count($billing->credit_breakdown) : 0;

        return [
            $billing->solarPlant->plant_number ?? '',
            $billing->solarPlant->name ?? '',
            $billing->solarPlant->location ?? '',
            $billing->solarPlant->total_kwp ?? '',
            $customerName,
            $customerType,
            $monthName,
            $billing->billing_year,
            $billing->invoice_number ?? '',
            $billing->produced_energy_kwh ?? '',
            $currentPercentage ? number_format($currentPercentage, 2, ',', '.') : '',
            $currentKwp ? number_format($currentKwp, 2, ',', '.') : '',
            $billing->total_costs ? number_format($billing->total_costs, 2, ',', '.') : '0,00',
            $billing->total_costs_net ? number_format($billing->total_costs_net, 2, ',', '.') : '0,00',
            $billing->total_credits ? number_format($billing->total_credits, 2, ',', '.') : '0,00',
            $billing->total_credits_net ? number_format($billing->total_credits_net, 2, ',', '.') : '0,00',
            $billing->total_vat_amount ? number_format($billing->total_vat_amount, 2, ',', '.') : '0,00',
            $billing->net_amount ? number_format($billing->net_amount, 2, ',', '.') : '0,00',
            $costCount,
            $creditCount,
            $status,
            $billing->notes ?? '',
            $billing->show_hints ? 'Ja' : 'Nein',
            $billing->created_at ? $billing->created_at->format('d.m.Y H:i') : '',
        ];
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
