<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierContractSolarPlant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'supplier_contract_solar_plants';

    protected $fillable = [
        'supplier_contract_id',
        'solar_plant_id',
        'percentage',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Beziehung zum Lieferantenvertrag
     */
    public function supplierContract(): BelongsTo
    {
        return $this->belongsTo(SupplierContract::class);
    }

    /**
     * Beziehung zur Solaranlage
     */
    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    /**
     * Formatierter Prozentsatz für Anzeige
     */
    public function getFormattedPercentageAttribute(): string
    {
        return number_format($this->percentage, 2, ',', '.') . '%';
    }

    /**
     * Scope für aktive Zuordnungen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Validierung: Prüft ob die Gesamtsumme der Prozentsätze für einen Vertrag 100% nicht überschreitet
     */
    public static function validateTotalPercentage(string $contractId, float $newPercentage, ?string $excludeId = null): bool
    {
        $query = static::where('supplier_contract_id', $contractId)
            ->where('is_active', true);
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $currentTotal = $query->sum('percentage');
        
        return ($currentTotal + $newPercentage) <= 100.00;
    }

    /**
     * Berechnet die verfügbare Prozentsatz-Kapazität für einen Vertrag
     */
    public static function getAvailablePercentage(string $contractId, ?string $excludeId = null): float
    {
        $query = static::where('supplier_contract_id', $contractId)
            ->where('is_active', true);
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $currentTotal = $query->sum('percentage');
        
        return max(0, 100.00 - $currentTotal);
    }

    /**
     * Berechnet die Gesamtsumme der Prozentsätze für einen Vertrag
     */
    public static function getTotalPercentage(string $contractId): float
    {
        return static::where('supplier_contract_id', $contractId)
            ->where('is_active', true)
            ->sum('percentage');
    }

    /**
     * Boot-Methode für Model-Events
     */
    protected static function boot()
    {
        parent::boot();

        // Validierung vor dem Speichern
        static::saving(function ($model) {
            // Prüfe ob die Gesamtsumme 100% nicht überschreitet
            if (!static::validateTotalPercentage(
                $model->supplier_contract_id, 
                $model->percentage, 
                $model->exists ? $model->id : null
            )) {
                throw new \InvalidArgumentException(
                    'Die Gesamtsumme aller Prozentsätze darf 100% nicht überschreiten. ' .
                    'Verfügbar: ' . static::getAvailablePercentage(
                        $model->supplier_contract_id, 
                        $model->exists ? $model->id : null
                    ) . '%'
                );
            }
        });
    }
}