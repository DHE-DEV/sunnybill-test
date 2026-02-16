<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SolarPlantBilling extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Model Events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($billing) {
            // Only generate invoice number if it's not already provided
            if (empty($billing->invoice_number) || is_null($billing->invoice_number)) {
                $billing->invoice_number = static::generateInvoiceNumber();
            }
        });
    }

    protected $fillable = [
        'solar_plant_id',
        'customer_id',
        'billing_year',
        'billing_month',
        'participation_percentage',
        'produced_energy_kwh',
        'total_costs',
        'total_credits',
        'net_amount',
        'total_costs_net',
        'total_credits_net',
        'total_vat_amount',
        'status',
        'notes',
        'show_hints',
        'cost_breakdown',
        'credit_breakdown',
        'finalized_at',
        'sent_at',
        'paid_at',
        'created_by',
        'invoice_number',
        'cancellation_date',
        'cancellation_reason',
        'previous_month_outstanding',
    ];

    protected $casts = [
        'participation_percentage' => 'decimal:2',
        'produced_energy_kwh' => 'decimal:3',
        'total_costs' => 'decimal:2',
        'total_credits' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'total_costs_net' => 'decimal:2',
        'total_credits_net' => 'decimal:2',
        'total_vat_amount' => 'decimal:2',
        'cost_breakdown' => 'array',
        'credit_breakdown' => 'array',
        'finalized_at' => 'datetime',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancellation_date' => 'date',
        'previous_month_outstanding' => 'decimal:2',
    ];

    /**
     * Beziehung zur Solaranlage
     */
    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    /**
     * Beziehung zum Kunden
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Beziehung zum Ersteller
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Polymorphe Beziehung zu Dokumenten
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Beziehung zu Zahlungen
     */
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SolarPlantBillingPayment::class);
    }

    /**
     * Status-Optionen
     */
    public static function getStatusOptions(): array
    {
        return [
            'draft' => 'Entwurf',
            'finalized' => 'Finalisiert',
            'sent' => 'Versendet',
            'paid' => 'Bezahlt',
            'cancelled' => 'Storniert',
        ];
    }

    /**
     * Formatierter Monat für Anzeige
     */
    public function getFormattedMonthAttribute(): string
    {
        return Carbon::createFromDate($this->billing_year, $this->billing_month, 1)
            ->locale('de')
            ->translatedFormat('F Y');
    }

    /**
     * Formatierter Nettobetrag
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return number_format($this->net_amount, 2, ',', '.') . ' €';
    }

    /**
     * Formatierte Gesamtkosten
     */
    public function getFormattedTotalCostsAttribute(): string
    {
        return number_format($this->total_costs, 2, ',', '.') . ' €';
    }

    /**
     * Formatierte Gesamtgutschriften
     */
    public function getFormattedTotalCreditsAttribute(): string
    {
        return number_format($this->total_credits, 2, ',', '.') . ' €';
    }

    /**
     * Generiert eine fortlaufende Rechnungsnummer basierend auf den Firmeneinstellungen
     */
    public static function generateInvoiceNumber(): string
    {
        $companySettings = \App\Models\CompanySetting::current();
        $currentYear = date('Y');
        
        // Erstelle das Präfix basierend auf den Firmeneinstellungen
        $prefixParts = [];
        
        if ($companySettings->invoice_number_prefix) {
            $prefixParts[] = $companySettings->invoice_number_prefix;
        }
        
        if ($companySettings->invoice_number_include_year) {
            $prefixParts[] = $currentYear;
        }
        
        $prefix = !empty($prefixParts) ? implode('-', $prefixParts) . '-' : '';

        // Hole die letzte Rechnungsnummer mit dem aktuellen Präfix (auch aus gelöschten Datensätzen)
        // Wichtig: Filtere NULL/leere Rechnungsnummern aus und verwende numerische Sortierung
        $query = static::withTrashed()
            ->whereNotNull('invoice_number')
            ->where('invoice_number', '!=', '');
            
        if ($prefix) {
            // Mit Präfix: verwende LIKE Pattern
            $query->where('invoice_number', 'LIKE', $prefix . '%');
        } else {
            // Ohne Präfix: nur numerische Invoice-Nummern (keine mit Bindestrichen)
            $query->where('invoice_number', 'REGEXP', '^[0-9]+$');
        }

        if ($prefix) {
            // Mit Präfix: String-Sortierung
            $lastBilling = $query->orderBy('invoice_number', 'desc')->first();
        } else {
            // Ohne Präfix: numerische Sortierung für bessere Genauigkeit
            $lastBilling = $query->orderByRaw('CAST(invoice_number AS UNSIGNED) DESC')->first();
        }

        if ($lastBilling) {
            // Extrahiere die Nummer aus der letzten Rechnungsnummer
            $lastNumber = intval(substr($lastBilling->invoice_number, strlen($prefix)));
            $nextNumber = $lastNumber + 1;
        } else {
            // Erste Rechnung mit diesem Präfix
            $nextNumber = 1;
        }

        // Formatiere die Nummer mit führenden Nullen (6 Stellen)
        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generiert mehrere fortlaufende Rechnungsnummern auf einmal (thread-safe)
     */
    public static function generateBatchInvoiceNumbers(int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $companySettings = \App\Models\CompanySetting::current();
        $currentYear = date('Y');
        
        // Erstelle das Präfix basierend auf den Firmeneinstellungen
        $prefixParts = [];
        
        if ($companySettings->invoice_number_prefix) {
            $prefixParts[] = $companySettings->invoice_number_prefix;
        }
        
        if ($companySettings->invoice_number_include_year) {
            $prefixParts[] = $currentYear;
        }
        
        $prefix = !empty($prefixParts) ? implode('-', $prefixParts) . '-' : '';

        // Verwende Database Lock für thread-safe Operation
        return \DB::transaction(function() use ($prefix, $count) {
            // Hole die letzte Rechnungsnummer mit dem aktuellen Präfix mit FOR UPDATE Lock (auch aus gelöschten Datensätzen)
            // Wichtig: Filtere NULL/leere Rechnungsnummern aus und verwende numerische Sortierung
            $query = static::withTrashed()
                ->whereNotNull('invoice_number')
                ->where('invoice_number', '!=', '')
                ->lockForUpdate();
                
            if ($prefix) {
                // Mit Präfix: verwende LIKE Pattern
                $query->where('invoice_number', 'LIKE', $prefix . '%');
                $lastBilling = $query->orderBy('invoice_number', 'desc')->first();
            } else {
                // Ohne Präfix: nur numerische Invoice-Nummern (keine mit Bindestrichen)
                $query->where('invoice_number', 'REGEXP', '^[0-9]+$');
                $lastBilling = $query->orderByRaw('CAST(invoice_number AS UNSIGNED) DESC')->first();
            }

            if ($lastBilling) {
                // Extrahiere die Nummer aus der letzten Rechnungsnummer
                $lastNumber = intval(substr($lastBilling->invoice_number, strlen($prefix)));
                $startNumber = $lastNumber + 1;
            } else {
                // Erste Rechnung mit diesem Präfix
                $startNumber = 1;
            }

            $invoiceNumbers = [];
            for ($i = 0; $i < $count; $i++) {
                $number = $startNumber + $i;
                $invoiceNumbers[] = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
            }

            return $invoiceNumbers;
        });
    }

    /**
     * Ermittelt den offenen Rechnungsbetrag (net_amount > 0) des Vormonats
     * für einen bestimmten Kunden und eine Solaranlage
     */
    public static function getPreviousMonthOutstanding(
        string $solarPlantId,
        string $customerId,
        int $year,
        int $month
    ): float {
        // Berechne den Vormonat
        $previousMonth = Carbon::createFromDate($year, $month, 1)->subMonth();
        $prevYear = $previousMonth->year;
        $prevMonth = $previousMonth->month;

        // Suche die Abrechnung für diesen Kunden/Anlage/Vormonat
        // Ignoriere stornierte Abrechnungen (status != 'cancelled')
        $previousBilling = self::where('solar_plant_id', $solarPlantId)
            ->where('customer_id', $customerId)
            ->where('billing_year', $prevYear)
            ->where('billing_month', $prevMonth)
            ->where('status', '!=', 'cancelled')
            ->first();

        // Gib net_amount zurück wenn > 0, sonst 0
        if ($previousBilling && $previousBilling->net_amount > 0) {
            return (float) $previousBilling->net_amount;
        }

        return 0.0;
    }

    /**
     * Prüft ob alle Vertragsabrechnungen für eine Solaranlage und einen Monat vorhanden sind
     */
    public static function canCreateBillingForMonth(string $solarPlantId, int $year, int $month): bool
    {
        $solarPlant = SolarPlant::find($solarPlantId);
        if (!$solarPlant) {
            return false;
        }

        // Hole alle aktiven Verträge für diese Solaranlage
        $activeContracts = $solarPlant->activeSupplierContracts()->get();

        if ($activeContracts->isEmpty()) {
            return false;
        }

        // Prüfe ob für jeden Vertrag eine Abrechnung für den Monat existiert
        foreach ($activeContracts as $contract) {
            $billingExists = $contract->billings()
                ->where('billing_year', $year)
                ->where('billing_month', $month)
                ->exists();

            if (!$billingExists) {
                return false;
            }
        }

        return true;
    }

    /**
     * Erstellt Abrechnungen für alle Kunden einer Solaranlage für einen bestimmten Monat
     */
    public static function createBillingsForMonth(string $solarPlantId, int $year, int $month, ?float $producedEnergyKwh = null): array
    {
        $solarPlant = SolarPlant::find($solarPlantId);
        if (!$solarPlant) {
            throw new \Exception('Solaranlage nicht gefunden');
        }

        // Prüfe ob Abrechnungen erstellt werden können
        if (!self::canCreateBillingForMonth($solarPlantId, $year, $month)) {
            throw new \Exception('Nicht alle Vertragsabrechnungen für diesen Monat sind vorhanden');
        }

        // Hole alle Kundenbeteiligungen
        $participations = $solarPlant->participations()->get();

        if ($participations->isEmpty()) {
            throw new \Exception('Keine aktiven Kundenbeteiligungen gefunden');
        }

        // Filtere Beteiligungen für die noch keine Abrechnung existiert
        $participationsToProcess = [];
        foreach ($participations as $participation) {
            // Prüfe ob bereits eine Abrechnung für diesen Kunden und Monat existiert (auch gelöschte)
            $existingBilling = self::withTrashed()
                ->where('solar_plant_id', $solarPlantId)
                ->where('customer_id', $participation->customer_id)
                ->where('billing_year', $year)
                ->where('billing_month', $month)
                ->first();

            if ($existingBilling) {
                if ($existingBilling->trashed()) {
                    // Wenn die Abrechnung gelöscht wurde, entferne sie permanent und erstelle eine neue
                    $existingBilling->forceDelete();
                    $participationsToProcess[] = $participation;
                } else {
                    // Abrechnung existiert bereits und ist nicht gelöscht - überspringe
                    continue;
                }
            } else {
                $participationsToProcess[] = $participation;
            }
        }

        if (empty($participationsToProcess)) {
            return [];
        }

        // Generiere alle Rechnungsnummern im Voraus um Duplikate zu vermeiden
        $invoiceNumbers = self::generateBatchInvoiceNumbers(count($participationsToProcess));
        $createdBillings = [];

        foreach ($participationsToProcess as $index => $participation) {
            // Berechne Kosten und Gutschriften für diesen Kunden
            $costData = self::calculateCostsForCustomer($solarPlantId, $participation->customer_id, $year, $month, $participation->percentage);

            // Ermittle den offenen Rechnungsbetrag aus dem Vormonat
            $previousMonthOutstanding = self::getPreviousMonthOutstanding(
                $solarPlantId,
                $participation->customer_id,
                $year,
                $month
            );

            // Berechne net_amount inklusive Vormonats-OP
            $baseAmount = $costData['total_costs'] - $costData['total_credits'];
            if ($baseAmount < 0) {
                // Gutschrift: Vormonats-OP abziehen (Gutschrift wird größer)
                $netAmount = $baseAmount - $previousMonthOutstanding;
            } else {
                // Rechnung: Vormonats-OP aufrechnen (Rechnung wird größer)
                $netAmount = $baseAmount + $previousMonthOutstanding;
            }

            // Erstelle die Abrechnung mit vorgenerierter Rechnungsnummer
            $billing = self::create([
                'solar_plant_id' => $solarPlantId,
                'customer_id' => $participation->customer_id,
                'billing_year' => $year,
                'billing_month' => $month,
                'participation_percentage' => $participation->percentage,
                'produced_energy_kwh' => $producedEnergyKwh,
                'total_costs' => $costData['total_costs'],
                'total_credits' => $costData['total_credits'],
                'total_costs_net' => $costData['total_costs_net'],
                'total_credits_net' => $costData['total_credits_net'],
                'total_vat_amount' => $costData['total_vat_amount'],
                'net_amount' => $netAmount,
                'previous_month_outstanding' => $previousMonthOutstanding,
                'cost_breakdown' => $costData['cost_breakdown'],
                'credit_breakdown' => $costData['credit_breakdown'],
                'status' => 'draft',
                'created_by' => auth()->id(),
                'invoice_number' => $invoiceNumbers[$index],
            ]);

            $createdBillings[] = $billing;
        }

        return $createdBillings;
    }

    /**
     * Bereinigt gelöschte Abrechnungen für eine Solaranlage und einen Monat
     * Diese Methode entfernt permanent gelöschte Abrechnungen, um Unique Constraint Probleme zu vermeiden
     */
    public static function cleanupDeletedBillingsForMonth(string $solarPlantId, int $year, int $month): int
    {
        $deletedCount = self::onlyTrashed()
            ->where('solar_plant_id', $solarPlantId)
            ->where('billing_year', $year)
            ->where('billing_month', $month)
            ->forceDelete();

        return $deletedCount;
    }

    /**
     * Berechnet Kosten und Gutschriften für einen Kunden basierend auf seinem Beteiligungsprozentsatz
     */
    public static function calculateCostsForCustomer(string $solarPlantId, string $customerId, int $year, int $month, float $percentage = null): array
    {
        $solarPlant = SolarPlant::find($solarPlantId);
        $activeContracts = $solarPlant->activeSupplierContracts()->get();

        // Wenn kein Prozentsatz übergeben wurde, hole ihn aus der Beteiligung
        if ($percentage === null) {
            $participation = $solarPlant->participations()->where('customer_id', $customerId)->first();
            if (!$participation) {
                throw new \Exception('Keine Beteiligung für diesen Kunden gefunden');
            }
            $percentage = $participation->percentage;
        }

        $totalCosts = 0;
        $totalCredits = 0;
        $totalCostsNet = 0;
        $totalCreditsNet = 0;
        $totalVatAmount = 0;
        $costBreakdown = [];
        $creditBreakdown = [];

        foreach ($activeContracts as $contract) {
            $billings = $contract->billings()
                ->where('billing_year', $year)
                ->where('billing_month', $month)
                ->get(); // ✅ KORREKTUR: Hole ALLE Belege, nicht nur den ersten

            if ($billings->isEmpty()) {
                continue;
            }

            // Hole den Solaranlagen-Anteil aus der Pivot-Tabelle
            $solarPlantPivot = $contract->solarPlants()
                ->where('solar_plant_id', $solarPlantId)
                ->first();

            if (!$solarPlantPivot) {
                // Wenn diese Solaranlage nicht als Kostenträger für diesen Vertrag hinterlegt ist, überspringe
                continue;
            }

            $solarPlantPercentage = $solarPlantPivot->pivot->percentage ?? 100;

            // Berechne den Anteil: Vertragsbetrag * Solaranlagen-Anteil * Kunden-Anteil
            $solarPlantShare = ($solarPlantPercentage / 100);
            $customerShare = ($percentage / 100);
            $finalShare = $solarPlantShare * $customerShare;

            // Verarbeite ALLE Belege für diesen Vertrag
            foreach ($billings as $billing) {
                // Prüfe ob es sich um Kosten oder Gutschriften handelt basierend auf .env Konfiguration
                $minusIntoInvoiceSetting = config('app.minus_into_invoice', 'CREDIT_NOTE');

                if ($minusIntoInvoiceSetting === 'INVOICE') {
                    // Bei INVOICE: nur billing_type 'credit_note' ist Gutschrift
                    // Negative Beträge mit billing_type 'invoice' sind Kosten mit negativem Vorzeichen
                    $isCredit = $billing->billing_type === 'credit_note';
                } else {
                    // Bei CREDIT_NOTE (Standard): billing_type 'credit_note' ODER negativer Betrag ist Gutschrift
                    $isCredit = $billing->billing_type === 'credit_note' || $billing->total_amount < 0;
                }

            if ($isCredit) {
                // Gutschriften - verwende den absoluten Betrag für die Berechnung
                $customerCredit = abs($billing->total_amount) * $finalShare;
                $totalCredits += $customerCredit;

                // Berechne Netto-Gutschriften und MwSt.
                if ($billing->net_amount) {
                    $customerCreditNet = abs($billing->net_amount) * $finalShare;
                    $totalCreditsNet += $customerCreditNet;

                    // MwSt.-Betrag = Brutto - Netto
                    $vatAmount = $customerCredit - $customerCreditNet;
                    $totalVatAmount -= $vatAmount; // Subtrahiere MwSt. bei Gutschriften
                } else {
                    // Fallback: Verwende 19% MwSt. wenn net_amount nicht verfügbar
                    $customerCreditNet = $customerCredit / 1.19;
                    $totalCreditsNet += $customerCreditNet;
                    $totalVatAmount -= ($customerCredit - $customerCreditNet);
                }

                // Hole die Artikel-Details für diese Gutschrift
                $articles = $billing->articles()->get();
                $articleDetails = [];

                foreach ($articles as $article) {
                    $articleRecord = $article->article;
                    $netTotal = $article->total_price;
                    $taxRate = $articleRecord ? $articleRecord->getCurrentTaxRate() : 0.19;
                    $taxAmount = $netTotal * $taxRate;
                    $grossTotal = $netTotal + $taxAmount;

                    $articleDetails[] = [
                        'article_id' => $article->article_id,
                        'article_name' => $articleRecord ? $articleRecord->name : ($article->description ?? 'Unbekannt'),
                        'quantity' => $article->quantity,
                        'unit' => $articleRecord ? ($articleRecord->unit ?? 'Stk.') : 'Stk.',
                        'unit_price' => $article->unit_price,
                        'total_price_net' => $netTotal,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $taxAmount,
                        'total_price_gross' => $grossTotal,
                        'description' => $article->description,
                        'detailed_description' => $article->detailed_description ?: ($articleRecord ? $articleRecord->detailed_description : '') ?: '',
                    ];
                }

                // Berechne Netto- und MwSt.-Beträge für diese Gutschrift
                $customerCreditNet = $billing->net_amount ? (abs($billing->net_amount) * $finalShare) : ($customerCredit / 1.19);
                $customerCreditVat = $customerCredit - $customerCreditNet;
                $vatRate = $billing->vat_rate ?? 0.19;

                $creditBreakdown[] = [
                    'contract_id' => $contract->id,
                    'contract_title' => $contract->title,
                    'contract_number' => $contract->contract_number,
                    'supplier_id' => $contract->supplier->id,
                    'supplier_name' => $contract->supplier->company_name ?? $contract->supplier->name ?? 'Unbekannt',
                    'contract_billing_id' => $billing->id,
                    'billing_number' => $billing->billing_number,
                    'billing_description' => $billing->description,
                    'total_amount' => $billing->total_amount,
                    'solar_plant_percentage' => $solarPlantPercentage,
                    'customer_percentage' => $percentage,
                    'customer_share' => $customerCredit,
                    'customer_share_net' => $customerCreditNet,
                    'customer_share_vat' => $customerCreditVat,
                    'vat_rate' => $vatRate,
                    'articles' => $articleDetails,
                ];
            } else {
                //dump($billing);
                //dump('Kosten berechnen');
                // Kosten - behandle je nach Konfiguration
                if ($minusIntoInvoiceSetting === 'INVOICE' && $billing->total_amount < 0 && $billing->billing_type === 'invoice') {
                    //dump('start debug');
                    // Bei INVOICE Modus: negative Beträge mit billing_type 'invoice' als negative Kosten
                    $customerCost = $billing->total_amount * $finalShare; // Behalte das negative Vorzeichen
                    //dump($billing->total_amount);
                    //dump($finalShare);
                    //dump($customerCost);
                    //dump('stop debug');
                } else {
                    //dump('Gesamtbetrag brutto: ');
                    // Standard: alle Kosten als positive Beträge
                    $customerCost = abs($billing->total_amount) * $finalShare;
                    //dump($customerCost);
                }
                $totalCosts += $customerCost;

                // Berechne Netto-Kosten und MwSt.
                if ($billing->net_amount) {
                    //dump($billing->net_amount);
                    //dump($finalShare);
                    //$customerCostNet = abs($billing->net_amount) * $finalShare;
                    $customerCostNet = $billing->net_amount * $finalShare;
                    //dump('Gesamtbetrag netto: ');
                    //dump($customerCostNet);
                    //$totalCostsNet += $customerCostNet;
                    $totalCostsNet = $customerCostNet;
                    //dump('Betrag TotalCostsNet');
                    //dump($totalCostsNet);
                    // MwSt.-Betrag = Brutto - Netto
                    $vatAmount = $customerCost - $customerCostNet;
                    //$totalVatAmount += $vatAmount; // Addiere MwSt. bei Kosten
                    $totalVatAmount = $vatAmount; // Addiere MwSt. bei Kosten
                    //dump('Betrag TotalCostsNet');
                    //dump($customerCostNet);
                    //dump($totalCostsNet);
                    //dump($vatAmount);
                    //dump($totalVatAmount);

                } else {
                    // Fallback: Verwende 19% MwSt. wenn net_amount nicht verfügbar
                    $customerCostNet = $customerCost / 1.19;
                    //$totalCostsNet += $customerCostNet;
                    //$totalVatAmount += ($customerCost - $customerCostNet);
                    $totalCostsNet = $customerCostNet;
                    $totalVatAmount = ($customerCost - $customerCostNet);
                }

                // Hole die Artikel-Details für diese Abrechnung
                $articles = $billing->articles()->get();
                $articleDetails = [];

                foreach ($articles as $article) {
                    $articleRecord = $article->article;
                    $netTotal = $article->total_price;
                    $taxRate = $articleRecord ? $articleRecord->getCurrentTaxRate() : 0.19;
                    $taxAmount = $netTotal * $taxRate;
                    $grossTotal = $netTotal + $taxAmount;

                    $articleDetails[] = [
                        'article_id' => $article->article_id,
                        'article_name' => $articleRecord ? $articleRecord->name : ($article->description ?? 'Unbekannt'),
                        'quantity' => $article->quantity,
                        'unit' => $articleRecord ? ($articleRecord->unit ?? 'Stk.') : 'Stk.',
                        'unit_price' => $article->unit_price,
                        'total_price_net' => $netTotal,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $taxAmount,
                        'total_price_gross' => $grossTotal,
                        'description' => $article->description,
                        'detailed_description' => $article->detailed_description ?: ($articleRecord ? $articleRecord->detailed_description : '') ?: '',
                    ];
                }
                /*
                if ($billing->billing_number == "AB-2025-0307") {
                    dump('start debug Kostenrechnung');
                    dump($customerCost.'_x_');
                    dump($customerCostNet);
                    dump($totalCostsNet);
                    dump($vatAmount);
                    dump($totalVatAmount);
                    dump('ende debug Kostenrechnung');
                }
                */
                // Berechne Netto- und MwSt.-Beträge für diese Kosten
                //$customerCostNet = $billing->net_amount ? (abs($billing->net_amount) * $finalShare) : ($customerCost / 1.19);
                //$customerCostVat = $customerCost - $customerCostNet;
                $customerCostNet = $billing->net_amount * $finalShare;
                $customerCostVat = $customerCost - $customerCostNet;

                $vatRate = $billing->vat_rate ?? 0.19;

                /*
                if ($billing->billing_number == "AB-2025-0151") {
                    dump($billing->billing_number);
                    dump($billing->description);
                    dump($customerCostNet);
                    dump($customerCostVat);
                    dump($customerCost);
                    dump($vatRate);
                    dump($billing);
                }*/
/*
                if ($billing->billing_number == "AB-2025-0307") {
                    dump($billing->billing_number);
                    dump($billing->description);
                    dump($customerCostNet);
                    dump($customerCostVat);
                    dump($customerCost);
                    dump($vatRate);
                    dump($billing);
                }
*/
                $costBreakdown[] = [
                    'contract_id' => $contract->id,
                    'contract_title' => $contract->title,
                    'contract_number' => $contract->contract_number,
                    'supplier_id' => $contract->supplier->id,
                    'supplier_name' => $contract->supplier->company_name ?? $contract->supplier->name ?? 'Unbekannt',
                    'contract_billing_id' => $billing->id,
                    'billing_number' => $billing->billing_number,
                    'billing_description' => $billing->description,
                    'total_amount' => $billing->total_amount,
                    'solar_plant_percentage' => $solarPlantPercentage,
                    'customer_percentage' => $percentage,
                    'customer_share' => $customerCost,
                    'customer_share_net' => $customerCostNet,
                    'customer_share_vat' => $customerCostVat,
                    'vat_rate' => $vatRate,
                    'articles' => $articleDetails,
                ];
            }
            } // ✅ Schließe die foreach($billings as $billing) Schleife
        } // ✅ Schließe die foreach($activeContracts as $contract) Schleife
#exit;
        return [
            'total_costs' => $totalCosts,
            'total_credits' => $totalCredits,
            'total_costs_net' => $totalCostsNet,
            'total_credits_net' => $totalCreditsNet,
            'total_vat_amount' => $totalVatAmount,
            'net_amount' => $totalCosts - $totalCredits,
            'cost_breakdown' => $costBreakdown,
            'credit_breakdown' => $creditBreakdown,
        ];
    }
}
