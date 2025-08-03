<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarPlantBillingPayment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'solar_plant_billing_id',
        'recorded_by_user_id',
        'payment_type',
        'amount',
        'notes',
        'reference',
        'payment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Relation zur Abrechnung
     */
    public function solarPlantBilling(): BelongsTo
    {
        return $this->belongsTo(SolarPlantBilling::class);
    }

    /**
     * Relation zum User der die Zahlung erfasst hat
     */
    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * Formatierte Zahlungsart
     */
    public function getFormattedPaymentTypeAttribute(): string
    {
        return match($this->payment_type) {
            'bank_transfer' => 'Überweisung',
            'instant_transfer' => 'Sofortüberweisung',
            'direct_debit' => 'Lastschrift/Abbuchung',
            'cash' => 'Barzahlung',
            'check' => 'Scheck',
            'credit_card' => 'Kreditkarte',
            'paypal' => 'PayPal',
            'other' => 'Sonstiges',
            default => $this->payment_type,
        };
    }

    /**
     * Farbe für die Zahlungsart
     */
    public function getPaymentTypeColorAttribute(): string
    {
        return match($this->payment_type) {
            'bank_transfer' => 'info',
            'instant_transfer' => 'success',
            'direct_debit' => 'warning',
            'cash' => 'success',
            'check' => 'gray',
            'credit_card' => 'primary',
            'paypal' => 'info',
            'other' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Icon für die Zahlungsart
     */
    public function getPaymentTypeIconAttribute(): string
    {
        return match($this->payment_type) {
            'bank_transfer' => 'heroicon-o-building-library',
            'instant_transfer' => 'heroicon-o-bolt',
            'direct_debit' => 'heroicon-o-arrow-left-on-rectangle',
            'cash' => 'heroicon-o-banknotes',
            'check' => 'heroicon-o-document-text',
            'credit_card' => 'heroicon-o-credit-card',
            'paypal' => 'heroicon-o-globe-alt',
            'other' => 'heroicon-o-ellipsis-horizontal',
            default => 'heroicon-o-currency-euro',
        };
    }

    /**
     * Scope für Zahlungen einer bestimmten Abrechnung
     */
    public function scopeForBilling($query, $billingId)
    {
        return $query->where('solar_plant_billing_id', $billingId);
    }

    /**
     * Scope für Zahlungen sortiert nach Datum
     */
    public function scopeOrderedByDate($query, $direction = 'desc')
    {
        return $query->orderBy('payment_date', $direction)->orderBy('created_at', $direction);
    }
}
