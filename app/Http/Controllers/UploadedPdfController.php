<?php

namespace App\Http\Controllers;

use App\Models\UploadedPdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use horstoeko\zugferd\ZugferdDocumentPdfReader;

class UploadedPdfController extends Controller
{
    /**
     * Download einer hochgeladenen PDF
     */
    public function download(UploadedPdf $uploadedPdf)
    {
        if (!$uploadedPdf->fileExists()) {
            abort(404, 'Datei nicht gefunden');
        }

        $filePath = $uploadedPdf->getFullPath();
        $filename = $uploadedPdf->original_filename ?: $uploadedPdf->name . '.pdf';

        \Log::info('PDF-Download angefordert', [
            'uploaded_pdf_id' => $uploadedPdf->id,
            'filename' => $filename,
            'file_path' => $uploadedPdf->file_path,
            'user_id' => auth()->id(),
        ]);

        return response()->download($filePath, $filename);
    }

    /**
     * PDF im Browser anzeigen
     */
    public function viewPdf(UploadedPdf $uploadedPdf)
    {
        if (!$uploadedPdf->fileExists()) {
            abort(404, 'Datei nicht gefunden');
        }

        $filePath = $uploadedPdf->getFullPath();
        $filename = $uploadedPdf->original_filename ?: $uploadedPdf->name . '.pdf';

        \Log::info('PDF-Anzeige angefordert', [
            'uploaded_pdf_id' => $uploadedPdf->id,
            'filename' => $filename,
            'file_path' => $uploadedPdf->file_path,
            'user_id' => auth()->id(),
        ]);

        // Lese die PDF-Datei
        $fileContent = file_get_contents($filePath);
        
        if (!$fileContent) {
            abort(404, 'Datei konnte nicht gelesen werden');
        }

        // Gebe die PDF mit inline Content-Disposition zurück (für Browser-Anzeige)
        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Content-Length', strlen($fileContent));
    }

