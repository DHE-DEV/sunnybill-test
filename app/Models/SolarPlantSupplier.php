<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarPlantSupplier extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'solar_plant_suppliers';

    protected $fillable = [
        'solar_plant_id',
        'supplier_id',
        'supplier_employee_id',
        'role',
        'notes',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Beziehung zur Solaranlage
     */
    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    /**
     * Beziehung zum Lieferanten
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Beziehung zum Lieferanten-Mitarbeiter
     */
    public function supplierEmployee(): BelongsTo
    {
        return $this->belongsTo(SupplierEmployee::class);
    }

    /**
     * Anzeigename für die Zuordnung
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->supplier->company_name;
        
        if ($this->supplierEmployee) {
            $name .= ' - ' . $this->supplierEmployee->full_name;
        }
        
        if ($this->role) {
            $name .= ' (' . $this->role . ')';
        }
        
        return $name;
    }

    /**
     * Status der Zuordnung
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'Inaktiv';
        }
        
        if ($this->end_date && $this->end_date->isPast()) {
            return 'Beendet';
        }
        
        if ($this->start_date && $this->start_date->isFuture()) {
            return 'Geplant';
        }
        
        return 'Aktiv';
    }

    /**
     * Dauer der Zusammenarbeit
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->start_date) {
            return null;
        }
        
        $end = $this->end_date ?? now();
        $duration = $this->start_date->diffInDays($end);
        
        if ($duration < 30) {
            return $duration . ' Tage';
        } elseif ($duration < 365) {
            return round($duration / 30) . ' Monate';
        } else {
            return round($duration / 365, 1) . ' Jahre';
        }
    }

    /**
     * Scope für aktive Zuordnungen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für laufende Zuordnungen
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope für bestimmte Rolle
     */
    public function scopeWithRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope für bestimmten Lieferanten
     */
    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope für bestimmte Solaranlage
     */
    public function scopeForSolarPlant($query, $solarPlantId)
    {
        return $query->where('solar_plant_id', $solarPlantId);
    }
}