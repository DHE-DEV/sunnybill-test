<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceVersion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'invoice_id',
        'version_number',
        'invoice_data',
        'customer_data',
        'items_data',
        'changed_by',
        'change_reason',
        'changed_fields',
        'is_current',
    ];

    protected $casts = [
        'invoice_data' => 'array',
        'customer_data' => 'array',
        'items_data' => 'array',
        'changed_fields' => 'array',
        'is_current' => 'boolean',
    ];

    /**
     * Beziehung zur Rechnung
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Scope für aktuelle Version
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope für bestimmte Rechnung
     */
    public function scopeForInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    /**
     * Erstelle eine neue Version für eine Rechnung
     */
    public static function createVersion(Invoice $invoice, array $changedFields = [], string $changeReason = null, string $changedBy = null): self
    {
        // Aktuelle Version deaktivieren
        static::where('invoice_id', $invoice->id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        // Nächste Versionsnummer ermitteln
        $nextVersion = static::where('invoice_id', $invoice->id)
            ->max('version_number') + 1;

        // Lade alle notwendigen Daten
        $invoice->load(['customer', 'items.article', 'items.articleVersion', 'items.taxRateVersion']);

        // Erstelle vollständige Datenstrukturen
        $invoiceData = [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status,
            'total' => $invoice->total,
            'due_date' => $invoice->due_date?->toDateString(),
            'lexoffice_id' => $invoice->lexoffice_id,
            'created_at' => $invoice->created_at?->toISOString(),
            'updated_at' => $invoice->updated_at?->toISOString(),
        ];

        $customerData = $invoice->customer ? [
            'id' => $invoice->customer->id,
            'customer_number' => $invoice->customer->customer_number,
            'name' => $invoice->customer->name,
            'customer_type' => $invoice->customer->customer_type,
            'email' => $invoice->customer->email,
            'phone' => $invoice->customer->phone,
            'street' => $invoice->customer->street,
            'city' => $invoice->customer->city,
            'postal_code' => $invoice->customer->postal_code,
            'country' => $invoice->customer->country,
            'country_code' => $invoice->customer->country_code,
            'company_name' => $invoice->customer->company_name,
            'contact_person' => $invoice->customer->contact_person,
            'department' => $invoice->customer->department,
            'website' => $invoice->customer->website,
            'vat_id' => $invoice->customer->vat_id,
            'bank_name' => $invoice->customer->bank_name,
            'iban' => $invoice->customer->iban,
            'bic' => $invoice->customer->bic,
            'tax_number' => $invoice->customer->tax_number,
            'payment_days' => $invoice->customer->payment_days,
        ] : null;

        $itemsData = $invoice->items->map(function ($item) {
            return [
                'id' => $item->id, // ID wird für die Versionserstellung benötigt
                'article_id' => $item->article_id,
                'article_version_id' => $item->article_version_id,
                'tax_rate_version_id' => $item->tax_rate_version_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
                'total' => $item->total,
                // Artikel-Daten zum Zeitpunkt der Rechnung
                'article_data' => $item->article ? [
                    'id' => $item->article->id,
                    'name' => $item->article->name,
                    'description' => $item->article->description,
                    'type' => $item->article->type,
                    'price' => $item->article->price,
                    'tax_rate' => $item->article->tax_rate,
                    'tax_rate_id' => $item->article->tax_rate_id,
                    'unit' => $item->article->unit,
                    'decimal_places' => $item->article->decimal_places,
                    'total_decimal_places' => $item->article->total_decimal_places,
                ] : null,
                // Artikel-Version-Daten falls vorhanden
                'article_version_data' => $item->articleVersion ? [
                    'id' => $item->articleVersion->id,
                    'version_number' => $item->articleVersion->version_number,
                    'name' => $item->articleVersion->name,
                    'description' => $item->articleVersion->description,
                    'type' => $item->articleVersion->type,
                    'price' => $item->articleVersion->price,
                    'tax_rate' => $item->articleVersion->tax_rate,
                    'unit' => $item->articleVersion->unit,
                    'decimal_places' => $item->articleVersion->decimal_places,
                    'total_decimal_places' => $item->articleVersion->total_decimal_places,
                ] : null,
                // Steuersatz-Version-Daten falls vorhanden
                'tax_rate_version_data' => $item->taxRateVersion ? [
                    'id' => $item->taxRateVersion->id,
                    'version_number' => $item->taxRateVersion->version_number,
                    'name' => $item->taxRateVersion->name,
                    'description' => $item->taxRateVersion->description,
                    'rate' => $item->taxRateVersion->rate,
                    'valid_from' => $item->taxRateVersion->valid_from?->toDateString(),
                    'valid_until' => $item->taxRateVersion->valid_until?->toDateString(),
                ] : null,
            ];
        })->toArray();

        // Neue Version erstellen
        return static::create([
            'invoice_id' => $invoice->id,
            'version_number' => $nextVersion,
            'invoice_data' => $invoiceData,
            'customer_data' => $customerData,
            'items_data' => $itemsData,
            'changed_by' => $changedBy ?? auth()->user()?->name ?? 'System',
            'change_reason' => $changeReason,
            'changed_fields' => $changedFields,
            'is_current' => true,
        ]);
    }

    /**
     * Erstelle eine neue Version für eine Rechnung mit spezifischer Versionsnummer
     */
    public static function createVersionWithNumber(Invoice $invoice, ?int $versionNumber = null, array $changedFields = [], string $changeReason = null, string $changedBy = null): self
    {
        // Aktuelle Version deaktivieren
        static::where('invoice_id', $invoice->id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        // Bestimme Versionsnummer
        if ($versionNumber === null) {
            // Nächste Versionsnummer ermitteln
            $versionNumber = static::where('invoice_id', $invoice->id)
                ->max('version_number') + 1;
        }

        // Lade alle notwendigen Daten
        $invoice->load(['customer', 'items.article', 'items.articleVersion', 'items.taxRateVersion']);

        // Erstelle vollständige Datenstrukturen (gleiche Logik wie createVersion)
        $invoiceData = [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status,
            'total' => $invoice->total,
            'due_date' => $invoice->due_date?->toDateString(),
            'lexoffice_id' => $invoice->lexoffice_id,
            'created_at' => $invoice->created_at?->toISOString(),
            'updated_at' => $invoice->updated_at?->toISOString(),
        ];

        $customerData = $invoice->customer ? [
            'id' => $invoice->customer->id,
            'customer_number' => $invoice->customer->customer_number,
            'name' => $invoice->customer->name,
            'customer_type' => $invoice->customer->customer_type,
            'email' => $invoice->customer->email,
            'phone' => $invoice->customer->phone,
            'street' => $invoice->customer->street,
            'city' => $invoice->customer->city,
            'postal_code' => $invoice->customer->postal_code,
            'country' => $invoice->customer->country,
            'country_code' => $invoice->customer->country_code,
            'company_name' => $invoice->customer->company_name,
            'contact_person' => $invoice->customer->contact_person,
            'department' => $invoice->customer->department,
            'website' => $invoice->customer->website,
            'vat_id' => $invoice->customer->vat_id,
            'bank_name' => $invoice->customer->bank_name,
            'iban' => $invoice->customer->iban,
            'bic' => $invoice->customer->bic,
            'tax_number' => $invoice->customer->tax_number,
            'payment_days' => $invoice->customer->payment_days,
        ] : null;

        $itemsData = $invoice->items->map(function ($item) {
            return [
                'id' => $item->id, // ID wird für die Versionserstellung benötigt
                'article_id' => $item->article_id,
                'article_version_id' => $item->article_version_id,
                'tax_rate_version_id' => $item->tax_rate_version_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
                'total' => $item->total,
                // Artikel-Daten zum Zeitpunkt der Rechnung
                'article_data' => $item->article ? [
                    'id' => $item->article->id,
                    'name' => $item->article->name,
                    'description' => $item->article->description,
                    'type' => $item->article->type,
                    'price' => $item->article->price,
                    'tax_rate' => $item->article->tax_rate,
                    'tax_rate_id' => $item->article->tax_rate_id,
                    'unit' => $item->article->unit,
                    'decimal_places' => $item->article->decimal_places,
                    'total_decimal_places' => $item->article->total_decimal_places,
                ] : null,
                // Artikel-Version-Daten falls vorhanden
                'article_version_data' => $item->articleVersion ? [
                    'id' => $item->articleVersion->id,
                    'version_number' => $item->articleVersion->version_number,
                    'name' => $item->articleVersion->name,
                    'description' => $item->articleVersion->description,
                    'type' => $item->articleVersion->type,
                    'price' => $item->articleVersion->price,
                    'tax_rate' => $item->articleVersion->tax_rate,
                    'unit' => $item->articleVersion->unit,
                    'decimal_places' => $item->articleVersion->decimal_places,
                    'total_decimal_places' => $item->articleVersion->total_decimal_places,
                ] : null,
                // Steuersatz-Version-Daten falls vorhanden
                'tax_rate_version_data' => $item->taxRateVersion ? [
                    'id' => $item->taxRateVersion->id,
                    'version_number' => $item->taxRateVersion->version_number,
                    'name' => $item->taxRateVersion->name,
                    'description' => $item->taxRateVersion->description,
                    'rate' => $item->taxRateVersion->rate,
                    'valid_from' => $item->taxRateVersion->valid_from?->toDateString(),
                    'valid_until' => $item->taxRateVersion->valid_until?->toDateString(),
                ] : null,
            ];
        })->toArray();

        // Neue Version erstellen
        return static::create([
            'invoice_id' => $invoice->id,
            'version_number' => $versionNumber,
            'invoice_data' => $invoiceData,
            'customer_data' => $customerData,
            'items_data' => $itemsData,
            'changed_by' => $changedBy ?? auth()->user()?->name ?? 'System',
            'change_reason' => $changeReason,
            'changed_fields' => $changedFields,
            'is_current' => true,
        ]);
    }

    /**
     * Prüfe ob sich die Rechnung seit der letzten Version geändert hat
     */
    public static function hasChangedSinceLastVersion(Invoice $invoice): bool
    {
        // Lade die letzte Version
        $lastVersion = static::getCurrentVersion($invoice);
        
        // Wenn keine Version existiert, ist es eine Änderung
        if (!$lastVersion) {
            return true;
        }
        
        // Vereinfachter Vergleich: Nur die wichtigsten Felder
        $currentHash = static::createInvoiceHash($invoice);
        $lastHash = static::createInvoiceHashFromVersion($lastVersion);
        
        $hasChanged = $currentHash !== $lastHash;
        
        // Debug-Logging (erweitert)
        \Log::info('Invoice version comparison', [
            'invoice_id' => $invoice->id,
            'current_hash' => $currentHash,
            'last_hash' => $lastHash,
            'has_changed' => $hasChanged,
        ]);
        
        return $hasChanged;
    }
    
    /**
     * Erstelle einen Hash der wichtigsten Rechnungsdaten
     */
    private static function createInvoiceHash(Invoice $invoice): string
    {
        $invoice->load(['customer', 'items']);
        
        $data = [
            // Rechnungsfelder (ohne Zeitstempel)
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status,
            'total' => $invoice->total,
            'due_date' => $invoice->due_date?->toDateString(),
            'customer_id' => $invoice->customer_id,
            
            // Kundendaten (nur die wichtigsten)
            'customer' => $invoice->customer ? [
                'name' => $invoice->customer->name,
                'email' => $invoice->customer->email,
                'company_name' => $invoice->customer->company_name,
            ] : null,
            
            // Items (nur die wichtigsten Felder)
            'items' => $invoice->items->map(function ($item) {
                return [
                    'article_id' => $item->article_id,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'tax_rate' => (float) $item->tax_rate,
                    'total' => (float) $item->total,
                ];
            })->toArray(),
        ];
        
        return md5(json_encode($data));
    }
    
    /**
     * Erstelle einen Hash aus einer gespeicherten Version
     */
    private static function createInvoiceHashFromVersion(InvoiceVersion $version): string
    {
        $data = [
            // Rechnungsfelder (ohne Zeitstempel)
            'invoice_number' => $version->invoice_data['invoice_number'] ?? null,
            'status' => $version->invoice_data['status'] ?? null,
            'total' => $version->invoice_data['total'] ?? null,
            'due_date' => $version->invoice_data['due_date'] ?? null,
            'customer_id' => $version->customer_data['id'] ?? null,
            
            // Kundendaten (nur die wichtigsten)
            'customer' => $version->customer_data ? [
                'name' => $version->customer_data['name'] ?? null,
                'email' => $version->customer_data['email'] ?? null,
                'company_name' => $version->customer_data['company_name'] ?? null,
            ] : null,
            
            // Items (nur die wichtigsten Felder)
            'items' => collect($version->items_data)->map(function ($item) {
                return [
                    'article_id' => $item['article_id'] ?? null,
                    'description' => $item['description'] ?? null,
                    'quantity' => (float) ($item['quantity'] ?? 0),
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'tax_rate' => (float) ($item['tax_rate'] ?? 0),
                    'total' => (float) ($item['total'] ?? 0),
                ];
            })->toArray(),
        ];
        
        return md5(json_encode($data));
    }

    /**
     * Hole eine bestimmte Version einer Rechnung
     */
    public static function getVersion(Invoice $invoice, int $versionNumber): ?self
    {
        return static::where('invoice_id', $invoice->id)
            ->where('version_number', $versionNumber)
            ->first();
    }

    /**
     * Hole die aktuelle Version einer Rechnung
     */
    public static function getCurrentVersion(Invoice $invoice): ?self
    {
        return static::where('invoice_id', $invoice->id)
            ->where('is_current', true)
            ->first();
    }

    /**
     * Hole alle Versionen einer Rechnung
     */
    public static function getVersionHistory(Invoice $invoice)
    {
        return static::where('invoice_id', $invoice->id)
            ->orderBy('version_number', 'desc')
            ->get();
    }

    /**
     * Formatierte Gesamtsumme aus den gespeicherten Daten
     */
    public function getFormattedTotalAttribute(): string
    {
        $total = $this->invoice_data['total'] ?? 0;
        return number_format($total, 2, ',', '.') . ' €';
    }

    /**
     * Kundenname aus den gespeicherten Daten
     */
    public function getCustomerNameAttribute(): string
    {
        return $this->customer_data['name'] ?? 'Unbekannter Kunde';
    }

    /**
     * Anzahl der Rechnungsposten
     */
    public function getItemsCountAttribute(): int
    {
        return count($this->items_data ?? []);
    }

    /**
     * Erstelle eine Kopie der Rechnung basierend auf dieser Version
     */
    public function createInvoiceCopy(): Invoice
    {
        $invoiceData = $this->invoice_data;
        
        // Erstelle neue Rechnung mit Daten aus dieser Version
        $newInvoice = Invoice::create([
            'customer_id' => $this->customer_data['id'] ?? null,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'status' => 'draft', // Neue Rechnung ist immer Entwurf
            'total' => 0, // Wird durch Items berechnet
            'due_date' => null, // Wird automatisch berechnet
        ]);

        // Erstelle Items basierend auf gespeicherten Daten
        foreach ($this->items_data as $itemData) {
            InvoiceItem::create([
                'invoice_id' => $newInvoice->id,
                'article_id' => $itemData['article_id'],
                'article_version_id' => $itemData['article_version_id'],
                'tax_rate_version_id' => $itemData['tax_rate_version_id'],
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'],
                // total wird automatisch berechnet
            ]);
        }

        return $newInvoice;
    }
}