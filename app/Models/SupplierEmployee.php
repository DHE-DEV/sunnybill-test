<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierEmployee extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'supplier_id',
        'first_name',
        'last_name',
        'position',
        'email',
        'notes',
        'is_primary_contact',
        'is_active',
    ];

    protected $casts = [
        'is_primary_contact' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'full_name',
        'display_name',
        'primary_phone',
    ];

    /**
     * Beziehung zum Lieferanten
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Polymorphe Beziehung zu Telefonnummern
     */
    public function phoneNumbers(): MorphMany
    {
        return $this->morphMany(PhoneNumber::class, 'phoneable');
    }

    /**
     * Beziehung zu Solaranlagen-Zuordnungen
     */
    public function solarPlantAssignments(): HasMany
    {
        return $this->hasMany(SolarPlantSupplier::class);
    }

    /**
     * Vollständiger Name
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Name mit Position
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->full_name;
        if ($this->position) {
            $name .= ' (' . $this->position . ')';
        }
        return $name;
    }

    /**
     * Haupttelefonnummer
     */
    public function getPrimaryPhoneAttribute(): ?string
    {
        return $this->phoneNumbers()->where('is_primary', true)->first()?->phone_number;
    }

    /**
     * Geschäftliche Telefonnummer
     */
    public function getBusinessPhoneAttribute(): ?string
    {
        return $this->phoneNumbers()->where('type', 'business')->first()?->phone_number;
    }

    /**
     * Mobiltelefonnummer
     */
    public function getMobilePhoneAttribute(): ?string
    {
        return $this->phoneNumbers()->where('type', 'mobile')->first()?->phone_number;
    }

    /**
     * Scope für aktive Mitarbeiter
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für Hauptansprechpartner
     */
    public function scopePrimaryContact($query)
    {
        return $query->where('is_primary_contact', true);
    }

    /**
     * Scope für Suche
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('position', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Boot-Methode für Model-Events
     */
    protected static function booted()
    {
        // Wenn ein Mitarbeiter als Hauptansprechpartner markiert wird,
        // alle anderen des gleichen Lieferanten deaktivieren
        static::saving(function (SupplierEmployee $employee) {
            if ($employee->is_primary_contact && $employee->isDirty('is_primary_contact')) {
                static::where('supplier_id', $employee->supplier_id)
                    ->where('id', '!=', $employee->id)
                    ->update(['is_primary_contact' => false]);
            }
        });
    }
}