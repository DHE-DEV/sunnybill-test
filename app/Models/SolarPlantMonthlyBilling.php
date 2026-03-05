<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SolarPlantMonthlyBilling extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'solar_plant_id',
        'billing_year',
        'billing_month',
        'total_capacity_kwp',
        'billed_kwp',
        'difference_kwp',
        'difference_percentage',
        'produced_energy_kwh',
        'total_costs',
        'total_costs_net',
        'total_credits',
        'total_credits_net',
        'total_vat_amount',
        'net_amount',
        'customer_count',
        'total_participation_percentage',
        'status',
        'notes',
        'finalized_at',
        'created_by',
    ];

    protected $casts = [
        'total_capacity_kwp' => 'decimal:6',
        'billed_kwp' => 'decimal:6',
        'difference_kwp' => 'decimal:6',
        'difference_percentage' => 'decimal:4',
        'produced_energy_kwh' => 'decimal:3',
        'total_costs' => 'decimal:2',
        'total_costs_net' => 'decimal:2',
        'total_credits' => 'decimal:2',
        'total_credits_net' => 'decimal:2',
        'total_vat_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'total_participation_percentage' => 'decimal:4',
        'finalized_at' => 'datetime',
    ];

    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alle Kundenabrechnungen dieses Monats
     */
    public function customerBillings(): HasMany
    {
        return $this->solarPlant->billings()
            ->where('billing_year', $this->billing_year)
            ->where('billing_month', $this->billing_month)
            ->getQuery();
    }

    public function getFormattedMonthAttribute(): string
    {
        return Carbon::createFromDate($this->billing_year, $this->billing_month, 1)
            ->locale('de')
            ->translatedFormat('F Y');
    }

    /**
     * Erstellt oder aktualisiert den Monatskopf aus den Kundenabrechnungen
     */
    public static function createOrUpdateFromBillings(string $solarPlantId, int $year, int $month): self
    {
        $solarPlant = SolarPlant::withTrashed()->findOrFail($solarPlantId);

        $billings = SolarPlantBilling::where('solar_plant_id', $solarPlantId)
            ->where('billing_year', $year)
            ->where('billing_month', $month)
            ->where('status', '!=', 'cancelled')
            ->get();

        $totalCapacityKwp = $solarPlant->total_capacity_kw ?? 0;
        $totalParticipation = $billings->sum('participation_percentage');
        $billedKwp = $totalCapacityKwp * ($totalParticipation / 100);
        $differenceKwp = $totalCapacityKwp - $billedKwp;
        $differencePercentage = $totalCapacityKwp > 0
            ? (($totalCapacityKwp - $billedKwp) / $totalCapacityKwp) * 100
            : 0;

        return self::updateOrCreate(
            [
                'solar_plant_id' => $solarPlantId,
                'billing_year' => $year,
                'billing_month' => $month,
            ],
            [
                'total_capacity_kwp' => $totalCapacityKwp,
                'billed_kwp' => $billedKwp,
                'difference_kwp' => $differenceKwp,
                'difference_percentage' => $differencePercentage,
                'produced_energy_kwh' => $billings->max('produced_energy_kwh'),
                'total_costs' => $billings->sum('total_costs'),
                'total_costs_net' => $billings->sum('total_costs_net'),
                'total_credits' => $billings->sum('total_credits'),
                'total_credits_net' => $billings->sum('total_credits_net'),
                'total_vat_amount' => $billings->sum('total_vat_amount'),
                'net_amount' => $billings->sum('net_amount'),
                'customer_count' => $billings->count(),
                'total_participation_percentage' => $totalParticipation,
                'created_by' => auth()->id(),
            ]
        );
    }
}
