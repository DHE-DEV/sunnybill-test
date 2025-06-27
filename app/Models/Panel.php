<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Panel extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'solar_plant_id',
        'model',
        'manufacturer',
        'serial_number',
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
     * Seriennummer mit Hersteller
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->full_name} (SN: {$this->serial_number})";
    }
}
