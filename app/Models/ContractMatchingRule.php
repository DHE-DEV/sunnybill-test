<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractMatchingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_contract_id',
        'field_source',
        'field_name',
        'matching_pattern',
        'match_type',
        'description',
        'confidence_weight',
        'case_sensitive',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'case_sensitive' => 'boolean',
        'confidence_weight' => 'integer',
    ];

    /**
     * Beziehung zum SupplierContract
     */
    public function supplierContract(): BelongsTo
    {
        return $this->belongsTo(SupplierContract::class);
    }

    /**
     * Scope für aktive Regeln
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für bestimmten Vertrag
     */
    public function scopeForContract($query, int $contractId)
    {
        return $query->where('supplier_contract_id', $contractId);
    }

    /**
     * Scope für bestimmte Feldquelle
     */
    public function scopeForFieldSource($query, string $fieldSource)
    {
        return $query->where('field_source', $fieldSource);
    }

    /**
     * Prüft ob ein Wert mit dieser Regel übereinstimmt
     */
    public function matches(string $value): bool
    {
        $searchValue = $this->case_sensitive ? $value : strtolower($value);
        $pattern = $this->case_sensitive ? $this->matching_pattern : strtolower($this->matching_pattern);

        switch ($this->match_type) {
            case 'exact':
                return $searchValue === $pattern;
                
            case 'contains':
                return str_contains($searchValue, $pattern);
                
            case 'regex':
                $flags = $this->case_sensitive ? '' : 'i';
                return preg_match('/' . $this->matching_pattern . '/' . $flags, $value) === 1;
                
            case 'starts_with':
                return str_starts_with($searchValue, $pattern);
                
            case 'ends_with':
                return str_ends_with($searchValue, $pattern);
                
            default:
                return false;
        }
    }

    /**
     * Berechnet Confidence-Score für einen Match
     */
    public function calculateConfidence(string $value): float
    {
        if (!$this->matches($value)) {
            return 0.0;
        }

        // Basis-Confidence basierend auf Match-Typ
        $baseConfidence = match ($this->match_type) {
            'exact' => 1.0,
            'regex' => 0.9,
            'starts_with', 'ends_with' => 0.8,
            'contains' => 0.7,
            default => 0.5,
        };

        // Gewichtung anwenden
        $weightedConfidence = $baseConfidence * ($this->confidence_weight / 10);

        return min(1.0, $weightedConfidence);
    }

    /**
     * Extrahiert Wert aus verschiedenen Quellen
     */
    public function extractValueFromSource(array $data): ?string
    {
        switch ($this->field_source) {
            case 'pdf_text':
                return $data['pdf_text'] ?? null;
                
            case 'email_text':
                return $data['email_text'] ?? null;
                
            case 'email_subject':
                return $data['email_subject'] ?? null;
                
            case 'sender_email':
                return $data['sender_email'] ?? null;
                
            case 'extracted_data':
                if (!$this->field_name) {
                    return null;
                }
                return $data['extracted_data'][$this->field_name] ?? null;
                
            default:
                return null;
        }
    }

    /**
     * Verfügbare Feldquellen
     */
    public static function getFieldSources(): array
    {
        return [
            'pdf_text' => 'PDF-Text',
            'email_text' => 'E-Mail-Text',
            'extracted_data' => 'Extrahierte Daten',
            'email_subject' => 'E-Mail-Betreff',
            'sender_email' => 'Absender E-Mail',
        ];
    }

    /**
     * Verfügbare Match-Typen
     */
    public static function getMatchTypes(): array
    {
        return [
            'exact' => 'Exakte Übereinstimmung',
            'contains' => 'Enthält',
            'regex' => 'Regulärer Ausdruck',
            'starts_with' => 'Beginnt mit',
            'ends_with' => 'Endet mit',
        ];
    }

    /**
     * Standard-Feldnamen für extracted_data
     */
    public static function getExtractedDataFields(): array
    {
        return [
            'invoice_number' => 'Rechnungsnummer',
            'contract_number' => 'Vertragsnummer',
            'customer_number' => 'Kundennummer',
            'creditor_number' => 'Gläubigernummer',
            'period_start' => 'Abrechnungszeitraum Start',
            'period_end' => 'Abrechnungszeitraum Ende',
            'total_amount' => 'Gesamtbetrag',
            'invoice_date' => 'Rechnungsdatum',
        ];
    }
}