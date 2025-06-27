<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'addressable_id',
        'addressable_type',
        'type',
        'company_name',
        'contact_person',
        'street_address',
        'postal_code',
        'city',
        'state',
        'country',
        'label',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Polymorphe Beziehung zum Besitzer der Adresse
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Typ-Label für Anzeige
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'standard' => 'Standard',
            'billing' => 'Rechnung',
            'shipping' => 'Lieferung',
            default => ucfirst($this->type)
        };
    }

    /**
     * Vollständige Adresse als String
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->company_name,
            $this->contact_person,
            $this->street_address,
            trim($this->postal_code . ' ' . $this->city),
            $this->state,
            $this->country !== 'Deutschland' ? $this->country : null
        ]);
        
        return implode("\n", $parts);
    }

    /**
     * Kurze Adresse für Listen
     */
    public function getShortAddressAttribute(): string
    {
        return trim($this->postal_code . ' ' . $this->city);
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
            $label .= ' [Hauptadresse]';
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
     * Scope für Hauptadressen
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Boot-Methode für Model-Events
     */
    protected static function booted()
    {
        // Wenn eine Adresse als Hauptadresse markiert wird,
        // alle anderen des gleichen Typs und Besitzers deaktivieren
        static::saving(function (Address $address) {
            if ($address->is_primary && $address->isDirty('is_primary')) {
                static::where('addressable_id', $address->addressable_id)
                    ->where('addressable_type', $address->addressable_type)
                    ->where('type', $address->type)
                    ->where('id', '!=', $address->id)
                    ->update(['is_primary' => false]);
            }
        });
    }
}