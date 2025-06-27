<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Battery extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'solar_plant_id',
        'model',
        'manufacturer',
        'serial_number',
        'capacity_kwh',
    ];

    protected $casts = [
        'capacity_kwh' => 'decimal:2',
    ];

    /**
     * Beziehung zur Solaranlage
     */
    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    /**
     * Vollständige Bezeichnung
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->manufacturer} {$this->model}";
    }

    /**
     * Seriennummer mit Hersteller und Kapazität
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->full_name} ({$this->capacity_kwh} kWh, SN: {$this->serial_number})";
    }

    /**
     * Formatierte Kapazität
     */
    public function getFormattedCapacityAttribute(): string
    {
        return number_format($this->capacity_kwh, 2, ',', '.') . ' kWh';
    }
}
