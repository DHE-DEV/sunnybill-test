<?php

namespace App\Models;

use App\Helpers\PriceFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'article_id',
        'article_version_id',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_rate_version_id',
        'total',
        'description',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:6',
        'tax_rate' => 'decimal:2',
        'total' => 'decimal:6',
    ];

    /**
     * Beziehung zur Rechnung
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Beziehung zum Artikel
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Beziehung zur Artikel-Version
     */
    public function articleVersion(): BelongsTo
    {
        return $this->belongsTo(ArticleVersion::class);
    }

    /**
     * Beziehung zur Steuersatz-Version
     */
    public function taxRateVersion(): BelongsTo
    {
        return $this->belongsTo(TaxRateVersion::class);
    }

    /**
     * Gesamtpreis berechnen
     */
    public function calculateTotal(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Steuerbetrag berechnen
     * Der Steuerbetrag wird auf den Netto-Betrag (total) aufgeschlagen
     */
    public function getTaxAmountAttribute(): float
    {
        return $this->total * $this->tax_rate;
    }

    /**
     * Nettobetrag berechnen
     * Total ist bereits der Netto-Betrag (quantity * unit_price)
     */
    public function getNetAmountAttribute(): float
    {
        return $this->total;
    }

    /**
     * Bruttobetrag berechnen
     * Netto-Betrag + Steuer
     */
    public function getGrossAmountAttribute(): float
    {
        return $this->total + $this->tax_amount;
    }

    /**
     * Formatierter Gesamtpreis mit artikelspezifischen Nachkommastellen für Gesamtpreise
     * Zeigt den Netto-Betrag an
     */
    public function getFormattedTotalAttribute(): string
    {
        $decimalPlaces = $this->getArticleTotalDecimalPlaces();
        $formatted = number_format($this->total, $decimalPlaces, ',', '.');
        return $formatted . ' €';
    }

    /**
     * Formatierter Einzelpreis mit artikelspezifischen Nachkommastellen
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        // Verwende Artikel-Version falls vorhanden, sonst aktuellen Artikel
        $decimalPlaces = $this->getArticleDecimalPlaces();
        $formatted = number_format($this->unit_price, $decimalPlaces, ',', '.');
        return $formatted . ' €';
    }

    /**
     * Hole die Nachkommastellen vom Artikel oder der Artikel-Version
     */
    private function getArticleDecimalPlaces(): int
    {
        if ($this->articleVersion) {
            return $this->articleVersion->decimal_places ?? 2;
        }
        
        if ($this->article) {
            return $this->article->getDecimalPlaces();
        }
        
        return 2;
    }

    /**
     * Hole die Gesamtpreis-Nachkommastellen vom Artikel oder der Artikel-Version
     */
    private function getArticleTotalDecimalPlaces(): int
    {
        if ($this->articleVersion) {
            return $this->articleVersion->total_decimal_places ?? 2;
        }
        
        if ($this->article) {
            return $this->article->getTotalDecimalPlaces();
        }
        
        return 2;
    }

    /**
     * Formatierter Nettobetrag mit konfigurierten Nachkommastellen
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return PriceFormatter::formatTotalPrice($this->net_amount);
    }

    /**
     * Formatierter Steuerbetrag mit konfigurierten Nachkommastellen
     */
    public function getFormattedTaxAmountAttribute(): string
    {
        return PriceFormatter::formatTotalPrice($this->tax_amount);
    }

    /**
     * Steuersatz als Prozent
     */
    public function getTaxRatePercentAttribute(): string
    {
        return ($this->tax_rate * 100) . '%';
    }

    /**
     * Einzelpreis für Lexoffice (auf 2 Nachkommastellen gerundet)
     */
    public function getLexofficeUnitPriceAttribute(): float
    {
        return round($this->unit_price, 2);
    }

    /**
     * Gesamtpreis für Lexoffice (auf 2 Nachkommastellen gerundet)
     */
    public function getLexofficeTotalAttribute(): float
    {
        return round($this->total, 2);
    }

    /**
     * Formatierter Einzelpreis mit artikelspezifischen Nachkommastellen (entfernt trailing zeros)
     */
    public function getFormattedUnitPriceDynamicAttribute(): string
    {
        $decimalPlaces = $this->getArticleDecimalPlaces();
        $formatted = rtrim(rtrim(number_format($this->unit_price, $decimalPlaces, ',', '.'), '0'), ',');
        return $formatted . ' €';
    }

    /**
     * Formatierter Gesamtpreis mit artikelspezifischen Nachkommastellen (entfernt trailing zeros)
     */
    public function getFormattedTotalDynamicAttribute(): string
    {
        $decimalPlaces = $this->getArticleTotalDecimalPlaces();
        $formatted = rtrim(rtrim(number_format($this->total, $decimalPlaces, ',', '.'), '0'), ',');
        return $formatted . ' €';
    }

    /**
     * Boot-Methode für automatische Berechnung
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($invoiceItem) {
            // Automatische Berechnung des Gesamtpreises
            $invoiceItem->total = $invoiceItem->calculateTotal();
        });

        static::saved(function ($invoiceItem) {
            // Rechnung-Total aktualisieren
            $invoice = $invoiceItem->invoice;
            if ($invoice) {
                $invoice->total = $invoice->calculateTotal();
                $invoice->save();
                
                // Neue Version erstellen wenn Item geändert wurde
                if ($invoiceItem->wasRecentlyCreated) {
                    InvoiceVersion::createVersion($invoice, [], 'Rechnungsposten hinzugefügt');
                } else {
                    $changedFields = [];
                    foreach ($invoiceItem->getDirty() as $field => $newValue) {
                        if (!in_array($field, ['updated_at', 'total'])) {
                            $changedFields[$field] = [
                                'old' => $invoiceItem->getOriginal($field),
                                'new' => $newValue
                            ];
                        }
                    }
                    
                    if (!empty($changedFields)) {
                        InvoiceVersion::createVersion($invoice, $changedFields, 'Rechnungsposten geändert');
                    }
                }
            }
        });

        static::deleted(function ($invoiceItem) {
            // Rechnung-Total aktualisieren nach Löschung
            $invoice = $invoiceItem->invoice;
            if ($invoice) {
                $invoice->total = $invoice->calculateTotal();
                $invoice->save();
                
                // Neue Version erstellen
                InvoiceVersion::createVersion($invoice, [], 'Rechnungsposten gelöscht');
            }
        });
    }
}
