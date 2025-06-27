<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class TaxRate extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'rate',
        'valid_from',
        'valid_until',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected static function booted()
    {
        // Bei Erstellung eines neuen Steuersatzes automatisch erste Version erstellen
        static::created(function (TaxRate $taxRate) {
            TaxRateVersion::createVersion($taxRate, [], 'Steuersatz erstellt');
        });

        // Bei Aktualisierung eines Steuersatzes neue Version erstellen
        static::updated(function (TaxRate $taxRate) {
            $changedFields = [];
            $original = $taxRate->getOriginal();
            
            foreach (['name', 'description', 'rate', 'valid_from', 'valid_until', 'is_active', 'is_default'] as $field) {
                if ($taxRate->wasChanged($field)) {
                    $changedFields[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $taxRate->getAttribute($field)
                    ];
                }
            }

            if (!empty($changedFields)) {
                TaxRateVersion::createVersion(
                    $taxRate,
                    $changedFields,
                    'Steuersatz aktualisiert'
                );
            }
        });
    }

    /**
     * Beziehung zu Artikeln
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Beziehung zu Steuersatz-Versionen
     */
    public function versions(): HasMany
    {
        return $this->hasMany(TaxRateVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Aktuelle Version des Steuersatzes
     */
    public function currentVersion()
    {
        return $this->hasOne(TaxRateVersion::class)->where('is_current', true);
    }

    /**
     * Steuersatz als Prozent formatiert
     */
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->rate * 100, 2) . '%';
    }

    /**
     * Aktueller Steuersatz als Prozent
     */
    public function getCurrentRateAttribute(): string
    {
        return number_format($this->rate * 100, 2);
    }

    /**
     * Prüft ob der Steuersatz zu einem bestimmten Datum gültig ist
     */
    public function isValidAt(Carbon $date): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->valid_from && $date->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $date->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    /**
     * Scope für aktive Steuersätze
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für zu einem bestimmten Datum gültige Steuersätze
     */
    public function scopeValidAt($query, Carbon $date)
    {
        return $query->where('is_active', true)
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', $date);
            });
    }

    /**
     * Scope für Standard-Steuersatz
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Hole den aktuell gültigen Standard-Steuersatz
     */
    public static function getCurrentDefault(): ?self
    {
        return static::active()
            ->default()
            ->validAt(now())
            ->first();
    }

    /**
     * Hole alle zu einem Datum gültigen Steuersätze
     */
    public static function getValidAt(Carbon $date)
    {
        return static::validAt($date)->get();
    }

    /**
     * Gültigkeitszeitraum als String
     */
    public function getValidityPeriodAttribute(): string
    {
        $from = $this->valid_from ? $this->valid_from->format('d.m.Y') : 'Unbegrenzt';
        $until = $this->valid_until ? $this->valid_until->format('d.m.Y') : 'Unbegrenzt';
        
        return "Von {$from} bis {$until}";
    }

    /**
     * Prüft ob der Steuersatz gelöscht werden kann
     */
    public function canBeDeleted(): bool
    {
        return !$this->articles()->exists();
    }
}