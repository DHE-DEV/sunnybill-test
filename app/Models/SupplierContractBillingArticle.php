<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierContractBillingArticle extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'supplier_contract_billing_id',
        'article_id',
        'quantity',
        'unit_price',
        'total_price',
        'description',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:6',
        'total_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Beziehung zur Abrechnung
     */
    public function billing(): BelongsTo
    {
        return $this->belongsTo(SupplierContractBilling::class, 'supplier_contract_billing_id');
    }

    /**
     * Beziehung zum Artikel
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Berechnet den Gesamtpreis basierend auf Menge und Einzelpreis
     */
    public function calculateTotalPrice(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Formatierter Einzelpreis
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2, ',', '.') . ' €';
    }

    /**
     * Formatierter Gesamtpreis
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return number_format($this->total_price, 2, ',', '.') . ' €';
    }

    /**
     * Formatierte Menge
     */
    public function getFormattedQuantityAttribute(): string
    {
        return number_format($this->quantity, 2, ',', '.');
    }

    /**
     * Boot-Methode für automatische Berechnung
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($billingArticle) {
            $billingArticle->total_price = $billingArticle->calculateTotalPrice();
        });
    }
}
