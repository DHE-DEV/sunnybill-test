<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarPlantNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'solar_plant_id',
        'user_id',
        'title',
        'content',
        'type',
        'is_favorite',
        'sort_order',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_favorite' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Beziehung zur Solaranlage
     */
    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    /**
     * Beziehung zum Benutzer (Ersteller der Notiz)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Formatierter Typ
     */
    public function getFormattedTypeAttribute(): string
    {
        return match($this->type) {
            'general' => 'Allgemein',
            'maintenance' => 'Wartung',
            'issue' => 'Problem',
            'improvement' => 'Verbesserung',
            default => $this->type,
        };
    }

    /**
     * Kurzer Inhalt für Übersichten
     */
    public function getShortContentAttribute(): string
    {
        return strlen($this->content) > 100 
            ? substr($this->content, 0, 100) . '...' 
            : $this->content;
    }

    /**
     * Formatiertes Erstellungsdatum
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('d.m.Y H:i');
    }
}
