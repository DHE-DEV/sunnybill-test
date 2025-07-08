<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($customer) {
            if (empty($customer->customer_number)) {
                $customer->customer_number = static::generateCustomerNumber();
            }
        });

        static::updating(function ($customer) {
            // Automatisch deactivated_at setzen, wenn Kunde deaktiviert wird
            if ($customer->isDirty('is_active')) {
                if (!$customer->is_active && $customer->getOriginal('is_active')) {
                    // Kunde wird deaktiviert
                    $customer->deactivated_at = now();
                } elseif ($customer->is_active && !$customer->getOriginal('is_active')) {
                    // Kunde wird reaktiviert
                    $customer->deactivated_at = null;
                }
            }
        });
    }

    protected $fillable = [
        'name',
        'customer_number',
        'company_name',
        'contact_person',
        'department',
        'email',
        'phone',
        'fax',
        'website',
        'lexoffice_id',
        'street',
        'address_line_2',
        'postal_code',
        'city',
        'state',
        'country',
        'country_code',
        'tax_number',
        'vat_id',
        'payment_terms',
        'payment_days',
        'bank_name',
        'iban',
        'bic',
        'notes',
        'lexoffice_synced_at',
        'is_active',
        'deactivated_at',
        'customer_type',
        'lexware_version',
        'lexware_json',
    ];

    protected $casts = [
        'lexoffice_id' => 'string',
        'lexoffice_synced_at' => 'datetime',
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
        'payment_days' => 'integer',
        'lexware_version' => 'integer',
        'lexware_json' => 'array',
    ];

    protected $attributes = [
        'country_code' => 'DE',
        'country' => 'Deutschland',
        'is_active' => true,
    ];

    /**
     * Beziehung zu Rechnungen
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Beziehung zu Solaranlagen-Beteiligungen
     */
    public function plantParticipations(): HasMany
    {
        return $this->hasMany(PlantParticipation::class);
    }

    /**
     * Beziehung zu monatlichen Gutschriften
     */
    public function monthlyCredits(): HasMany
    {
        return $this->hasMany(CustomerMonthlyCredit::class);
    }

    /**
     * Beziehung zu Kunden-Notizen
     */
    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    /**
     * Beziehung zu Mitarbeitern/Personen
     */
    public function employees(): HasMany
    {
        return $this->hasMany(CustomerEmployee::class);
    }

    /**
     * Beziehung zu aktiven Mitarbeitern/Personen
     */
    public function activeEmployees(): HasMany
    {
        return $this->hasMany(CustomerEmployee::class)->where('is_active', true);
    }

    /**
     * Hauptansprechpartner
     */
    public function primaryContact()
    {
        return $this->hasOne(CustomerEmployee::class)->where('is_primary_contact', true);
    }

    /**
     * Polymorphe Beziehung zu Telefonnummern
     */
    public function phoneNumbers(): MorphMany
    {
        return $this->morphMany(PhoneNumber::class, 'phoneable');
    }

    /**
     * Beziehung zu Adressen (polymorphe Beziehung)
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    /**
     * Standard-Adresse
     */
    public function standardAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('type', 'standard')->where('is_primary', true);
    }

    /**
     * Rechnungsadresse
     */
    public function billingAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('type', 'billing');
    }

    /**
     * Lieferadresse
     */
    public function shippingAddress()
    {
        return $this->morphOne(Address::class, 'addressable')->where('type', 'shipping');
    }

    /**
     * Beziehung zu Dokumenten
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Beziehung zu Solaranlagen-Abrechnungen
     */
    public function solarPlantBillings(): HasMany
    {
        return $this->hasMany(SolarPlantBilling::class);
    }

    /**
     * Alias für plantParticipations (für bessere Lesbarkeit in der UI)
     */
    public function solarParticipations(): HasMany
    {
        return $this->plantParticipations();
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
     * Vollständige Adresse als String
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->street;
        
        if ($this->address_line_2) {
            $address .= "\n" . $this->address_line_2;
        }
        
        $address .= "\n" . $this->postal_code . ' ' . $this->city;
        
        if ($this->state) {
            $address .= ', ' . $this->state;
        }
        
        if ($this->country && $this->country !== 'Deutschland') {
            $address .= "\n" . $this->country;
        }
        
        return $address;
    }

    /**
     * Prüft ob Kunde bereits mit Lexoffice synchronisiert ist
     */
    public function isSyncedWithLexoffice(): bool
    {
        return !empty($this->lexoffice_id);
    }

    /**
     * Prüft ob dieser Kunde Rechnungen hat
     */
    public function hasInvoices(): bool
    {
        return $this->invoices()->exists();
    }

    /**
     * Anzahl der Rechnungen dieses Kunden
     */
    public function getInvoiceCount(): int
    {
        return $this->invoices()->count();
    }

    /**
     * Prüft ob der Kunde gelöscht werden kann
     */
    public function canBeDeleted(): bool
    {
        return !$this->hasInvoices();
    }

    /**
     * Vollständiger Name für Anzeige
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->customer_type === 'business') {
            return $this->company_name ?: $this->name;
        }
        return $this->name;
    }

    /**
     * Prüft ob es sich um einen Geschäftskunden handelt
     */
    public function isBusinessCustomer(): bool
    {
        return $this->customer_type === 'business';
    }

    /**
     * Prüft ob es sich um einen Privatkunden handelt
     */
    public function isPrivateCustomer(): bool
    {
        return $this->customer_type === 'private';
    }

    /**
     * Prüft ob der Kunde deaktiviert ist
     */
    public function isDeactivated(): bool
    {
        return !$this->is_active;
    }

    /**
     * Gibt das Deaktivierungsdatum formatiert zurück
     */
    public function getFormattedDeactivatedAtAttribute(): ?string
    {
        return $this->deactivated_at?->format('d.m.Y H:i');
    }

    /**
     * Gibt den Status als Text zurück
     */
    public function getStatusTextAttribute(): string
    {
        return $this->is_active ? 'Aktiv' : 'Deaktiviert';
    }

    /**
     * Scope für aktive Kunden
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für deaktivierte Kunden
     */
    public function scopeDeactivated($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope für Geschäftskunden
     */
    public function scopeBusinessCustomers($query)
    {
        return $query->where('customer_type', 'business');
    }

    /**
     * Scope für Privatkunden
     */
    public function scopePrivateCustomers($query)
    {
        return $query->where('customer_type', 'private');
    }

    /**
     * Scope für Suche
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('company_name', 'like', "%{$search}%")
              ->orWhere('contact_person', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('city', 'like', "%{$search}%");
        });
    }

    /**
     * Prüfe ob USt-IdNr vorhanden
     */
    public function hasVatId(): bool
    {
        return !empty($this->vat_id);
    }

    /**
     * Formatierte USt-IdNr
     */
    public function getFormattedVatIdAttribute(): ?string
    {
        if (!$this->vat_id) {
            return null;
        }

        // Deutsche USt-IdNr formatieren
        if (str_starts_with($this->vat_id, 'DE')) {
            return substr($this->vat_id, 0, 2) . ' ' .
                   substr($this->vat_id, 2, 3) . ' ' .
                   substr($this->vat_id, 5, 3) . ' ' .
                   substr($this->vat_id, 8);
        }

        return $this->vat_id;
    }

    /**
     * Rechnungsadresse für ZUGFeRD (bevorzugt separate Rechnungsadresse, sonst Standard)
     */
    public function getBillingAddressForInvoice(): array
    {
        // Prüfe ob separate Rechnungsadresse existiert
        $billingAddress = $this->billingAddress;
        
        if ($billingAddress) {
            return [
                'street' => $billingAddress->street,
                'address_line_2' => $billingAddress->address_line_2,
                'postal_code' => $billingAddress->postal_code,
                'city' => $billingAddress->city,
                'state' => $billingAddress->state,
                'country' => $billingAddress->country,
                'country_code' => $billingAddress->country_code,
            ];
        }
        
        // Fallback auf Standard-Adresse aus Customer-Tabelle
        return [
            'street' => $this->street,
            'address_line_2' => $this->address_line_2,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'country_code' => $this->country_code,
        ];
    }

    /**
     * Prüft ob eine separate Rechnungsadresse hinterlegt ist
     */
    public function hasSeparateBillingAddress(): bool
    {
        return $this->billingAddress()->exists();
    }

    /**
     * Generiere nächste Kundennummer
     */
    public static function generateCustomerNumber(): string
    {
        try {
            $companySettings = CompanySetting::current();
            if (!$companySettings) {
                // Fallback wenn keine CompanySettings existieren
                $lastCustomer = static::orderBy('customer_number', 'desc')->first();
                if ($lastCustomer && preg_match('/(\d+)$/', $lastCustomer->customer_number, $matches)) {
                    $newNumber = (int) $matches[1] + 1;
                } else {
                    $newNumber = 1;
                }
                return 'K' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
            }
            
            $lastCustomers = static::orderBy('customer_number', 'desc')->get();
            $highestNumber = 0;
            
            foreach ($lastCustomers as $customer) {
                // Überspringe Kunden ohne Kundennummer
                if (empty($customer->customer_number)) {
                    continue;
                }
                
                try {
                    $number = $companySettings->extractCustomerNumber($customer->customer_number);
                    $highestNumber = max($highestNumber, $number);
                } catch (Exception $e) {
                    // Fallback für alte Formate
                    if (preg_match('/(\d+)$/', $customer->customer_number, $matches)) {
                        $number = (int) $matches[1];
                        $highestNumber = max($highestNumber, $number);
                    }
                }
            }
            
            $newNumber = $highestNumber + 1;
            return $companySettings->generateCustomerNumber($newNumber);
        } catch (Exception $e) {
            // Fallback wenn alles fehlschlägt
            $timestamp = time();
            return 'K' . substr($timestamp, -6);
        }
    }
}
