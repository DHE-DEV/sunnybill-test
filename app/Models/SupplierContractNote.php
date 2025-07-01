<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierContractNote extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'supplier_contract_id',
        'title',
        'content',
        'created_by',
        'is_favorite',
        'sort_order',
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
    ];

    /**
     * Beziehung zum Lieferantenvertrag
     */
    public function supplierContract(): BelongsTo
    {
        return $this->belongsTo(SupplierContract::class);
    }

    /**
     * Kurzer Inhalt für Anzeige (HTML-Tags entfernt)
     */
    public function getShortContentAttribute(): string
    {
        $plainText = strip_tags($this->content);
        return strlen($plainText) > 100
            ? substr($plainText, 0, 100) . '...'
            : $plainText;
    }

    /**
     * Formatiertes Erstellungsdatum
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('d.m.Y H:i');
    }

    /**
     * Scope für bestimmten Vertrag
     */
    public function scopeForContract($query, $contractId)
    {
        return $query->where('supplier_contract_id', $contractId);
    }

    /**
     * Scope für Suche
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%")
              ->orWhere('created_by', 'like', "%{$search}%");
        });
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
        static::creating(function (SupplierContractNote $note) {
            if (!$note->created_by) {
                $note->created_by = auth()->user()?->name ?? 'System';
            }
            
            // Automatisch sort_order setzen für neue Favoriten
            if ($note->is_favorite && !$note->sort_order) {
                $maxSortOrder = static::where('supplier_contract_id', $note->supplier_contract_id)
                    ->where('is_favorite', true)
                    ->max('sort_order') ?? 0;
                $note->sort_order = $maxSortOrder + 1;
            }
        });
    }
}