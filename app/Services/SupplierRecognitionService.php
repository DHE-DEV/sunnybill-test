<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierRecognitionPattern;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SupplierRecognitionService
{
    /**
     * Erkennt den Lieferanten basierend auf PDF-Text, E-Mail-Text und Metadaten
     */
    public function recognizeSupplier(string $pdfText, string $emailText = '', array $metadata = []): ?Supplier
    {
        $patterns = SupplierRecognitionPattern::active()
            ->with('supplier')
            ->get();

        if ($patterns->isEmpty()) {
            Log::info('Keine aktiven Erkennungspattern gefunden');
            return null;
        }

        $scores = [];
        $matchDetails = [];

        foreach ($patterns as $pattern) {
            $score = $this->calculatePatternScore($pattern, $pdfText, $emailText, $metadata);
            
            if ($score > 0) {
                $supplierId = $pattern->supplier_id;
                $scores[$supplierId] = ($scores[$supplierId] ?? 0) + $score;
                
                if (!isset($matchDetails[$supplierId])) {
                    $matchDetails[$supplierId] = [];
                }
                
                $matchDetails[$supplierId][] = [
                    'pattern_type' => $pattern->pattern_type,
                    'pattern_value' => $pattern->pattern_value,
                    'score' => $score,
                    'confidence_weight' => $pattern->confidence_weight,
                ];
            }
        }

        if (empty($scores)) {
            Log::info('Keine Lieferanten-Pattern gefunden', [
                'pdf_text_length' => strlen($pdfText),
                'email_text_length' => strlen($emailText),
                'metadata' => $metadata
            ]);
            return null;
        }

        // Finde den besten Match
        $bestSupplierId = array_keys($scores, max($scores))[0];
        $bestScore = $scores[$bestSupplierId];
        $supplier = Supplier::find($bestSupplierId);

        Log::info('Lieferant erkannt', [
            'supplier_id' => $bestSupplierId,
            'supplier_name' => $supplier?->display_name,
            'score' => $bestScore,
            'all_scores' => $scores,
            'match_details' => $matchDetails[$bestSupplierId] ?? []
        ]);

        return $supplier;
    }

    /**
     * Berechnet den Score für ein bestimmtes Pattern
     */
    private function calculatePatternScore(
        SupplierRecognitionPattern $pattern, 
        string $pdfText, 
        string $emailText, 
        array $metadata
    ): float {
        $searchTexts = $this->prepareSearchTexts($pdfText, $emailText, $metadata);
        
        foreach ($searchTexts as $source => $text) {
            if ($this->patternMatchesText($pattern, $text, $source)) {
                $baseScore = $this->getBaseScoreForPatternType($pattern->pattern_type);
                $weightedScore = $baseScore * ($pattern->confidence_weight / 10);
                
                Log::debug('Pattern Match gefunden', [
                    'pattern_type' => $pattern->pattern_type,
                    'pattern_value' => $pattern->pattern_value,
                    'source' => $source,
                    'base_score' => $baseScore,
                    'confidence_weight' => $pattern->confidence_weight,
                    'weighted_score' => $weightedScore
                ]);
                
                return $weightedScore;
            }
        }

        return 0;
    }

    /**
     * Bereitet die Suchtexte für verschiedene Pattern-Typen vor
     */
    private function prepareSearchTexts(string $pdfText, string $emailText, array $metadata): array
    {
        return [
            'pdf_text' => $pdfText,
            'email_text' => $emailText,
            'email_subject' => $metadata['email_subject'] ?? '',
            'sender_email' => $metadata['sender_email'] ?? '',
            'sender_name' => $metadata['sender_name'] ?? '',
            'combined' => $pdfText . ' ' . $emailText,
        ];
    }

    /**
     * Prüft ob ein Pattern mit einem Text übereinstimmt
     */
    private function patternMatchesText(SupplierRecognitionPattern $pattern, string $text, string $source): bool
    {
        if (empty($text)) {
            return false;
        }

        // Spezielle Behandlung für verschiedene Pattern-Typen und Quellen
        switch ($pattern->pattern_type) {
            case 'email_domain':
                return $this->matchEmailDomain($pattern, $text, $source);
                
            case 'sender_email':
                return $source === 'sender_email' && $pattern->matches($text);
                
            case 'company_name':
                return $pattern->matches($text);
                
            case 'tax_id':
                return $pattern->matches($text);
                
            case 'bank_account':
                return $pattern->matches($text);
                
            case 'pdf_text_contains':
                return $source === 'pdf_text' && $pattern->matches($text);
                
            case 'invoice_format':
                return $pattern->matches($text);
                
            default:
                return $pattern->matches($text);
        }
    }

    /**
     * Spezielle Behandlung für E-Mail-Domain-Pattern
     */
    private function matchEmailDomain(SupplierRecognitionPattern $pattern, string $text, string $source): bool
    {
        // E-Mail-Domain sollte hauptsächlich in sender_email oder email_text gesucht werden
        if (!in_array($source, ['sender_email', 'email_text', 'combined'])) {
            return false;
        }

        return $pattern->matches($text);
    }

    /**
     * Gibt den Basis-Score für verschiedene Pattern-Typen zurück
     */
    private function getBaseScoreForPatternType(string $patternType): float
    {
        return match ($patternType) {
            'email_domain' => 0.9,
            'sender_email' => 0.95,
            'tax_id' => 0.85,
            'bank_account' => 0.8,
            'company_name' => 0.7,
            'pdf_text_contains' => 0.6,
            'invoice_format' => 0.75,
            default => 0.5,
        };
    }

    /**
     * Gibt alle möglichen Lieferanten mit ihren Scores zurück
     */
    public function getAllSupplierScores(string $pdfText, string $emailText = '', array $metadata = []): array
    {
        $patterns = SupplierRecognitionPattern::active()
            ->with('supplier')
            ->get();

        $scores = [];
        $matchDetails = [];

        foreach ($patterns as $pattern) {
            $score = $this->calculatePatternScore($pattern, $pdfText, $emailText, $metadata);
            
            if ($score > 0) {
                $supplierId = $pattern->supplier_id;
                $scores[$supplierId] = ($scores[$supplierId] ?? 0) + $score;
                
                if (!isset($matchDetails[$supplierId])) {
                    $matchDetails[$supplierId] = [];
                }
                
                $matchDetails[$supplierId][] = [
                    'pattern_type' => $pattern->pattern_type,
                    'pattern_value' => $pattern->pattern_value,
                    'score' => $score,
                    'confidence_weight' => $pattern->confidence_weight,
                ];
            }
        }

        // Sortiere nach Score absteigend
        arsort($scores);

        $result = [];
        foreach ($scores as $supplierId => $totalScore) {
            $supplier = Supplier::find($supplierId);
            if ($supplier) {
                $result[] = [
                    'supplier' => $supplier,
                    'total_score' => $totalScore,
                    'confidence' => min(1.0, $totalScore),
                    'match_details' => $matchDetails[$supplierId] ?? [],
                ];
            }
        }

        return $result;
    }

    /**
     * Testet ein Pattern gegen gegebene Texte
     */
    public function testPattern(SupplierRecognitionPattern $pattern, string $pdfText, string $emailText = '', array $metadata = []): array
    {
        $searchTexts = $this->prepareSearchTexts($pdfText, $emailText, $metadata);
        $results = [];

        foreach ($searchTexts as $source => $text) {
            if (!empty($text)) {
                $matches = $this->patternMatchesText($pattern, $text, $source);
                $score = $matches ? $this->calculatePatternScore($pattern, $pdfText, $emailText, $metadata) : 0;
                
                $results[$source] = [
                    'text_length' => strlen($text),
                    'matches' => $matches,
                    'score' => $score,
                    'text_preview' => substr($text, 0, 200) . (strlen($text) > 200 ? '...' : ''),
                ];
            }
        }

        return $results;
    }

    /**
     * Erstellt automatisch Pattern basierend auf Lieferanten-Daten
     */
    public function generatePatternsForSupplier(Supplier $supplier): Collection
    {
        $patterns = collect();

        // E-Mail-Domain Pattern
        if ($supplier->email) {
            $domain = substr(strrchr($supplier->email, "@"), 1);
            if ($domain) {
                $patterns->push([
                    'pattern_type' => 'email_domain',
                    'pattern_value' => $domain,
                    'confidence_weight' => 9,
                    'description' => "Automatisch generiert aus Lieferanten-E-Mail: {$supplier->email}",
                ]);
            }
        }

        // Firmenname Pattern
        if ($supplier->company_name) {
            $patterns->push([
                'pattern_type' => 'company_name',
                'pattern_value' => $supplier->company_name,
                'confidence_weight' => 8,
                'description' => "Automatisch generiert aus Firmenname",
            ]);
        }

        // USt-ID Pattern
        if ($supplier->vat_id) {
            $patterns->push([
                'pattern_type' => 'tax_id',
                'pattern_value' => $supplier->vat_id,
                'confidence_weight' => 9,
                'description' => "Automatisch generiert aus USt-ID",
            ]);
        }

        // IBAN Pattern
        if ($supplier->iban) {
            $patterns->push([
                'pattern_type' => 'bank_account',
                'pattern_value' => $supplier->iban,
                'confidence_weight' => 8,
                'description' => "Automatisch generiert aus IBAN",
            ]);
        }

        return $patterns;
    }
}