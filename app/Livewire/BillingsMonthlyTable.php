<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\SolarPlantBilling;
use App\Models\SolarPlant;
use App\Models\SupplierContractBilling;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Contracts\View\View;

class BillingsMonthlyTable extends Component
{
    public SolarPlant $solarPlant;

    public ?int $detailYear = null;
    public ?int $detailMonth = null;
    public bool $showDetail = false;

    // Create billing modal
    public bool $showCreateBilling = false;
    public ?int $createBillingYear = null;
    public ?int $createBillingMonth = null;
    public ?string $producedEnergyKwh = null;
    public string $billingNotes = 'Diese Rechnung / Gutschrift, wurde maschinell erstellt und bedarf keiner Unterschrift. Wir legen höchsten Wert auf Transparenz und hoffen, dass wir ihnen die abrechnungsrelevanten Positionen klar und einfach verständlich erläutern konnten. Sollten Sie noch weitere Informationen zu Ihrer Abrechnung wünschen, rufen Sie uns gerne unter 02234-4300614 an oder schreiben Sie uns  eine Mail mit Ihrem Anliegen an: abrechnung@prosoltec-anlagenbetreiber.de';
    public bool $showHints = false;

    public function mount(SolarPlant $solarPlant): void
    {
        $this->solarPlant = $solarPlant;
    }

    public function openDetail(int $year, int $month): void
    {
        $this->detailYear = $year;
        $this->detailMonth = $month;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailYear = null;
        $this->detailMonth = null;
    }

    public function openCreateBilling(int $year, int $month): void
    {
        $this->createBillingYear = $year;
        $this->createBillingMonth = $month;
        $this->producedEnergyKwh = null;
        $this->billingNotes = 'Diese Rechnung / Gutschrift, wurde maschinell erstellt und bedarf keiner Unterschrift. Wir legen höchsten Wert auf Transparenz und hoffen, dass wir ihnen die abrechnungsrelevanten Positionen klar und einfach verständlich erläutern konnten. Sollten Sie noch weitere Informationen zu Ihrer Abrechnung wünschen, rufen Sie uns gerne unter 02234-4300614 an oder schreiben Sie uns  eine Mail mit Ihrem Anliegen an: abrechnung@prosoltec-anlagenbetreiber.de';
        $this->showHints = false;
        $this->showCreateBilling = true;
    }

    public function closeCreateBilling(): void
    {
        $this->showCreateBilling = false;
        $this->createBillingYear = null;
        $this->createBillingMonth = null;
    }

    public function createBilling(): void
    {
        try {
            $billings = SolarPlantBilling::createBillingsForMonth(
                $this->solarPlant->id,
                $this->createBillingYear,
                $this->createBillingMonth,
                $this->producedEnergyKwh ? (float) $this->producedEnergyKwh : null
            );

            if (!empty($this->billingNotes) || $this->showHints !== null) {
                foreach ($billings as $billing) {
                    $updateData = [];
                    if (!empty($this->billingNotes)) {
                        $updateData['notes'] = $this->billingNotes;
                    }
                    $updateData['show_hints'] = $this->showHints;
                    $billing->update($updateData);
                }
            }

            $count = count($billings);
            $monthNames = [
                1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
            ];
            $monthName = $monthNames[$this->createBillingMonth] . ' ' . $this->createBillingYear;

            session()->flash('billing-success', "{$count} Abrechnungen für {$monthName} wurden erstellt.");

            $this->closeCreateBilling();
        } catch (\Exception $e) {
            session()->flash('billing-error', $e->getMessage());
        }
    }

