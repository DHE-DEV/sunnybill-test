<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'solar_plant_id',
        'fusion_solar_device_id',
        'name',
        'model',
        'serial_number',
        'manufacturer',
        'rated_power_wp',
        'efficiency_percent',
        'installation_date',
        'status',
        'is_active',
        'last_sync_at',
        
        // Technische Spezifikationen
        'cell_type',
        'module_type',
        'voltage_vmp',
        'current_imp',
        'voltage_voc',
        'current_isc',
        'temperature_coefficient',
        'dimensions',
        'weight_kg',
        'frame_color',
        'glass_type',
        
        // String/Array Zuordnung
        'string_number',
        'position_in_string',
        'orientation_degrees',
        'tilt_degrees',
        'shading_factor',
        
        // Aktuelle Werte
        'current_power_w',
        'current_voltage_v',
        'current_current_a',
        'current_temperature_c',
        'daily_yield_kwh',
        'total_yield_kwh',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
        'rated_power_wp' => 'integer',
        'efficiency_percent' => 'decimal:2',
        'voltage_vmp' => 'decimal:2',
        'current_imp' => 'decimal:2',
        'voltage_voc' => 'decimal:2',
        'current_isc' => 'decimal:2',
        'temperature_coefficient' => 'decimal:4',
        'weight_kg' => 'decimal:1',
        'orientation_degrees' => 'integer',
        'tilt_degrees' => 'integer',
        'shading_factor' => 'decimal:2',
        'current_power_w' => 'decimal:1',
        'current_voltage_v' => 'decimal:2',
        'current_current_a' => 'decimal:3',
        'current_temperature_c' => 'decimal:1',
        'daily_yield_kwh' => 'decimal:3',
        'total_yield_kwh' => 'decimal:2',
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
            'degraded' => 'Leistungsminderung',
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
            'degraded' => 'warning',
            default => 'gray'
        };
    }

    public function getFormattedCellTypeAttribute(): string
    {
        return match($this->cell_type) {
            'mono' => 'Monokristallin',
            'poly' => 'Polykristallin',
            'thin_film' => 'Dünnschicht',
            'perc' => 'PERC',
            'bifacial' => 'Bifazial',
            default => $this->cell_type ?? 'Unbekannt'
        };
    }
}