    /**
     * PDF-Analyse für hochgeladene Datei
     */
    public function analyze(UploadedPdf $uploadedPdf)
    {
        if (!$uploadedPdf->fileExists()) {
            abort(404, 'Datei nicht gefunden');
        }

        \Log::info('PDF-Analyse für hochgeladene Datei gestartet', [
            'uploaded_pdf_id' => $uploadedPdf->id,
            'filename' => $uploadedPdf->original_filename,
            'file_path' => $uploadedPdf->file_path,
            'user_id' => auth()->id(),
        ]);

        // Setze Status auf "processing"
        $uploadedPdf->setAnalysisStatus('processing');

        try {
            // Lade die PDF-Datei
            $filePath = $uploadedPdf->getFullPath();
            $fileContent = file_get_contents($filePath);

            if (!$fileContent) {
                throw new \Exception('Datei konnte nicht gelesen werden');
            }

            \Log::info('PDF-Datei erfolgreich geladen', [
                'uploaded_pdf_id' => $uploadedPdf->id,
                'file_size' => strlen($fileContent),
            ]);

        // Analysiere die PDF
        $analysisData = $this->performPdfAnalysis($fileContent, $uploadedPdf);

            // Speichere Analyse-Ergebnisse
            $uploadedPdf->setAnalysisStatus('completed', $analysisData);

            \Log::info('PDF-Analyse erfolgreich abgeschlossen', [
                'uploaded_pdf_id' => $uploadedPdf->id,
                'analysis_data_keys' => array_keys($analysisData),
            ]);

            // Zeige Analyse-Ergebnisse
            return view('pdf-analysis.uploaded-result', [
                'uploadedPdf' => $uploadedPdf,
                'analysisData' => $analysisData,
            ]);

        } catch (\Exception $e) {
            \Log::error('PDF-Analyse fehlgeschlagen', [
                'uploaded_pdf_id' => $uploadedPdf->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Setze Status auf "failed"
            $uploadedPdf->setAnalysisStatus('failed', [
                'error' => $e->getMessage(),
                'failed_at' => now()->toISOString(),
            ]);

            return view('pdf-analysis.uploaded-result', [
                'uploadedPdf' => $uploadedPdf,
                'error' => 'PDF-Analyse fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Führt die PDF-Analyse durch - Refaktoriert für Service-basierte Extraktion
     */
    private function performPdfAnalysis(string $fileContent, UploadedPdf $uploadedPdf): array
    {
        $parser = new Parser();
        $pdf = $parser->parseContent($fileContent);
        
        // Extrahiere Text
        $text = $pdf->getText();
        
        // Extrahiere ZUGFeRD XML
        $rawXml = $this->extractRawXml($uploadedPdf);

        \Log::info('PDF-Text und XML extrahiert', [
            'uploaded_pdf_id' => $uploadedPdf->id,
            'text_length' => strlen($text),
            'xml_length' => $rawXml ? strlen($rawXml) : 0,
            'text_preview' => substr($text, 0, 200),
        ]);

        // SCHRITT 1: Lieferanten-Erkennung (primär)
        $supplierRecognitionService = app(\App\Services\SupplierRecognitionService::class);
        $recognitionResult = $supplierRecognitionService->getAllSupplierScores($text, '', []);
        $recognizedSupplier = !empty($recognitionResult) ? $recognitionResult[0]['supplier'] : null;
        $scores = $this->calculateMatchingScores($text); // Behalte die alte Logik für Scores

        // DEBUG: EON-spezifische Diagnose-Logs
        \Log::info('EON-Diagnose: Lieferanten-Erkennung', [
            'uploaded_pdf_id' => $uploadedPdf->id,
            'text_contains_eon' => stripos($text, 'eon') !== false,
            'text_contains_e_on' => stripos($text, 'e.on') !== false,
            'recognition_results_count' => count($recognitionResult),
            'recognized_supplier_id' => $recognizedSupplier?->id,
            'recognized_supplier_name' => $recognizedSupplier?->display_name,
            'text_preview_first_500' => substr($text, 0, 500),
        ]);

        // DEBUG: Prüfe EON-Lieferant in Datenbank
        $eonSuppliers = \App\Models\Supplier::where('company_name', 'LIKE', '%eon%')
            ->orWhere('company_name', 'LIKE', '%e.on%')
            ->orWhere('name', 'LIKE', '%eon%')
            ->get();
        
        \Log::info('EON-Diagnose: EON-Lieferanten in Datenbank', [
            'uploaded_pdf_id' => $uploadedPdf->id,
            'eon_suppliers_found' => $eonSuppliers->count(),
            'eon_suppliers' => $eonSuppliers->map(function($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'company_name' => $s->company_name,
                    'display_name' => $s->display_name,
                    'is_active' => $s->is_active,
                    'recognition_patterns_count' => $s->activeRecognitionPatterns()->count(),
                    'extraction_rules_count' => $s->activePdfExtractionRules()->count(),
                ];
            })->toArray(),
        ]);

        $usedRecognitionPatternIds = [];
        if (!empty($recognitionResult)) {
            // Sammle alle Pattern-IDs von allen passenden Lieferanten
            $usedRecognitionPatternIds = collect($recognitionResult)->pluck('match_details.*.pattern_id')->flatten()->unique()->all();
        }

        // SCHRITT 2: Service-basierte Datenextraktion (primär)
        $serviceBasedData = null;
        $contractMatching = null;
        
        if ($recognizedSupplier) {
            \Log::info('PDF-Analyse: Lieferant erkannt, verwende Service-basierte Extraktion', [
                'uploaded_pdf_id' => $uploadedPdf->id,
                'supplier_id' => $recognizedSupplier->id,
                'supplier_name' => $recognizedSupplier->display_name
            ]);
            
            // Verwende RuleBasedExtractionService für Datenextraktion
            $ruleBasedService = app(\App\Services\RuleBasedExtractionService::class);
            $serviceBasedData = $ruleBasedService->extractDataWithConfidence($recognizedSupplier, $text);
            
            // Verwende ContractMatchingService
            if (!empty($serviceBasedData['extracted_data'])) {
                $contractService = app(\App\Services\ContractMatchingService::class);
                $contractMatching = $contractService->findMatchingContracts(
                    $recognizedSupplier,
                    $serviceBasedData['extracted_data'],
                    $text
                );
            }
        }

        // SCHRITT 3: Fallback zu hardkodierten Pattern (nur wenn Service-Extraktion fehlschlägt)
        $fallbackInvoiceData = [];
        $fallbackSupplierData = [];
        
        if (!$serviceBasedData || empty($serviceBasedData['extracted_data'])) {
            \Log::info('PDF-Analyse: Fallback zu hardkodierten Pattern', [
                'uploaded_pdf_id' => $uploadedPdf->id,
                'reason' => $recognizedSupplier ? 'Keine Service-Daten extrahiert' : 'Kein Lieferant erkannt'
            ]);
            
            $fallbackInvoiceData = $this->extractInvoiceDataFallback($text);
            $fallbackSupplierData = $this->extractSupplierDataFallback($text);
            
            // DEBUG: EON-spezifische Fallback-Analyse
            \Log::info('EON-Diagnose: Fallback-Extraktion Ergebnisse', [
                'uploaded_pdf_id' => $uploadedPdf->id,
                'fallback_invoice_data' => $fallbackInvoiceData,
                'fallback_supplier_data' => $fallbackSupplierData,
                'invoice_number_found' => isset($fallbackInvoiceData['invoice_number']),
                'total_amount_found' => isset($fallbackInvoiceData['total_amount']),
                'company_name_found' => isset($fallbackSupplierData['company_name']),
            ]);
        }

        // SCHRITT 4: Kombiniere Ergebnisse
        $finalInvoiceData = $serviceBasedData['extracted_data'] ?? $fallbackInvoiceData;
        $finalSupplierData = $recognizedSupplier ? [
            'company_name' => $recognizedSupplier->company_name,
            'email' => $recognizedSupplier->email,
            'phone' => $recognizedSupplier->phone,
            'vat_id' => $recognizedSupplier->vat_id,
            'iban' => $recognizedSupplier->iban,
            'recognized_supplier_id' => $recognizedSupplier->id,
        ] : $fallbackSupplierData;

        // Berechne Gesamt-Confidence-Score
        $overallConfidence = $this->calculateOverallConfidence($finalInvoiceData, $finalSupplierData, $scores);

        // Bestimme den Dokumententyp
        $documentType = 'Unbekannt';
        if (stripos($text, 'gutschrift') !== false || stripos($text, 'credit note') !== false) {
            $documentType = 'Gutschrift';
        } elseif (stripos($text, 'rechnung') !== false || stripos($text, 'invoice') !== false) {
            $documentType = 'Rechnung';
        }

        return [
            'document_type' => $documentType,
            'text' => $text,
            'text_length' => strlen($text),
            'raw_xml' => $rawXml,
            'invoice_data' => $finalInvoiceData,
            'supplier_data' => $finalSupplierData,
            'matching_scores' => $scores,
            'recognized_supplier' => $recognizedSupplier,
            'service_based_extraction' => $serviceBasedData,
            'contract_matching' => $contractMatching,
            'fallback_used' => !$serviceBasedData || empty($serviceBasedData['extracted_data']),
            'overall_confidence' => $overallConfidence,
            'analyzed_at' => now()->toISOString(),
            'used_rules_and_patterns' => [
                'recognition_pattern_ids' => $usedRecognitionPatternIds,
                'extraction_rule_ids' => $serviceBasedData['used_rule_ids'] ?? [],
                'contract_matching_rule_ids' => collect($contractMatching)->pluck('matching_fields.*.rule_id')->flatten()->filter()->unique()->all(),
            ],
            'file_info' => [
                'name' => $uploadedPdf->name,
                'original_filename' => $uploadedPdf->original_filename,
                'file_size' => $uploadedPdf->file_size,
                'mime_type' => $uploadedPdf->mime_type,
            ],
        ];
    }

    /**
     * DEPRECATED: Diese Methode wurde durch die Service-Integration in performPdfAnalysis() ersetzt
     * Wird nur noch für Kompatibilität beibehalten
     */
    private function performAdvancedAnalysis(\App\Models\Supplier $supplier, string $text, UploadedPdf $uploadedPdf): array
    {
        \Log::warning('PDF-Analyse: performAdvancedAnalysis() ist deprecated, verwende Service-Integration in performPdfAnalysis()', [
            'uploaded_pdf_id' => $uploadedPdf->id,
            'supplier_id' => $supplier->id
        ]);

        // Fallback für Kompatibilität
        $ruleBasedService = app(\App\Services\RuleBasedExtractionService::class);
        $extractionResult = $ruleBasedService->extractDataWithConfidence($supplier, $text);
        
        $contractMatches = [];
        if (!empty($extractionResult['extracted_data'])) {
            $contractService = app(\App\Services\ContractMatchingService::class);
            $contractMatches = $contractService->findMatchingContracts(
                $supplier,
                $extractionResult['extracted_data'],
                $text
            );
        }

        return [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->display_name,
            'rule_based_extraction' => $extractionResult,
            'contract_matching' => $contractMatches,
            'processing_time' => '0 ms (deprecated)',
            'errors' => ['Diese Methode ist deprecated']
        ];
    }

    /**
     * Berechnet einen Gesamt-Confidence-Score für die Analyse
     */
    private function calculateOverallConfidence(array $invoiceData, array $supplierData, array $scores): array
    {
        $confidence = [
            'invoice_data_score' => 0,
            'supplier_data_score' => 0,
            'matching_score' => 0,
            'overall_score' => 0,
            'quality_indicators' => []
        ];

        // Bewerte Rechnungsdaten-Qualität
        $invoiceFields = ['invoice_number', 'invoice_date', 'total_amount'];
        $foundInvoiceFields = array_intersect($invoiceFields, array_keys($invoiceData));
        $confidence['invoice_data_score'] = (count($foundInvoiceFields) / count($invoiceFields)) * 100;

        if (count($foundInvoiceFields) >= 2) {
            $confidence['quality_indicators'][] = 'Grundlegende Rechnungsdaten vollständig';
        }

        // Bewerte Lieferantendaten-Qualität
        $supplierFields = ['company_name', 'email', 'phone', 'address'];
        $foundSupplierFields = array_intersect($supplierFields, array_keys($supplierData));
        $confidence['supplier_data_score'] = (count($foundSupplierFields) / count($supplierFields)) * 100;

        if (isset($supplierData['vat_id'])) {
            $confidence['supplier_data_score'] += 20; // Bonus für USt-ID
            $confidence['quality_indicators'][] = 'USt-ID gefunden';
        }

        // Bewerte Lieferanten-Matching
        if (!empty($scores)) {
            $confidence['matching_score'] = min(100, $scores[0]['score']);
            if ($scores[0]['score'] > 70) {
                $confidence['quality_indicators'][] = 'Hohe Lieferanten-Übereinstimmung';
            }
        }

        // Berechne Gesamt-Score
        $confidence['overall_score'] = round(
            ($confidence['invoice_data_score'] * 0.4 +
             $confidence['supplier_data_score'] * 0.3 +
             $confidence['matching_score'] * 0.3), 2
        );

        // Qualitätsbewertung
        if ($confidence['overall_score'] >= 80) {
            $confidence['quality_level'] = 'Sehr gut';
        } elseif ($confidence['overall_score'] >= 60) {
            $confidence['quality_level'] = 'Gut';
        } elseif ($confidence['overall_score'] >= 40) {
            $confidence['quality_level'] = 'Befriedigend';
        } else {
            $confidence['quality_level'] = 'Verbesserungsbedürftig';
        }

        \Log::info('PDF-Analyse: Confidence-Bewertung', [
            'overall_score' => $confidence['overall_score'],
            'quality_level' => $confidence['quality_level'],
            'invoice_data_score' => $confidence['invoice_data_score'],
            'supplier_data_score' => $confidence['supplier_data_score'],
            'matching_score' => $confidence['matching_score'],
            'quality_indicators' => $confidence['quality_indicators']
        ]);

        return $confidence;
    }

    /**
     * Extrahiert Rechnungsdaten aus dem Text (Fallback-Methode)
     */
    private function extractInvoiceDataFallback(string $text): array
    {
        $invoiceData = [];
        $extractionLog = [];

        // Erweiterte Rechnungsnummer-Pattern
        $invoiceNumberPatterns = [
            '/(?:Rechnung(?:s)?(?:nummer)?|Invoice(?:\s+Number)?|Bill(?:\s+Number)?|Rg\.?\s*Nr\.?)[:\s\-\#]*([A-Z0-9\s\-\/\.]+?)(?:\s*\n|\s*$|\s{2,})/i',
            '/(?:Invoice|Bill|Rechnung)[:\s\-\#]*([A-Z0-9\s\-\/\.]{3,20})/i',
            '/(?:Nr\.?|No\.?|Number)[:\s]*([A-Z0-9\s\-\/\.]{3,20})/i'
        ];

        // DEBUG: EON-spezifische Pattern-Tests
        \Log::info('EON-Diagnose: Teste Rechnungsnummer-Pattern', [
            'text_contains_rechnung' => stripos($text, 'rechnung') !== false,
            'text_contains_invoice' => stripos($text, 'invoice') !== false,
            'text_contains_nr' => stripos($text, 'nr.') !== false || stripos($text, 'nr:') !== false,
        ]);

        foreach ($invoiceNumberPatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // DEBUG: Prüfe Array-Zugriff
                if (isset($matches[1])) {
                    $invoiceData['invoice_number'] = preg_replace('/\s+/', '', trim($matches[1]));
                    $extractionLog[] = "Rechnungsnummer gefunden mit Pattern $index: " . $matches[1];
                    
                    // DEBUG: EON-spezifisches Logging
                    \Log::info('EON-Diagnose: Rechnungsnummer gefunden', [
                        'pattern_index' => $index,
                        'pattern' => $pattern,
                        'raw_match' => $matches[1],
                        'cleaned_match' => $invoiceData['invoice_number'],
                        'full_matches' => $matches
                    ]);
                } else {
                    \Log::warning('PDF-Analyse: Rechnungsnummer-Pattern ohne Capturing Group', [
                        'pattern_index' => $index,
                        'pattern' => $pattern,
                        'matches' => $matches
                    ]);
                }
                break;
            } else {
                // DEBUG: Pattern hat nicht gematcht
                \Log::debug('EON-Diagnose: Rechnungsnummer-Pattern kein Match', [
                    'pattern_index' => $index,
                    'pattern' => $pattern
                ]);
            }
        }

        // Erweiterte Rechnungsdatum-Pattern - ALLE haben Capturing Groups
        $datePatterns = [
            '/(?:Rechnung(?:s)?datum|Invoice\s+Date|Datum)[:\s]*(\d{1,2}[\.\-\/]\d{1,2}[\.\-\/]\d{2,4})/i',
            '/(?:vom|dated?)[:\s]*(\d{1,2}[\.\-\/]\d{1,2}[\.\-\/]\d{2,4})/i',
            '/(\d{1,2}[\.\-\/]\d{1,2}[\.\-\/]\d{4})/i'  // ✅ Bereits korrekt
        ];

        foreach ($datePatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // DEBUG: Prüfe Array-Zugriff
                if (isset($matches[1])) {
                    $invoiceData['invoice_date'] = trim($matches[1]);
                    $extractionLog[] = "Rechnungsdatum gefunden mit Pattern $index: " . $matches[1];
                } else {
                    \Log::warning('PDF-Analyse: Datum-Pattern ohne Capturing Group', [
                        'pattern_index' => $index,
                        'pattern' => $pattern,
                        'matches' => $matches
                    ]);
                }
                break;
            }
        }

        // Erweiterte Gesamtbetrag-Pattern - ALLE haben Capturing Groups
        $amountPatterns = [
            '/(?:Gesamtbetrag|Gesamtsumme|Total|Summe|Endbetrag|Rechnungsbetrag)[:\s]*([0-9.,]+)\s*€?/i',
            '/(?:zu\s+zahlender?\s+Betrag|Zahlbetrag)[:\s]*([0-9.,]+)\s*€?/i',
            '/€\s*([0-9.,]+)/',  // ✅ Bereits korrekt
            '/([0-9.,]+)\s*EUR/i'  // ✅ Bereits korrekt
        ];

        // DEBUG: EON-spezifische Gesamtbetrag-Tests
        \Log::info('EON-Diagnose: Teste Gesamtbetrag-Pattern', [
            'text_contains_gesamtbetrag' => stripos($text, 'gesamtbetrag') !== false,
            'text_contains_total' => stripos($text, 'total') !== false,
            'text_contains_summe' => stripos($text, 'summe') !== false,
            'text_contains_euro_symbol' => stripos($text, '€') !== false,
            'text_contains_eur' => stripos($text, 'eur') !== false,
        ]);

        foreach ($amountPatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $invoiceData['total_amount'] = $this->normalizeAmount(trim($matches[1]));
                $extractionLog[] = "Gesamtbetrag gefunden mit Pattern $index: " . $matches[1];
                
                // DEBUG: EON-spezifisches Logging
                \Log::info('EON-Diagnose: Gesamtbetrag gefunden', [
                    'pattern_index' => $index,
                    'pattern' => $pattern,
                    'raw_match' => $matches[1],
                    'normalized_amount' => $invoiceData['total_amount'],
                    'full_matches' => $matches
                ]);
                break;
            } else {
                // DEBUG: Pattern hat nicht gematcht
                \Log::debug('EON-Diagnose: Gesamtbetrag-Pattern kein Match', [
                    'pattern_index' => $index,
                    'pattern' => $pattern
                ]);
            }
        }

        // Erweiterte Nettobetrag-Pattern
        $netPatterns = [
            '/(?:Nettobetrag|Netto|Net\s+Amount)[:\s]*([0-9.,]+)\s*€?/i',
            '/(?:Summe\s+netto|Netto\s+Summe)[:\s]*([0-9.,]+)\s*€?/i'
        ];

        foreach ($netPatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $invoiceData['net_amount'] = $this->normalizeAmount(trim($matches[1]));
                $extractionLog[] = "Nettobetrag gefunden mit Pattern $index: " . $matches[1];
                break;
            }
        }

        // Erweiterte MwSt-Pattern
        $taxPatterns = [
            '/(?:MwSt\.?|USt\.?|VAT|Tax|Mehrwertsteuer)[:\s]*([0-9.,]+)\s*€?/i',
            '/(\d{1,2}(?:[,\.]\d{1,2})?)\s*%\s*(?:MwSt\.?|USt\.?|VAT)[:\s]*([0-9.,]+)\s*€?/i'
        ];

        foreach ($taxPatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $invoiceData['tax_amount'] = $this->normalizeAmount(trim($matches[count($matches) - 1]));
                if (count($matches) > 2) {
                    $invoiceData['tax_rate'] = trim($matches[1]) . '%';
                }
                $extractionLog[] = "MwSt gefunden mit Pattern $index: " . $matches[0];
                break;
            }
        }

        // Log Extraction Results
        \Log::info('PDF-Analyse: Rechnungsdaten-Extraktion', [
            'extracted_fields' => array_keys($invoiceData),
            'extraction_log' => $extractionLog,
            'text_length' => strlen($text),
            'text_preview' => substr($text, 0, 300)
        ]);

        return $invoiceData;
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
     * Extrahiert Lieferantendaten aus dem Text (Fallback-Methode)
     */
    private function extractSupplierDataFallback(string $text): array
    {
        $supplierData = [];
        $extractionLog = [];

        // Erweiterte Firmenname-Extraktion
        $lines = explode("\n", $text);
        $cleanLines = array_filter(array_map('trim', $lines));
        
        if (!empty($cleanLines)) {
            $firstLine = reset($cleanLines);
            $supplierData['company_name'] = $firstLine;
            $extractionLog[] = "Firmenname aus erster Zeile: " . $firstLine;
            
            // Suche nach Rechtsformen für bessere Firmenname-Erkennung
            $companyPatterns = [
                '/([A-Za-zäöüÄÖÜß\s&\-\.]+(?:GmbH|AG|KG|OHG|eG|SE|UG|mbH)(?:\s+&\s+Co\.?\s+KG)?)/i',
                '/([A-Za-zäöüÄÖÜß\s\-\.]+(?:Energie|Strom|Gas|Wasser|Stadtwerke|Versorger|Netze|Netz)(?:\s+[A-Za-zäöüÄÖÜß\s\-\.]*)?)/i',
                '/([A-Za-zäöüÄÖÜß\s\-\.]+(?:Bank|Sparkasse|Volksbank|Raiffeisenbank|Genossenschaftsbank)(?:\s+[A-Za-zäöüÄÖÜß\s\-\.]*)?)/i'
            ];
            
            foreach ($companyPatterns as $index => $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $supplierData['company_name_structured'] = trim($matches[1]);
                    $extractionLog[] = "Strukturierter Firmenname gefunden mit Pattern $index: " . $matches[1];
                    break;
                }
            }
        }

        // Erweiterte E-Mail-Extraktion - ALLE haben Capturing Groups
        $emailPatterns = [
            '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/',  // ✅ Bereits korrekt
            '/(?:E-?Mail|Email)[:\s]*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i'  // ✅ Bereits korrekt
        ];

        foreach ($emailPatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // DEBUG: Prüfe Array-Zugriff
                if (isset($matches[1])) {
                    $supplierData['email'] = trim($matches[1]);
                    $extractionLog[] = "E-Mail gefunden mit Pattern $index: " . $matches[1];
                } else {
                    \Log::warning('PDF-Analyse: E-Mail-Pattern ohne Capturing Group', [
                        'pattern_index' => $index,
                        'pattern' => $pattern,
                        'matches' => $matches
                    ]);
                }
                break;
            }
        }

