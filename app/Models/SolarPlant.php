<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolarPlant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($solarPlant) {
            if (empty($solarPlant->plant_number)) {
                $solarPlant->plant_number = static::generatePlantNumber();
            }
        });
    }

    protected $fillable = [
        'plant_number',
        'name',
        'location',
        'mastr_number',
        'mastr_registration_date',
        'malo_id',
        'melo_id',
        'vnb_process_number',
        'unit_commissioning_date',
        'pv_soll_planning_date',
        'pv_soll_project_number',
        'latitude',
        'longitude',
        'description',
        'installation_date',
        'planned_installation_date',
        'commissioning_date',
        'planned_commissioning_date',
        'total_capacity_kw',
        'panel_count',
        'inverter_count',
        'battery_capacity_kwh',
        'expected_annual_yield_kwh',
        'total_investment',
        'annual_operating_costs',
        'feed_in_tariff_per_kwh',
        'electricity_price_per_kwh',
        'degradation_rate',
        'status',
        'is_active',
        'notes',
        'fusion_solar_id',
        'last_sync_at',
        'custom_field_1',
        'custom_field_2',
        'custom_field_3',
        'custom_field_4',
        'custom_field_5',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'planned_installation_date' => 'date',
        'commissioning_date' => 'date',
        'planned_commissioning_date' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'total_capacity_kw' => 'decimal:6',
        'battery_capacity_kwh' => 'decimal:6',
        'expected_annual_yield_kwh' => 'decimal:6',
        'total_investment' => 'decimal:2',
        'annual_operating_costs' => 'decimal:2',
        'feed_in_tariff_per_kwh' => 'decimal:6',
        'electricity_price_per_kwh' => 'decimal:6',
        'degradation_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'panel_count' => 'integer',
        'inverter_count' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Beziehung zu Wechselrichtern
     */
    public function solarInverters(): HasMany
    {
        return $this->hasMany(SolarInverter::class);
    }

    /**
     * Beziehung zu Modulen
     */
    public function solarModules(): HasMany
    {
        return $this->hasMany(SolarModule::class);
    }

    /**
     * Beziehung zu Batterien
     */
    public function solarBatteries(): HasMany
    {
        return $this->hasMany(SolarBattery::class);
    }

    /**
     * Legacy-Beziehungen für Rückwärtskompatibilität
     */
    public function inverters(): HasMany
    {
        return $this->solarInverters();
    }

    public function panels(): HasMany
    {
        return $this->solarModules();
    }

    public function batteries(): HasMany
    {
        return $this->solarBatteries();
    }

    /**
     * Beziehung zu Kundenbeteiligungen
     */
    public function participations(): HasMany
    {
        return $this->hasMany(PlantParticipation::class);
    }

    /**
     * Beziehung zu monatlichen Ergebnissen
     */
    public function monthlyResults(): HasMany
    {
        return $this->hasMany(PlantMonthlyResult::class);
    }

    /**
     * Beziehung zu SOLL-Erträgen
     */
    public function targetYields(): HasMany
    {
        return $this->hasMany(SolarPlantTargetYield::class);
    }

    /**
     * Beziehung zu Notizen
     */
    public function notes(): HasMany
    {
        return $this->hasMany(SolarPlantNote::class);
    }

    /**
     * Beziehung zu Meilensteinen/Projektterminen
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(SolarPlantMilestone::class);
    }

    /**
     * Beziehung zu Lieferanten über Pivot-Tabelle
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'solar_plant_suppliers')
            ->withPivot(['supplier_employee_id', 'role', 'notes', 'start_date', 'end_date', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Aktive Lieferanten-Zuordnungen
     */
    public function activeSuppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'solar_plant_suppliers')
            ->wherePivot('is_active', true)
            ->withPivot(['supplier_employee_id', 'role', 'notes', 'start_date', 'end_date', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Direkte Beziehung zu Lieferanten-Zuordnungen
     */
    public function supplierAssignments(): HasMany
    {
        return $this->hasMany(SolarPlantSupplier::class);
    }

    /**
     * Aktive Lieferanten-Zuordnungen
     */
    public function activeSupplierAssignments(): HasMany
    {
        return $this->hasMany(SolarPlantSupplier::class)->where('is_active', true);
    }

    /**
     * Beziehung zu Dokumenten
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Beziehung zu Lieferantenverträgen über Pivot-Tabelle
     */
    public function supplierContracts(): BelongsToMany
    {
        return $this->belongsToMany(SupplierContract::class, 'supplier_contract_solar_plants')
            ->withPivot(['percentage', 'notes', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Aktive Lieferantenverträge (ohne gelöschte)
     */
    public function activeSupplierContracts(): BelongsToMany
    {
        return $this->belongsToMany(SupplierContract::class, 'supplier_contract_solar_plants')
            ->wherePivot('is_active', true)
            ->whereNull('supplier_contract_solar_plants.deleted_at')
            ->withPivot(['percentage', 'notes', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Direkte Beziehung zu Lieferantenvertrag-Zuordnungen
     */
    public function supplierContractAssignments(): HasMany
    {
        return $this->hasMany(SupplierContractSolarPlant::class);
    }

    /**
     * Aktive Lieferantenvertrag-Zuordnungen
     */
    public function activeSupplierContractAssignments(): HasMany
    {
        return $this->supplierContractAssignments()->active();
    }

    /**
     * Beziehung zu Solaranlagen-Abrechnungen
     */
    public function billings(): HasMany
    {
        return $this->hasMany(SolarPlantBilling::class);
    }

    /**
     * Gesamtbeteiligung aller Kunden berechnen
     */
    public function getTotalParticipationAttribute(): float
    {
        return $this->participations->sum('percentage');
    }

    /**
     * Verfügbare Beteiligung berechnen
     */
    public function getAvailableParticipationAttribute(): float
    {
        return 100 - $this->total_participation;
    }

    /**
     * Anzahl der Beteiligungen
     */
    public function getParticipationsCountAttribute(): int
    {
        return $this->participations()->count();
    }

    /**
     * Prüft ob weitere Beteiligungen möglich sind
     */
    public function canAddParticipation(float $percentage): bool
    {
        return ($this->total_participation + $percentage) <= 100;
    }

    /**
     * Gesamtprozentsatz aller Lieferantenvertrag-Zuordnungen
     */
    public function getTotalSupplierContractPercentageAttribute(): float
    {
        return $this->activeSupplierContractAssignments()->sum('percentage');
    }

    /**
     * Verfügbarer Prozentsatz für weitere Lieferantenvertrag-Zuordnungen
     */
    public function getAvailableSupplierContractPercentageAttribute(): float
    {
        return max(0, 100.00 - $this->total_supplier_contract_percentage);
    }

    /**
     * Prüft ob weitere Lieferantenvertrag-Zuordnungen möglich sind
     */
    public function canAddSupplierContractAssignment(float $percentage): bool
    {
        return ($this->total_supplier_contract_percentage + $percentage) <= 100.00;
    }

    /**
     * Anzahl der Lieferantenvertrag-Zuordnungen
     */
    public function getSupplierContractAssignmentsCountAttribute(): int
    {
        return $this->activeSupplierContractAssignments()->count();
    }

    /**
     * Anzahl der Komponenten
     */
    public function getComponentsCountAttribute(): array
    {
        return [
            'inverters' => $this->solarInverters()->count(),
            'modules' => $this->solarModules()->count(),
            'batteries' => $this->solarBatteries()->count(),
        ];
    }

    /**
     * Gesamtleistung aller Wechselrichter
     */
    public function getTotalInverterPowerAttribute(): float
    {
        return $this->solarInverters()->sum('rated_power_kw') ?? 0;
    }

    /**
     * Gesamtleistung aller Module
     */
    public function getTotalModulePowerAttribute(): float
    {
        return ($this->solarModules()->sum('rated_power_wp') ?? 0) / 1000; // Wp zu kW
    }

    /**
     * Gesamtkapazität aller Batterien
     */
    public function getTotalBatteryCapacityAttribute(): float
    {
        return $this->solarBatteries()->sum('capacity_kwh') ?? 0;
    }

    /**
     * Aktuelle Gesamtleistung
     */
    public function getCurrentTotalPowerAttribute(): float
    {
        return $this->solarInverters()->sum('current_power_kw') ?? 0;
    }

    /**
     * Aktueller Batterieladezustand (Durchschnitt)
     */
    public function getCurrentBatterySocAttribute(): ?float
    {
        $batteries = $this->solarBatteries()->whereNotNull('current_soc_percent');
        return $batteries->count() > 0 ? $batteries->avg('current_soc_percent') : null;
    }

    /**
     * Formatierte Gesamtinvestition
     */
    public function getFormattedTotalInvestmentAttribute(): string
    {
        return $this->total_investment ? number_format($this->total_investment, 2, ',', '.') . ' €' : '-';
    }

    /**
     * Formatierte jährliche Betriebskosten
     */
    public function getFormattedAnnualOperatingCostsAttribute(): string
    {
        return $this->annual_operating_costs ? number_format($this->annual_operating_costs, 2, ',', '.') . ' €' : '-';
    }

    /**
     * Formatierte Einspeisevergütung
     */
    public function getFormattedFeedInTariffAttribute(): string
    {
        return $this->feed_in_tariff_per_kwh ? number_format($this->feed_in_tariff_per_kwh, 6, ',', '.') . ' €/kWh' : '-';
    }

    /**
     * Formatierter Strompreis
     */
    public function getFormattedElectricityPriceAttribute(): string
    {
        return $this->electricity_price_per_kwh ? number_format($this->electricity_price_per_kwh, 6, ',', '.') . ' €/kWh' : '-';
    }

    /**
     * Formatierte Degradationsrate
     */
    public function getFormattedDegradationRateAttribute(): string
    {
        return $this->degradation_rate ? number_format($this->degradation_rate, 2, ',', '.') . ' %/Jahr' : '-';
    }

    /**
     * Prüft ob Geokoordinaten vorhanden sind
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Formatierte Koordinaten für Anzeige
     */
    public function getFormattedCoordinatesAttribute(): string
    {
        if (!$this->hasCoordinates()) {
            return 'Keine Koordinaten hinterlegt';
        }
        
        return number_format($this->latitude, 6, ',', '.') . '°N, ' .
               number_format($this->longitude, 6, ',', '.') . '°E';
    }

    /**
     * Google Maps URL für die Koordinaten
     */
    public function getGoogleMapsUrlAttribute(): ?string
    {
        if (!$this->hasCoordinates()) {
            return null;
        }
        
        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * OpenStreetMap URL für die Koordinaten
     */
    public function getOpenStreetMapUrlAttribute(): ?string
    {
        if (!$this->hasCoordinates()) {
            return null;
        }
        
        return "https://www.openstreetmap.org/?mlat={$this->latitude}&mlon={$this->longitude}&zoom=15";
    }

    /**
     * Generiert eine eindeutige Solaranlagennummer
     */
    private static function generatePlantNumber(): string
    {
        try {
            $companySettings = \App\Models\CompanySetting::current();
            $prefix = $companySettings?->solar_plant_number_prefix ?? 'SA';
            
            // Finde die höchste Nummer für das Präfix
            $lastPlant = static::where('plant_number', 'like', $prefix . '%')
                ->orderBy('plant_number', 'desc')
                ->first();
            
            if ($lastPlant && preg_match('/' . preg_quote($prefix) . '(\d+)$/', $lastPlant->plant_number, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            } else {
                $nextNumber = 1;
            }
            
            // Formatiere mit führenden Nullen (6 Stellen)
            return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            // Fallback wenn alles fehlschlägt
            $timestamp = time();
            return 'SA' . substr($timestamp, -6);
        }
    }
}
