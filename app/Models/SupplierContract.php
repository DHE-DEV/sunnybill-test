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
        'malo_id',
        'ep_id',
        'creditor_number',
        'external_contract_number',
        'title',
        'description',
        'start_date',
        'end_date',
        'contract_value',
        'currency',
        'status',
        'payment_terms',
        'notes',
        'contract_recognition_1',
        'contract_recognition_2',
        'contract_recognition_3',
        'custom_field_1',
        'custom_field_2',
        'custom_field_3',
        'custom_field_4',
        'custom_field_5',
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
     * Beziehung zu Contract Matching Rules
     */
    public function contractMatchingRules(): HasMany
    {
        return $this->hasMany(ContractMatchingRule::class);
    }

    /**
     * Aktive Contract Matching Rules
     */
    public function activeContractMatchingRules(): HasMany
    {
        return $this->contractMatchingRules()->active();
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
     * Holt die Custom Field Labels für Supplier Contracts
     */
    public static function getCustomFieldLabels(): array
    {
        return \App\Models\CustomField::active()
            ->forContext('supplier_contract')
            ->ordered()
            ->pluck('field_label', 'field_key')
            ->toArray();
    }

    /**
     * Holt die Custom Field Konfigurationen für Supplier Contracts
     */
    public static function getCustomFieldConfigs(): array
    {
        return \App\Models\CustomField::active()
            ->forContext('supplier_contract')
            ->ordered()
            ->get()
            ->keyBy('field_key')
            ->toArray();
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
            
            if (empty($contract->contract_number)) {
                $contract->contract_number = static::generateUniqueContractNumber();
            }
        });
    }

    /**
     * Generiere eindeutige Vertragsnummer (verhindert Duplikate)
     * Verwendet fortlaufende Nummerierung und überspringt bereits verwendete Nummern
     */
    public static function generateUniqueContractNumber(): string
    {
        $companySettings = \App\Models\CompanySetting::current();
        $prefix = $companySettings?->supplier_contract_number_prefix ?? 'LV';
        $maxAttempts = 1000; // Genug Versuche für fortlaufende Nummerierung
        
        // Starte bei 1 und suche die erste verfügbare Nummer
        for ($number = 1; $number <= $maxAttempts; $number++) {
            $testNumber = $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            
            // Prüfe ob diese Nummer bereits existiert (aktive + soft-deleted)
            $exists = static::withTrashed()->where('contract_number', $testNumber)->exists();
            
            if (!$exists) {
                return $testNumber; // Erste verfügbare Nummer gefunden
            }
        }
        
        // Fallback: Verwende Timestamp wenn alle Versuche fehlschlagen
        $timestamp = time();
        return $prefix . '-' . $timestamp;
    }
}
