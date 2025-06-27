<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'company_legal_form',
        'company_address',
        'company_postal_code',
        'company_city',
        'company_country',
        'phone',
        'fax',
        'email',
        'website',
        'tax_number',
        'vat_id',
        'commercial_register',
        'commercial_register_number',
        'management',
        'bank_name',
        'iban',
        'bic',
        'logo_path',
        'logo_width',
        'logo_height',
        'logo_margin_top',
        'logo_margin_right',
        'logo_margin_bottom',
        'logo_margin_left',
        'default_payment_days',
        'payment_terms',
        'pdf_margin_top',
        'pdf_margin_right',
        'pdf_margin_bottom',
        'pdf_margin_left',
        'article_price_decimal_places',
        'total_price_decimal_places',
        'customer_number_prefix',
        'supplier_number_prefix',
        'invoice_number_prefix',
        'invoice_number_include_year',
        'solar_plant_number_prefix',
        'project_number_prefix',
    ];

    protected $casts = [
        'logo_width' => 'integer',
        'logo_height' => 'integer',
        'logo_margin_top' => 'integer',
        'logo_margin_right' => 'integer',
        'logo_margin_bottom' => 'integer',
        'logo_margin_left' => 'integer',
        'default_payment_days' => 'integer',
        'pdf_margin_top' => 'decimal:2',
        'pdf_margin_right' => 'decimal:2',
        'pdf_margin_bottom' => 'decimal:2',
        'pdf_margin_left' => 'decimal:2',
        'article_price_decimal_places' => 'integer',
        'total_price_decimal_places' => 'integer',
        'invoice_number_include_year' => 'boolean',
    ];

    /**
     * Singleton-Pattern: Es gibt nur eine Einstellungs-Instanz
     */
    public static function current(): self
    {
        try {
            return static::first() ?? static::create([]);
        } catch (\Exception $e) {
            // Fallback wenn Tabelle noch nicht existiert (z.B. während Migration)
            return new static([
                'company_name' => 'SunnyBill',
                'default_payment_days' => 14,
                'article_price_decimal_places' => 2,
                'total_price_decimal_places' => 2,
                'customer_number_prefix' => 'KD',
                'supplier_number_prefix' => 'LF',
                'invoice_number_prefix' => 'RE',
                'invoice_number_include_year' => true,
            ]);
        }
    }

    /**
     * Vollständige Firmenadresse
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->company_address;
        
        if ($this->company_postal_code || $this->company_city) {
            $address .= "\n" . trim($this->company_postal_code . ' ' . $this->company_city);
        }
        
        if ($this->company_country && $this->company_country !== 'Deutschland') {
            $address .= "\n" . $this->company_country;
        }
        
        return $address;
    }

    /**
     * Vollständiger Firmenname mit Rechtsform
     */
    public function getFullCompanyNameAttribute(): string
    {
        $name = $this->company_name;
        
        if ($this->company_legal_form) {
            $name .= ' ' . $this->company_legal_form;
        }
        
        return $name;
    }

    /**
     * Logo-URL für die Anzeige
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }
        
        return Storage::url($this->logo_path);
    }

    /**
     * Prüft ob ein Logo hochgeladen wurde
     */
    public function hasLogo(): bool
    {
        return !empty($this->logo_path) && Storage::exists($this->logo_path);
    }

    /**
     * PDF Margins als String
     */
    public function getPdfMarginsAttribute(): string
    {
        return "{$this->pdf_margin_top}cm {$this->pdf_margin_right}cm {$this->pdf_margin_bottom}cm {$this->pdf_margin_left}cm";
    }

    /**
     * Logo-Styles für CSS
     */
    public function getLogoStylesAttribute(): string
    {
        $styles = [];
        
        if ($this->logo_width) {
            $styles[] = "width: {$this->logo_width}px";
        }
        
        if ($this->logo_height) {
            $styles[] = "height: {$this->logo_height}px";
        }
        
        if ($this->logo_margin_top) {
            $styles[] = "margin-top: {$this->logo_margin_top}px";
        }
        
        if ($this->logo_margin_right) {
            $styles[] = "margin-right: {$this->logo_margin_right}px";
        }
        
        if ($this->logo_margin_bottom) {
            $styles[] = "margin-bottom: {$this->logo_margin_bottom}px";
        }
        
        if ($this->logo_margin_left) {
            $styles[] = "margin-left: {$this->logo_margin_left}px";
        }
        
        return implode('; ', $styles);
    }


    /**
     * Formatierte IBAN
     */
    public function getFormattedIbanAttribute(): ?string
    {
        if (!$this->iban) {
            return null;
        }
        
        // IBAN in 4er-Gruppen formatieren
        return chunk_split($this->iban, 4, ' ');
    }

    /**
     * Handelsregister-Eintrag formatiert
     */
    public function getFormattedCommercialRegisterAttribute(): ?string
    {
        if (!$this->commercial_register || !$this->commercial_register_number) {
            return null;
        }
        
        return $this->commercial_register . ' ' . $this->commercial_register_number;
    }

    /**
     * Gibt die konfigurierten Nachkommastellen für Artikelpreise zurück
     */
    public function getArticlePriceDecimalPlaces(): int
    {
        return $this->article_price_decimal_places ?? 2;
    }

    /**
     * Gibt die konfigurierten Nachkommastellen für Gesamtpreise zurück
     */
    public function getTotalPriceDecimalPlaces(): int
    {
        return $this->total_price_decimal_places ?? 2;
    }

    /**
     * Generiert eine formatierte Kundennummer
     */
    public function generateCustomerNumber(int $number): string
    {
        $parts = [];
        
        if ($this->customer_number_prefix) {
            $parts[] = $this->customer_number_prefix;
        }
        
        $parts[] = str_pad($number, 4, '0', STR_PAD_LEFT);
        
        return implode('-', $parts);
    }

    /**
     * Generiert eine formatierte Lieferantennummer
     */
    public function generateSupplierNumber(int $number): string
    {
        $parts = [];
        
        if ($this->supplier_number_prefix) {
            $parts[] = $this->supplier_number_prefix;
        }
        
        $parts[] = str_pad($number, 4, '0', STR_PAD_LEFT);
        
        return implode('-', $parts);
    }

    /**
     * Generiert eine formatierte Rechnungsnummer
     */
    public function generateInvoiceNumber(int $number, ?int $year = null): string
    {
        $parts = [];
        
        if ($this->invoice_number_prefix) {
            $parts[] = $this->invoice_number_prefix;
        }
        
        if ($this->invoice_number_include_year) {
            $parts[] = $year ?? date('Y');
        }
        
        $parts[] = str_pad($number, 4, '0', STR_PAD_LEFT);
        
        return implode('-', $parts);
    }

    /**
     * Extrahiert die Nummer aus einer formatierten Kundennummer
     */
    public function extractCustomerNumber(string $customerNumber): int
    {
        $parts = explode('-', $customerNumber);
        return (int) end($parts);
    }

    /**
     * Extrahiert die Nummer aus einer formatierten Lieferantennummer
     */
    public function extractSupplierNumber(string $supplierNumber): int
    {
        $parts = explode('-', $supplierNumber);
        return (int) end($parts);
    }

    /**
     * Extrahiert die Nummer aus einer formatierten Rechnungsnummer
     */
    public function extractInvoiceNumber(string $invoiceNumber): int
    {
        $parts = explode('-', $invoiceNumber);
        return (int) end($parts);
    }
}
