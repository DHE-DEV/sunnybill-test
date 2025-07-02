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
     * Berechnet und aktualisiert die Prozentsätze aller Kostenträger eines Vertrags
     * basierend auf deren Kapazität
     */
    public static function recalculatePercentagesBasedOnCapacity(string $contractId): void
    {
        // Hole alle aktiven Zuordnungen mit den zugehörigen Solaranlagen
        $assignments = static::with('solarPlant')
            ->where('supplier_contract_id', $contractId)
            ->where('is_active', true)
            ->get();
        
        // Berechne die Gesamtkapazität
        $totalCapacity = $assignments->sum(function ($assignment) {
            return $assignment->solarPlant->total_capacity_kw ?? 0;
        });
        
        // Wenn keine Kapazität vorhanden ist, setze alle auf 0%
        if ($totalCapacity == 0) {
            $assignments->each(function ($assignment) {
                $assignment->update(['percentage' => 0]);
            });
            return;
        }
        
        // Berechne und aktualisiere die Prozentsätze
        $assignments->each(function ($assignment) use ($totalCapacity) {
            $plantCapacity = $assignment->solarPlant->total_capacity_kw ?? 0;
            $percentage = ($plantCapacity / $totalCapacity) * 100;
            
            // Runde auf 2 Dezimalstellen
            $percentage = round($percentage, 2);
            
            // Deaktiviere temporär die Validierung für diese Aktualisierung
            $assignment->withoutEvents(function () use ($assignment, $percentage) {
                $assignment->update(['percentage' => $percentage]);
            });
        });
        
        // Stelle sicher, dass die Summe genau 100% ergibt (Rundungsfehler korrigieren)
        static::adjustPercentagesToTotal($contractId);
    }
    
    /**
     * Korrigiert Rundungsfehler, damit die Summe genau 100% ergibt
     */
    private static function adjustPercentagesToTotal(string $contractId): void
    {
        $assignments = static::where('supplier_contract_id', $contractId)
            ->where('is_active', true)
            ->orderBy('percentage', 'desc')
            ->get();
        
        $currentTotal = $assignments->sum('percentage');
        
        if ($currentTotal == 100) {
            return;
        }
        
        // Berechne die Differenz
        $difference = 100 - $currentTotal;
        
        // Wende die Differenz auf den größten Wert an
        if ($assignments->isNotEmpty()) {
            $largestAssignment = $assignments->first();
            $newPercentage = $largestAssignment->percentage + $difference;
            
            // Stelle sicher, dass der Wert nicht negativ wird
            if ($newPercentage >= 0) {
                $largestAssignment->withoutEvents(function () use ($largestAssignment, $newPercentage) {
                    $largestAssignment->update(['percentage' => round($newPercentage, 2)]);
                });
            }
        }
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