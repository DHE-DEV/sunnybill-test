<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlantMonthlyResult extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'solar_plant_id',
        'month',
        'energy_produced_kwh',
        'total_revenue',
        'billing_type',
    ];

    protected $casts = [
        'total_revenue' => 'decimal:6',
        'energy_produced_kwh' => 'decimal:6',
    ];

    /**
     * Beziehung zur Solaranlage
     */
    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    /**
     * Beziehung zu Kundengutschriften
     */
    public function customerCredits(): HasMany
    {
        return $this->hasMany(CustomerMonthlyCredit::class);
    }

    /**
     * Formatierte Umsätze
     */
    public function getFormattedTotalRevenueAttribute(): string
    {
        return number_format($this->total_revenue, 6, ',', '.') . ' €';
    }

    /**
     * Monat formatiert anzeigen
     */
    public function getFormattedMonthAttribute(): string
    {
        $date = \Carbon\Carbon::createFromFormat('Y-m', $this->month);
        return $date->format('F Y');
    }

    /**
     * Automatische Generierung der Kundengutschriften
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($monthlyResult) {
            $monthlyResult->generateCustomerCredits();
        });
    }

    /**
     * Kundengutschriften generieren
     */
    public function generateCustomerCredits(): void
    {
        // Bestehende Gutschriften löschen
        $this->customerCredits()->delete();

        // Prüfe ob total_revenue vorhanden ist
        if (!$this->total_revenue || $this->total_revenue <= 0) {
            return;
        }

        // Neue Gutschriften basierend auf Beteiligungen erstellen
        $participations = $this->solarPlant->participations;

        foreach ($participations as $participation) {
            // Sicherstellen, dass alle Werte numerisch sind
            $totalRevenue = (float) $this->total_revenue;
            $percentage = (float) $participation->percentage;
            $creditedAmount = ($totalRevenue * $percentage) / 100;
            
            // Berechne den Energieanteil basierend auf der Beteiligung
            $energyShare = ((float) $this->energy_produced_kwh * $percentage) / 100;

            CustomerMonthlyCredit::create([
                'customer_id' => $participation->customer_id,
                'solar_plant_id' => $this->solar_plant_id,
                'plant_monthly_result_id' => $this->id,
                'month' => $this->month,
                'participation_percentage' => $percentage,
                'energy_share_kwh' => $energyShare,
                'savings_amount' => 0, // Kann später berechnet werden
                'feed_in_revenue' => $creditedAmount,
                'total_credit' => $creditedAmount,
                'share_percentage' => $percentage,
                'credited_amount' => $creditedAmount,
                'full_plant_revenue' => $totalRevenue,
            ]);
        }
    }

    /**
     * Gesamtsumme aller Gutschriften
     */
    public function getTotalCreditsAttribute(): float
    {
        return $this->customerCredits->sum('credited_amount');
    }
}
