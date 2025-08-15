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
        'net_amount',
        'vat_rate',
        'currency',
        'status',
        'notes',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:6',
        'net_amount' => 'decimal:6',
        'vat_rate' => 'decimal:2',
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

    public function articles(): HasMany
    {
        return $this->hasMany(SupplierContractBillingArticle::class, 'supplier_contract_billing_id');
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
            'captured' => 'Erfasst',
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
            'captured' => 'Erfasst',
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

        static::created(function ($billing) {
            // Automatisch Allocations basierend auf den Kostenträgern des Vertrags erstellen
            $billing->createAllocationsFromContract();
            
            // Automatisch Artikel vom Vertrag zu der Abrechnung hinzufügen
            $billing->addArticlesFromContract();
        });
    }

    /**
     * Erstellt automatisch Allocations basierend auf den Kostenträgern des Vertrags
     */
    public function createAllocationsFromContract(): void
    {
        if (!$this->supplierContract) {
            return;
        }

        // Hole alle aktiven Solaranlagen-Zuordnungen des Vertrags über die Pivot-Tabelle
        $activeSolarPlants = $this->supplierContract->activeSolarPlants()->get();

        if ($activeSolarPlants->isEmpty()) {
            return;
        }

        foreach ($activeSolarPlants as $solarPlant) {
            $this->allocations()->create([
                'solar_plant_id' => $solarPlant->id,
                'percentage' => $solarPlant->pivot->percentage,
                'amount' => ($this->total_amount * $solarPlant->pivot->percentage) / 100,
                'notes' => 'Automatisch erstellt basierend auf Vertragszuordnung',
                'is_active' => true,
            ]);
        }
    }

    /**
     * Fügt automatisch Artikel vom Vertrag zur Abrechnung hinzu
     */
    public function addArticlesFromContract(): void
    {
        if (!$this->supplierContract) {
            return;
        }

        // Hole alle aktiven Artikel des Vertrags über die Pivot-Tabelle
        $activeArticles = $this->supplierContract->activeArticles()->get();

        if ($activeArticles->isEmpty()) {
            return;
        }

        foreach ($activeArticles as $article) {
            $this->articles()->create([
                'article_id' => $article->id,
                'quantity' => $article->pivot->quantity ?? 1,
                'unit_price' => $article->pivot->unit_price ?? $article->price,
                'description' => $article->name,
                'notes' => 'Automatisch hinzugefügt aus Vertragsartikel',
                'is_active' => true,
            ]);
        }
    }

    /**
     * Berechnet alle Allocations neu basierend auf den aktuellen Prozentsätzen
     */
    public function recalculateAllocations(): void
    {
        foreach ($this->allocations as $allocation) {
            $allocation->calculateAmount();
            $allocation->save();
        }
    }

    public static function generateBillingNumber(): string
    {
        $year = now()->year;
        $lastBilling = static::withTrashed()->whereYear('created_at', $year)
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
