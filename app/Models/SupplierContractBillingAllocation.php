<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierContractBillingAllocation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'supplier_contract_billing_id',
        'solar_plant_id',
        'percentage',
        'amount',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function billing(): BelongsTo
    {
        return $this->belongsTo(SupplierContractBilling::class, 'supplier_contract_billing_id');
    }

    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class, 'solar_plant_id');
    }

    public function getFormattedPercentageAttribute(): string
    {
        return number_format($this->percentage, 2, ',', '.') . '%';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2, ',', '.') . ' €';
    }

    /**
     * Berechnet den Betrag basierend auf dem Prozentsatz und dem Gesamtbetrag der Abrechnung
     */
    public function calculateAmount(): void
    {
        if ($this->billing && $this->percentage) {
            $this->amount = ($this->billing->total_amount * $this->percentage) / 100;
        }
    }

    /**
     * Validiert, dass die Gesamtsumme aller Prozentsätze einer Abrechnung 100% nicht überschreitet
     */
    public static function validateTotalPercentage(string $billingId, float $percentage, ?string $excludeAllocationId = null): bool
    {
        $query = static::where('supplier_contract_billing_id', $billingId);
        
        if ($excludeAllocationId) {
            $query->where('id', '!=', $excludeAllocationId);
        }
        
        $currentTotal = $query->sum('percentage');
        
        return ($currentTotal + $percentage) <= 100;
    }

    /**
     * Gibt den verfügbaren Prozentsatz für eine Abrechnung zurück
     */
    public static function getAvailablePercentage(string $billingId, ?string $excludeAllocationId = null): float
    {
        $query = static::where('supplier_contract_billing_id', $billingId);
        
        if ($excludeAllocationId) {
            $query->where('id', '!=', $excludeAllocationId);
        }
        
        $currentTotal = $query->sum('percentage');
        
        return max(0, 100 - $currentTotal);
    }

    /**
     * Gibt die Gesamtsumme aller Prozentsätze für eine Abrechnung zurück
     */
    public static function getTotalPercentage(string $billingId): float
    {
        return static::where('supplier_contract_billing_id', $billingId)->sum('percentage');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($allocation) {
            $allocation->calculateAmount();
        });
    }
}