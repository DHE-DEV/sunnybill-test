<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarBattery extends Model
{
    use HasFactory;

    protected $fillable = [
        'solar_plant_id',
        'fusion_solar_device_id',
        'name',
        'model',
        'serial_number',
        'manufacturer',
        'capacity_kwh',
        'usable_capacity_kwh',
        'rated_power_kw',
        'installation_date',
        'status',
        'is_active',
        'last_sync_at',
        
        // Technische Spezifikationen
        'battery_type',
        'chemistry',
        'nominal_voltage_v',
        'max_charge_power_kw',
        'max_discharge_power_kw',
        'efficiency_percent',
        'cycle_life',
        'warranty_years',
        'operating_temp_min',
        'operating_temp_max',
        'dimensions',
        'weight_kg',
        'protection_class',
        
        // Aktuelle Werte
        'current_soc_percent',
        'current_voltage_v',
        'current_current_a',
        'current_power_kw',
        'current_temperature_c',
        'charge_cycles',
        'daily_charge_kwh',
        'daily_discharge_kwh',
        'total_charge_kwh',
        'total_discharge_kwh',
        
        // Zustand und Gesundheit
        'health_percent',
        'remaining_capacity_kwh',
        'degradation_percent',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
        'capacity_kwh' => 'decimal:2',
        'usable_capacity_kwh' => 'decimal:2',
        'rated_power_kw' => 'decimal:2',
        'nominal_voltage_v' => 'decimal:1',
        'max_charge_power_kw' => 'decimal:2',
        'max_discharge_power_kw' => 'decimal:2',
        'efficiency_percent' => 'decimal:2',
        'cycle_life' => 'integer',
        'warranty_years' => 'integer',
        'operating_temp_min' => 'integer',
        'operating_temp_max' => 'integer',
        'weight_kg' => 'decimal:1',
        'current_soc_percent' => 'decimal:1',
        'current_voltage_v' => 'decimal:2',
        'current_current_a' => 'decimal:2',
        'current_power_kw' => 'decimal:3',
        'current_temperature_c' => 'decimal:1',
        'charge_cycles' => 'integer',
        'daily_charge_kwh' => 'decimal:2',
        'daily_discharge_kwh' => 'decimal:2',
        'total_charge_kwh' => 'decimal:2',
        'total_discharge_kwh' => 'decimal:2',
        'health_percent' => 'decimal:1',
        'remaining_capacity_kwh' => 'decimal:2',
        'degradation_percent' => 'decimal:2',
    ];

    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    public function getFormattedStatusAttribute(): string
    {
        return match($this->status) {
            'normal' => 'Normal',
            'charging' => 'Lädt',
            'discharging' => 'Entlädt',
            'standby' => 'Bereitschaft',
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
            'charging' => 'info',
            'discharging' => 'warning',
            'standby' => 'gray',
            'alarm' => 'warning',
            'offline' => 'danger',
            'maintenance' => 'info',
            default => 'gray'
        };
    }

    public function getFormattedChemistryAttribute(): string
    {
        return match($this->chemistry) {
            'li_ion' => 'Lithium-Ionen',
            'lifepo4' => 'LiFePO4',
            'lead_acid' => 'Blei-Säure',
            'saltwater' => 'Salzwasser',
            'flow' => 'Redox-Flow',
            default => $this->chemistry ?? 'Unbekannt'
        };
    }

    public function getSocColorAttribute(): string
    {
        $soc = $this->current_soc_percent ?? 0;
        
        if ($soc >= 80) return 'success';
        if ($soc >= 50) return 'warning';
        if ($soc >= 20) return 'danger';
        return 'gray';
    }

    public function getHealthColorAttribute(): string
    {
        $health = $this->health_percent ?? 100;
        
        if ($health >= 90) return 'success';
        if ($health >= 70) return 'warning';
        return 'danger';
    }
}