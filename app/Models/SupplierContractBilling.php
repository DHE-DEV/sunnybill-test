<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierContractBilling extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'supplier_contract_id',
        'billing_number',
        'supplier_invoice_number',
        'billing_type',
        'billing_year',
        'billing_month',
        'title',
        'description',
        'billing_date',
        'due_date',
        'total_amount',
        'currency',
        'status',
        'notes',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function supplierContract(): BelongsTo
    {
        return $this->belongsTo(SupplierContract::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SupplierContractBillingAllocation::class, 'supplier_contract_billing_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return number_format($this->total_amount, 2, ',', '.') . ' €';
    }

    public static function getBillingTypeOptions(): array
    {
        return [
            'invoice' => 'Rechnung',
            'credit_note' => 'Gutschrift',
        ];
    }

    public static function getMonthOptions(): array
    {
        return [
            1 => 'Januar',
            2 => 'Februar',
            3 => 'März',
            4 => 'April',
            5 => 'Mai',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'August',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Dezember',
        ];
    }

    public function getBillingPeriodAttribute(): ?string
    {
        if ($this->billing_year && $this->billing_month) {
            $monthName = self::getMonthOptions()[$this->billing_month] ?? $this->billing_month;
            return "{$monthName} {$this->billing_year}";
        }
        return null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Entwurf',
            'pending' => 'Ausstehend',
            'approved' => 'Genehmigt',
            'paid' => 'Bezahlt',
            'cancelled' => 'Storniert',
            default => 'Unbekannt',
        };
    }

    /**
     * Verfügbare Status-Optionen
     */
    public static function getStatusOptions(): array
    {
        return [
            'draft' => 'Entwurf',
            'pending' => 'Ausstehend',
            'approved' => 'Genehmigt',
            'paid' => 'Bezahlt',
            'cancelled' => 'Storniert',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($billing) {
            if (empty($billing->billing_number)) {
                $billing->billing_number = static::generateBillingNumber();
            }
        });
    }

    public static function generateBillingNumber(): string
    {
        $year = now()->year;
        $lastBilling = static::whereYear('created_at', $year)
            ->orderBy('billing_number', 'desc')
            ->first();

        if ($lastBilling && preg_match('/AB-' . $year . '-(\d+)/', $lastBilling->billing_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return 'AB-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}