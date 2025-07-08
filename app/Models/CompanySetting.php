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
        'portal_url',
        'portal_name',
        'portal_description',
        'portal_enabled',
        // Lexware/Lexoffice API Einstellungen
        'lexware_sync_enabled',
        'lexware_api_url',
        'lexware_api_key',
        'lexware_organization_id',
        'lexware_auto_sync_customers',
        'lexware_auto_sync_addresses',
        'lexware_import_customer_numbers',
        'lexware_debug_logging',
        'lexware_last_sync',
        'lexware_last_error',
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
        'portal_enabled' => 'boolean',
        // Lexware/Lexoffice API Einstellungen
        'lexware_sync_enabled' => 'boolean',
        'lexware_auto_sync_customers' => 'boolean',
        'lexware_auto_sync_addresses' => 'boolean',
        'lexware_import_customer_numbers' => 'boolean',
        'lexware_debug_logging' => 'boolean',
        'lexware_last_sync' => 'datetime',
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
    public function extractCustomerNumber(?string $customerNumber): int
    {
        if (empty($customerNumber)) {
            return 0;
        }
        
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

    /**
     * Gibt die Portal-URL zurück oder einen Fallback
     */
    public function getPortalUrl(): string
    {
        try {
            if ($this->portal_enabled && $this->portal_url) {
                return rtrim($this->portal_url, '/');
            }
        } catch (\Exception $e) {
            // Spalte existiert noch nicht
        }
        
        // Fallback zur Admin-URL
        return rtrim(config('app.url'), '/') . '/admin';
    }

    /**
     * Gibt den Portal-Namen zurück oder einen Fallback
     */
    public function getPortalName(): string
    {
        try {
            return $this->portal_name ?: config('app.name', 'SunnyBill');
        } catch (\Exception $e) {
            // Spalte existiert noch nicht
            return config('app.name', 'SunnyBill');
        }
    }

    /**
     * Prüft ob das Portal aktiviert ist
     */
    public function isPortalEnabled(): bool
    {
        try {
            return (bool) $this->portal_enabled;
        } catch (\Exception $e) {
            // Spalte existiert noch nicht
            return false;
        }
    }

    // ===== LEXWARE/LEXOFFICE API METHODEN =====

    /**
     * Prüft ob die Lexware-Synchronisation aktiviert ist
     */
    public function isLexwareSyncEnabled(): bool
    {
        try {
            return (bool) $this->lexware_sync_enabled;
        } catch (\Exception $e) {
            // Spalte existiert noch nicht
            return false;
        }
    }

    /**
     * Gibt die Lexware API URL zurück oder Standard-URL
     */
    public function getLexwareApiUrl(): string
    {
        try {
            return $this->lexware_api_url ?: 'https://api.lexoffice.io/v1';
        } catch (\Exception $e) {
            return 'https://api.lexoffice.io/v1';
        }
    }

    /**
     * Gibt den Lexware API Key zurück
     */
    public function getLexwareApiKey(): ?string
    {
        try {
            return $this->lexware_api_key;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Gibt die Lexware Organization ID zurück
     */
    public function getLexwareOrganizationId(): ?string
    {
        try {
            return $this->lexware_organization_id;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Prüft ob alle erforderlichen Lexware-Einstellungen konfiguriert sind
     */
    public function hasValidLexwareConfig(): bool
    {
        return $this->isLexwareSyncEnabled() 
            && !empty($this->getLexwareApiKey()) 
            && !empty($this->getLexwareOrganizationId());
    }

    /**
     * Prüft ob automatische Kunden-Synchronisation aktiviert ist
     */
    public function isLexwareAutoSyncCustomersEnabled(): bool
    {
        try {
            return (bool) $this->lexware_auto_sync_customers;
        } catch (\Exception $e) {
            return true; // Standard: aktiviert
        }
    }

    /**
     * Prüft ob automatische Adress-Synchronisation aktiviert ist
     */
    public function isLexwareAutoSyncAddressesEnabled(): bool
    {
        try {
            return (bool) $this->lexware_auto_sync_addresses;
        } catch (\Exception $e) {
            return true; // Standard: aktiviert
        }
    }

    /**
     * Prüft ob Kundennummern-Import aktiviert ist
     */
    public function isLexwareImportCustomerNumbersEnabled(): bool
    {
        try {
            return (bool) $this->lexware_import_customer_numbers;
        } catch (\Exception $e) {
            return true; // Standard: aktiviert
        }
    }

    /**
     * Prüft ob Debug-Logging aktiviert ist
     */
    public function isLexwareDebugLoggingEnabled(): bool
    {
        try {
            return (bool) $this->lexware_debug_logging;
        } catch (\Exception $e) {
            return false; // Standard: deaktiviert
        }
    }

    /**
     * Aktualisiert den letzten Sync-Zeitstempel
     */
    public function updateLexwareLastSync(): void
    {
        try {
            $this->update(['lexware_last_sync' => now()]);
        } catch (\Exception $e) {
            // Spalte existiert noch nicht - ignorieren
        }
    }

    /**
     * Speichert den letzten Lexware-Fehler
     */
    public function setLexwareLastError(?string $error): void
    {
        try {
            $this->update(['lexware_last_error' => $error]);
        } catch (\Exception $e) {
            // Spalte existiert noch nicht - ignorieren
        }
    }

    /**
     * Gibt den letzten Lexware-Fehler zurück
     */
    public function getLexwareLastError(): ?string
    {
        try {
            return $this->lexware_last_error;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Gibt den letzten Sync-Zeitstempel zurück
     */
    public function getLexwareLastSync(): ?string
    {
        try {
            return $this->lexware_last_sync?->format('d.m.Y H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Prüft ob die Lexware-Konfiguration vollständig ist
     */
    public function getLexwareConfigStatus(): array
    {
        $status = [
            'enabled' => $this->isLexwareSyncEnabled(),
            'api_key_set' => !empty($this->getLexwareApiKey()),
            'organization_id_set' => !empty($this->getLexwareOrganizationId()),
            'api_url_set' => !empty($this->getLexwareApiUrl()),
            'last_sync' => $this->getLexwareLastSync(),
            'last_error' => $this->getLexwareLastError(),
            'is_valid' => $this->hasValidLexwareConfig()
        ];

        return $status;
    }
}
