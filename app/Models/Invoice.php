<?php

namespace App\Models;

use App\Helpers\PriceFormatter;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Enum\Laravel\HasEnums;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'customer_id',
        'invoice_number',
        'status',
        'total',
        'lexoffice_id',
        'due_date',
        'cancellation_date',
    ];

    protected $attributes = [
        'total' => 0,
        'status' => 'draft',
    ];

    /**
     * Boot-Methode für automatische Fälligkeitsdatum-Berechnung
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            // Setze Rechnungsnummer automatisch, falls nicht bereits gesetzt
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
            
            // Setze Fälligkeitsdatum automatisch, falls nicht bereits gesetzt
            if (!$invoice->due_date) {
                $invoice->due_date = $invoice->calculateDueDate();
            }
        });

        static::created(function ($invoice) {
            // Bei Erstellung einer neuen Rechnung automatisch erste Version erstellen
            InvoiceVersion::createVersion($invoice, [], 'Rechnung erstellt');
        });

        static::updating(function ($invoice) {
            // Wenn sich der Kunde ändert, berechne das Fälligkeitsdatum neu
            if ($invoice->isDirty('customer_id')) {
                $invoice->due_date = $invoice->calculateDueDate();
            }
        });

        // Versionserstellung wird jetzt in EditInvoice.php gehandhabt
        // static::updated() Event entfernt um doppelte Versionen zu vermeiden
    }

    /**
     * Berechnet das Fälligkeitsdatum basierend auf Kunden- oder Firmeneinstellungen
     */
    public function calculateDueDate(): Carbon
    {
        // Priorisierung: 1. Kundenspezifisches Zahlungsziel, 2. Firmeneinstellungen, 3. Fallback
        $paymentDays = $this->getPaymentDays();
        
        // Verwende das Erstellungsdatum der Rechnung als Basis, falls vorhanden
        $baseDate = $this->created_at ?? now();
        
        return $baseDate->copy()->addDays($paymentDays);
    }

    /**
     * Ermittelt die Zahlungstage in der richtigen Priorität
     */
    public function getPaymentDays(): int
    {
        // 1. Priorität: Kundenspezifisches Zahlungsziel
        if ($this->customer && $this->customer->payment_days) {
            return $this->customer->payment_days;
        }
        
        // 2. Priorität: Firmeneinstellungen
        $companySettings = CompanySetting::current();
        if ($companySettings->default_payment_days) {
            return $companySettings->default_payment_days;
        }
        
        // 3. Fallback: 14 Tage
        return 14;
    }

    protected $casts = [
        'total' => 'decimal:6',
        'lexoffice_id' => 'string',
        'due_date' => 'date',
        'cancellation_date' => 'date',
    ];

    /**
     * Beziehung zum Kunden
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Beziehung zu Rechnungsposten
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Beziehung zu Rechnungsversionen
     */
    public function versions(): HasMany
    {
        return $this->hasMany(InvoiceVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Beziehung zur aktuellen Version
     */
    public function currentVersion(): HasOne
    {
        return $this->hasOne(InvoiceVersion::class)->where('is_current', true);
    }

    /**
     * Gesamtsumme berechnen basierend auf Items
     * Total = Summe aller Netto-Beträge (quantity * unit_price)
     */
    public function calculateTotal(): float
    {
        return $this->items->sum('total');
    }

    /**
     * Steuersumme berechnen
     * Steuerbetrag wird auf den Netto-Betrag aufgeschlagen
     */
    public function getTaxAmountAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->tax_amount;
        });
    }

    /**
     * Nettosumme berechnen
     * Total ist bereits der Netto-Betrag
     */
    public function getNetAmountAttribute(): float
    {
        return $this->total;
    }

    /**
     * Bruttosumme berechnen
     * Netto + Steuer
     */
    public function getGrossAmountAttribute(): float
    {
        return $this->total + $this->tax_amount;
    }

    /**
     * Formatierte Gesamtsumme mit konfigurierten Nachkommastellen
     */
    public function getFormattedTotalAttribute(): string
    {
        return PriceFormatter::formatTotalPrice($this->total);
    }

    /**
     * Formatierte Nettosumme mit konfigurierten Nachkommastellen
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return PriceFormatter::formatTotalPrice($this->net_amount);
    }

    /**
     * Formatierte Steuersumme mit konfigurierten Nachkommastellen
     */
    public function getFormattedTaxAmountAttribute(): string
    {
        return PriceFormatter::formatTotalPrice($this->tax_amount);
    }

    /**
     * Gesamtsumme für Lexoffice (auf 2 Nachkommastellen gerundet)
     */
    public function getLexofficeTotalAttribute(): float
    {
        return round($this->total, 2);
    }

    /**
     * Status-Badge für Filament
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Entwurf',
            'sent' => 'Versendet',
            'paid' => 'Bezahlt',
            'canceled' => 'Storniert',
            default => $this->status
        };
    }

    /**
     * Status-Farbe für Filament
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'sent' => 'warning',
            'paid' => 'success',
            'canceled' => 'danger',
            default => 'gray'
        };
    }

    /**
     * Prüft ob Rechnung bereits mit Lexoffice synchronisiert ist
     */
    public function isSyncedWithLexoffice(): bool
    {
        return !empty($this->lexoffice_id);
    }

    /**
     * Kann die Rechnung bearbeitet werden?
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft']);
    }

    /**
     * Kann die Rechnung an Lexoffice gesendet werden?
     */
    public function canBeSentToLexoffice(): bool
    {
        return !$this->isSyncedWithLexoffice() && $this->items->count() > 0;
    }

    /**
     * Automatische Rechnungsnummer generieren
     */
    public static function generateInvoiceNumber(): string
    {
        $companySettings = CompanySetting::current();
        $year = date('Y');
        
        // Finde die höchste Rechnungsnummer
        $lastInvoices = self::orderBy('invoice_number', 'desc')->get();
        $highestNumber = 0;
        
        foreach ($lastInvoices as $invoice) {
            try {
                $number = $companySettings->extractInvoiceNumber($invoice->invoice_number);
                $highestNumber = max($highestNumber, $number);
            } catch (Exception $e) {
                // Fallback für alte Formate
                if (preg_match('/(\d{4})$/', $invoice->invoice_number, $matches)) {
                    $number = (int) $matches[1];
                    $highestNumber = max($highestNumber, $number);
                }
            }
        }

        $newNumber = $highestNumber + 1;
        return $companySettings->generateInvoiceNumber($newNumber, $year);
    }

    /**
     * Erstelle eine neue Version manuell
     */
    public function createVersion(string $changeReason = null, string $changedBy = null): InvoiceVersion
    {
        return InvoiceVersion::createVersion($this, [], $changeReason, $changedBy);
    }

    /**
     * Hole die aktuelle Version
     */
    public function getCurrentVersion(): ?InvoiceVersion
    {
        return InvoiceVersion::getCurrentVersion($this);
    }

    /**
     * Hole eine bestimmte Version
     */
    public function getVersion(int $versionNumber): ?InvoiceVersion
    {
        return InvoiceVersion::getVersion($this, $versionNumber);
    }

    /**
     * Hole die Versionshistorie
     */
    public function getVersionHistory()
    {
        return InvoiceVersion::getVersionHistory($this);
    }

    /**
     * Erstelle eine Kopie basierend auf einer bestimmten Version
     */
    public function createCopyFromVersion(int $versionNumber): ?Invoice
    {
        $version = $this->getVersion($versionNumber);
        return $version?->createInvoiceCopy();
    }

    /**
     * Erstelle eine Kopie basierend auf der aktuellen Version
     */
    public function createCopy(): ?Invoice
    {
        $currentVersion = $this->getCurrentVersion();
        return $currentVersion?->createInvoiceCopy();
    }
}
