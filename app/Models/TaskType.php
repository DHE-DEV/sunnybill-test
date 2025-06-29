<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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
     * Beziehung zu Aufgaben
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Scope für aktive Aufgabentypen
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für sortierte Aufgabentypen
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Prüft ob der Aufgabentyp gelöscht werden kann
     */
    public function canBeDeleted(): bool
    {
        return $this->tasks()->count() === 0;
    }

    /**
     * Boot-Methode für Model-Events
     */
    protected static function boot()
    {
        parent::boot();

        // Verhindert das Löschen wenn Aufgaben zugeordnet sind
        static::deleting(function ($taskType) {
            if (!$taskType->canBeDeleted()) {
                throw new \Exception('Aufgabentyp kann nicht gelöscht werden, da noch Aufgaben zugeordnet sind.');
            }
        });
    }
}
