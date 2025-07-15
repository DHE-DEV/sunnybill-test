<?php

namespace App\Models;

use App\Helpers\PriceFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'price',
        'tax_rate', // Wird schrittweise durch tax_rate_id ersetzt
        'tax_rate_id',
        'unit',
        'decimal_places',
        'total_decimal_places',
        'lexoffice_id',
    ];

    protected $casts = [
        'price' => 'decimal:6',
        'tax_rate' => 'decimal:4',
        'decimal_places' => 'integer',
        'total_decimal_places' => 'integer',
        'lexoffice_id' => 'string',
    ];

    protected static function booted()
    {
        // Bei Erstellung eines neuen Artikels automatisch erste Version erstellen
        static::created(function (Article $article) {
            ArticleVersion::createVersion($article, [], 'Artikel erstellt');
        });

        // Bei Aktualisierung eines Artikels neue Version erstellen
        static::updated(function (Article $article) {
            $changedFields = [];
            $original = $article->getOriginal();
            
            foreach (['name', 'description', 'type', 'price', 'tax_rate', 'tax_rate_id', 'unit', 'decimal_places', 'total_decimal_places'] as $field) {
                if ($article->wasChanged($field)) {
                    $changedFields[$field] = [
                        'old' => $original[$field] ?? null,
                        'new' => $article->getAttribute($field)
                    ];
                }
            }

            if (!empty($changedFields)) {
                ArticleVersion::createVersion(
                    $article,
                    $changedFields,
                    'Artikel aktualisiert'
                );
            }
        });
    }

    /**
     * Beziehung zu Rechnungsposten
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Beziehung zu Artikel-Versionen
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ArticleVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Aktuelle Version des Artikels
     */
    public function currentVersion()
    {
        return $this->hasOne(ArticleVersion::class)->where('is_current', true);
    }

    /**
     * Beziehung zum Steuersatz
     */
    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * Beziehung zu Lieferantenverträgen über Pivot-Tabelle
     */
    public function supplierContracts()
    {
        return $this->belongsToMany(SupplierContract::class, 'supplier_contract_articles')
            ->withPivot(['quantity', 'unit_price', 'notes', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Aktive Lieferantenverträge
     */
    public function activeSupplierContracts()
    {
        return $this->belongsToMany(SupplierContract::class, 'supplier_contract_articles')
            ->wherePivot('is_active', true)
            ->withPivot(['quantity', 'unit_price', 'notes', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Preis formatiert für Anzeige mit artikelspezifischen Nachkommastellen
     */
    public function getFormattedPriceAttribute(): string
    {
        $decimalPlaces = $this->decimal_places ?? 2;
        $formatted = number_format($this->price, $decimalPlaces, ',', '.');
        return $formatted . ' €';
    }

    /**
     * Steuersatz als Prozent (unterstützt sowohl direkte tax_rate als auch TaxRate-Beziehung)
     */
    public function getTaxRatePercentAttribute(): string
    {
        $rate = $this->getCurrentTaxRate();
        return number_format($rate * 100, 2) . '%';
    }

    /**
     * Hole den aktuellen Steuersatz (unterstützt sowohl alte als auch neue Struktur)
     */
    public function getCurrentTaxRate(): float
    {
        // Wenn tax_rate_id gesetzt ist, verwende die TaxRate-Beziehung
        if ($this->tax_rate_id && $this->taxRate) {
            return $this->taxRate->rate;
        }
        
        // Fallback auf das alte tax_rate Feld
        return $this->tax_rate ?? 0.19;
    }

    /**
     * Brutto-Preis berechnen (verwendet aktuellen Steuersatz)
     */
    public function getGrossPriceAttribute(): float
    {
        $taxRate = $this->getCurrentTaxRate();
        return $this->price * (1 + $taxRate);
    }

    /**
     * Formatierter Brutto-Preis mit artikelspezifischen Nachkommastellen
     */
    public function getFormattedGrossPriceAttribute(): string
    {
        $decimalPlaces = $this->decimal_places ?? 2;
        $formatted = number_format($this->gross_price, $decimalPlaces, ',', '.');
        return $formatted . ' €';
    }

    /**
     * Gibt die konfigurierten Nachkommastellen für diesen Artikel zurück
     */
    public function getDecimalPlaces(): int
    {
        return $this->decimal_places ?? 2;
    }

    /**
     * Gibt die konfigurierten Nachkommastellen für Gesamtpreise dieses Artikels zurück
     */
    public function getTotalDecimalPlaces(): int
    {
        return $this->total_decimal_places ?? 2;
    }

    /**
     * Preis für Lexoffice (auf 2 Nachkommastellen gerundet)
     */
    public function getLexofficePriceAttribute(): float
    {
        return round($this->price, 2);
    }

    /**
     * Prüft ob Artikel bereits mit Lexoffice synchronisiert ist
     */
    public function isSyncedWithLexoffice(): bool
    {
        return !empty($this->lexoffice_id);
    }

    /**
     * Prüft ob dieser Artikel in Rechnungen verwendet wird
     */
    public function isUsedInInvoices(): bool
    {
        return $this->invoiceItems()->exists();
    }

    /**
     * Anzahl der Rechnungen, in denen dieser Artikel verwendet wird
     */
    public function getInvoiceUsageCount(): int
    {
        return $this->invoiceItems()->distinct('invoice_id')->count('invoice_id');
    }

    /**
     * Rechnungen, in denen dieser Artikel verwendet wird
     */
    public function getUsedInInvoices()
    {
        return Invoice::whereHas('items', function ($query) {
            $query->where('article_id', $this->id);
        })->get();
    }

    /**
     * Prüft ob der Artikel gelöscht werden kann
     */
    public function canBeDeleted(): bool
    {
        return !$this->isUsedInInvoices();
    }
}
