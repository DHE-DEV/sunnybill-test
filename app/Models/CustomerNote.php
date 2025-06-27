<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'title',
        'content',
        'type',
        'is_favorite',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_favorite' => 'boolean',
    ];

    /**
     * Beziehung zum Kunden
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
            'contact' => 'Kontakt',
            'issue' => 'Problem',
            'payment' => 'Zahlung',
            'contract' => 'Vertrag',
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

    /**
     * Scope für Favoriten
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope für Standard-Notizen (nicht Favoriten)
     */
    public function scopeStandard($query)
    {
        return $query->where('is_favorite', false);
    }

    /**
     * Scope für sortierte Anzeige (Favoriten zuerst, dann nach sort_order)
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_favorite', 'desc')
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Scope für Favoriten sortiert nach sort_order
     */
    public function scopeFavoritesOrdered($query)
    {
        return $query->favorites()
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Scope für Standard-Notizen sortiert nach Datum
     */
    public function scopeStandardOrdered($query)
    {
        return $query->standard()
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Boot-Methode für Model-Events
     */
    protected static function booted()
    {
        // Automatisch den aktuellen Benutzer als Ersteller setzen
        static::creating(function (CustomerNote $note) {
            if (!$note->created_by) {
                $note->created_by = auth()->user()?->name ?? 'System';
            }
            
            // Automatisch sort_order setzen für neue Favoriten
            if ($note->is_favorite && !$note->sort_order) {
                $maxSortOrder = static::where('customer_id', $note->customer_id)
                    ->where('is_favorite', true)
                    ->max('sort_order') ?? 0;
                $note->sort_order = $maxSortOrder + 1;
            }
        });
    }
}
