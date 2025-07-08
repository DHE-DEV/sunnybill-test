<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolarPlantStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'color',
        'sort_order',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Beziehung zu Solaranlagen
     */
    public function solarPlants(): HasMany
    {
        return $this->hasMany(SolarPlant::class, 'status', 'key');
    }

    /**
     * Scope für aktive Status
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für sortierte Status
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Standard-Status abrufen
     */
    public static function getDefault()
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Alle aktiven Status als Array für Select-Felder
     */
    public static function getActiveOptions(): array
    {
        return static::active()
            ->ordered()
            ->pluck('name', 'key')
            ->toArray();
    }

    /**
     * Status-Farben als Array
     */
    public static function getColorOptions(): array
    {
        return [
            'gray' => 'Grau',
            'primary' => 'Primär',
            'secondary' => 'Sekundär',
            'success' => 'Erfolg',
            'danger' => 'Gefahr',
            'warning' => 'Warnung',
            'info' => 'Information',
        ];
    }

    /**
     * Prüft ob der Status gelöscht werden kann
     */
    public function canBeDeleted(): bool
    {
        // Standard-Status kann nicht gelöscht werden
        if ($this->is_default) {
            return false;
        }

        // Status mit zugeordneten Solaranlagen kann nicht gelöscht werden
        return $this->solarPlants()->count() === 0;
    }

    /**
     * Setzt einen neuen Standard-Status
     */
    public static function setDefault(string $key): bool
    {
        // Alle anderen als nicht-Standard markieren
        static::where('is_default', true)->update(['is_default' => false]);
        
        // Neuen Standard setzen
        return static::where('key', $key)->update(['is_default' => true]) > 0;
    }

    /**
     * Aktualisiert die Sortierreihenfolge
     */
    public static function updateSortOrder(array $sortOrder): void
    {
        foreach ($sortOrder as $index => $id) {
            static::where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }
}
