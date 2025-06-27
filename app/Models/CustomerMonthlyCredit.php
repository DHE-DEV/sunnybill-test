<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMonthlyCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'plant_monthly_result_id',
        'solar_plant_id',
        'month',
        'participation_percentage',
        'energy_share_kwh',
        'savings_amount',
        'feed_in_revenue',
        'total_credit',
        'share_percentage',
        'credited_amount',
        'full_plant_revenue',
    ];

    protected $casts = [
        'month' => 'date',
        'participation_percentage' => 'decimal:2',
        'energy_share_kwh' => 'decimal:6',
        'savings_amount' => 'decimal:6',
        'feed_in_revenue' => 'decimal:6',
        'total_credit' => 'decimal:6',
        'share_percentage' => 'decimal:2',
        'credited_amount' => 'decimal:6',
        'full_plant_revenue' => 'decimal:6',
    ];

    /**
     * Beziehung zum Kunden
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Beziehung zur Solaranlage
     */
    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    /**
     * Beziehung zum monatlichen Ergebnis
     */
    public function plantMonthlyResult(): BelongsTo
    {
        return $this->belongsTo(PlantMonthlyResult::class);
    }

    /**
     * Formatierte Gesamtgutschrift
     */
    public function getFormattedTotalCreditAttribute(): string
    {
        return number_format($this->total_credit, 6, ',', '.') . ' €';
    }

    /**
     * Formatierte Prozentangabe
     */
    public function getFormattedParticipationPercentageAttribute(): string
    {
        return number_format($this->participation_percentage, 2, ',', '.') . '%';
    }

    /**
     * Formatierte Ersparnis
     */
    public function getFormattedSavingsAmountAttribute(): string
    {
        return number_format($this->savings_amount, 6, ',', '.') . ' €';
    }

    /**
     * Formatierter Einspeiseerlös
     */
    public function getFormattedFeedInRevenueAttribute(): string
    {
        return number_format($this->feed_in_revenue, 6, ',', '.') . ' €';
    }

    /**
     * Formatierter Energieanteil
     */
    public function getFormattedEnergyShareAttribute(): string
    {
        return number_format($this->energy_share_kwh, 6, ',', '.') . ' kWh';
    }
}
