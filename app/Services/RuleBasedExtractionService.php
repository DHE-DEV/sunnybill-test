<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\PdfExtractionRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RuleBasedExtractionService
{
    /**
     * Extrahiert Daten aus PDF-Text basierend auf Lieferanten-spezifischen Regeln
     */
    public function extractData(Supplier $supplier, string $pdfText): array
    {
        $rules = PdfExtractionRule::where('supplier_id', $supplier->id)
            ->active()
            ->orderBy('priority')
            ->get();

        if ($rules->isEmpty()) {
            Log::info('Keine Extraktionsregeln für Lieferanten gefunden', [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->display_name
            ]);
            return [];
        }

        $extractedData = [];
        $extractionLog = [];
        $usedRuleIds = [];

        foreach ($rules as $rule) {
            $value = $rule->extractWithFallback($pdfText);
            
            if ($value !== null) {
                $extractedData[$rule->field_name] = $this->cleanExtractedValue($value, $rule->field_name);
                $usedRuleIds[] = $rule->id;
                
                $extractionLog[] = [
                    'rule_id' => $rule->id,
                    'field_name' => $rule->field_name,
                    'extraction_method' => $rule->extraction_method,
                    'pattern' => $rule->pattern,
                    'extracted_value' => $value,
                    'cleaned_value' => $extractedData[$rule->field_name],
                    'priority' => $rule->priority,
                ];
            } else {
                $extractionLog[] = [
                    'field_name' => $rule->field_name,
                    'extraction_method' => $rule->extraction_method,
                    'pattern' => $rule->pattern,
                    'extracted_value' => null,
                    'error' => 'Keine Übereinstimmung gefunden',
                    'priority' => $rule->priority,
                ];
            }
        }

        Log::info('Datenextraktion abgeschlossen', [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->display_name,
            'extracted_fields' => array_keys($extractedData),
            'total_rules' => $rules->count(),
            'successful_extractions' => count($extractedData),
            'extraction_log' => $extractionLog
        ]);

        return [
            'data' => $extractedData,
            'used_rule_ids' => array_unique($usedRuleIds),
        ];
    }

    /**
     * Bereinigt extrahierte Werte basierend auf dem Feldtyp
     */
    private function cleanExtractedValue(string $value, string $fieldName): string
    {
        $originalValue = $value;
        $cleaned = trim($value);

        switch ($fieldName) {
            case 'invoice_number':
            case 'contract_number':
            case 'customer_number':
            case 'creditor_number':
                // Entferne führende/nachfolgende Sonderzeichen, behalte aber Bindestriche und Punkte
                $cleaned = preg_replace('/^[^\w\-\.]+|[^\w\-\.]+$/', '', $cleaned);
                break;

            case 'total_amount':
            case 'net_amount':
            case 'tax_amount':
                // Normalisiere Geldbeträge
                $cleaned = $this->normalizeAmount($cleaned);
                break;

            case 'tax_rate':
                // Normalisiere Prozentsätze
                $cleaned = $this->normalizePercentage($cleaned);
                break;

            case 'invoice_date':
            case 'due_date':
            case 'period_start':
            case 'period_end':
                // Normalisiere Datumsangaben
                $cleaned = $this->normalizeDate($cleaned);
                break;

            default:
                // Standard-Bereinigung: Entferne überflüssige Leerzeichen
                $cleaned = preg_replace('/\s+/', ' ', $cleaned);
                break;
        }

        // Log wenn Bereinigung einen leeren String produziert
        if (empty($cleaned) && !empty($originalValue)) {
            Log::warning('Bereinigung produzierte leeren String', [
                'field_name' => $fieldName,
                'original_value' => $originalValue,
                'original_length' => strlen($originalValue),
                'cleaned_value' => $cleaned,
                'cleaned_length' => strlen($cleaned)
            ]);
        }

        return $cleaned;
    }

    /**
     * Normalisiert Geldbeträge
     */
    private function normalizeAmount(string $amount): string
    {
        // Entferne Währungssymbole und Leerzeichen
        $cleaned = preg_replace('/[€$£¥\s]/', '', $amount);
        
        // Behandle deutsche Zahlenformate (1.234,56)
        if (preg_match('/^\d{1,3}(?:\.\d{3})*,\d{2}$/', $cleaned)) {
            $cleaned = str_replace(['.', ','], ['', '.'], $cleaned);
        }
        // Behandle englische Zahlenformate (1,234.56)
        elseif (preg_match('/^\d{1,3}(?:,\d{3})*\.\d{2}$/', $cleaned)) {
            $cleaned = str_replace(',', '', $cleaned);
        }
        // Behandle einfache Formate ohne Tausendertrennzeichen
        elseif (preg_match('/^\d+[,\.]\d{2}$/', $cleaned)) {
            $cleaned = str_replace(',', '.', $cleaned);
        }

        return $cleaned;
    }

    /**
     * Normalisiert Prozentsätze
     */
    private function normalizePercentage(string $percentage): string
    {
        // Entferne Prozentzeichen und Leerzeichen
        $cleaned = preg_replace('/[%\s]/', '', $percentage);
        
        // Ersetze Komma durch Punkt
        $cleaned = str_replace(',', '.', $cleaned);
        
        return $cleaned;
    }

    /**
     * Normalisiert Datumsangaben
     */
    private function normalizeDate(string $date): string
    {
        // Entferne überflüssige Leerzeichen
        $cleaned = preg_replace('/\s+/', ' ', trim($date));
        
        // Versuche verschiedene Datumsformate zu erkennen und zu normalisieren
        $patterns = [
            '/(\d{1,2})\.(\d{1,2})\.(\d{4})/' => '$3-$2-$1', // DD.MM.YYYY -> YYYY-MM-DD
            '/(\d{1,2})\/(\d{1,2})\/(\d{4})/' => '$3-$2-$1', // DD/MM/YYYY -> YYYY-MM-DD
            '/(\d{4})-(\d{1,2})-(\d{1,2})/' => '$1-$2-$3',   // YYYY-MM-DD (bereits korrekt)
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $cleaned)) {
                $cleaned = preg_replace($pattern, $replacement, $cleaned);
                break;
            }
        }

        return $cleaned;
    }

    /**
     * Testet eine Extraktionsregel gegen gegebenen Text
     */
    public function testRule(PdfExtractionRule $rule, string $pdfText): array
    {
        $startTime = microtime(true);
        
        $primaryResult = $rule->extractFromText($pdfText);
        $fallbackResult = null;
        
        if ($primaryResult === null && $rule->fallback_pattern) {
            $fallbackResult = $rule->extractWithFallback($pdfText);
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // in Millisekunden

        return [
            'rule_id' => $rule->id,
            'field_name' => $rule->field_name,
            'extraction_method' => $rule->extraction_method,
            'pattern' => $rule->pattern,
            'fallback_pattern' => $rule->fallback_pattern,
            'primary_result' => $primaryResult,
            'fallback_result' => $fallbackResult,
            'final_result' => $fallbackResult ?? $primaryResult,
            'cleaned_result' => $primaryResult ? $this->cleanExtractedValue($primaryResult, $rule->field_name) : null,
            'execution_time_ms' => round($executionTime, 2),
            'success' => $primaryResult !== null || $fallbackResult !== null,
        ];
    }

    /**
     * Testet alle Regeln eines Lieferanten gegen gegebenen Text
     */
    public function testAllRulesForSupplier(Supplier $supplier, string $pdfText): array
    {
        $rules = PdfExtractionRule::where('supplier_id', $supplier->id)
            ->active()
            ->orderBy('priority')
            ->get();

        $results = [];
        $totalStartTime = microtime(true);

        foreach ($rules as $rule) {
            $results[] = $this->testRule($rule, $pdfText);
        }

        $totalEndTime = microtime(true);
        $totalExecutionTime = ($totalEndTime - $totalStartTime) * 1000;

        return [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->display_name,
            'total_rules' => $rules->count(),
            'successful_extractions' => collect($results)->where('success', true)->count(),
            'total_execution_time_ms' => round($totalExecutionTime, 2),
            'rule_results' => $results,
        ];
    }

    /**
     * Extrahiert Daten mit Confidence-Scoring
     */
    public function extractDataWithConfidence(Supplier $supplier, string $pdfText): array
    {
        $extractionResult = $this->extractData($supplier, $pdfText);
        
        if (empty($extractionResult)) {
            return [
                'extracted_data' => [],
                'used_rule_ids' => [],
                'confidence_scores' => [],
                'overall_confidence' => 0,
            ];
        }

        $extractedData = $extractionResult['data'];
        $confidenceScores = [];

        foreach ($extractedData as $fieldName => $value) {
            $confidence = $this->calculateFieldConfidence($fieldName, $value, $pdfText);
            $confidenceScores[$fieldName] = $confidence;
        }

        return [
            'extracted_data' => $extractedData,
            'used_rule_ids' => $extractionResult['used_rule_ids'],
            'confidence_scores' => $confidenceScores,
            'overall_confidence' => empty($confidenceScores) ? 0 : array_sum($confidenceScores) / count($confidenceScores),
        ];
    }

    /**
     * Berechnet Confidence-Score für ein extrahiertes Feld
     */
    private function calculateFieldConfidence(string $fieldName, string $value, string $pdfText): float
    {
        $confidence = 0.5; // Basis-Confidence

        // Erhöhe Confidence basierend auf Feldtyp und Wert-Eigenschaften
        switch ($fieldName) {
            case 'invoice_number':
            case 'contract_number':
                // Höhere Confidence für strukturierte Nummern
                if (preg_match('/^[A-Z0-9\-\.]+$/', $value)) {
                    $confidence += 0.3;
                }
                break;

            case 'total_amount':
            case 'net_amount':
            case 'tax_amount':
                // Höhere Confidence für gültige Geldbeträge
                if (is_numeric(str_replace(',', '.', $value))) {
                    $confidence += 0.4;
                }
                break;

            case 'invoice_date':
            case 'due_date':
                // Höhere Confidence für gültige Datumsformate
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $confidence += 0.4;
                }
                break;
        }

        // Reduziere Confidence für sehr kurze oder sehr lange Werte
        $valueLength = strlen($value);
        if ($valueLength < 3 || $valueLength > 100) {
            $confidence -= 0.2;
        }

        // Erhöhe Confidence wenn der Wert mehrfach im Text vorkommt
        // Prüfe auf leeren String um substr_count Fehler zu vermeiden
        if (empty($value)) {
            Log::warning('Leerer Wert in calculateFieldConfidence gefunden', [
                'field_name' => $fieldName,
                'value' => $value,
                'value_length' => strlen($value),
                'pdf_text_preview' => substr($pdfText, 0, 200)
            ]);
            $confidence -= 0.3; // Reduziere Confidence für leere Werte
        } else {
            $occurrences = substr_count($pdfText, $value);
            if ($occurrences > 1) {
                $confidence += min(0.2, $occurrences * 0.05);
            }
        }

        return max(0.0, min(1.0, $confidence));
    }

    /**
     * Generiert Extraktionsregeln basierend auf Beispiel-Daten
     */
    public function generateRulesFromExample(Supplier $supplier, string $pdfText, array $knownValues): Collection
    {
        $suggestedRules = collect();

        foreach ($knownValues as $fieldName => $expectedValue) {
            $rules = $this->findPatternsForValue($fieldName, $expectedValue, $pdfText);
            $suggestedRules = $suggestedRules->merge($rules);
        }

        return $suggestedRules;
    }

    /**
     * Findet mögliche Pattern für einen bekannten Wert im Text
     */
    private function findPatternsForValue(string $fieldName, string $value, string $pdfText): Collection
    {
        $patterns = collect();
        $lines = explode("\n", $pdfText);

        // Suche nach dem Wert im Text
        foreach ($lines as $lineIndex => $line) {
            if (str_contains($line, $value)) {
                // Keyword-basierte Extraktion
                $keywords = $this->findKeywordsBeforeValue($line, $value);
                foreach ($keywords as $keyword) {
                    $patterns->push([
                        'field_name' => $fieldName,
                        'extraction_method' => 'keyword_search',
                        'pattern' => $keyword,
                        'priority' => 1,
                        'description' => "Automatisch generiert: Keyword '{$keyword}' vor Wert '{$value}'",
                    ]);
                }

                // Regex-basierte Extraktion
                $regexPattern = $this->generateRegexPattern($fieldName, $value, $line);
                if ($regexPattern) {
                    $patterns->push([
                        'field_name' => $fieldName,
                        'extraction_method' => 'regex',
                        'pattern' => $regexPattern,
                        'priority' => 2,
                        'description' => "Automatisch generiert: Regex-Pattern für '{$value}'",
                    ]);
                }

                // Zeile-nach-Keyword Extraktion
                if ($lineIndex > 0) {
                    $previousLine = $lines[$lineIndex - 1];
                    $potentialKeywords = $this->extractPotentialKeywords($previousLine);
                    foreach ($potentialKeywords as $keyword) {
                        $patterns->push([
                            'field_name' => $fieldName,
                            'extraction_method' => 'line_after_keyword',
                            'pattern' => $keyword,
                            'priority' => 3,
                            'description' => "Automatisch generiert: Zeile nach Keyword '{$keyword}'",
                        ]);
                    }
                }
            }
        }

        return $patterns;
    }

    /**
     * Findet Keywords vor einem Wert in einer Zeile
     */
    private function findKeywordsBeforeValue(string $line, string $value): array
    {
        $position = strpos($line, $value);
        if ($position === false) {
            return [];
        }

        $beforeValue = substr($line, 0, $position);
        $words = preg_split('/\s+/', trim($beforeValue));
        
        return array_filter($words, function($word) {
            return strlen($word) > 2 && !is_numeric($word);
        });
    }

    /**
     * Generiert ein Regex-Pattern für einen Wert
     */
    private function generateRegexPattern(string $fieldName, string $value, string $line): ?string
    {
        switch ($fieldName) {
            case 'invoice_number':
            case 'contract_number':
                return '([A-Z0-9\-\.]+)';
                
            case 'total_amount':
            case 'net_amount':
            case 'tax_amount':
                return '(\d+[,\.]\d{2})';
                
            case 'invoice_date':
            case 'due_date':
                return '(\d{1,2}[\.\/]\d{1,2}[\.\/]\d{4})';
                
            default:
                return null;
        }
    }

    /**
     * Extrahiert potentielle Keywords aus einer Zeile
     */
    private function extractPotentialKeywords(string $line): array
    {
        $words = preg_split('/\s+/', trim($line));
        
        return array_filter($words, function($word) {
            return strlen($word) > 3 && 
                   !is_numeric($word) && 
                   !preg_match('/^\d+[,\.]\d+$/', $word) && // Keine Zahlen
                   preg_match('/^[a-zA-ZäöüÄÖÜß]+:?$/', $word); // Nur Buchstaben, optional mit Doppelpunkt
        });
    }
}
