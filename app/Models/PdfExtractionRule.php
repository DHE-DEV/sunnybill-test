<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfExtractionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'field_name',
        'extraction_method',
        'pattern',
        'fallback_pattern',
        'description',
        'priority',
        'options',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'options' => 'array',
    ];

    /**
     * Beziehung zum Supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Scope für aktive Regeln
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für bestimmten Supplier
     */
    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope für bestimmtes Feld
     */
    public function scopeForField($query, string $fieldName)
    {
        return $query->where('field_name', $fieldName);
    }

    /**
     * Extrahiert Daten aus Text basierend auf der Regel
     */
    public function extractFromText(string $text): ?string
    {
        $options = $this->options ?? [];
        $caseSensitive = $options['case_sensitive'] ?? false;
        $multiline = $options['multiline'] ?? false;
        
        $searchText = $caseSensitive ? $text : strtolower($text);
        $pattern = $caseSensitive ? $this->pattern : strtolower($this->pattern);

        switch ($this->extraction_method) {
            case 'regex':
                return $this->extractWithRegex($text, $this->pattern, $multiline);
                
            case 'keyword_search':
                return $this->extractWithKeyword($searchText, $pattern);
                
            case 'position_based':
                return $this->extractWithPosition($text, $this->pattern);
                
            case 'zugferd':
                return $this->extractFromZugferd($text);
                
            case 'line_after_keyword':
                return $this->extractLineAfterKeyword($searchText, $pattern);
                
            case 'between_keywords':
                return $this->extractBetweenKeywords($searchText, $pattern);
                
            default:
                return null;
        }
    }

    /**
     * Regex-basierte Extraktion
     */
    private function extractWithRegex(string $text, string $pattern, bool $multiline = false): ?string
    {
        $flags = 'i'; // Case insensitive
        if ($multiline) {
            $flags .= 'm';
        }
        
        if (preg_match('/' . $pattern . '/' . $flags, $text, $matches)) {
            return isset($matches[1]) ? trim($matches[1]) : trim($matches[0]);
        }
        
        return null;
    }

    /**
     * Keyword-basierte Extraktion
     */
    private function extractWithKeyword(string $text, string $keyword): ?string
    {
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            if (str_contains($line, $keyword)) {
                // Extrahiere Text nach dem Keyword
                $parts = explode($keyword, $line, 2);
                if (count($parts) > 1) {
                    return trim($parts[1]);
                }
            }
        }
        
        return null;
    }

    /**
     * Positions-basierte Extraktion
     */
    private function extractWithPosition(string $text, string $positionData): ?string
    {
        // Format: "line:5,start:10,length:20" oder "line:5,after:keyword"
        $params = [];
        parse_str(str_replace([':', ','], ['=', '&'], $positionData), $params);
        
        $lines = explode("\n", $text);
        $lineIndex = ($params['line'] ?? 1) - 1;
        
        if (!isset($lines[$lineIndex])) {
            return null;
        }
        
        $line = $lines[$lineIndex];
        
        if (isset($params['after'])) {
            $pos = strpos($line, $params['after']);
            if ($pos !== false) {
                return trim(substr($line, $pos + strlen($params['after'])));
            }
        } elseif (isset($params['start']) && isset($params['length'])) {
            return trim(substr($line, $params['start'], $params['length']));
        }
        
        return trim($line);
    }

    /**
     * ZuGFeRD-Extraktion (Placeholder)
     */
    private function extractFromZugferd(string $text): ?string
    {
        // Hier würde ZuGFeRD-spezifische Logik implementiert
        // Für jetzt als Placeholder
        return null;
    }

    /**
     * Extrahiert Zeile nach Keyword
     */
    private function extractLineAfterKeyword(string $text, string $keyword): ?string
    {
        $lines = explode("\n", $text);
        
        for ($i = 0; $i < count($lines) - 1; $i++) {
            if (str_contains($lines[$i], $keyword)) {
                return trim($lines[$i + 1]);
            }
        }
        
        return null;
    }

    /**
     * Extrahiert Text zwischen zwei Keywords
     */
    private function extractBetweenKeywords(string $text, string $pattern): ?string
    {
        // Format: "start_keyword|end_keyword"
        $keywords = explode('|', $pattern);
        if (count($keywords) !== 2) {
            return null;
        }
        
        $startPos = strpos($text, $keywords[0]);
        $endPos = strpos($text, $keywords[1], $startPos);
        
        if ($startPos !== false && $endPos !== false) {
            $start = $startPos + strlen($keywords[0]);
            return trim(substr($text, $start, $endPos - $start));
        }
        
        return null;
    }

    /**
     * Versucht Extraktion mit Fallback-Pattern
     */
    public function extractWithFallback(string $text): ?string
    {
        $result = $this->extractFromText($text);
        
        if ($result === null && $this->fallback_pattern) {
            // Temporär Pattern ändern für Fallback
            $originalPattern = $this->pattern;
            $this->pattern = $this->fallback_pattern;
            $result = $this->extractFromText($text);
            $this->pattern = $originalPattern;
        }
        
        return $result;
    }

    /**
     * Verfügbare Extraktionsmethoden
     */
    public static function getExtractionMethods(): array
    {
        return [
            'regex' => 'Regulärer Ausdruck',
            'keyword_search' => 'Keyword-Suche',
            'position_based' => 'Positions-basiert',
            'zugferd' => 'ZuGFeRD-Daten',
            'line_after_keyword' => 'Zeile nach Keyword',
            'between_keywords' => 'Zwischen Keywords',
        ];
    }

    /**
     * Standard-Feldnamen
     */
    public static function getStandardFields(): array
    {
        return [
            'invoice_number' => 'Rechnungsnummer',
            'invoice_date' => 'Rechnungsdatum',
            'due_date' => 'Fälligkeitsdatum',
            'total_amount' => 'Gesamtbetrag',
            'net_amount' => 'Nettobetrag',
            'tax_amount' => 'Steuerbetrag',
            'tax_rate' => 'Steuersatz',
            'customer_number' => 'Kundennummer',
            'contract_number' => 'Vertragsnummer',
            'period_start' => 'Abrechnungszeitraum Start',
            'period_end' => 'Abrechnungszeitraum Ende',
        ];
    }
}