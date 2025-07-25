<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SolarPlantBilling extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'solar_plant_id',
        'customer_id',
        'billing_year',
        'billing_month',
        'participation_percentage',
        'produced_energy_kwh',
        'total_costs',
        'total_credits',
        'net_amount',
        'total_costs_net',
        'total_credits_net',
        'total_vat_amount',
        'status',
        'notes',
        'cost_breakdown',
        'credit_breakdown',
        'finalized_at',
        'sent_at',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'participation_percentage' => 'decimal:2',
        'produced_energy_kwh' => 'decimal:3',
        'total_costs' => 'decimal:2',
        'total_credits' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'total_costs_net' => 'decimal:2',
        'total_credits_net' => 'decimal:2',
        'total_vat_amount' => 'decimal:2',
        'cost_breakdown' => 'array',
        'credit_breakdown' => 'array',
        'finalized_at' => 'datetime',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Beziehung zur Solaranlage
     */
    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    /**
     * Beziehung zum Kunden
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Beziehung zum Ersteller
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Polymorphe Beziehung zu Dokumenten
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Status-Optionen
     */
    public static function getStatusOptions(): array
    {
        return [
            'draft' => 'Entwurf',
            'finalized' => 'Finalisiert',
            'sent' => 'Versendet',
            'paid' => 'Bezahlt',
        ];
    }

    /**
     * Formatierter Monat für Anzeige
     */
    public function getFormattedMonthAttribute(): string
    {
        return Carbon::createFromDate($this->billing_year, $this->billing_month, 1)
            ->locale('de')
            ->translatedFormat('F Y');
    }

    /**
     * Formatierter Nettobetrag
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return number_format($this->net_amount, 2, ',', '.') . ' €';
    }

    /**
     * Formatierte Gesamtkosten
     */
    public function getFormattedTotalCostsAttribute(): string
    {
        return number_format($this->total_costs, 2, ',', '.') . ' €';
    }

    /**
     * Formatierte Gesamtgutschriften
     */
    public function getFormattedTotalCreditsAttribute(): string
    {
        return number_format($this->total_credits, 2, ',', '.') . ' €';
    }

    /**
     * Prüft ob alle Vertragsabrechnungen für eine Solaranlage und einen Monat vorhanden sind
     */
    public static function canCreateBillingForMonth(string $solarPlantId, int $year, int $month): bool
    {
        $solarPlant = SolarPlant::find($solarPlantId);
        if (!$solarPlant) {
            return false;
        }

        // Hole alle aktiven Verträge für diese Solaranlage
        $activeContracts = $solarPlant->activeSupplierContracts()->get();
        
        if ($activeContracts->isEmpty()) {
            return false;
        }

        // Prüfe ob für jeden Vertrag eine Abrechnung für den Monat existiert
        foreach ($activeContracts as $contract) {
            $billingExists = $contract->billings()
                ->where('billing_year', $year)
                ->where('billing_month', $month)
                ->exists();
                
            if (!$billingExists) {
                return false;
            }
        }

        return true;
    }

    /**
     * Erstellt Abrechnungen für alle Kunden einer Solaranlage für einen bestimmten Monat
     */
    public static function createBillingsForMonth(string $solarPlantId, int $year, int $month, ?float $producedEnergyKwh = null): array
    {
        $solarPlant = SolarPlant::find($solarPlantId);
        if (!$solarPlant) {
            throw new \Exception('Solaranlage nicht gefunden');
        }

        // Prüfe ob Abrechnungen erstellt werden können
        if (!self::canCreateBillingForMonth($solarPlantId, $year, $month)) {
            throw new \Exception('Nicht alle Vertragsabrechnungen für diesen Monat sind vorhanden');
        }

        // Hole alle Kundenbeteiligungen
        $participations = $solarPlant->participations()->get();
        
        if ($participations->isEmpty()) {
            throw new \Exception('Keine aktiven Kundenbeteiligungen gefunden');
        }

        $createdBillings = [];

        foreach ($participations as $participation) {
            // Prüfe ob bereits eine Abrechnung für diesen Kunden und Monat existiert (auch gelöschte)
            $existingBilling = self::withTrashed()
                ->where('solar_plant_id', $solarPlantId)
                ->where('customer_id', $participation->customer_id)
                ->where('billing_year', $year)
                ->where('billing_month', $month)
                ->first();

            if ($existingBilling) {
                if ($existingBilling->trashed()) {
                    // Wenn die Abrechnung gelöscht wurde, entferne sie permanent und erstelle eine neue
                    $existingBilling->forceDelete();
                } else {
                    // Abrechnung existiert bereits und ist nicht gelöscht - überspringe
                    continue;
                }
            }

            // Berechne Kosten und Gutschriften für diesen Kunden
            $costData = self::calculateCostsForCustomer($solarPlantId, $participation->customer_id, $year, $month, $participation->percentage);

            // Erstelle die Abrechnung
            $billing = self::create([
                'solar_plant_id' => $solarPlantId,
                'customer_id' => $participation->customer_id,
                'billing_year' => $year,
                'billing_month' => $month,
                'participation_percentage' => $participation->percentage,
                'produced_energy_kwh' => $producedEnergyKwh,
                'total_costs' => $costData['total_costs'],
                'total_credits' => $costData['total_credits'],
                'total_costs_net' => $costData['total_costs_net'],
                'total_credits_net' => $costData['total_credits_net'],
                'total_vat_amount' => $costData['total_vat_amount'],
                'net_amount' => $costData['total_costs'] - $costData['total_credits'],
                'cost_breakdown' => $costData['cost_breakdown'],
                'credit_breakdown' => $costData['credit_breakdown'],
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            $createdBillings[] = $billing;
        }

        return $createdBillings;
    }

    /**
     * Bereinigt gelöschte Abrechnungen für eine Solaranlage und einen Monat
     * Diese Methode entfernt permanent gelöschte Abrechnungen, um Unique Constraint Probleme zu vermeiden
     */
    public static function cleanupDeletedBillingsForMonth(string $solarPlantId, int $year, int $month): int
    {
        $deletedCount = self::onlyTrashed()
            ->where('solar_plant_id', $solarPlantId)
            ->where('billing_year', $year)
            ->where('billing_month', $month)
            ->forceDelete();
            
        return $deletedCount;
    }

    /**
     * Berechnet Kosten und Gutschriften für einen Kunden basierend auf seinem Beteiligungsprozentsatz
     */
    public static function calculateCostsForCustomer(string $solarPlantId, string $customerId, int $year, int $month, float $percentage = null): array
    {
        $solarPlant = SolarPlant::find($solarPlantId);
        $activeContracts = $solarPlant->activeSupplierContracts()->get();

        // Wenn kein Prozentsatz übergeben wurde, hole ihn aus der Beteiligung
        if ($percentage === null) {
            $participation = $solarPlant->participations()->where('customer_id', $customerId)->first();
            if (!$participation) {
                throw new \Exception('Keine Beteiligung für diesen Kunden gefunden');
            }
            $percentage = $participation->percentage;
        }

        $totalCosts = 0;
        $totalCredits = 0;
        $totalCostsNet = 0;
        $totalCreditsNet = 0;
        $totalVatAmount = 0;
        $costBreakdown = [];
        $creditBreakdown = [];

        foreach ($activeContracts as $contract) {
            $billing = $contract->billings()
                ->where('billing_year', $year)
                ->where('billing_month', $month)
                ->first();

            if (!$billing) {
                continue;
            }

            // Hole den Solaranlagen-Anteil aus der Pivot-Tabelle
            $solarPlantPivot = $contract->solarPlants()
                ->where('solar_plant_id', $solarPlantId)
                ->first();
                
            if (!$solarPlantPivot) {
                // Wenn diese Solaranlage nicht als Kostenträger für diesen Vertrag hinterlegt ist, überspringe
                continue;
            }
            
            $solarPlantPercentage = $solarPlantPivot->pivot->percentage ?? 100;

            // Berechne den Anteil: Vertragsbetrag * Solaranlagen-Anteil * Kunden-Anteil
            $solarPlantShare = ($solarPlantPercentage / 100);
            $customerShare = ($percentage / 100);
            $finalShare = $solarPlantShare * $customerShare;
            
            // Prüfe ob es sich um Kosten oder Gutschriften handelt
            // Gutschriften werden erkannt durch:
            // 1. billing_type ist 'credit_note' ODER
            // 2. Der Betrag ist negativ
            $isCredit = $billing->billing_type === 'credit_note' || $billing->total_amount < 0;
            
            if ($isCredit) {
                // Gutschriften - verwende den absoluten Betrag für die Berechnung
                $customerCredit = abs($billing->total_amount) * $finalShare;
                $totalCredits += $customerCredit;
                
                // Berechne Netto-Gutschriften und MwSt.
                if ($billing->net_amount) {
                    $customerCreditNet = abs($billing->net_amount) * $finalShare;
                    $totalCreditsNet += $customerCreditNet;
                    
                    // MwSt.-Betrag = Brutto - Netto
                    $vatAmount = $customerCredit - $customerCreditNet;
                    $totalVatAmount -= $vatAmount; // Subtrahiere MwSt. bei Gutschriften
                } else {
                    // Fallback: Verwende 19% MwSt. wenn net_amount nicht verfügbar
                    $customerCreditNet = $customerCredit / 1.19;
                    $totalCreditsNet += $customerCreditNet;
                    $totalVatAmount -= ($customerCredit - $customerCreditNet);
                }
                
                // Hole die Artikel-Details für diese Gutschrift
                $articles = $billing->articles()->get();
                $articleDetails = [];
                
                foreach ($articles as $article) {
                    $articleRecord = $article->article;
                    $netTotal = $article->total_price;
                    $taxRate = $articleRecord ? $articleRecord->getCurrentTaxRate() : 0.19;
                    $taxAmount = $netTotal * $taxRate;
                    $grossTotal = $netTotal + $taxAmount;
                    
                    $articleDetails[] = [
                        'article_name' => $articleRecord ? $articleRecord->name : ($article->description ?? 'Unbekannt'),
                        'quantity' => $article->quantity,
                        'unit' => $articleRecord ? ($articleRecord->unit ?? 'Stk.') : 'Stk.',
                        'unit_price' => $article->unit_price,
                        'total_price_net' => $netTotal,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $taxAmount,
                        'total_price_gross' => $grossTotal,
                        'description' => $article->description,
                    ];
                }
                
                // Berechne Netto- und MwSt.-Beträge für diese Gutschrift
                $customerCreditNet = $billing->net_amount ? (abs($billing->net_amount) * $finalShare) : ($customerCredit / 1.19);
                $customerCreditVat = $customerCredit - $customerCreditNet;
                $vatRate = $billing->vat_rate ?? 0.19;
                
                $creditBreakdown[] = [
                    'contract_id' => $contract->id,
                    'contract_title' => $contract->title,
                    'contract_number' => $contract->contract_number,
                    'supplier_id' => $contract->supplier->id,
                    'supplier_name' => $contract->supplier->company_name ?? $contract->supplier->name ?? 'Unbekannt',
                    'contract_billing_id' => $billing->id,
                    'billing_number' => $billing->billing_number,
                    'total_amount' => $billing->total_amount,
                    'solar_plant_percentage' => $solarPlantPercentage,
                    'customer_percentage' => $percentage,
                    'customer_share' => $customerCredit,
                    'customer_share_net' => $customerCreditNet,
                    'customer_share_vat' => $customerCreditVat,
                    'vat_rate' => $vatRate,
                    'articles' => $articleDetails,
                ];
            } else {
                // Kosten - alle anderen Verträge (nur positive Beträge)
                if ($billing->total_amount > 0) {
                    $customerCost = $billing->total_amount * $finalShare;
                    $totalCosts += $customerCost;
                    
                    // Berechne Netto-Kosten und MwSt.
                    if ($billing->net_amount) {
                        $customerCostNet = $billing->net_amount * $finalShare;
                        $totalCostsNet += $customerCostNet;
                        
                        // MwSt.-Betrag = Brutto - Netto
                        $vatAmount = $customerCost - $customerCostNet;
                        $totalVatAmount += $vatAmount; // Addiere MwSt. bei Kosten
                    } else {
                        // Fallback: Verwende 19% MwSt. wenn net_amount nicht verfügbar
                        $customerCostNet = $customerCost / 1.19;
                        $totalCostsNet += $customerCostNet;
                        $totalVatAmount += ($customerCost - $customerCostNet);
                    }
                    
                // Hole die Artikel-Details für diese Abrechnung
                $articles = $billing->articles()->get();
                $articleDetails = [];
                
                foreach ($articles as $article) {
                    $articleRecord = $article->article;
                    $netTotal = $article->total_price;
                    $taxRate = $articleRecord ? $articleRecord->getCurrentTaxRate() : 0.19;
                    $taxAmount = $netTotal * $taxRate;
                    $grossTotal = $netTotal + $taxAmount;
                    
                    $articleDetails[] = [
                        'article_name' => $articleRecord ? $articleRecord->name : ($article->description ?? 'Unbekannt'),
                        'quantity' => $article->quantity,
                        'unit' => $articleRecord ? ($articleRecord->unit ?? 'Stk.') : 'Stk.',
                        'unit_price' => $article->unit_price,
                        'total_price_net' => $netTotal,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $taxAmount,
                        'total_price_gross' => $grossTotal,
                        'description' => $article->description,
                    ];
                }

                // Berechne Netto- und MwSt.-Beträge für diese Kosten
                $customerCostNet = $billing->net_amount ? ($billing->net_amount * $finalShare) : ($customerCost / 1.19);
                $customerCostVat = $customerCost - $customerCostNet;
                $vatRate = $billing->vat_rate ?? 0.19;
                
                $costBreakdown[] = [
                    'contract_id' => $contract->id,
                    'contract_title' => $contract->title,
                    'contract_number' => $contract->contract_number,
                    'supplier_id' => $contract->supplier->id,
                    'supplier_name' => $contract->supplier->company_name ?? $contract->supplier->name ?? 'Unbekannt',
                    'contract_billing_id' => $billing->id,
                    'billing_number' => $billing->billing_number,
                    'total_amount' => $billing->total_amount,
                    'solar_plant_percentage' => $solarPlantPercentage,
                    'customer_percentage' => $percentage,
                    'customer_share' => $customerCost,
                    'customer_share_net' => $customerCostNet,
                    'customer_share_vat' => $customerCostVat,
                    'vat_rate' => $vatRate,
                    'articles' => $articleDetails,
                ];
                }
            }
        }

        return [
            'total_costs' => $totalCosts,
            'total_credits' => $totalCredits,
            'total_costs_net' => $totalCostsNet,
            'total_credits_net' => $totalCreditsNet,
            'total_vat_amount' => $totalVatAmount,
            'net_amount' => $totalCosts - $totalCredits,
            'cost_breakdown' => $costBreakdown,
            'credit_breakdown' => $creditBreakdown,
        ];
    }
}
