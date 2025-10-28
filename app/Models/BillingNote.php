<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingNote extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'solar_plant_id',
        'supplier_contract_id',
        'billing_year',
        'billing_month',
        'note',
        'created_by',
    ];

    protected $casts = [
        'billing_year' => 'integer',
        'billing_month' => 'integer',
    ];

    /**
     * Beziehung zur Solaranlage
     */
    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    /**
     * Beziehung zum Lieferantenvertrag
     */
    public function supplierContract(): BelongsTo
    {
        return $this->belongsTo(SupplierContract::class);
    }

    /**
     * Beziehung zum Benutzer, der die Notiz erstellt hat
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope für Notizen zu einem bestimmten Monat
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->where('billing_year', $year)
                     ->where('billing_month', $month);
    }

    /**
     * Scope für Notizen zu einer bestimmten Solaranlage
     */
    public function scopeForSolarPlant($query, string $solarPlantId)
    {
        return $query->where('solar_plant_id', $solarPlantId);
    }

    /**
     * Scope für Notizen zu einem bestimmten Vertrag
     */
    public function scopeForContract($query, string $contractId)
    {
        return $query->where('supplier_contract_id', $contractId);
    }
}
