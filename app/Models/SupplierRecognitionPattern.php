<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierRecognitionPattern extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'pattern_type',
        'pattern_value',
        'description',
        'confidence_weight',
        'priority',
        'is_regex',
        'case_sensitive',
        'test_examples',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_regex' => 'boolean',
        'case_sensitive' => 'boolean',
        'confidence_weight' => 'float',
        'priority' => 'integer',
    ];

    /**
     * Beziehung zum Supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Scope für aktive Pattern
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für bestimmten Pattern-Typ
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('pattern_type', $type);
    }

    /**
     * Scope für bestimmten Supplier
     */
    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Prüft ob ein Text mit diesem Pattern übereinstimmt
     */
    public function matches(string $text): bool
    {
        $pattern = $this->pattern_value;
        
        switch ($this->pattern_type) {
            case 'email_domain':
                return str_contains(strtolower($text), strtolower($pattern));
                
            case 'company_name':
                return str_contains(strtolower($text), strtolower($pattern));
                
            case 'tax_id':
                // Entferne Leerzeichen und Sonderzeichen für Vergleich
                $cleanText = preg_replace('/[^a-zA-Z0-9]/', '', $text);
                $cleanPattern = preg_replace('/[^a-zA-Z0-9]/', '', $pattern);
                return str_contains(strtolower($cleanText), strtolower($cleanPattern));
                
            case 'bank_account':
                // Entferne Leerzeichen für IBAN-Vergleich
                $cleanText = str_replace(' ', '', $text);
                $cleanPattern = str_replace(' ', '', $pattern);
                return str_contains(strtolower($cleanText), strtolower($cleanPattern));
                
            case 'sender_email':
                return str_contains(strtolower($text), strtolower($pattern));
                
            case 'pdf_text_contains':
                return str_contains(strtolower($text), strtolower($pattern));
                
            case 'invoice_format':
                // Regex-Pattern für Rechnungsformat
                return preg_match('/' . $pattern . '/i', $text) === 1;
                
            default:
                return false;
        }
    }

    /**
     * Verfügbare Pattern-Typen
     */
    public static function getPatternTypes(): array
    {
        return [
            'email_domain' => 'E-Mail Domain',
            'company_name' => 'Firmenname',
            'tax_id' => 'Steuernummer/USt-ID',
            'bank_account' => 'Bankverbindung',
            'sender_email' => 'Absender E-Mail',
            'pdf_text_contains' => 'PDF enthält Text',
            'invoice_format' => 'Rechnungsformat (Regex)',
        ];
    }
}