<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRateVersion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tax_rate_id',
        'version_number',
        'name',
        'description',
        'rate',
        'valid_from',
        'valid_until',
        'is_active',
        'is_default',
        'changed_by',
        'change_reason',
        'changed_fields',
        'is_current',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'changed_fields' => 'array',
        'is_current' => 'boolean',
    ];

    /**
     * Beziehung zum Steuersatz
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * Formatierter Steuersatz als Prozent
     */
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->rate * 100, 2) . '%';
    }

    /**
     * Scope für aktuelle Version
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope für bestimmten Steuersatz
     */
    public function scopeForTaxRate($query, $taxRateId)
    {
        return $query->where('tax_rate_id', $taxRateId);
    }

    /**
     * Scope für aktuell gültige Versionen
     */
    public function scopeCurrentlyValid($query)
    {
        $now = now()->toDateString();
        return $query->where('valid_from', '<=', $now)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('valid_until')
                          ->orWhere('valid_until', '>=', $now);
                    });
    }

    /**
     * Scope für zukünftig gültige Versionen
     */
    public function scopeFutureValid($query)
    {
        $now = now()->toDateString();
        return $query->where('valid_from', '>', $now);
    }

    /**
     * Scope für abgelaufene Versionen
     */
    public function scopeExpired($query)
    {
        $now = now()->toDateString();
        return $query->whereNotNull('valid_until')
                    ->where('valid_until', '<', $now);
    }

    /**
     * Erstelle eine neue Version für einen Steuersatz
     */
    public static function createVersion(TaxRate $taxRate, array $changedFields = [], string $changeReason = null, string $changedBy = null): self
    {
        // Aktuelle Version deaktivieren
        static::where('tax_rate_id', $taxRate->id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        // Nächste Versionsnummer ermitteln
        $nextVersion = static::where('tax_rate_id', $taxRate->id)
            ->max('version_number') + 1;

        // Neue Version erstellen
        return static::create([
            'tax_rate_id' => $taxRate->id,
            'version_number' => $nextVersion,
            'name' => $taxRate->name,
            'description' => $taxRate->description,
            'rate' => $taxRate->rate,
            'valid_from' => $taxRate->valid_from,
            'valid_until' => $taxRate->valid_until,
            'is_active' => $taxRate->is_active,
            'is_default' => $taxRate->is_default,
            'changed_by' => $changedBy ?? auth()->user()?->name ?? 'System',
            'change_reason' => $changeReason,
            'changed_fields' => $changedFields,
            'is_current' => true,
        ]);
    }

    /**
     * Hole eine bestimmte Version eines Steuersatzes
     */
    public static function getVersion(TaxRate $taxRate, int $versionNumber): ?self
    {
        return static::where('tax_rate_id', $taxRate->id)
            ->where('version_number', $versionNumber)
            ->first();
    }

    /**
     * Hole die aktuelle Version eines Steuersatzes
     */
    public static function getCurrentVersion(TaxRate $taxRate): ?self
    {
        return static::where('tax_rate_id', $taxRate->id)
            ->where('is_current', true)
            ->first();
    }

    /**
     * Hole alle Versionen eines Steuersatzes
     */
    public static function getVersionHistory(TaxRate $taxRate)
    {
        return static::where('tax_rate_id', $taxRate->id)
            ->orderBy('version_number', 'desc')
            ->get();
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
}