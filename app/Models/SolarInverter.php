<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarInverter extends Model
{
    use HasFactory;

    protected $fillable = [
        'solar_plant_id',
        'fusion_solar_device_id',
        'name',
        'model',
        'serial_number',
        'manufacturer',
        'rated_power_kw',
        'efficiency_percent',
        'installation_date',
        'firmware_version',
        'status',
        'is_active',
        'last_sync_at',
        
        // Technische Details
        'input_voltage_range',
        'output_voltage',
        'max_dc_current',
        'max_ac_current',
        'protection_class',
        'cooling_method',
        'dimensions',
        'weight_kg',
        
        // Aktuelle Werte
        'current_power_kw',
        'current_voltage_v',
        'current_current_a',
        'current_frequency_hz',
        'current_temperature_c',
        'daily_yield_kwh',
        'total_yield_kwh',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
        'rated_power_kw' => 'decimal:2',
        'efficiency_percent' => 'decimal:2',
        'current_power_kw' => 'decimal:3',
        'current_voltage_v' => 'decimal:1',
        'current_current_a' => 'decimal:2',
        'current_frequency_hz' => 'decimal:2',
        'current_temperature_c' => 'decimal:1',
        'daily_yield_kwh' => 'decimal:2',
        'total_yield_kwh' => 'decimal:2',
        'weight_kg' => 'decimal:1',
    ];

    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    public function getFormattedStatusAttribute(): string
    {
        return match($this->status) {
            'normal' => 'Normal',
            'alarm' => 'Alarm',
            'offline' => 'Offline',
            'maintenance' => 'Wartung',
            default => 'Unbekannt'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'normal' => 'success',
            'alarm' => 'warning',
            'offline' => 'danger',
            'maintenance' => 'info',
            default => 'gray'
        };
    }
}