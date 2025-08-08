<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PhoneNumber extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'phoneable_id',
        'phoneable_type',
        'phone_number',
        'type',
        'label',
        'is_primary',
        'is_favorite',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_favorite' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Polymorphe Beziehung zum Besitzer der Telefonnummer
     */
    public function phoneable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Formatierte Telefonnummer für Anzeige
     */
    public function getFormattedNumberAttribute(): string
    {
        // Einfache deutsche Telefonnummer-Formatierung
        $number = preg_replace('/[^\d+]/', '', $this->phone_number);
        
        if (str_starts_with($number, '+49')) {
            // Deutsche Nummer mit Ländercode
            $number = substr($number, 3);
            return '+49 ' . $this->formatGermanNumber($number);
        } elseif (str_starts_with($number, '0')) {
            // Deutsche Nummer ohne Ländercode
            return $this->formatGermanNumber($number);
        }
        
        return $this->phone_number;
    }

    /**
     * Formatiert deutsche Telefonnummern
     */
    private function formatGermanNumber(string $number): string
    {
        if (strlen($number) >= 10) {
            // Mobilnummer oder längere Festnetznummer
            return preg_replace('/(\d{4})(\d{3})(\d+)/', '$1 $2 $3', $number);
        } elseif (strlen($number) >= 7) {
            // Kürzere Festnetznummer
            return preg_replace('/(\d{3,4})(\d+)/', '$1 $2', $number);
        }
        
        return $number;
    }

    /**
     * Typ-Label für Anzeige
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'business' => 'Geschäftlich',
            'private' => 'Privat',
            'mobile' => 'Mobil',
            default => ucfirst($this->type)
        };
    }

    /**
     * Vollständige Beschreibung
     */
    public function getDisplayLabelAttribute(): string
    {
        $label = $this->getTypeLabel();
        if ($this->label) {
            $label .= ' (' . $this->label . ')';
        }
        if ($this->is_primary) {
            $label .= ' [Hauptnummer]';
        }
        return $label;
    }

    /**
     * Scope für Typ
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope für Hauptnummern
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope für Favoriten
     */
    public function scopeFavorite($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope für sortierte Reihenfolge (Favoriten zuerst, dann nach sort_order)
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_favorite', 'desc')
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Setzt diese Telefonnummer als Hauptnummer und deaktiviert alle anderen
     */
    public function makePrimary(): bool
    {
        // Transaktion verwenden für Konsistenz
        return \DB::transaction(function () {
            // Zuerst alle anderen Hauptnummern desselben Besitzers deaktivieren
            static::where('phoneable_id', $this->phoneable_id)
                ->where('phoneable_type', $this->phoneable_type)
                ->where('id', '!=', $this->getKey()) // getKey() für UUID-Kompatibilität
                ->update(['is_primary' => false]);

            // Dann diese als Hauptnummer setzen
            return $this->update(['is_primary' => true]);
        });
    }
}
