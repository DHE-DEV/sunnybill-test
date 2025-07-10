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
        'rule_name',
        'source_field',
        'target_field',
        'field_source',
        'field_name',
        'matching_pattern',
        'match_type',
        'match_threshold',
        'match_pattern',
        'priority',
        'description',
        'confidence_weight',
        'case_sensitive',
        'normalize_whitespace',
        'remove_special_chars',
        'preprocessing_rules',
        'fallback_rules',
        'test_examples',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'case_sensitive' => 'boolean',
        'normalize_whitespace' => 'boolean',
        'remove_special_chars' => 'boolean',
        'confidence_weight' => 'integer',
        'priority' => 'integer',
        'match_threshold' => 'decimal:2',
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
        $searchValue = $this->normalizeValue($value);
        $pattern = $this->normalizeValue($this->matching_pattern);

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
     * Normalisiert einen Wert basierend auf den Rule-Einstellungen
     */
    public function normalizeValue(string $value): string
    {
        $normalized = $value;

        // Whitespace normalisieren
        if ($this->normalize_whitespace) {
            $normalized = preg_replace('/\s+/', '', $normalized);
        }

        // Sonderzeichen entfernen
        if ($this->remove_special_chars) {
            $normalized = preg_replace('/[^a-zA-Z0-9\s]/', '', $normalized);
        }

        // Case-Sensitivity
        if (!$this->case_sensitive) {
            $normalized = strtolower($normalized);
        }

        return $normalized;
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