        // Erweiterte Telefon-Extraktion - KORRIGIERT: Alle Patterns haben jetzt Capturing Groups
        $phonePatterns = [
            '/(?:Tel\.?|Phone|Telefon|Fon)[:\s]*([+\d\s\-\(\)\/]{8,})/i',
            '/(\+49[0-9\s\-\/\(\)]{8,}|0[0-9\s\-\/\(\)]{8,})/',  // KORRIGIERT: Capturing Group hinzugefügt
            '/(?:Telefax|Fax)[:\s]*([+\d\s\-\(\)\/]{8,})/i'
        ];

        foreach ($phonePatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // DEBUG: Log alle matches für Diagnose
                \Log::debug('PDF-Analyse: Telefon-Pattern Debug', [
                    'pattern_index' => $index,
                    'pattern' => $pattern,
                    'matches_count' => count($matches),
                    'matches_array' => $matches,
                    'has_matches_1' => isset($matches[1])
                ]);
                
                // Prüfe ob $matches[1] existiert
                if (isset($matches[1])) {
                    $supplierData['phone'] = trim($matches[1]);
                    $extractionLog[] = "Telefon gefunden mit Pattern $index: " . $matches[1];
                } else {
                    // Fallback: verwende $matches[0] wenn keine Capturing Group
                    $supplierData['phone'] = trim($matches[0]);
                    $extractionLog[] = "Telefon gefunden mit Pattern $index (ohne Capturing Group): " . $matches[0];
                    \Log::warning('PDF-Analyse: Telefon-Pattern ohne Capturing Group verwendet', [
                        'pattern_index' => $index,
                        'pattern' => $pattern,
                        'matched_text' => $matches[0]
                    ]);
                }
                break;
            }
        }

        // Erweiterte Adress-Extraktion
        $addressPatterns = [
            '/([A-Za-zäöüÄÖÜß\s\-\.]+(?:straße|str\.|platz|weg|gasse|allee))\s+(\d+[a-zA-Z]?)\s*,?\s*(\d{5})\s+([A-Za-zäöüÄÖÜß\s\-\.]+)/i',
            '/Postfach\s+(\d+[a-zA-Z]?)\s*,?\s*(\d{5})\s+([A-Za-zäöüÄÖÜß\s\-\.]+)/i',
            '/(\d{5})\s+([A-Za-zäöüÄÖÜß\s\-\.]+)/i'
        ];

        foreach ($addressPatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if ($index == 0) {
                    $supplierData['address'] = [
                        'street' => trim($matches[1]),
                        'house_number' => trim($matches[2]),
                        'postal_code' => trim($matches[3]),
                        'city' => trim($matches[4]),
                        'full_address' => trim($matches[0])
                    ];
                } elseif ($index == 1) {
                    $supplierData['address'] = [
                        'postbox' => trim($matches[1]),
                        'postal_code' => trim($matches[2]),
                        'city' => trim($matches[3]),
                        'full_address' => trim($matches[0])
                    ];
                } else {
                    $supplierData['address'] = [
                        'postal_code' => trim($matches[1]),
                        'city' => trim($matches[2]),
                        'full_address' => trim($matches[0])
                    ];
                }
                $extractionLog[] = "Adresse gefunden mit Pattern $index: " . $matches[0];
                break;
            }
        }

        // USt-ID Extraktion
        if (preg_match('/(?:USt[.\-]?IdNr\.?|Umsatzsteuer[.\-]?Identifikationsnummer)[:\s]*([A-Z]{2}[0-9]+)/i', $text, $matches)) {
            $supplierData['vat_id'] = trim($matches[1]);
            $extractionLog[] = "USt-ID gefunden: " . $matches[1];
        }

        // Handelsregister
        if (preg_match('/(?:Handelsregister|HRB|HRA)[:\s]*([A-Z0-9\s]+)/i', $text, $matches)) {
            $supplierData['commercial_register'] = trim($matches[1]);
            $extractionLog[] = "Handelsregister gefunden: " . $matches[1];
        }

        // Erweiterte Energieversorgungs-Daten
        
        // Verbrauchsstelle
        $consumptionSitePatterns = [
            '/(?:Verbrauchsstelle|Lieferstelle)[:\s]*([A-Za-zäöüÄÖÜß\s\-\.]+\d+[a-zA-Z]?\s*,?\s*\d{5}\s+[A-Za-zäöüÄÖÜß\s\-\.]+)/i',
            '/Verbrauchsstelle\s*([^,\n]+,\s*\d{5}\s+[^,\n]+)/i'
        ];

        foreach ($consumptionSitePatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (isset($matches[1])) {
                    $supplierData['consumption_site'] = trim($matches[1]);
                    $extractionLog[] = "Verbrauchsstelle gefunden mit Pattern $index: " . $matches[1];
                } else {
                    \Log::warning('PDF-Analyse: Verbrauchsstelle-Pattern ohne Capturing Group', [
                        'pattern_index' => $index,
                        'pattern' => $pattern,
                        'matches' => $matches
                    ]);
                }
                break;
            }
        }

        // Marktlokation
        $marketLocationPatterns = [
            '/(?:Marktlokation|MaLo)[:\s]*(\d{11,})/i',
            '/Marktlokation\s+(\d{11,})/i'
        ];

        foreach ($marketLocationPatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (isset($matches[1])) {
                    $supplierData['market_location'] = trim($matches[1]);
                    $extractionLog[] = "Marktlokation gefunden mit Pattern $index: " . $matches[1];
                } else {
                    \Log::warning('PDF-Analyse: Marktlokation-Pattern ohne Capturing Group', [
                        'pattern_index' => $index,
                        'pattern' => $pattern,
                        'matches' => $matches
                    ]);
                }
                break;
            }
        }

        // Netzbetreiber
        $networkOperatorPatterns = [
            '/(?:Netzbetreiber|Verteilnetzbetreiber)[:\s]*([A-Za-zäöüÄÖÜß\s&\-\.]+(?:GmbH|AG|KG|OHG|eG|SE|UG|mbH)(?:\s+&\s+Co\.?\s+KG)?)/i',
            '/Netzbetreiber[:\s]*([^(]+)(?:\s*\([^)]+\))?/i'
        ];

        foreach ($networkOperatorPatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (isset($matches[1])) {
                    $supplierData['network_operator'] = trim($matches[1]);
                    $extractionLog[] = "Netzbetreiber gefunden mit Pattern $index: " . $matches[1];
                } else {
                    \Log::warning('PDF-Analyse: Netzbetreiber-Pattern ohne Capturing Group', [
                        'pattern_index' => $index,
                        'pattern' => $pattern,
                        'matches' => $matches
                    ]);
                }
                break;
            }
        }

        // Codenummer des Netzbetreibers
        if (preg_match('/(?:Codenummer|Code)[:\s]*(\d{13,})/i', $text, $matches)) {
            if (isset($matches[1])) {
                $supplierData['network_operator_code'] = trim($matches[1]);
                $extractionLog[] = "Netzbetreiber-Codenummer gefunden: " . $matches[1];
            }
        }

        // Messstellenbetreiber
        $meteringOperatorPatterns = [
            '/(?:Messstellenbetreiber|MSB)[:\s]*([A-Za-zäöüÄÖÜß\s&\-\.]+(?:GmbH|AG|KG|OHG|eG|SE|UG|mbH)(?:\s+&\s+Co\.?\s+KG)?)/i',
            '/Messstellenbetreiber[:\s]*([^,\n]+)/i'
        ];

        foreach ($meteringOperatorPatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (isset($matches[1])) {
                    $supplierData['metering_operator'] = trim($matches[1]);
                    $extractionLog[] = "Messstellenbetreiber gefunden mit Pattern $index: " . $matches[1];
                } else {
                    \Log::warning('PDF-Analyse: Messstellenbetreiber-Pattern ohne Capturing Group', [
                        'pattern_index' => $index,
                        'pattern' => $pattern,
                        'matches' => $matches
                    ]);
                }
                break;
            }
        }

        // Zählernummer
        $meterNumberPatterns = [
            '/(?:Zählernummer|Zähler-Nr\.?|Meter)[:\s]*([A-Z0-9\-]+)/i',
            '/(?:Nr\.?\s*des\s*Zählers)[:\s]*([A-Z0-9\-]+)/i'
        ];

        foreach ($meterNumberPatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (isset($matches[1])) {
                    $supplierData['meter_number'] = trim($matches[1]);
                    $extractionLog[] = "Zählernummer gefunden mit Pattern $index: " . $matches[1];
                } else {
                    \Log::warning('PDF-Analyse: Zählernummer-Pattern ohne Capturing Group', [
                        'pattern_index' => $index,
                        'pattern' => $pattern,
                        'matches' => $matches
                    ]);
                }
                break;
            }
        }

        // Log Extraction Results
        \Log::info('PDF-Analyse: Lieferantendaten-Extraktion', [
            'extracted_fields' => array_keys($supplierData),
            'extraction_log' => $extractionLog,
            'text_length' => strlen($text),
            'first_lines' => array_slice($cleanLines, 0, 5)
        ]);

        return $supplierData;
    }

    /**
     * Berechnet Matching-Scores für bekannte Lieferanten mit erweiterten Services
     */
    private function calculateMatchingScores(string $text): array
    {
        $scores = [];
        $detailedLog = [];
        
        try {
            // Verwende den SupplierRecognitionService für bessere Erkennung
            $supplierRecognitionService = app(\App\Services\SupplierRecognitionService::class);
            
            // Hole alle Lieferanten-Scores
            $supplierScores = $supplierRecognitionService->getAllSupplierScores($text, '', []);
            
            foreach ($supplierScores as $supplierScore) {
                $supplier = $supplierScore['supplier'];
                $confidence = $supplierScore['confidence'];
                $matchDetails = $supplierScore['match_details'];
                
                $scores[] = [
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->company_name ?? $supplier->display_name,
                    'score' => round($confidence * 100, 2),
                    'confidence' => $confidence,
                    'matches' => array_map(function($detail) {
                        return $detail['pattern_type'] . ': ' . $detail['pattern_value'] . ' (Score: ' . $detail['score'] . ')';
                    }, $matchDetails),
                    'match_details' => $matchDetails,
                    'recognition_method' => 'SupplierRecognitionService'
                ];
                
                $detailedLog[] = [
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->display_name,
                    'confidence' => $confidence,
                    'match_count' => count($matchDetails)
                ];
            }
            
            // Fallback: Verwende die alte Methode wenn keine Pattern-basierten Matches gefunden wurden
            if (empty($scores)) {
                $detailedLog[] = 'Fallback zu einfacher Lieferanten-Erkennung';
                $scores = $this->calculateSimpleMatchingScores($text);
            }
            
        } catch (\Exception $e) {
            \Log::error('Fehler bei erweiterte Lieferanten-Erkennung', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback zur einfachen Methode
            $detailedLog[] = 'Fehler bei SupplierRecognitionService, Fallback zu einfacher Methode';
            $scores = $this->calculateSimpleMatchingScores($text);
        }
        
        // Log Matching Results
        \Log::info('PDF-Analyse: Lieferanten-Matching', [
            'total_matches' => count($scores),
            'best_match_score' => $scores[0]['score'] ?? 0,
            'best_match_supplier' => $scores[0]['supplier_name'] ?? 'Keine',
            'detailed_log' => $detailedLog,
            'text_length' => strlen($text)
        ]);
        
        return $scores;
    }

    /**
     * Extrahiert das rohe ZUGFeRD XML aus einer PDF-Datei.
     */
    private function extractRawXml(UploadedPdf $uploadedPdf): ?string
    {
        try {
            $filePath = $uploadedPdf->getFullPath();
            if (Storage::exists($uploadedPdf->file_path)) {
                return ZugferdDocumentPdfReader::getXmlFromFile($filePath);
            }
            return null;
        } catch (\Exception $e) {
            \Log::error('Konnte ZUGFeRD XML nicht extrahieren', [
                'uploaded_pdf_id' => $uploadedPdf->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Einfache Lieferanten-Matching-Methode als Fallback
     */
    private function calculateSimpleMatchingScores(string $text): array
    {
        $scores = [];

        // Hole alle Lieferanten aus der Datenbank
        $suppliers = \App\Models\Supplier::all();
        
        foreach ($suppliers as $supplier) {
            $score = 0;
            $matches = [];
            
            // Erweiterte Firmenname-Prüfung
            if ($supplier->company_name) {
                $companyName = $supplier->company_name;
                if (stripos($text, $companyName) !== false) {
                    $score += 50;
                    $matches[] = 'Firmenname: ' . $companyName;
                }
                
                // Prüfe auch Teilstrings des Firmennamens
                $nameParts = explode(' ', $companyName);
                foreach ($nameParts as $part) {
                    if (strlen($part) > 3 && stripos($text, $part) !== false) {
                        $score += 10;
                        $matches[] = 'Firmenname-Teil: ' . $part;
                    }
                }
            }
            
            // Erweiterte E-Mail-Prüfung
            if ($supplier->email) {
                if (stripos($text, $supplier->email) !== false) {
                    $score += 30;
                    $matches[] = 'E-Mail: ' . $supplier->email;
                }
                
                // Prüfe auch E-Mail-Domain
                $domain = substr(strrchr($supplier->email, "@"), 1);
                if ($domain && stripos($text, $domain) !== false) {
                    $score += 15;
                    $matches[] = 'E-Mail-Domain: ' . $domain;
                }
            }
            
            // Erweiterte Telefon-Prüfung
            if ($supplier->phone) {
                $cleanPhone = preg_replace('/[^\d]/', '', $supplier->phone);
                $cleanText = preg_replace('/[^\d]/', '', $text);
                
                if (strpos($cleanText, $cleanPhone) !== false) {
                    $score += 20;
                    $matches[] = 'Telefon: ' . $supplier->phone;
                }
            }
            
            // USt-ID Prüfung
            if ($supplier->vat_id && stripos($text, $supplier->vat_id) !== false) {
                $score += 40;
                $matches[] = 'USt-ID: ' . $supplier->vat_id;
            }
            
            // IBAN Prüfung
            if ($supplier->iban && stripos($text, $supplier->iban) !== false) {
                $score += 35;
                $matches[] = 'IBAN: ' . $supplier->iban;
            }
            
            if ($score > 0) {
                $scores[] = [
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->company_name,
                    'score' => $score,
                    'matches' => $matches,
                    'recognition_method' => 'SimpleMatching'
                ];
            }
        }
        
        // Sortiere nach Score
        usort($scores, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return $scores;
    }
}
