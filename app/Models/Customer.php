<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
                $customer->customer_number = static::generateUniqueCustomerNumber();
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
        'account_holder',
        'payment_method',
        'notes',
        'lexoffice_synced_at',
        'is_active',
        'deactivated_at',
        'customer_type',
        'contact_source',
        'ranking',
        'lexware_version',
        'lexware_json',
        'custom_field_1',
        'custom_field_2',
        'custom_field_3',
        'custom_field_4',
        'custom_field_5',
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

    protected $appends = [
        'display_name',
        'full_address',
        'primary_phone',
        'business_phone',
        'mobile_phone',
        'status_text',
        'formatted_vat_id',
        'customer_score',
        'formatted_customer_score',
        'total_kwp_participation',
        'formatted_total_kwp_participation',
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
     * Direkte Beziehung zu Solaranlagen über Beteiligungen
     */
    public function solarPlants(): BelongsToMany
    {
        return $this->belongsToMany(SolarPlant::class, 'plant_participations')
            ->withPivot('percentage')
            ->withTimestamps();
    }

    /**
     * Beziehung zu Solaranlagen-Abrechnungen
     */
    public function solarPlantBillings(): HasMany
    {
        return $this->hasMany(SolarPlantBilling::class);
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
     * Beziehung zu Artikeln über Pivot-Tabelle
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'customer_article')
            ->withPivot('quantity', 'unit_price', 'notes', 'is_active', 'billing_requirement')
            ->withTimestamps();
    }

    /**
     * Alias für plantParticipations (für bessere Lesbarkeit in der UI)
     */
    public function solarParticipations(): HasMany
    {
        return $this->plantParticipations();
    }

    /**
     * Alias für plantParticipations (für API Kompatibilität)
     */
    public function participations(): HasMany
    {
        return $this->plantParticipations();
    }

    /**
     * Beziehung zu Projekten
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Beziehung zu Aufgaben
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
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
        $addressParts = [];
        
        // Straße hinzufügen
        if (!empty($this->street)) {
            $addressParts[] = $this->street;
        }
        
        // Zusätzliche Adresszeile hinzufügen
        if (!empty($this->address_line_2)) {
            $addressParts[] = $this->address_line_2;
        }
        
        // PLZ und Stadt hinzufügen
        $cityLine = [];
        if (!empty($this->postal_code)) {
            $cityLine[] = $this->postal_code;
        }
        if (!empty($this->city)) {
            $cityLine[] = $this->city;
        }
        
        if (!empty($cityLine)) {
            $addressParts[] = implode(' ', $cityLine);
        }
        
        // Bundesland hinzufügen
        if (!empty($this->state)) {
            $addressParts[count($addressParts) - 1] .= ', ' . $this->state;
        }
        
        // Land hinzufügen (nur wenn es nicht Deutschland ist)
        if (!empty($this->country) && $this->country !== 'Deutschland') {
            $addressParts[] = $this->country;
        }
        
        return implode("\n", $addressParts);
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
        if ($this->customer_type === 'lead') {
            return ($this->company_name ?: $this->name) . ' (Lead)';
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
     * Prüft ob es sich um einen Lead handelt
     */
    public function isLead(): bool
    {
        return $this->customer_type === 'lead';
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
     * Scope für Leads
     */
    public function scopeLeads($query)
    {
        return $query->where('customer_type', 'lead');
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
     * Generiere nächste Kundennummer (nur für Fallback)
     * Für normale Verwendung sollte generateUniqueCustomerNumber() verwendet werden
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
            
            // Berücksichtige nur aktive Records für fortlaufende Nummerierung
            $lastCustomers = static::orderBy('customer_number', 'desc')->get();
            $highestNumber = 0;
            
            foreach ($lastCustomers as $customer) {
                // Überspringe Kunden ohne Kundennummer
                if (empty($customer->customer_number)) {
                    continue;
                }
                
                try {
                    $number = $companySettings->extractCustomerNumber($customer->customer_number);
                    // Nur "normale" Nummern berücksichtigen (unter 10000)
                    if ($number < 10000) {
                        $highestNumber = max($highestNumber, $number);
                    }
                } catch (Exception $e) {
                    // Fallback für alte Formate
                    if (preg_match('/(\d+)$/', $customer->customer_number, $matches)) {
                        $number = (int) $matches[1];
                        if ($number < 10000) {
                            $highestNumber = max($highestNumber, $number);
                        }
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

    /**
     * Generiere eindeutige Kundennummer (verhindert Duplikate)
     * Verwendet fortlaufende Nummerierung und überspringt bereits verwendete Nummern
     */
    public static function generateUniqueCustomerNumber(): string
    {
        $companySettings = CompanySetting::current();
        $maxAttempts = 1000; // Genug Versuche für fortlaufende Nummerierung
        
        // Starte bei 1 und suche die erste verfügbare Nummer
        for ($number = 1; $number <= $maxAttempts; $number++) {
            $testNumber = $companySettings ?
                $companySettings->generateCustomerNumber($number) :
                'K' . str_pad($number, 6, '0', STR_PAD_LEFT);
            
            // Prüfe ob diese Nummer bereits existiert (aktive + soft-deleted)
            $exists = static::withTrashed()->where('customer_number', $testNumber)->exists();
            
            if (!$exists) {
                return $testNumber; // Erste verfügbare Nummer gefunden
            }
        }
        
        // Fallback: Verwende Timestamp wenn alle Versuche fehlschlagen
        $timestamp = time();
        $prefix = $companySettings ? ($companySettings->customer_number_prefix ?? 'K') : 'K';
        return $prefix . '-' . $timestamp;
    }

    /**
     * Berechnet den automatischen Kundenscore basierend auf Anlagen-Beteiligungen
     * Formel: (Summe der Beteiligung von der Anlagen kWp gerundet auf 0 Nachkommastellen) * 1000
     */
    public function getCustomerScoreAttribute(): int
    {
        $totalKwp = $this->plantParticipations()->with('solarPlant')->get()->sum(function ($participation) {
            $plantKwp = $participation->solarPlant->total_capacity_kw ?? 0;
            $participationKwp = $plantKwp * ($participation->percentage / 100);
            return $participationKwp;
        });
        
        return (int) round($totalKwp * 1000);
    }

    /**
     * Formatierter Kundenscore mit Tausender-Trennzeichen
     */
    public function getFormattedCustomerScoreAttribute(): string
    {
        return number_format($this->customer_score, 0, ',', '.');
    }

    /**
     * Gesamte kWp-Beteiligung des Kunden (ohne Multiplikation)
     */
    public function getTotalKwpParticipationAttribute(): float
    {
        return $this->plantParticipations()->with('solarPlant')->get()->sum(function ($participation) {
            $plantKwp = $participation->solarPlant->total_capacity_kw ?? 0;
            return $plantKwp * ($participation->percentage / 100);
        });
    }

    /**
     * Formatierte Gesamt-kWp-Beteiligung
     */
    public function getFormattedTotalKwpParticipationAttribute(): string
    {
        return number_format($this->total_kwp_participation, 2, ',', '.') . ' kWp';
    }
}
