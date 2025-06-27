<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CustomerEmployee extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'customer_id',
        'first_name',
        'last_name',
        'email',
        'position',
        'department',
        'is_primary_contact',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_primary_contact' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Beziehung zum Kunden
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Beziehung zu Telefonnummern (polymorphe Beziehung)
     */
    public function phoneNumbers(): MorphMany
    {
        return $this->morphMany(PhoneNumber::class, 'phoneable');
    }

    /**
     * Haupttelefonnummer
     */
    public function primaryPhoneNumber()
    {
        return $this->morphOne(PhoneNumber::class, 'phoneable')->where('is_primary', true);
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
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('position', 'like', "%{$search}%")
              ->orWhere('department', 'like', "%{$search}%");
        });
    }

    /**
     * Boot-Methode für Model-Events
     */
    protected static function booted()
    {
        // Sicherstellen, dass nur ein Hauptansprechpartner pro Kunde existiert
        static::saving(function (CustomerEmployee $employee) {
            if ($employee->is_primary_contact) {
                static::where('customer_id', $employee->customer_id)
                    ->where('id', '!=', $employee->id)
                    ->update(['is_primary_contact' => false]);
            }
        });
    }
}