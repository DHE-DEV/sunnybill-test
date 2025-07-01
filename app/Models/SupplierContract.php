<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierContract extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'contract_number',
        'title',
        'description',
        'start_date',
        'end_date',
        'contract_value',
        'currency',
        'status',
        'payment_terms',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'contract_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Beziehung zum Lieferanten
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Beziehung zu Vertragsnotizen
     */
    public function contractNotes(): HasMany
    {
        return $this->hasMany(SupplierContractNote::class);
    }

    /**
     * Favoriten-Notizen
     */
    public function favoriteNotes(): HasMany
    {
        return $this->contractNotes()->favorites()->ordered();
    }

    /**
     * Standard-Notizen
     */
    public function standardNotes(): HasMany
    {
        return $this->contractNotes()->standard()->ordered();
    }

    /**
     * Polymorphe Beziehung zu Dokumenten
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Beziehung zu Solaranlagen-Zuordnungen
     */
    public function solarPlantAssignments(): HasMany
    {
        return $this->hasMany(SupplierContractSolarPlant::class);
    }

    /**
     * Beziehung zu Abrechnungen
     */
    public function billings(): HasMany
    {
        return $this->hasMany(SupplierContractBilling::class);
    }

    /**
     * Aktive Solaranlagen-Zuordnungen
     */
    public function activeSolarPlantAssignments(): HasMany
    {
        return $this->solarPlantAssignments()->active();
    }

    /**
     * Beziehung zu Solaranlagen über Pivot-Tabelle
     */
    public function solarPlants(): BelongsToMany
    {
        return $this->belongsToMany(SolarPlant::class, 'supplier_contract_solar_plants')
            ->withPivot(['percentage', 'notes', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Aktive Solaranlagen
     */
    public function activeSolarPlants(): BelongsToMany
    {
        return $this->belongsToMany(SolarPlant::class, 'supplier_contract_solar_plants')
            ->wherePivot('is_active', true)
            ->withPivot(['percentage', 'notes', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Scope für aktive Verträge
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für bestimmten Lieferanten
     */
    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope für Suche
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('contract_number', 'like', "%{$search}%")
              ->orWhere('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Formatierter Vertragswert
     */
    public function getFormattedContractValueAttribute(): string
    {
        if (!$this->contract_value) {
            return '-';
        }

        return number_format($this->contract_value, 2, ',', '.') . ' ' . ($this->currency ?? 'EUR');
    }

    /**
     * Status-Label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Entwurf',
            'active' => 'Aktiv',
            'expired' => 'Abgelaufen',
            'terminated' => 'Gekündigt',
            'completed' => 'Abgeschlossen',
            default => 'Unbekannt',
        };
    }

    /**
     * Prüft ob Vertrag noch gültig ist
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        return in_array($this->status, ['active']);
    }

    /**
     * Prüft ob Vertrag bald abläuft (30 Tage)
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->end_date) {
            return false;
        }

        return $this->end_date->diffInDays(now()) <= 30 && $this->end_date->isFuture();
    }

    /**
     * Gesamtprozentsatz aller Solaranlagen-Zuordnungen
     */
    public function getTotalSolarPlantPercentageAttribute(): float
    {
        return $this->activeSolarPlantAssignments()->sum('percentage');
    }

    /**
     * Verfügbarer Prozentsatz für weitere Solaranlagen-Zuordnungen
     */
    public function getAvailableSolarPlantPercentageAttribute(): float
    {
        return max(0, 100.00 - $this->total_solar_plant_percentage);
    }

    /**
     * Prüft ob weitere Solaranlagen-Zuordnungen möglich sind
     */
    public function canAddSolarPlantAssignment(float $percentage): bool
    {
        return ($this->total_solar_plant_percentage + $percentage) <= 100.00;
    }

    /**
     * Anzahl der Solaranlagen-Zuordnungen
     */
    public function getSolarPlantAssignmentsCountAttribute(): int
    {
        return $this->activeSolarPlantAssignments()->count();
    }

    /**
     * Verfügbare Status-Optionen
     */
    public static function getStatusOptions(): array
    {
        return [
            'draft' => 'Entwurf',
            'active' => 'Aktiv',
            'expired' => 'Abgelaufen',
            'terminated' => 'Gekündigt',
            'completed' => 'Abgeschlossen',
        ];
    }

    /**
     * Boot-Methode für Model-Events
     */
    protected static function booted()
    {
        static::creating(function (SupplierContract $contract) {
            if (!$contract->created_by) {
                $contract->created_by = auth()->user()?->name ?? 'System';
            }
        });
    }
}