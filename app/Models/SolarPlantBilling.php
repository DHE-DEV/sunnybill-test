<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'total_costs',
        'total_credits',
        'net_amount',
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
        'total_costs' => 'decimal:2',
        'total_credits' => 'decimal:2',
        'net_amount' => 'decimal:2',
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
    public static function createBillingsForMonth(string $solarPlantId, int $year, int $month): array
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
            // Prüfe ob bereits eine Abrechnung für diesen Kunden und Monat existiert
            $existingBilling = self::where('solar_plant_id', $solarPlantId)
                ->where('customer_id', $participation->customer_id)
                ->where('billing_year', $year)
                ->where('billing_month', $month)
                ->first();

            if ($existingBilling) {
                continue; // Überspringe wenn bereits vorhanden
            }

            // Berechne Kosten und Gutschriften für diesen Kunden
            $costData = self::calculateCostsForCustomer($solarPlantId, $participation->customer_id, $participation->percentage, $year, $month);

            // Erstelle die Abrechnung
            $billing = self::create([
                'solar_plant_id' => $solarPlantId,
                'customer_id' => $participation->customer_id,
                'billing_year' => $year,
                'billing_month' => $month,
                'participation_percentage' => $participation->percentage,
                'total_costs' => $costData['total_costs'],
                'total_credits' => $costData['total_credits'],
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
     * Berechnet Kosten und Gutschriften für einen Kunden basierend auf seinem Beteiligungsprozentsatz
     */
    private static function calculateCostsForCustomer(string $solarPlantId, string $customerId, float $percentage, int $year, int $month): array
    {
        $solarPlant = SolarPlant::find($solarPlantId);
        $activeContracts = $solarPlant->activeSupplierContracts()->get();

        $totalCosts = 0;
        $totalCredits = 0;
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

            // Berechne den Anteil basierend auf dem Beteiligungsprozentsatz
            $customerShare = ($percentage / 100);
            
            if ($billing->amount > 0) {
                // Kosten
                $customerCost = $billing->amount * $customerShare;
                $totalCosts += $customerCost;
                
                $costBreakdown[] = [
                    'contract_id' => $contract->id,
                    'contract_title' => $contract->title,
                    'supplier_name' => $contract->supplier->company_name,
                    'total_amount' => $billing->amount,
                    'customer_share' => $customerCost,
                    'percentage' => $percentage,
                ];
            } else {
                // Gutschriften (negative Beträge)
                $customerCredit = abs($billing->amount) * $customerShare;
                $totalCredits += $customerCredit;
                
                $creditBreakdown[] = [
                    'contract_id' => $contract->id,
                    'contract_title' => $contract->title,
                    'supplier_name' => $contract->supplier->company_name,
                    'total_amount' => abs($billing->amount),
                    'customer_share' => $customerCredit,
                    'percentage' => $percentage,
                ];
            }
        }

        return [
            'total_costs' => $totalCosts,
            'total_credits' => $totalCredits,
            'cost_breakdown' => $costBreakdown,
            'credit_breakdown' => $creditBreakdown,
        ];
    }
}