    public function getMonthsProperty(): array
    {
        // Startdatum bestimmen: billing_start_date, frühester Vertrag, oder früheste Abrechnung
        $startDate = null;

        if ($this->solarPlant->billing_start_date) {
            $startDate = Carbon::parse($this->solarPlant->billing_start_date)->startOfMonth();
        }

        // Frühestes Vertragsstartdatum
        $earliestContract = $this->solarPlant->supplierContracts()
            ->whereNotNull('start_date')
            ->orderBy('start_date')
            ->value('start_date');
        if ($earliestContract) {
            $contractStart = Carbon::parse($earliestContract)->startOfMonth();
            $startDate = $startDate ? $startDate->min($contractStart) : $contractStart;
        }

        // Früheste Abrechnung
        $earliestBilling = $this->solarPlant->billings()
            ->selectRaw('MIN(CONCAT(billing_year, "-", LPAD(billing_month, 2, "0"), "-01")) as earliest')
            ->value('earliest');
        if ($earliestBilling) {
            $billingStart = Carbon::parse($earliestBilling)->startOfMonth();
            $startDate = $startDate ? $startDate->min($billingStart) : $billingStart;
        }

        if (!$startDate) {
            return [];
        }

        $endDate = Carbon::now()->startOfMonth();

        // Alle existierenden Abrechnungen gruppiert laden
        $existingBillings = $this->solarPlant->billings()
            ->selectRaw('billing_year, billing_month')
            ->selectRaw('COUNT(*) as billings_count')
            ->selectRaw('SUM(CASE WHEN status != \'cancelled\' THEN 1 ELSE 0 END) as active_billings_count')
            ->selectRaw('SUM(CASE WHEN status != \'cancelled\' THEN total_costs ELSE 0 END) as total_costs_sum')
            ->selectRaw('SUM(CASE WHEN status != \'cancelled\' THEN total_credits ELSE 0 END) as total_credits_sum')
            ->selectRaw('SUM(CASE WHEN status != \'cancelled\' THEN net_amount ELSE 0 END) as net_amount_sum')
            ->selectRaw('SUM(CASE WHEN status != \'cancelled\' THEN participation_percentage ELSE 0 END) as total_participation_percentage')
            ->selectRaw('SUM(CASE WHEN status = \'draft\' THEN 1 ELSE 0 END) as draft_count')
            ->selectRaw('SUM(CASE WHEN status = \'finalized\' THEN 1 ELSE 0 END) as finalized_count')
            ->selectRaw('SUM(CASE WHEN status = \'sent\' THEN 1 ELSE 0 END) as sent_count')
            ->selectRaw('SUM(CASE WHEN status = \'paid\' THEN 1 ELSE 0 END) as paid_count')
            ->selectRaw('SUM(CASE WHEN status = \'cancelled\' THEN 1 ELSE 0 END) as cancelled_count')
            ->groupBy('billing_year', 'billing_month')
            ->get()
            ->keyBy(fn ($item) => $item->billing_year . '-' . $item->billing_month);

        // Alle Lieferantenverträge mit Belegen pro Monat laden
        $contracts = $this->solarPlant->supplierContracts()->with('supplier')->get();
        $contractBillings = [];
        foreach ($contracts as $contract) {
            $billings = $contract->billings()
                ->selectRaw('billing_year, billing_month, COUNT(*) as cnt')
                ->groupBy('billing_year', 'billing_month')
                ->get();
            foreach ($billings as $b) {
                $key = $b->billing_year . '-' . $b->billing_month;
                if (!isset($contractBillings[$key])) {
                    $contractBillings[$key] = 0;
                }
                $contractBillings[$key] += $b->cnt;
            }
        }

        $totalContracts = $contracts->count();

        // Alle Monate generieren (neueste zuerst)
        $months = [];
        $current = $endDate->copy();

        while ($current->gte($startDate)) {
            $year = $current->year;
            $month = $current->month;
            $key = $year . '-' . $month;

            $billing = $existingBillings->get($key);
            $contractBillingCount = $contractBillings[$key] ?? 0;

            // Prüfe pro Vertrag ob Beleg existiert
            $contractsWithBillings = 0;
            foreach ($contracts as $contract) {
                $hasBilling = $contract->billings()
                    ->where('billing_year', $year)
                    ->where('billing_month', $month)
                    ->exists();
                if ($hasBilling) {
                    $contractsWithBillings++;
                }
            }

            $months[] = [
                'year' => $year,
                'month' => $month,
                'has_billings' => $billing !== null,
                'billings_count' => $billing?->active_billings_count ?? 0,
                'total_costs_sum' => $billing?->total_costs_sum ?? 0,
                'total_credits_sum' => $billing?->total_credits_sum ?? 0,
                'net_amount_sum' => $billing?->net_amount_sum ?? 0,
                'billed_kwp' => $billing ? ($this->solarPlant->total_capacity_kw * ($billing->total_participation_percentage / 100)) : 0,
                'draft_count' => $billing?->draft_count ?? 0,
                'finalized_count' => $billing?->finalized_count ?? 0,
                'sent_count' => $billing?->sent_count ?? 0,
                'paid_count' => $billing?->paid_count ?? 0,
                'cancelled_count' => $billing?->cancelled_count ?? 0,
                'total_contracts' => $totalContracts,
                'contracts_with_billings' => $contractsWithBillings,
                'all_contracts_have_billings' => $contractsWithBillings >= $totalContracts,
            ];

            $current->subMonth();
        }

        return $months;
    }

