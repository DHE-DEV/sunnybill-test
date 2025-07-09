<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($supplier) {
            if (empty($supplier->supplier_number)) {
                $supplier->supplier_number = static::generateSupplierNumber();
            }
        });
    }

    protected $fillable = [
        'name',
        'supplier_number',
        'creditor_number',
        'contract_number',
        'contract_recognition_1',
        'contract_recognition_2',
        'contract_recognition_3',
        'company_name',
        'supplier_type_id',
        'contact_person',
        'department',
        'email',
        'phone',
        'fax',
        'website',
        'tax_number',
        'vat_id',
        'commercial_register',
        'street',
        'address_line_2',
        'postal_code',
        'city',
        'state',
        'country',
        'country_code',
        'bank_name',
        'iban',
        'bic',
        'account_holder',
        'payment_terms',
        'payment_days',
        'discount_percentage',
        'discount_days',
        'notes',
        'is_active',
        'lexoffice_id',
    ];

    protected $casts = [
        'payment_days' => 'integer',
        'discount_days' => 'integer',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Scope für aktive Lieferanten
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
     * Anzeigename (Firmenname oder Name)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
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
     * Prüft ob Lieferant bereits mit Lexoffice synchronisiert ist
     */
    public function isSyncedWithLexoffice(): bool
    {
        return !empty($this->lexoffice_id);
    }

    /**
     * Beziehungen
     */

    public function supplierType(): BelongsTo
    {
        return $this->belongsTo(SupplierType::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(SupplierEmployee::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(SupplierNote::class);
    }

    public function phoneNumbers(): MorphMany
    {
        return $this->morphMany(PhoneNumber::class, 'phoneable');
    }

    public function solarPlants(): HasMany
    {
        return $this->hasMany(SolarPlantSupplier::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(SupplierContract::class);
    }

    public function activeContracts(): HasMany
    {
        return $this->contracts()->active();
    }

    /**
     * Polymorphe Beziehung zu Dokumenten
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Polymorphe Beziehung zu Adressen
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    /**
     * Hole die primäre Rechnungsadresse oder Standard-Adresse
     */
    public function getBillingAddressForInvoice(): ?Address
    {
        // Erst nach separater Rechnungsadresse suchen
        $billingAddress = $this->addresses()
            ->where('type', 'billing')
            ->where('is_primary', true)
            ->first();

        if ($billingAddress) {
            return $billingAddress;
        }

        // Fallback: Standard-Adresse verwenden (aus den direkten Feldern)
        return null; // Supplier verwendet die direkten Adressfelder
    }

    /**
     * Prüfe ob eine separate Rechnungsadresse vorhanden ist
     */
    public function hasSeparateBillingAddress(): bool
    {
        return $this->addresses()
            ->where('type', 'billing')
            ->where('is_primary', true)
            ->exists();
    }

    /**
     * Hole die primäre Lieferadresse
     */
    public function getShippingAddress(): ?Address
    {
        return $this->addresses()
            ->where('type', 'shipping')
            ->where('is_primary', true)
            ->first();
    }

    /**
     * Prüfe ob eine separate Lieferadresse vorhanden ist
     */
    public function hasSeparateShippingAddress(): bool
    {
        return $this->addresses()
            ->where('type', 'shipping')
            ->where('is_primary', true)
            ->exists();
    }

    /**
     * Generiere nächste Lieferantennummer
     */
    public static function generateSupplierNumber(): string
    {
        $companySettings = CompanySetting::current();
        $lastSuppliers = static::orderBy('supplier_number', 'desc')->get();
        $highestNumber = 0;
        
        foreach ($lastSuppliers as $supplier) {
            try {
                $number = $companySettings->extractSupplierNumber($supplier->supplier_number);
                $highestNumber = max($highestNumber, $number);
            } catch (Exception $e) {
                // Fallback für alte Formate
                if (preg_match('/(\d+)$/', $supplier->supplier_number, $matches)) {
                    $number = (int) $matches[1];
                    $highestNumber = max($highestNumber, $number);
                }
            }
        }
        
        $newNumber = $highestNumber + 1;
        return $companySettings->generateSupplierNumber($newNumber);
    }
}