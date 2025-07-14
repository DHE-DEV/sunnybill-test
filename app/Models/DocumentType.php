<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'description',
        'color',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Beziehung zu Dokumenten
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Scope für aktive Dokumententypen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für sortierte Dokumententypen
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Gibt alle aktiven Dokumententypen als Array für Select-Felder zurück
     */
    public static function getSelectOptions(): array
    {
        return static::active()
            ->ordered()
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Gibt alle aktiven Dokumententypen als Array mit Key-Value für Select-Felder zurück
     */
    public static function getKeySelectOptions(): array
    {
        return static::active()
            ->ordered()
            ->pluck('name', 'key')
            ->toArray();
    }

    /**
     * Findet einen Dokumententyp anhand des Keys
     */
    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }
}