    public function getDetailDataProperty(): ?array
    {
        if (!$this->showDetail || !$this->detailYear || !$this->detailMonth) {
            return null;
        }

        $contracts = $this->solarPlant->supplierContracts()
            ->with(['supplier'])
            ->get();

        $contractData = $contracts->map(function ($contract) {
            $billings = $contract->billings()
                ->where('billing_year', $this->detailYear)
                ->where('billing_month', $this->detailMonth)
                ->with('articles.article')
                ->get();

            $pivot = $contract->pivot;

            return [
                'contract' => $contract,
                'percentage' => $pivot->percentage ?? null,
                'is_active' => $pivot->is_active ?? true,
                'billings' => $billings,
                'has_billings' => $billings->isNotEmpty(),
            ];
        });

        // Pflicht-Kundenartikel für diesen Monat laden
        $billingDate = Carbon::create($this->detailYear, $this->detailMonth, 1);
        $billingMonthStart = $billingDate->copy()->startOfMonth();
        $billingMonthEnd = $billingDate->copy()->endOfMonth();

        $participations = $this->solarPlant->participations()->with('customer')->get();
        $customerArticlesData = collect();

        foreach ($participations as $participation) {
            $customer = $participation->customer;
            if (!$customer) continue;

            $articles = $customer->articles()
                ->wherePivot('solar_plant_id', $this->solarPlant->id)
                ->wherePivot('billing_requirement', 'mandatory')
                ->wherePivot('is_active', true)
                ->where(function ($query) use ($billingMonthEnd) {
                    $query->whereNull('customer_article.valid_from')
                        ->orWhere('customer_article.valid_from', '<=', $billingMonthEnd);
                })
                ->where(function ($query) use ($billingMonthStart) {
                    $query->whereNull('customer_article.valid_to')
                        ->orWhere('customer_article.valid_to', '>=', $billingMonthStart);
                })
                ->get();

            if ($articles->isEmpty()) continue;

            $articleItems = $articles->map(function ($article) use ($billingDate) {
                $pivot = $article->pivot;
                $unitPrice = $pivot->unit_price ?? $article->price;

                // Preiserhöhung anwenden
                if ($pivot->price_increase_percentage && $pivot->price_increase_interval_months && $pivot->price_increase_start_date) {
                    $increaseStart = Carbon::parse($pivot->price_increase_start_date);
                    if ($billingDate->gte($increaseStart)) {
                        $monthsDiff = $increaseStart->diffInMonths($billingDate);
                        $intervals = intdiv((int) $monthsDiff, (int) $pivot->price_increase_interval_months);
                        if ($intervals > 0) {
                            $unitPrice = $unitPrice * pow(1 + ($pivot->price_increase_percentage / 100), $intervals);
                        }
                    }
                }

                $quantity = $pivot->quantity ?? 1;
                $netTotal = $unitPrice * $quantity;
                $taxRate = $article->getCurrentTaxRate();
                $grossTotal = $netTotal + ($netTotal * $taxRate);

                return [
                    'article' => $article,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'net_total' => $netTotal,
                    'tax_rate' => $taxRate,
                    'gross_total' => $grossTotal,
                    'billing_type' => $pivot->billing_type ?? 'invoice',
                ];
            });

            $customerArticlesData->push([
                'customer' => $customer,
                'participation_percentage' => $participation->percentage,
                'articles' => $articleItems,
            ]);
        }

        // Kundenabrechnungen mit Artikelaufschlüsselung laden
        $customerBillings = SolarPlantBilling::where('solar_plant_id', $this->solarPlant->id)
            ->where('billing_year', $this->detailYear)
            ->where('billing_month', $this->detailMonth)
            ->with('customer')
            ->get();

        // Voraussichtliche Abrechnungen berechnen, wenn noch keine existieren
        $previewBillings = collect();
        if ($customerBillings->isEmpty()) {
            foreach ($participations as $participation) {
                $customer = $participation->customer;
                if (!$customer) continue;

                try {
                    $costData = SolarPlantBilling::calculateCostsForCustomer(
                        $this->solarPlant->id,
                        $customer->id,
                        $this->detailYear,
                        $this->detailMonth,
                        $participation->percentage
                    );

                    $previousOutstanding = SolarPlantBilling::getPreviousMonthOutstanding(
                        $this->solarPlant->id,
                        $customer->id,
                        $this->detailYear,
                        $this->detailMonth
                    );

                    $netAmount = ($costData['total_costs'] - $costData['total_credits']) + $previousOutstanding;

                    $previewBillings->push([
                        'customer' => $customer,
                        'participation_percentage' => $participation->percentage,
                        'total_costs' => $costData['total_costs'],
                        'total_credits' => $costData['total_credits'],
                        'total_costs_net' => $costData['total_costs_net'],
                        'total_credits_net' => $costData['total_credits_net'],
                        'total_vat_amount' => $costData['total_vat_amount'],
                        'net_amount' => $netAmount,
                        'previous_month_outstanding' => $previousOutstanding,
                        'cost_breakdown' => $costData['cost_breakdown'],
                        'credit_breakdown' => $costData['credit_breakdown'],
                    ]);
                } catch (\Exception $e) {
                    // Skip customers where calculation fails
                }
            }
        }

        return [
            'contractData' => $contractData,
            'customerArticlesData' => $customerArticlesData,
            'customerBillings' => $customerBillings,
            'previewBillings' => $previewBillings,
            'participations' => $participations,
            'billingYear' => $this->detailYear,
            'billingMonth' => $this->detailMonth,
        ];
    }

    public function render(): View
    {
        return view('livewire.billings-monthly-table', [
            'months' => $this->months,
            'detailData' => $this->detailData,
        ]);
    }
}
