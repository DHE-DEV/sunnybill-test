<?php

namespace App\Http\Controllers;

use App\Models\GmailEmail;
use App\Models\SupplierContract;
use App\Services\GmailService;
use App\Services\SupplierRecognitionService;
use App\Services\RuleBasedExtractionService;
use App\Services\ContractMatchingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Smalot\PdfParser\Parser;

class PdfAnalysisController extends Controller
{
    protected $supplierRecognitionService;
    protected $ruleBasedExtractionService;
    protected $contractMatchingService;

    public function __construct(
        SupplierRecognitionService $supplierRecognitionService,
        RuleBasedExtractionService $ruleBasedExtractionService,
        ContractMatchingService $contractMatchingService
    ) {
        $this->supplierRecognitionService = $supplierRecognitionService;
        $this->ruleBasedExtractionService = $ruleBasedExtractionService;
        $this->contractMatchingService = $contractMatchingService;
    }

    public function analyzePdf(Request $request, $emailUuid, $attachmentId)
    {
        try {
            // Gmail Email finden
            $email = GmailEmail::where('uuid', $emailUuid)->firstOrFail();
            
            // Gmail Service initialisieren
            $gmailService = new GmailService();
            
            // PDF-Anhang herunterladen
            // Verwende die neue Retry-Methode für Rate-Limit-Behandlung
            $attachmentData = $gmailService->downloadAttachmentWithRetry($email->gmail_id, $attachmentId, 3);
            
            if (!$attachmentData) {
                return response()->json([
                    'error' => 'PDF-Anhang konnte nicht geladen werden.'
                ], 404);
            }
            
            // PDF-Daten sind bereits dekodiert
            $pdfContent = $attachmentData;
            
            // PDF Parser initialisieren
            $parser = new Parser();
            $pdf = $parser->parseContent($pdfContent);
            
            // Grundlegende PDF-Informationen extrahieren
            $analysis = [
                'basic_info' => [
                    'title' => $pdf->getDetails()['Title'] ?? 'Nicht verfügbar',
                    'author' => $pdf->getDetails()['Author'] ?? 'Nicht verfügbar',
                    'subject' => $pdf->getDetails()['Subject'] ?? 'Nicht verfügbar',
                    'creator' => $pdf->getDetails()['Creator'] ?? 'Nicht verfügbar',
                    'producer' => $pdf->getDetails()['Producer'] ?? 'Nicht verfügbar',
                    'creation_date' => $pdf->getDetails()['CreationDate'] ?? 'Nicht verfügbar',
                    'modification_date' => $pdf->getDetails()['ModDate'] ?? 'Nicht verfügbar',
                    'page_count' => count($pdf->getPages()),
                ],
                'metadata' => $pdf->getDetails(),
                'text_content' => '',
                'pages_info' => [],
                'xml_data' => null,
                'file_info' => [
                    'size' => strlen($pdfContent),
                    'size_formatted' => $this->formatBytes(strlen($pdfContent)),
                    'mime_type' => 'application/pdf',
                ]
            ];
            
            // Text aus allen Seiten extrahieren
            $allText = '';
            $pages = $pdf->getPages();
            
            foreach ($pages as $pageNumber => $page) {
                $pageText = $page->getText();
                $allText .= $pageText . "\n\n";
                
                $analysis['pages_info'][] = [
                    'page_number' => $pageNumber + 1,
                    'text_length' => strlen($pageText),
                    'text_preview' => substr($pageText, 0, 200) . (strlen($pageText) > 200 ? '...' : ''),
                ];
            }
            
            $analysis['text_content'] = $allText;
            
            // Versuche XML-Daten zu extrahieren (falls vorhanden)
            try {
                // Suche nach XML-ähnlichen Strukturen im Text
                if (preg_match_all('/<[^>]+>/', $allText, $matches)) {
                    $analysis['xml_data'] = [
                        'found_tags' => array_unique($matches[0]),
                        'tag_count' => count($matches[0]),
                        'contains_xml' => true,
                    ];
                    
                    // Versuche strukturiertes XML zu finden
                    if (preg_match('/<\?xml.*?\?>/s', $allText, $xmlDeclaration)) {
                        $analysis['xml_data']['xml_declaration'] = $xmlDeclaration[0];
                    }
                } else {
                    $analysis['xml_data'] = [
                        'contains_xml' => false,
                        'message' => 'Keine XML-Strukturen gefunden',
                    ];
                }
            } catch (\Exception $e) {
                $analysis['xml_data'] = [
                    'error' => 'Fehler beim Analysieren von XML-Daten: ' . $e->getMessage(),
                ];
            }
            
            // Zusätzliche Analyse: Suche nach strukturierten Daten
            $analysis['structured_data'] = $this->extractStructuredData($allText);
            
            // ZuGFeRD-Daten analysieren
            $analysis['zugferd_data'] = $this->analyzeZugferdData($pdf, $allText);
            
            // Banking-Informationen extrahieren
            $analysis['banking_data'] = $this->extractBankingData($allText);
            
            // E-Mail-Text-Analyse
            $analysis['email_text_analysis'] = $this->analyzeEmailText($allText);
            
            return response()->json([
                'success' => true,
                'analysis' => $analysis,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Fehler bei der PDF-Analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analysiert PDF mit dem neuen variablen PDF-Analyse-System
     */
    public function analyzeWithVariableSystem(Request $request, $emailUuid, $attachmentId)
    {
        try {
            // Gmail Email finden
            $email = GmailEmail::where('uuid', $emailUuid)->firstOrFail();
            
            // Gmail Service initialisieren
            $gmailService = new GmailService();
            
            // PDF-Anhang herunterladen
            // Verwende die neue Retry-Methode für Rate-Limit-Behandlung
            $attachmentData = $gmailService->downloadAttachmentWithRetry($email->gmail_id, $attachmentId, 3);
            
            if (!$attachmentData) {
                return response()->json([
                    'error' => 'PDF-Anhang konnte nicht geladen werden.'
                ], 404);
            }
            
            // PDF-Daten sind bereits dekodiert
            $pdfContent = $attachmentData;
            
            // PDF Parser initialisieren
            $parser = new Parser();
            $pdf = $parser->parseContent($pdfContent);
            
            // Text aus allen Seiten extrahieren
            $allText = '';
            $pages = $pdf->getPages();
            
            foreach ($pages as $pageNumber => $page) {
                $pageText = $page->getText();
                $allText .= $pageText . "\n\n";
            }

            // Variables PDF-Analyse-System anwenden
            $variableAnalysis = $this->performVariableAnalysis($allText, $email);
            
            // Grundlegende PDF-Informationen (wie bisher)
            $basicAnalysis = [
                'basic_info' => [
                    'title' => $pdf->getDetails()['Title'] ?? 'Nicht verfügbar',
                    'author' => $pdf->getDetails()['Author'] ?? 'Nicht verfügbar',
                    'subject' => $pdf->getDetails()['Subject'] ?? 'Nicht verfügbar',
                    'creator' => $pdf->getDetails()['Creator'] ?? 'Nicht verfügbar',
                    'producer' => $pdf->getDetails()['Producer'] ?? 'Nicht verfügbar',
                    'creation_date' => $pdf->getDetails()['CreationDate'] ?? 'Nicht verfügbar',
                    'modification_date' => $pdf->getDetails()['ModDate'] ?? 'Nicht verfügbar',
                    'page_count' => count($pdf->getPages()),
                ],
                'metadata' => $pdf->getDetails(),
                'text_content' => $allText,
                'file_info' => [
                    'size' => strlen($pdfContent),
                    'size_formatted' => $this->formatBytes(strlen($pdfContent)),
                    'mime_type' => 'application/pdf',
                ]
            ];

            return response()->json([
                'success' => true,
                'analysis_type' => 'variable_system',
                'basic_analysis' => $basicAnalysis,
                'variable_analysis' => $variableAnalysis,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Fehler bei der variablen PDF-Analyse: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Zeigt die PDF-Analyse in einer benutzerfreundlichen HTML-Seite an
     */
    public function showAnalysis($emailUuid, $attachmentId)
    {
        try {
            // Gmail Email finden
            $email = GmailEmail::where('uuid', $emailUuid)->firstOrFail();
            
            if (!$email->has_attachments) {
                return $this->showError('Diese E-Mail hat keine Anhänge.');
            }

            $pdfAttachments = $email->getPdfAttachments();
            $targetAttachment = null;
            
            foreach ($pdfAttachments as $attachment) {
                if (($attachment['id'] ?? $attachment['attachmentId'] ?? null) === $attachmentId) {
                    $targetAttachment = $attachment;
                    break;
                }
            }

            if (!$targetAttachment) {
                return $this->showError('PDF-Anhang nicht gefunden.');
            }

            \Log::info('PDF-Analyse: Starte Anhang-Laden', [
                'email_id' => $email->id,
                'email_uuid' => $email->uuid,
                'gmail_id' => $email->gmail_id,
                'attachment_id' => $attachmentId,
                'email_subject' => $email->subject,
                'has_attachments' => $email->has_attachments
            ]);
            
            // Versuche zuerst die lokale Datei zu laden
            $attachmentData = $this->getAttachmentData($email, $targetAttachment, $attachmentId);
            
            if (!$attachmentData) {
                \Log::error('PDF-Analyse: Anhang-Daten konnten nicht geladen werden', [
                    'email_id' => $email->id,
                    'email_uuid' => $email->uuid,
                    'gmail_id' => $email->gmail_id,
                    'attachment_id' => $attachmentId,
                    'error' => 'Weder lokale Datei noch Gmail API-Download erfolgreich',
                    'email_subject' => $email->subject
                ]);
                return $this->showError('Anhang-Daten konnten nicht geladen werden.');
            }
            
            \Log::info('PDF-Analyse: Anhang-Daten erfolgreich geladen', [
                'email_id' => $email->id,
                'attachment_id' => $attachmentId,
                'data_size' => strlen($attachmentData),
                'data_type' => gettype($attachmentData)
            ]);

            // PDF-Daten sind bereits dekodiert
            $pdfContent = $attachmentData;
            
            \Log::info('PDF-Analyse: Starte PDF-Parsing', [
                'email_id' => $email->id,
                'attachment_id' => $attachmentId,
                'pdf_content_size' => strlen($pdfContent),
                'pdf_content_preview' => substr($pdfContent, 0, 100),
                'memory_usage_before' => memory_get_usage(true)
            ]);
            
            // PDF Parser initialisieren
            try {
                $parser = new Parser();
                $pdf = $parser->parseContent($pdfContent);
                
                \Log::info('PDF-Analyse: PDF-Parsing erfolgreich', [
                    'email_id' => $email->id,
                    'attachment_id' => $attachmentId,
                    'page_count' => count($pdf->getPages()),
                    'memory_usage_after' => memory_get_usage(true),
                    'pdf_details_count' => count($pdf->getDetails())
                ]);
                
            } catch (\Exception $e) {
                \Log::error('PDF-Analyse: PDF-Parsing fehlgeschlagen', [
                    'email_id' => $email->id,
                    'attachment_id' => $attachmentId,
                    'error_message' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'error_line' => $e->getLine(),
                    'error_file' => $e->getFile(),
                    'pdf_content_size' => strlen($pdfContent),
                    'memory_usage' => memory_get_usage(true)
                ]);
                throw $e; // Re-throw für normale Fehlerbehandlung
            }
            
            // Analyse durchführen (gleiche Logik wie analyzePdf)
            $analysis = [
                'filename' => $targetAttachment['filename'] ?? 'Unbekannte Datei',
                'basic_info' => [
                    'title' => $pdf->getDetails()['Title'] ?? 'Nicht verfügbar',
                    'author' => $pdf->getDetails()['Author'] ?? 'Nicht verfügbar',
                    'subject' => $pdf->getDetails()['Subject'] ?? 'Nicht verfügbar',
                    'creator' => $pdf->getDetails()['Creator'] ?? 'Nicht verfügbar',
                    'producer' => $pdf->getDetails()['Producer'] ?? 'Nicht verfügbar',
                    'creation_date' => $pdf->getDetails()['CreationDate'] ?? 'Nicht verfügbar',
                    'modification_date' => $pdf->getDetails()['ModDate'] ?? 'Nicht verfügbar',
                    'page_count' => count($pdf->getPages()),
                ],
                'metadata' => $pdf->getDetails(),
                'text_content' => '',
                'pages_info' => [],
                'xml_data' => null,
                'file_info' => [
                    'size' => strlen($pdfContent),
                    'size_formatted' => $this->formatBytes(strlen($pdfContent)),
                    'mime_type' => 'application/pdf',
                ]
            ];
            
            // Text aus allen Seiten extrahieren
            $allText = '';
            $pages = $pdf->getPages();
            
            foreach ($pages as $pageNumber => $page) {
                $pageText = $page->getText();
                $allText .= $pageText . "\n\n";
                
                $analysis['pages_info'][] = [
                    'page_number' => $pageNumber + 1,
                    'text_length' => strlen($pageText),
                    'text_preview' => substr($pageText, 0, 200) . (strlen($pageText) > 200 ? '...' : ''),
                ];
            }
            
            $analysis['text_content'] = $allText;
            
            // XML-Daten analysieren
            try {
                if (preg_match_all('/<[^>]+>/', $allText, $matches)) {
                    $analysis['xml_data'] = [
                        'found_tags' => array_unique($matches[0]),
                        'tag_count' => count($matches[0]),
                        'contains_xml' => true,
                    ];
                    
                    if (preg_match('/<\?xml.*?\?>/s', $allText, $xmlDeclaration)) {
                        $analysis['xml_data']['xml_declaration'] = $xmlDeclaration[0];
                    }
                } else {
                    $analysis['xml_data'] = [
                        'contains_xml' => false,
                        'message' => 'Keine XML-Strukturen gefunden',
                    ];
                }
            } catch (\Exception $e) {
                $analysis['xml_data'] = [
                    'error' => 'Fehler beim Analysieren von XML-Daten: ' . $e->getMessage(),
                ];
            }
            
            // Strukturierte Daten extrahieren
            $analysis['structured_data'] = $this->extractStructuredData($allText);
            
            // ZuGFeRD-Daten analysieren
            $analysis['zugferd_data'] = $this->analyzeZugferdData($pdf, $allText);
            
            // Banking-Informationen extrahieren
            $analysis['banking_data'] = $this->extractBankingData($allText);
            
            // E-Mail-Text-Analyse
            $analysis['email_text_analysis'] = $this->analyzeEmailText($allText);
            
            // E-Mail-Weiterleitung-Analyse
            $analysis['forwarding_analysis'] = $this->analyzeEmailForwarding($email, $allText);
            
            // Vertragserkennung (alte Methode)
            $analysis['contract_recognition'] = $this->recognizeContracts($allText, $email);

            // Neue variable PDF-Analyse hinzufügen
            $analysis['variable_analysis'] = $this->performVariableAnalysis($allText, $email);

            return $this->showAnalysisPage($analysis, $email);

        } catch (\Exception $e) {
            return $this->showError('Fehler bei der PDF-Analyse: ' . $e->getMessage());
        }
    }

    private function showError($message)
    {
        return response()->view('pdf-analysis.error', ['error' => $message], 500);
    }

    private function showAnalysisPage($analysis, $email)
    {
        return response()->view('pdf-analysis.show', [
            'analysis' => $analysis,
            'email' => $email
        ]);
    }
    
    private function extractStructuredData($text)
    {
        $structuredData = [
            'emails' => [],
            'phone_numbers' => [],
            'dates' => [],
            'urls' => [],
            'numbers' => [],
            'addresses' => [],
        ];
        
        // E-Mail-Adressen finden
        if (preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            $structuredData['emails'] = array_unique($matches[0]);
        }
        
        // Telefonnummern finden
        if (preg_match_all('/(?:\+49|0)[0-9\s\-\/\(\)]{8,}/', $text, $matches)) {
            $structuredData['phone_numbers'] = array_unique($matches[0]);
        }
        
        // Datumsangaben finden
        if (preg_match_all('/\d{1,2}[\.\/\-]\d{1,2}[\.\/\-]\d{2,4}/', $text, $matches)) {
            $structuredData['dates'] = array_unique($matches[0]);
        }
        
        // URLs finden
        if (preg_match_all('/https?:\/\/[^\s]+/', $text, $matches)) {
            $structuredData['urls'] = array_unique($matches[0]);
        }
        
        // Geldbeträge finden
        if (preg_match_all('/\d+[,\.]\d{2}\s*€|\d+\s*EUR|€\s*\d+[,\.]\d{2}/', $text, $matches)) {
            $structuredData['numbers'] = array_unique($matches[0]);
        }
        
        return $structuredData;
    }
    
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Analysiert ZuGFeRD-Daten in der PDF
     */
    private function analyzeZugferdData($pdf, $text)
    {
        $zugferdData = [
            'is_zugferd' => false,
            'version' => null,
            'profile' => null,
            'invoice_data' => [],
            'xml_content' => null,
            'attachments' => [],
            'validation_errors' => [],
        ];
        
        try {
            // Prüfe auf ZuGFeRD-Indikatoren im Text
            $zugferdIndicators = [
                'ZUGFeRD',
                'zugferd',
                'Factur-X',
                'factur-x',
                'urn:cen.eu:en16931',
                'urn:ferd:CrossIndustryInvoice',
                'CrossIndustryInvoice',
                'ExchangedDocument',
                'SupplyChainTradeTransaction'
            ];
            
            $foundIndicators = [];
            foreach ($zugferdIndicators as $indicator) {
                if (stripos($text, $indicator) !== false) {
                    $foundIndicators[] = $indicator;
                    $zugferdData['is_zugferd'] = true;
                }
            }
            
            if (!$zugferdData['is_zugferd']) {
                return $zugferdData;
            }
            
            $zugferdData['found_indicators'] = $foundIndicators;
            
            // Versuche ZuGFeRD-Version zu ermitteln
            if (preg_match('/ZUGFeRD[_\s]*([0-9\.]+)/i', $text, $matches)) {
                $zugferdData['version'] = $matches[1];
            } elseif (stripos($text, 'Factur-X') !== false) {
                $zugferdData['version'] = 'Factur-X (ZuGFeRD 2.x)';
            }
            
            // Versuche Profil zu ermitteln
            $profiles = [
                'MINIMUM' => 'Minimum',
                'BASIC' => 'Basic',
                'COMFORT' => 'Comfort',
                'EXTENDED' => 'Extended',
                'EN16931' => 'EN 16931 (Core)',
                'XRECHNUNG' => 'XRechnung'
            ];
            
            foreach ($profiles as $key => $profile) {
                if (stripos($text, $key) !== false || stripos($text, $profile) !== false) {
                    $zugferdData['profile'] = $profile;
                    break;
                }
            }
            
            // Extrahiere Rechnungsdaten aus dem Text
            $zugferdData['invoice_data'] = $this->extractInvoiceData($text);
            
            // Suche nach eingebetteten XML-Daten
            $zugferdData['xml_content'] = $this->extractZugferdXml($text);
            
            // Prüfe auf PDF-Anhänge (ZuGFeRD nutzt oft eingebettete Dateien)
            $zugferdData['attachments'] = $this->findPdfAttachments($pdf);
            
            // Validierung
            $zugferdData['validation_errors'] = $this->validateZugferdData($zugferdData);
            
        } catch (\Exception $e) {
            $zugferdData['error'] = 'Fehler bei der ZuGFeRD-Analyse: ' . $e->getMessage();
        }
        
        return $zugferdData;
    }
    
    /**
     * Extrahiert Rechnungsdaten aus dem Text
     */
    private function extractInvoiceData($text)
    {
        $invoiceData = [];
        
        // Rechnungsnummer (erweitert um Leerzeichen)
        if (preg_match('/(?:Rechnung(?:s)?(?:nummer)?|Invoice(?:\s+Number)?|Rg\.?\s*Nr\.?)[:\s]*([A-Z0-9\s\-\/]+)/i', $text, $matches)) {
            $invoiceData['invoice_number'] = preg_replace('/\s+/', '', trim($matches[1]));
        }
        
        // Rechnungsdatum
        if (preg_match('/(?:Rechnung(?:s)?datum|Invoice\s+Date|Datum)[:\s]*(\d{1,2}[\.\/\-]\d{1,2}[\.\/\-]\d{2,4})/i', $text, $matches)) {
            $invoiceData['invoice_date'] = trim($matches[1]);
        }
        
        // Fälligkeitsdatum
        if (preg_match('/(?:Fälligkeitsdatum|Due\s+Date|Zahlbar\s+bis)[:\s]*(\d{1,2}[\.\/\-]\d{1,2}[\.\/\-]\d{2,4})/i', $text, $matches)) {
            $invoiceData['due_date'] = trim($matches[1]);
        }
        
        // Gesamtbetrag
        if (preg_match('/(?:Gesamtbetrag|Total|Summe|Endbetrag)[:\s]*([0-9.,]+)\s*€?/i', $text, $matches)) {
            $invoiceData['total_amount'] = trim($matches[1]);
        }
        
        // Nettobetrag
        if (preg_match('/(?:Nettobetrag|Netto|Net\s+Amount)[:\s]*([0-9.,]+)\s*€?/i', $text, $matches)) {
            $invoiceData['net_amount'] = trim($matches[1]);
        }
        
        // Mehrwertsteuer
        if (preg_match('/(?:MwSt\.?|USt\.?|VAT|Mehrwertsteuer)[:\s]*([0-9.,]+)\s*€?/i', $text, $matches)) {
            $invoiceData['vat_amount'] = trim($matches[1]);
        }
        
        // Steuersatz
        if (preg_match('/(\d{1,2}(?:[,\.]\d{1,2})?)\s*%\s*(?:MwSt\.?|USt\.?|VAT)/i', $text, $matches)) {
            $invoiceData['vat_rate'] = trim($matches[1]) . '%';
        }
        
        // Lieferant/Verkäufer
        if (preg_match('/(?:Verkäufer|Lieferant|Seller|Supplier)[:\s]*([^\n\r]{1,100})/i', $text, $matches)) {
            $invoiceData['seller'] = trim($matches[1]);
        }
        
        // Käufer
        if (preg_match('/(?:Käufer|Kunde|Buyer|Customer)[:\s]*([^\n\r]{1,100})/i', $text, $matches)) {
            $invoiceData['buyer'] = trim($matches[1]);
        }
        
        return $invoiceData;
    }
    
    /**
     * Extrahiert ZuGFeRD XML-Daten
     */
    private function extractZugferdXml($text)
    {
        $xmlData = [];
        
        // Suche nach XML-Strukturen
        if (preg_match('/<\?xml[^>]*\?>(.*?)<\/[^>]+>/s', $text, $matches)) {
            $xmlData['raw_xml'] = $matches[0];
            $xmlData['has_xml_declaration'] = true;
        }
        
        // Suche nach CrossIndustryInvoice (ZuGFeRD Hauptelement)
        if (preg_match('/<CrossIndustryInvoice[^>]*>(.*?)<\/CrossIndustryInvoice>/s', $text, $matches)) {
            $xmlData['cross_industry_invoice'] = $matches[0];
        }
        
        // Suche nach ExchangedDocument
        if (preg_match('/<ExchangedDocument[^>]*>(.*?)<\/ExchangedDocument>/s', $text, $matches)) {
            $xmlData['exchanged_document'] = $matches[0];
        }
        
        return $xmlData;
    }
    
    /**
     * Findet PDF-Anhänge (für eingebettete ZuGFeRD XML-Dateien)
     */
    private function findPdfAttachments($pdf)
    {
        $attachments = [];
        
        try {
            // Versuche PDF-Anhänge zu finden (dies ist komplex und hängt von der PDF-Struktur ab)
            $details = $pdf->getDetails();
            
            if (isset($details['EmbeddedFiles'])) {
                $attachments['embedded_files'] = $details['EmbeddedFiles'];
            }
            
            // Suche nach Hinweisen auf eingebettete Dateien in den Metadaten
            foreach ($details as $key => $value) {
                if (stripos($key, 'attachment') !== false || stripos($key, 'embedded') !== false) {
                    $attachments[$key] = $value;
                }
            }
            
        } catch (\Exception $e) {
            $attachments['error'] = 'Fehler beim Suchen nach Anhängen: ' . $e->getMessage();
        }
        
        return $attachments;
    }
    
    /**
     * Validiert ZuGFeRD-Daten
     */
    private function validateZugferdData($zugferdData)
    {
        $errors = [];
        
        if ($zugferdData['is_zugferd']) {
            // Prüfe ob Pflichtfelder vorhanden sind
            if (empty($zugferdData['invoice_data']['invoice_number'])) {
                $errors[] = 'Rechnungsnummer nicht gefunden';
            }
            
            if (empty($zugferdData['invoice_data']['invoice_date'])) {
                $errors[] = 'Rechnungsdatum nicht gefunden';
            }
            
            if (empty($zugferdData['invoice_data']['total_amount'])) {
                $errors[] = 'Gesamtbetrag nicht gefunden';
            }
            
            if (empty($zugferdData['xml_content'])) {
                $errors[] = 'Keine XML-Daten gefunden (möglicherweise nur visueller ZuGFeRD)';
            }
            
            if (empty($zugferdData['version'])) {
                $errors[] = 'ZuGFeRD-Version konnte nicht ermittelt werden';
            }
        }
        
        return $errors;
    }
    
    /**
     * Extrahiert Banking-Informationen aus dem Text
     */
    private function extractBankingData($text)
    {
        $bankingData = [
            'has_banking_info' => false,
            'total_amount' => null,
            'debit_date' => null,
            'sepa_mandate' => null,
            'iban' => null,
            'bank_name' => null,
            'bic' => null,
            'creditor_id' => null,
            'reference' => null,
        ];
        
        try {
            // IBAN suchen (deutsche und internationale Formate)
            if (preg_match('/IBAN[:\s]*([A-Z]{2}[0-9]{2}[A-Z0-9\s]{15,32})/i', $text, $matches)) {
                $bankingData['iban'] = preg_replace('/\s+/', '', trim($matches[1]));
                $bankingData['has_banking_info'] = true;
            }
            
            // BIC suchen
            if (preg_match('/BIC[:\s]*([A-Z]{4}[A-Z]{2}[A-Z0-9]{2}(?:[A-Z0-9]{3})?)/i', $text, $matches)) {
                $bankingData['bic'] = trim($matches[1]);
                $bankingData['has_banking_info'] = true;
            }
            
            // SEPA-Mandat suchen
            if (preg_match('/(?:SEPA[_\-\s]*)?(?:Mandat|Mandate)[_\-\s]*(?:Nr\.?|Number)[:\s]*([A-Z0-9\-_\/]+)/i', $text, $matches)) {
                $bankingData['sepa_mandate'] = trim($matches[1]);
                $bankingData['has_banking_info'] = true;
            } elseif (preg_match('/Mandatsreferenz[:\s]*([A-Z0-9\-_\/]+)/i', $text, $matches)) {
                $bankingData['sepa_mandate'] = trim($matches[1]);
                $bankingData['has_banking_info'] = true;
            }
            
            // Gläubiger-ID suchen
            if (preg_match('/(?:Gläubiger[_\-\s]*ID|Creditor[_\-\s]*ID)[:\s]*([A-Z]{2}[0-9]{2}[A-Z0-9]{3}[0-9]{11})/i', $text, $matches)) {
                $bankingData['creditor_id'] = trim($matches[1]);
                $bankingData['has_banking_info'] = true;
            }
            
            // Gesamtbetrag suchen (verschiedene Formate)
            $amountPatterns = [
                '/(?:Gesamtbetrag|Gesamtsumme|Total|Summe|Betrag)[:\s]*([0-9.,]+)\s*€?/i',
                '/€\s*([0-9.,]+)/',
                '/([0-9.,]+)\s*EUR/i',
                '/Abbuchung[:\s]*([0-9.,]+)\s*€?/i',
                '/zu\s+zahlender?\s+Betrag[:\s]*([0-9.,]+)\s*€?/i'
            ];
            
            foreach ($amountPatterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $bankingData['total_amount'] = trim($matches[1]);
                    $bankingData['has_banking_info'] = true;
                    break;
                }
            }
            
            // Abbuchungsdatum suchen
            $datePatterns = [
                '/(?:Abbuchung(?:s)?datum|Fälligkeitsdatum|Einzugsdatum)[:\s]*(\d{1,2}[\.\/\-]\d{1,2}[\.\/\-]\d{2,4})/i',
                '/(?:Valuta|Wertstellung)[:\s]*(\d{1,2}[\.\/\-]\d{1,2}[\.\/\-]\d{2,4})/i',
                '/(?:Datum\s+der\s+)?Abbuchung[:\s]*(\d{1,2}[\.\/\-]\d{1,2}[\.\/\-]\d{2,4})/i',
                '/(?:Fällig\s+am|Due\s+Date)[:\s]*(\d{1,2}[\.\/\-]\d{1,2}[\.\/\-]\d{2,4})/i'
            ];
            
            foreach ($datePatterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $bankingData['debit_date'] = trim($matches[1]);
                    $bankingData['has_banking_info'] = true;
                    break;
                }
            }
            
            // Bankname suchen (basierend auf IBAN oder explizit genannt)
            if ($bankingData['iban']) {
                $bankingData['bank_name'] = $this->getBankNameFromIban($bankingData['iban']);
            }
            
            // Explizite Bankname-Suche
            $bankPatterns = [
                '/Bank[:\s]*([A-Za-zäöüÄÖÜß\s]+(?:Bank|Sparkasse|Genossenschaftsbank|Volksbank|Raiffeisenbank))/i',
                '/(?:Sparkasse|Volksbank|Raiffeisenbank|Commerzbank|Deutsche Bank|Postbank|DKB|ING|Santander)[A-Za-zäöüÄÖÜß\s]*/i'
            ];
            
            foreach ($bankPatterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    if (!$bankingData['bank_name'] || strlen($matches[0]) > strlen($bankingData['bank_name'])) {
                        $bankingData['bank_name'] = trim($matches[0]);
                        $bankingData['has_banking_info'] = true;
                    }
                }
            }
            
            // Verwendungszweck/Referenz suchen
            if (preg_match('/(?:Verwendungszweck|Referenz|Reference)[:\s]*([^\n\r]{1,100})/i', $text, $matches)) {
                $bankingData['reference'] = trim($matches[1]);
                $bankingData['has_banking_info'] = true;
            }
            
        } catch (\Exception $e) {
            $bankingData['error'] = 'Fehler bei der Banking-Daten-Extraktion: ' . $e->getMessage();
        }
        
        return $bankingData;
    }
    
    /**
     * Ermittelt Bankname basierend auf IBAN
     */
    private function getBankNameFromIban($iban)
    {
        // Entferne Leerzeichen und konvertiere zu Großbuchstaben
        $iban = strtoupper(preg_replace('/\s+/', '', $iban));
        
        // Deutsche IBAN-Bankleitzahlen-Mapping (Auswahl der größten Banken)
        $bankMapping = [
            '10010010' => 'Postbank',
            '10020030' => 'Berliner Volksbank',
            '10050000' => 'Landesbank Berlin',
            '12030000' => 'DKB Deutsche Kreditbank',
            '20010020' => 'Postbank Hamburg',
            '20041133' => 'Commerzbank Hamburg',
            '20070000' => 'Deutsche Bank Hamburg',
            '25010030' => 'Postbank Hannover',
            '26580070' => 'UniCredit Bank',
            '30010010' => 'Postbank Köln',
            '37040044' => 'Commerzbank Köln',
            '37070060' => 'Deutsche Bank Köln',
            '40010046' => 'Postbank Dortmund',
            '44040037' => 'Commerzbank Dortmund',
            '44070050' => 'Deutsche Bank Dortmund',
            '50010060' => 'Postbank Frankfurt',
            '50040000' => 'Commerzbank Frankfurt',
            '50070010' => 'Deutsche Bank Frankfurt',
            '50120383' => 'DKB Deutsche Kreditbank',
            '60010070' => 'Postbank Stuttgart',
            '60040071' => 'Commerzbank Stuttgart',
            '60070070' => 'Deutsche Bank Stuttgart',
            '70010080' => 'Postbank München',
            '70040041' => 'Commerzbank München',
            '70070010' => 'Deutsche Bank München',
            '50010517' => 'ING-DiBa',
        ];
        
        // Extrahiere Bankleitzahl aus deutscher IBAN (Position 5-12)
        if (substr($iban, 0, 2) === 'DE' && strlen($iban) === 22) {
            $blz = substr($iban, 4, 8);
            
            if (isset($bankMapping[$blz])) {
                return $bankMapping[$blz];
            }
            
            // Fallback: Versuche anhand der ersten Ziffern zu identifizieren
            $firstDigits = substr($blz, 0, 3);
            switch ($firstDigits) {
                case '100':
                    return 'Berliner Bank';
                case '200':
                    return 'Hamburger Bank';
                case '250':
                    return 'Hannoversche Bank';
                case '300':
                    return 'Kölner Bank';
                case '400':
                    return 'Dortmunder Bank';
                case '500':
                    return 'Frankfurter Bank';
                case '600':
                    return 'Stuttgarter Bank';
                case '700':
                    return 'Münchener Bank';
                default:
                    return 'Deutsche Bank (BLZ: ' . $blz . ')';
            }
        }
        
        return null;
    }
    
    /**
     * Analysiert E-Mail-Texte im PDF-Inhalt und extrahiert strukturierte Informationen
     */
    private function analyzeEmailText($text)
    {
        $emailAnalysis = [
            'has_email_content' => false,
            'invoice_info' => [],
            'customer_info' => [],
            'supplier_info' => [],
            'payment_info' => [],
            'contact_info' => [],
            'amounts' => [],
            'dates' => [],
            'references' => [],
            'services' => [],
        ];
        
        try {
            // Prüfe auf E-Mail-typische Indikatoren
            $emailIndicators = [
                'Sehr geehrte',
                'Liebe',
                'Hallo',
                'Guten Tag',
                'Freundliche Grüße',
                'Mit freundlichen Grüßen',
                'Vielen Dank',
                'Ihre Rechnung',
                'Ihre Monatsrechnung',
                'haben wir für Sie erbracht',
                'Serviceportal',
                'Geschäftskundenbetreuung'
            ];
            
            $foundIndicators = [];
            foreach ($emailIndicators as $indicator) {
                if (stripos($text, $indicator) !== false) {
                    $foundIndicators[] = $indicator;
                    $emailAnalysis['has_email_content'] = true;
                }
            }
            
            if (!$emailAnalysis['has_email_content']) {
                return $emailAnalysis;
            }
            
            $emailAnalysis['found_indicators'] = $foundIndicators;
            
            // Rechnungsinformationen extrahieren
            $emailAnalysis['invoice_info'] = $this->extractInvoiceInfoFromEmail($text);
            
            // Kundeninformationen extrahieren
            $emailAnalysis['customer_info'] = $this->extractCustomerInfo($text);
            
            // Lieferanten-/Unternehmensinformationen extrahieren
            $emailAnalysis['supplier_info'] = $this->extractSupplierInfo($text);
            
            // Zahlungsinformationen extrahieren
            $emailAnalysis['payment_info'] = $this->extractPaymentInfo($text);
            
            // Kontaktinformationen extrahieren
            $emailAnalysis['contact_info'] = $this->extractContactInfo($text);
            
            // Beträge extrahieren
            $emailAnalysis['amounts'] = $this->extractAmountsFromEmail($text);
            
            // Datumsangaben extrahieren
            $emailAnalysis['dates'] = $this->extractDatesFromEmail($text);
            
            // Referenznummern extrahieren
            $emailAnalysis['references'] = $this->extractReferences($text);
            
            // Dienstleistungen/Produkte extrahieren
            $emailAnalysis['services'] = $this->extractServices($text);
            
        } catch (\Exception $e) {
            $emailAnalysis['error'] = 'Fehler bei der E-Mail-Text-Analyse: ' . $e->getMessage();
        }
        
        return $emailAnalysis;
    }
    
    /**
     * Extrahiert Rechnungsinformationen aus E-Mail-Text
     */
    private function extractInvoiceInfoFromEmail($text)
    {
        $invoiceInfo = [];
        
        // Rechnungstyp
        if (preg_match('/(?:Ihre\s+)?(Monats|Jahres|Quartals|Wochen)?rechnung\s+für\s+([^\n\r]+)/i', $text, $matches)) {
            $invoiceInfo['type'] = trim($matches[1] . 'rechnung');
            $invoiceInfo['period'] = trim($matches[2]);
        }
        
        // Rechnungsnummer (erweitert um bessere Leerzeichen-Behandlung)
        if (preg_match('/(?:Rechnung(?:s)?(?:nummer)?|Rg\.?\s*Nr\.?)[:\s]*([A-Z0-9\s\-\/]+?)(?:\s*\n|\s*$|\s{2,})/i', $text, $matches)) {
            $invoiceInfo['invoice_number'] = preg_replace('/\s+/', '', trim($matches[1]));
        }
        
        // Vertragskonto
        if (preg_match('/(?:Vertragskonto|Kundennummer|Konto)[:\s]*([A-Z0-9\s\-\/]+)/i', $text, $matches)) {
            $invoiceInfo['contract_account'] = preg_replace('/\s+/', '', trim($matches[1]));
        }
        
        // Verbrauchsstelle
        if (preg_match('/Verbrauchsstelle[:\s]*([^\n\r]+)/i', $text, $matches)) {
            $invoiceInfo['consumption_point'] = trim($matches[1]);
        }
        
        return $invoiceInfo;
    }
    
    /**
     * Extrahiert Kundeninformationen aus E-Mail-Text
     */
    private function extractCustomerInfo($text)
    {
        $customerInfo = [];
        
        // Kundenname (nach "Kunde:" oder vor Adresse)
        if (preg_match('/(?:Kunde|Customer)[:\s]*([^\n\r]+)/i', $text, $matches)) {
            $customerInfo['name'] = trim($matches[1]);
        }
        
        // Adresse extrahieren (typisches deutsches Adressformat)
        if (preg_match('/([A-Za-zäöüÄÖÜß\s]+)\s+(\d+[a-zA-Z]?)\s*,?\s*(\d{5})\s+([A-Za-zäöüÄÖÜß\s]+)/i', $text, $matches)) {
            $customerInfo['address'] = [
                'street' => trim($matches[1]),
                'house_number' => trim($matches[2]),
                'postal_code' => trim($matches[3]),
                'city' => trim($matches[4]),
                'full_address' => trim($matches[0])
            ];
        }
        
        return $customerInfo;
    }
    
    /**
     * Extrahiert Lieferanten-/Unternehmensinformationen aus E-Mail-Text
     */
    private function extractSupplierInfo($text)
    {
        $supplierInfo = [];
        
        // Suche nach E-Mail-Signatur nach "Freundliche Grüße"
        $signatureText = '';
        $greetingPatterns = [
            '/(?:Freundliche\s+Grüße|Mit\s+freundlichen\s+Grüßen|Freundliche\s+Grüûe|Mit\s+freundlichen\s+Grüûen)(.*?)(?:\n\n|\r\n\r\n|$)/is',
            '/(?:Viele\s+Grüße|Beste\s+Grüße|Herzliche\s+Grüße)(.*?)(?:\n\n|\r\n\r\n|$)/is'
        ];
        
        foreach ($greetingPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $signatureText = trim($matches[1]);
                break;
            }
        }
        
        // Falls keine Grußformel gefunden, suche am Ende des Textes
        if (empty($signatureText)) {
            // Nimm die letzten 500 Zeichen als potentielle Signatur
            $signatureText = substr($text, -500);
        }
        
        // Erweiterte Unternehmensname-Suche in der Signatur
        $companyPatterns = [
            // Rechtsformen
            '/([A-Za-zäöüÄÖÜß\s&\-\.]+(?:GmbH|AG|KG|OHG|eG|SE|UG|mbH)(?:\s+&\s+Co\.?\s+KG)?)/i',
            // Energieunternehmen
            '/([A-Za-zäöüÄÖÜß\s\-\.]+(?:Energie|Strom|Gas|Wasser|Stadtwerke|Versorger|Netze|Netz)(?:\s+[A-Za-zäöüÄÖÜß\s\-\.]*)?)/i',
            // Banken und Finanzdienstleister
            '/([A-Za-zäöüÄÖÜß\s\-\.]+(?:Bank|Sparkasse|Volksbank|Raiffeisenbank|Genossenschaftsbank)(?:\s+[A-Za-zäöüÄÖÜß\s\-\.]*)?)/i',
            // Telekommunikation
            '/([A-Za-zäöüÄÖÜß\s\-\.]+(?:Telekom|Telefon|Mobilfunk|Internet|Kabel)(?:\s+[A-Za-zäöüÄÖÜß\s\-\.]*)?)/i',
            // Versicherungen
            '/([A-Za-zäöüÄÖÜß\s\-\.]+(?:Versicherung|Assekuranz|Krankenversicherung|Lebensversicherung)(?:\s+[A-Za-zäöüÄÖÜß\s\-\.]*)?)/i'
        ];
        
        // Suche zuerst in der Signatur, dann im gesamten Text
        $searchTexts = [$signatureText, $text];
        
        foreach ($searchTexts as $searchText) {
            foreach ($companyPatterns as $pattern) {
                if (preg_match_all($pattern, $searchText, $matches)) {
                    foreach ($matches[1] as $match) {
                        $company = trim($match);
                        // Mindestlänge und Qualitätsprüfung
                        if (strlen($company) > 5 && !preg_match('/^\d+$/', $company)) {
                            $supplierInfo['companies'][] = $company;
                        }
                    }
                }
            }
            
            // Wenn Unternehmen in der Signatur gefunden wurden, bevorzuge diese
            if (!empty($supplierInfo['companies']) && $searchText === $signatureText) {
                break;
            }
        }
        
        if (isset($supplierInfo['companies'])) {
            $supplierInfo['companies'] = array_unique($supplierInfo['companies']);
            // Sortiere nach Länge (längere Namen sind oft vollständiger)
            usort($supplierInfo['companies'], function($a, $b) {
                return strlen($b) - strlen($a);
            });
            $supplierInfo['primary_company'] = $supplierInfo['companies'][0] ?? null;
        }
        
        // Postfach/Adresse des Unternehmens (bevorzugt in der Signatur)
        foreach ($searchTexts as $searchText) {
            if (preg_match('/Postfach\s+(\d+[a-zA-Z]?)\s*,?\s*(\d{5})\s+([A-Za-zäöüÄÖÜß\s\-\.]+)/i', $searchText, $matches)) {
                $supplierInfo['postal_address'] = [
                    'postbox' => trim($matches[1]),
                    'postal_code' => trim($matches[2]),
                    'city' => trim($matches[3]),
                    'full_address' => trim($matches[0])
                ];
                break;
            }
        }
        
        // Geschäftsadresse (bevorzugt in der Signatur)
        foreach ($searchTexts as $searchText) {
            if (preg_match('/([A-Za-zäöüÄÖÜß\s\-\.]+(?:straße|str\.|platz|weg|gasse|allee))\s+(\d+[a-zA-Z]?)\s*,?\s*(\d{5})\s+([A-Za-zäöüÄÖÜß\s\-\.]+)/i', $searchText, $matches)) {
                $supplierInfo['business_address'] = [
                    'street' => trim($matches[1]),
                    'house_number' => trim($matches[2]),
                    'postal_code' => trim($matches[3]),
                    'city' => trim($matches[4]),
                    'full_address' => trim($matches[0])
                ];
                break;
            }
        }
        
        // Zusätzliche Signatur-Informationen extrahieren
        if (!empty($signatureText)) {
            // Geschäftsführer/Vorstand
            if (preg_match('/(?:Geschäftsführer|Vorstand|CEO|Managing Director)[:\s]*([A-Za-zäöüÄÖÜß\s\-\.]+)/i', $signatureText, $matches)) {
                $supplierInfo['management'] = trim($matches[1]);
            }
            
            // Handelsregister
            if (preg_match('/(?:Handelsregister|HRB|HRA)[:\s]*([A-Z0-9\s]+)/i', $signatureText, $matches)) {
                $supplierInfo['commercial_register'] = trim($matches[1]);
            }
            
            // USt-IdNr
            if (preg_match('/(?:USt[.\-]?IdNr\.?|Umsatzsteuer[.\-]?Identifikationsnummer)[:\s]*([A-Z]{2}[0-9]+)/i', $signatureText, $matches)) {
                $supplierInfo['vat_id'] = trim($matches[1]);
            }
            
            // Telefon in Signatur
            if (preg_match('/(?:Tel\.?|Telefon|Phone)[:\s]*([0-9\s\-\/\(\)\+]+)/i', $signatureText, $matches)) {
                $supplierInfo['phone'] = trim($matches[1]);
            }
            
            // E-Mail in Signatur
            if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $signatureText, $matches)) {
                $supplierInfo['email'] = trim($matches[1]);
            }
            
            // Website in Signatur
            if (preg_match('/((?:www\.)?[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?:\/[^\s]*)?)/i', $signatureText, $matches)) {
                $supplierInfo['website'] = trim($matches[1]);
            }
        }
        
        return $supplierInfo;
    }
    
    /**
     * Extrahiert Zahlungsinformationen aus E-Mail-Text
     */
    private function extractPaymentInfo($text)
    {
        $paymentInfo = [];
        
        // SEPA-Abbuchung
        if (preg_match('/(?:buchen\s+wir|Abbuchung|Einzug).*?(\d{1,2}\.\s*[A-Za-z]+\s+\d{4})/i', $text, $matches)) {
            $paymentInfo['debit_date'] = trim($matches[1]);
        }
        
        // SEPA-Mandat
        if (preg_match('/SEPA[_\-\s]*Mandat[_\-\s]*([A-Z0-9]+)/i', $text, $matches)) {
            $paymentInfo['sepa_mandate'] = trim($matches[1]);
        }
        
        // IBAN (mit Maskierung)
        if (preg_match('/IBAN\s+([A-Z]{2}\d{2}\s*[A-Z0-9X\s]+)/i', $text, $matches)) {
            $paymentInfo['iban'] = preg_replace('/\s+/', '', trim($matches[1]));
        }
        
        // Bank
        if (preg_match('/\(([A-Za-zäöüÄÖÜß\s]+(?:BANK|SPARKASSE|VOLKSBANK|RAIFFEISENBANK)[A-Za-zäöüÄÖÜß\s]*)\)/i', $text, $matches)) {
            $paymentInfo['bank'] = trim($matches[1]);
        }
        
        return $paymentInfo;
    }
    
    /**
     * Extrahiert Kontaktinformationen aus E-Mail-Text
     */
    private function extractContactInfo($text)
    {
        $contactInfo = [];
        
        // Telefonnummer
        if (preg_match('/(?:T|Tel\.?|Telefon)[:\s]*([0-9\s\-\/\(\)]+)/i', $text, $matches)) {
            $contactInfo['phone'] = trim($matches[1]);
        }
        
        // E-Mail-Adresse
        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $text, $matches)) {
            $contactInfo['email'] = trim($matches[1]);
        }
        
        // Serviceportal/Website
        if (preg_match('/((?:www\.)?[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?:\/[^\s]*)?)/i', $text, $matches)) {
            $contactInfo['website'] = trim($matches[1]);
        }
        
        // Abteilung/Service
        if (preg_match('/(?:Ihr\s+persönlicher\s+Service|Abteilung)[:\s]*([^\n\r]+)/i', $text, $matches)) {
            $contactInfo['department'] = trim($matches[1]);
        }
        
        return $contactInfo;
    }
    
    /**
     * Extrahiert Geldbeträge aus E-Mail-Text
     */
    private function extractAmountsFromEmail($text)
    {
        $amounts = [];
        
        // Netto-Betrag
        if (preg_match('/(?:netto|Netto)[:\s]*([0-9.,]+)\s*€?/i', $text, $matches)) {
            $amounts['net'] = trim($matches[1]);
        }
        
        // MwSt.-Betrag
        if (preg_match('/(?:MwSt\.?|Mehrwertsteuer)[:\s]*\((\d+%)\)\s*([0-9.,]+)\s*€?/i', $text, $matches)) {
            $amounts['vat_rate'] = trim($matches[1]);
            $amounts['vat_amount'] = trim($matches[2]);
        }
        
        // Gesamtbetrag (erweiterte Suche)
        $totalPatterns = [
            '/(?:Gesamtbetrag|Gesamtsumme|Gesamt)[:\s]*([0-9.,]+)\s*€?/i',
            '/(?:Total|Summe|Endbetrag)[:\s]*([0-9.,]+)\s*€?/i',
            '/(?:zu\s+zahlender?\s+Betrag|Rechnungsbetrag)[:\s]*([0-9.,]+)\s*€?/i',
            '/(?:brutto|Brutto)[:\s]*([0-9.,]+)\s*€?/i',
            '/(?:Betrag\s+insgesamt|Insgesamt)[:\s]*([0-9.,]+)\s*€?/i',
            '/(?:Fälliger\s+Betrag|Zahlbetrag)[:\s]*([0-9.,]+)\s*€?/i'
        ];
        
        foreach ($totalPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $amounts['total'] = trim($matches[1]);
                break; // Nimm den ersten gefundenen Gesamtbetrag
            }
        }
        
        // Produktspezifische Beträge
        if (preg_match('/(?:Strom|Gas|Wasser|Energie)[:\s]*([0-9.,]+)/i', $text, $matches)) {
            $amounts['service_amount'] = trim($matches[1]);
        }
        
        // Zusätzliche Betragssuche für verschiedene Kontexte
        if (preg_match('/([0-9.,]+)\s*€\s*(?:werden|wird|buchen|abgebucht)/i', $text, $matches)) {
            if (!isset($amounts['total'])) {
                $amounts['total'] = trim($matches[1]);
            }
        }
        
        // Fallback: Suche nach größtem Betrag als potentieller Gesamtbetrag
        if (!isset($amounts['total'])) {
            if (preg_match_all('/([0-9.,]+)\s*€/i', $text, $matches)) {
                $allAmounts = [];
                foreach ($matches[1] as $amount) {
                    $numericAmount = (float) str_replace(',', '.', $amount);
                    if ($numericAmount > 0) {
                        $allAmounts[] = ['original' => $amount, 'numeric' => $numericAmount];
                    }
                }
                
                if (!empty($allAmounts)) {
                    // Sortiere nach Betragshöhe (absteigend)
                    usort($allAmounts, function($a, $b) {
                        return $b['numeric'] <=> $a['numeric'];
                    });
                    
                    // Nimm den größten Betrag als potentiellen Gesamtbetrag
                    $amounts['total'] = $allAmounts[0]['original'];
                }
            }
        }
        
        return $amounts;
    }
    
    /**
     * Extrahiert Datumsangaben aus E-Mail-Text
     */
    private function extractDatesFromEmail($text)
    {
        $dates = [];
        
        // Rechnungsdatum
        if (preg_match('/(\d{1,2}\.\s*[A-Za-z]+\s+\d{4})(?:\s|$)/i', $text, $matches)) {
            $dates['invoice_date'] = trim($matches[1]);
        }
        
        // Abrechnungszeitraum
        if (preg_match('/(?:für|im)\s+([A-Za-z]+\s+\d{4})/i', $text, $matches)) {
            $dates['billing_period'] = trim($matches[1]);
        }
        
        // Abbuchungsdatum
        if (preg_match('/(?:am|bis)\s+(\d{1,2}\.\s*[A-Za-z]+\s+\d{4})/i', $text, $matches)) {
            $dates['due_date'] = trim($matches[1]);
        }
        
        return $dates;
    }
    
    /**
     * Extrahiert Referenznummern aus E-Mail-Text
     */
    private function extractReferences($text)
    {
        $references = [];
        
        // Vertragskonto (deutsche und englische Varianten)
        if (preg_match('/(?:Vertragskonto|Contract\s+account)[:\s]*([0-9\s]+)/i', $text, $matches)) {
            $references['contract_account'] = preg_replace('/\s+/', '', trim($matches[1]));
        }
        
        // Rechnungsnummer (deutsche und englische Varianten)
        if (preg_match('/(?:Rechnungsnummer|Invoice\s+number)[:\s]*([A-Z0-9\s\-\/]+?)(?:\s*\n|\s*$|\s{2,})/i', $text, $matches)) {
            $references['invoice_number'] = preg_replace('/\s+/', '', trim($matches[1]));
        }
        
        // Kundennummer
        if (preg_match('/Kundennummer[:\s]*([A-Z0-9\s\-]+)/i', $text, $matches)) {
            $references['customer_number'] = preg_replace('/\s+/', '', trim($matches[1]));
        }
        
        // Gläubiger-ID
        if (preg_match('/Gläubiger[_\-\s]*ID[:\s]*([A-Z0-9]+)/i', $text, $matches)) {
            $references['creditor_id'] = trim($matches[1]);
        }
        
        return $references;
    }
    
    /**
     * Extrahiert Dienstleistungen/Produkte aus E-Mail-Text
     */
    private function extractServices($text)
    {
        $services = [];
        
        // Energiearten
        $energyTypes = ['Strom', 'Gas', 'Wasser', 'Fernwärme', 'Energie'];
        foreach ($energyTypes as $type) {
            if (stripos($text, $type) !== false) {
                $services['energy_types'][] = $type;
            }
        }
        
        // Dienstleistungshinweise
        if (preg_match('/(?:haben\s+wir|Dienstleistungen?)[^\n\r]*(?:für\s+Sie\s+erbracht|beliefert)/i', $text, $matches)) {
            $services['service_description'] = trim($matches[0]);
        }
        
        // Verbrauchsperiode
        if (preg_match('/(?:Im|Für)\s+([A-Za-z]+\s+\d{4})\s+haben\s+wir\s+Sie/i', $text, $matches)) {
            $services['service_period'] = trim($matches[1]);
        }
        
        return $services;
    }
    
    /**
     * Analysiert E-Mail-Weiterleitungen und extrahiert ursprüngliche Absender-Informationen
     */
    private function analyzeEmailForwarding($email, $text)
    {
        $forwardingAnalysis = [
            'is_forwarded' => false,
            'forwarding_indicators' => [],
            'original_sender' => null,
            'original_date' => null,
            'original_subject' => null,
            'forwarding_chain' => [],
            'confidence_score' => 0,
        ];
        
        try {
            // E-Mail-Header-Analyse (falls verfügbar)
            $headerAnalysis = $this->analyzeEmailHeaders($email);
            
            // Text-basierte Weiterleitungs-Indikatoren
            $textAnalysis = $this->analyzeForwardingInText($text);
            
            // Kombiniere Header- und Text-Analyse
            $forwardingAnalysis = array_merge($forwardingAnalysis, $headerAnalysis, $textAnalysis);
            
            // Berechne Confidence Score
            $forwardingAnalysis['confidence_score'] = $this->calculateForwardingConfidence($forwardingAnalysis);
            
            // Bestimme ob es sich um eine Weiterleitung handelt
            $forwardingAnalysis['is_forwarded'] = $forwardingAnalysis['confidence_score'] >= 60;
            
        } catch (\Exception $e) {
            $forwardingAnalysis['error'] = 'Fehler bei der Weiterleitungs-Analyse: ' . $e->getMessage();
        }
        
        return $forwardingAnalysis;
    }
    
    /**
     * Analysiert E-Mail-Header auf Weiterleitungs-Indikatoren
     */
    private function analyzeEmailHeaders($email)
    {
        $headerAnalysis = [
            'header_indicators' => [],
            'received_chain' => [],
            'forwarding_headers' => [],
        ];
        
        try {
            // Prüfe Subject auf "Fwd:" oder "Weiterleitung:"
            if ($email->subject) {
                if (preg_match('/^(?:Fwd?:|FW:|Weiterleitung:|WG:)/i', $email->subject)) {
                    $headerAnalysis['header_indicators'][] = 'Subject enthält Weiterleitungs-Präfix';
                    $headerAnalysis['forwarding_headers']['subject_prefix'] = true;
                    
                    // Extrahiere ursprünglichen Betreff
                    if (preg_match('/^(?:Fwd?:|FW:|Weiterleitung:|WG:)\s*(.+)$/i', $email->subject, $matches)) {
                        $headerAnalysis['original_subject'] = trim($matches[1]);
                    }
                }
            }
            
            // Prüfe From-Adresse vs. Reply-To (falls verfügbar)
            if ($email->from_email && $email->reply_to && $email->from_email !== $email->reply_to) {
                $headerAnalysis['header_indicators'][] = 'From und Reply-To unterscheiden sich';
                $headerAnalysis['forwarding_headers']['different_reply_to'] = true;
            }
            
            // Analysiere Message-ID Pattern (Gmail-spezifisch)
            if ($email->message_id) {
                // Gmail Message-IDs haben oft spezifische Patterns für Weiterleitungen
                if (preg_match('/\.gmail\.com/', $email->message_id)) {
                    $headerAnalysis['header_indicators'][] = 'Gmail Message-ID erkannt';
                }
            }
            
        } catch (\Exception $e) {
            $headerAnalysis['error'] = 'Fehler bei Header-Analyse: ' . $e->getMessage();
        }
        
        return $headerAnalysis;
    }
    
    /**
     * Analysiert Text-Inhalt auf Weiterleitungs-Indikatoren
     */
    private function analyzeForwardingInText($text)
    {
        $textAnalysis = [
            'text_indicators' => [],
            'forwarding_patterns' => [],
            'original_message_block' => null,
        ];
        
        try {
            // Deutsche Weiterleitungs-Pattern
            $forwardingPatterns = [
                // Standard Weiterleitungs-Header
                '/(?:---------- Weitergeleitete Nachricht ----------|---------- Forwarded message ----------)/i',
                '/(?:Von:|From:)\s*([^\n\r]+)/i',
                '/(?:Gesendet:|Sent:)\s*([^\n\r]+)/i',
                '/(?:An:|To:)\s*([^\n\r]+)/i',
                '/(?:Betreff:|Subject:)\s*([^\n\r]+)/i',
                
                // Gmail-spezifische Pattern
                '/---------- Forwarded message ----------/i',
                '/Begin forwarded message:/i',
                
                // Outlook-Pattern
                '/-----Ursprüngliche Nachricht-----/i',
                '/-----Original Message-----/i',
                
                // Allgemeine Pattern
                '/Am\s+\d{1,2}\.\d{1,2}\.\d{4}.*schrieb\s+([^<\n\r]+)/i',
                '/On\s+\w+,\s+\w+\s+\d{1,2},\s+\d{4}.*wrote:/i',
            ];
            
            foreach ($forwardingPatterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $textAnalysis['text_indicators'][] = 'Weiterleitungs-Pattern gefunden: ' . $pattern;
                    $textAnalysis['forwarding_patterns'][] = [
                        'pattern' => $pattern,
                        'match' => $matches[0] ?? null,
                        'extracted_info' => $matches[1] ?? null
                    ];
                }
            }
            
            // Extrahiere ursprüngliche Absender-Information
            $originalSenderPatterns = [
                '/(?:Von:|From:)\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i',
                '/(?:Von:|From:)\s*"?([^"<\n\r]+)"?\s*<([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})>/i',
                '/Am\s+\d{1,2}\.\d{1,2}\.\d{4}.*schrieb\s+"?([^"<\n\r]+)"?\s*<([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})>/i',
            ];
            
            foreach ($originalSenderPatterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    if (count($matches) >= 3) {
                        // Name und E-Mail gefunden
                        $textAnalysis['original_sender'] = [
                            'name' => trim($matches[1]),
                            'email' => trim($matches[2])
                        ];
                    } elseif (count($matches) >= 2) {
                        // Nur E-Mail gefunden
                        $textAnalysis['original_sender'] = [
                            'email' => trim($matches[1])
                        ];
                    }
                    break;
                }
            }
            
            // Extrahiere ursprüngliches Datum
            if (preg_match('/(?:Gesendet:|Sent:)\s*([^\n\r]+)/i', $text, $matches)) {
                $textAnalysis['original_date'] = trim($matches[1]);
            }
            
            // Extrahiere ursprünglichen Betreff
            if (preg_match('/(?:Betreff:|Subject:)\s*([^\n\r]+)/i', $text, $matches)) {
                $textAnalysis['original_subject'] = trim($matches[1]);
            }
            
            // Suche nach kompletten ursprünglichen Nachrichten-Blöcken
            if (preg_match('/(?:---------- Weitergeleitete Nachricht ----------|-----Ursprüngliche Nachricht-----)(.*?)(?:\n\n|\r\n\r\n|$)/s', $text, $matches)) {
                $textAnalysis['original_message_block'] = trim($matches[1]);
            }
            
            // Erkenne Weiterleitungs-Ketten (mehrfache Weiterleitungen)
            $chainCount = preg_match_all('/(?:Von:|From:)/i', $text);
            if ($chainCount > 1) {
                $textAnalysis['text_indicators'][] = "Mögliche Weiterleitungs-Kette erkannt ($chainCount Absender)";
                $textAnalysis['forwarding_chain_length'] = $chainCount;
            }
            
        } catch (\Exception $e) {
            $textAnalysis['error'] = 'Fehler bei Text-Analyse: ' . $e->getMessage();
        }
        
        return $textAnalysis;
    }
    
    /**
     * Berechnet Confidence Score für Weiterleitungs-Erkennung
     */
    private function calculateForwardingConfidence($analysis)
    {
        $score = 0;
        
        // Header-Indikatoren (hohe Gewichtung)
        if (isset($analysis['forwarding_headers']['subject_prefix']) && $analysis['forwarding_headers']['subject_prefix']) {
            $score += 40; // Fwd: im Subject ist sehr starker Indikator
        }
        
        if (isset($analysis['forwarding_headers']['different_reply_to']) && $analysis['forwarding_headers']['different_reply_to']) {
            $score += 20;
        }
        
        // Text-Indikatoren
        $textIndicatorCount = count($analysis['text_indicators'] ?? []);
        $score += min($textIndicatorCount * 15, 45); // Max 45 Punkte für Text-Indikatoren
        
        // Ursprünglicher Absender gefunden
        if (!empty($analysis['original_sender'])) {
            $score += 25;
        }
        
        // Weiterleitungs-Pattern gefunden
        $patternCount = count($analysis['forwarding_patterns'] ?? []);
        $score += min($patternCount * 10, 30); // Max 30 Punkte für Pattern
        
        // Weiterleitungs-Kette
        if (isset($analysis['forwarding_chain_length']) && $analysis['forwarding_chain_length'] > 1) {
            $score += 15;
        }
        
        // Ursprüngliche Nachricht-Block gefunden
        if (!empty($analysis['original_message_block'])) {
            $score += 20;
        }
        
        return min($score, 100); // Max 100%
    }
    
    /**
     * Erkennt passende Verträge basierend auf PDF- und E-Mail-Text
     */
    private function recognizeContracts($pdfText, $email)
    {
        $contractRecognition = [
            'has_matching_contracts' => false,
            'found_contracts' => [],
            'search_criteria' => [],
            'total_matches' => 0,
            'confidence_scores' => [],
        ];
        
        try {
            // Hole alle Verträge aus der Datenbank
            $contracts = SupplierContract::with('supplier')->get();
            
            if ($contracts->isEmpty()) {
                $contractRecognition['message'] = 'Keine Verträge in der Datenbank gefunden.';
                return $contractRecognition;
            }
            
            // Kombiniere PDF-Text und E-Mail-Text für die Suche
            $searchText = $pdfText;
            if ($email && $email->body_text) {
                $searchText .= "\n\n" . $email->body_text;
            }
            
            // Normalisiere den Suchtext (entferne Leerzeichen, konvertiere zu Kleinbuchstaben)
            $normalizedSearchText = strtolower(preg_replace('/\s+/', '', $searchText));
            
            $foundContracts = [];
            
            foreach ($contracts as $contract) {
                $matchScore = 0;
                $matchedFields = [];
                $matchDetails = [];
                
                // Prüfe Kreditorennummer
                if (!empty($contract->creditor_number)) {
                    $normalizedCreditorNumber = strtolower(preg_replace('/\s+/', '', $contract->creditor_number));
                    if (strpos($normalizedSearchText, $normalizedCreditorNumber) !== false) {
                        $matchScore += 30;
                        $matchedFields[] = 'Kreditorennummer';
                        $matchDetails['creditor_number'] = $contract->creditor_number;
                    }
                }
                
                // Prüfe externe Vertragsnummer
                if (!empty($contract->external_contract_number)) {
                    $normalizedExternalNumber = strtolower(preg_replace('/\s+/', '', $contract->external_contract_number));
                    if (strpos($normalizedSearchText, $normalizedExternalNumber) !== false) {
                        $matchScore += 25;
                        $matchedFields[] = 'Externe Vertragsnummer';
                        $matchDetails['external_contract_number'] = $contract->external_contract_number;
                    }
                }
                
                // Prüfe Vertragserkennung 1
                if (!empty($contract->contract_recognition_1)) {
                    $normalizedRecognition1 = strtolower(preg_replace('/\s+/', '', $contract->contract_recognition_1));
                    if (strpos($normalizedSearchText, $normalizedRecognition1) !== false) {
                        $matchScore += 20;
                        $matchedFields[] = 'Vertragserkennung 1';
                        $matchDetails['contract_recognition_1'] = $contract->contract_recognition_1;
                    }
                }
                
                // Prüfe Vertragserkennung 2
                if (!empty($contract->contract_recognition_2)) {
                    $normalizedRecognition2 = strtolower(preg_replace('/\s+/', '', $contract->contract_recognition_2));
                    if (strpos($normalizedSearchText, $normalizedRecognition2) !== false) {
                        $matchScore += 20;
                        $matchedFields[] = 'Vertragserkennung 2';
                        $matchDetails['contract_recognition_2'] = $contract->contract_recognition_2;
                    }
                }
                
                // Prüfe Vertragserkennung 3
                if (!empty($contract->contract_recognition_3)) {
                    $normalizedRecognition3 = strtolower(preg_replace('/\s+/', '', $contract->contract_recognition_3));
                    if (strpos($normalizedSearchText, $normalizedRecognition3) !== false) {
                        $matchScore += 20;
                        $matchedFields[] = 'Vertragserkennung 3';
                        $matchDetails['contract_recognition_3'] = $contract->contract_recognition_3;
                    }
                }
                
                // Zusätzliche Punkte für Lieferantenname (falls vorhanden)
                if ($contract->supplier && !empty($contract->supplier->name)) {
                    $normalizedSupplierName = strtolower(preg_replace('/\s+/', '', $contract->supplier->name));
                    if (strpos($normalizedSearchText, $normalizedSupplierName) !== false) {
                        $matchScore += 15;
                        $matchedFields[] = 'Lieferantenname';
                        $matchDetails['supplier_name'] = $contract->supplier->name;
                    }
                }
                
                // Wenn mindestens ein Match gefunden wurde
                if ($matchScore > 0) {
                    $foundContracts[] = [
                        'contract' => $contract,
                        'match_score' => $matchScore,
                        'matched_fields' => $matchedFields,
                        'match_details' => $matchDetails,
                        'confidence_level' => $this->getConfidenceLevel($matchScore),
                    ];
                }
            }
            
            // Sortiere nach Match-Score (höchste zuerst)
            usort($foundContracts, function($a, $b) {
                return $b['match_score'] - $a['match_score'];
            });
            
            if (!empty($foundContracts)) {
                $contractRecognition['has_matching_contracts'] = true;
                $contractRecognition['found_contracts'] = $foundContracts;
                $contractRecognition['total_matches'] = count($foundContracts);
                
                // Erstelle Suchkriterien-Übersicht
                $allMatchedFields = [];
                foreach ($foundContracts as $match) {
                    $allMatchedFields = array_merge($allMatchedFields, $match['matched_fields']);
                }
                $contractRecognition['search_criteria'] = array_unique($allMatchedFields);
                
                // Confidence Scores sammeln
                foreach ($foundContracts as $match) {
                    $contractRecognition['confidence_scores'][] = [
                        'contract_id' => $match['contract']->id,
                        'score' => $match['match_score'],
                        'level' => $match['confidence_level']
                    ];
                }
            } else {
                $contractRecognition['message'] = 'Keine passenden Verträge gefunden. Überprüfen Sie die Vertragsdaten in der Datenbank.';
            }
            
        } catch (\Exception $e) {
            $contractRecognition['error'] = 'Fehler bei der Vertragserkennung: ' . $e->getMessage();
        }
        
        return $contractRecognition;
    }
    
    /**
     * Bestimmt das Confidence Level basierend auf dem Match Score
     */
    private function getConfidenceLevel($score)
    {
        if ($score >= 50) {
            return 'Sehr hoch';
        } elseif ($score >= 35) {
            return 'Hoch';
        } elseif ($score >= 20) {
            return 'Mittel';
        } else {
            return 'Niedrig';
        }
    }

    /**
     * Führt die variable PDF-Analyse durch
     */
    private function performVariableAnalysis($pdfText, $email = null)
    {
        $analysis = [
            'supplier_recognition' => null,
            'extracted_data' => null,
            'contract_matching' => null,
            'processing_time' => null,
            'confidence_scores' => [],
            'errors' => [],
        ];

        $startTime = microtime(true);

        try {
            // Schritt 1: Lieferanten-Erkennung
            $supplierResult = $this->supplierRecognitionService->recognizeSupplier($pdfText);
            $analysis['supplier_recognition'] = $supplierResult;

            if ($supplierResult['success'] && !empty($supplierResult['recognized_supplier'])) {
                $recognizedSupplier = $supplierResult['recognized_supplier'];
                
                // Schritt 2: Regelbasierte Datenextraktion
                $extractionResult = $this->ruleBasedExtractionService->extractData(
                    $pdfText,
                    $recognizedSupplier['supplier_id']
                );
                $analysis['extracted_data'] = $extractionResult;

                // Schritt 3: Vertragszuordnung
                if ($extractionResult['success'] && !empty($extractionResult['extracted_data'])) {
                    $contractResult = $this->contractMatchingService->findMatchingContract(
                        $extractionResult['extracted_data'],
                        $recognizedSupplier['supplier_id']
                    );
                    $analysis['contract_matching'] = $contractResult;
                }
            } else {
                // Fallback: Versuche Datenextraktion ohne spezifischen Lieferanten
                $extractionResult = $this->ruleBasedExtractionService->extractDataWithoutSupplier($pdfText);
                $analysis['extracted_data'] = $extractionResult;
                
                // Versuche Vertragszuordnung mit extrahierten Daten
                if ($extractionResult['success'] && !empty($extractionResult['extracted_data'])) {
                    $contractResult = $this->contractMatchingService->findMatchingContractByData(
                        $extractionResult['extracted_data']
                    );
                    $analysis['contract_matching'] = $contractResult;
                }
            }

            // Sammle Confidence Scores
            $analysis['confidence_scores'] = $this->collectConfidenceScores($analysis);

        } catch (\Exception $e) {
            $analysis['errors'][] = 'Fehler bei der variablen Analyse: ' . $e->getMessage();
        }

        $analysis['processing_time'] = round((microtime(true) - $startTime) * 1000, 2) . ' ms';

        return $analysis;
    }

    /**
     * Sammelt Confidence Scores aus allen Analyseschritten
     */
    private function collectConfidenceScores($analysis)
    {
        $scores = [];

        if (isset($analysis['supplier_recognition']['confidence_score'])) {
            $scores['supplier_recognition'] = $analysis['supplier_recognition']['confidence_score'];
        }

        if (isset($analysis['extracted_data']['confidence_score'])) {
            $scores['data_extraction'] = $analysis['extracted_data']['confidence_score'];
        }

        if (isset($analysis['contract_matching']['confidence_score'])) {
            $scores['contract_matching'] = $analysis['contract_matching']['confidence_score'];
        }

        // Berechne Gesamt-Confidence
        if (!empty($scores)) {
            $scores['overall'] = round(array_sum($scores) / count($scores), 2);
        }

        return $scores;
    }

    /**
     * Lädt Anhang-Daten - zuerst aus lokalem Storage, dann von Gmail API
     */
    private function getAttachmentData($email, $targetAttachment, $attachmentId): ?string
    {
        // Versuche zuerst lokale Datei zu laden
        $localPath = $this->getLocalAttachmentPath($email, $targetAttachment);
        
        \Log::info('PDF-Analyse: Prüfe lokale Datei', [
            'email_id' => $email->id,
            'attachment_id' => $attachmentId,
            'local_path' => $localPath,
            'file_exists' => \Storage::exists($localPath)
        ]);
        
        if (\Storage::exists($localPath)) {
            try {
                $fileContent = \Storage::get($localPath);
                \Log::info('PDF-Analyse: Lokale Datei erfolgreich geladen', [
                    'email_id' => $email->id,
                    'attachment_id' => $attachmentId,
                    'local_path' => $localPath,
                    'file_size' => strlen($fileContent)
                ]);
                return $fileContent;
            } catch (\Exception $e) {
                \Log::warning('PDF-Analyse: Fehler beim Laden der lokalen Datei', [
                    'email_id' => $email->id,
                    'attachment_id' => $attachmentId,
                    'local_path' => $localPath,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Fallback: Lade von Gmail API
        \Log::info('PDF-Analyse: Fallback zu Gmail API', [
            'email_id' => $email->id,
            'attachment_id' => $attachmentId,
            'reason' => 'Lokale Datei nicht verfügbar'
        ]);
        
        try {
            $gmailService = new \App\Services\GmailService();
            $attachmentData = $gmailService->downloadAttachmentWithRetry($email->gmail_id, $attachmentId, 3);
            
            if ($attachmentData) {
                // Speichere für zukünftige Verwendung
                try {
                    \Storage::put($localPath, $attachmentData);
                    \Log::info('PDF-Analyse: Datei lokal gespeichert', [
                        'email_id' => $email->id,
                        'attachment_id' => $attachmentId,
                        'local_path' => $localPath,
                        'file_size' => strlen($attachmentData)
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('PDF-Analyse: Fehler beim lokalen Speichern', [
                        'email_id' => $email->id,
                        'attachment_id' => $attachmentId,
                        'local_path' => $localPath,
                        'error' => $e->getMessage()
                    ]);
                }
                
                return $attachmentData;
            }
        } catch (\Exception $e) {
            \Log::error('PDF-Analyse: Gmail API-Download fehlgeschlagen', [
                'email_id' => $email->id,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Ermittelt den lokalen Speicherpfad für einen Anhang
     */
    private function getLocalAttachmentPath($email, $attachment): string
    {
        $settings = \App\Models\CompanySetting::current();
        $attachmentPath = $settings->gmail_attachment_path ?? 'gmail-attachments';
        
        // Erstelle Verzeichnisstruktur: gmail-attachments/YYYY/MM/DD/gmail_id/
        $datePath = $email->gmail_date ? $email->gmail_date->format('Y/m/d') : date('Y/m/d');
        $fullPath = "{$attachmentPath}/{$datePath}/{$email->gmail_id}";
        
        $filename = $attachment['filename'] ?? "attachment_{$attachment['id']}";
        
        return "{$fullPath}/{$filename}";
    }

    /**
     * Erweitert die showAnalysis Methode um das variable System
     */
    public function showVariableAnalysis($emailUuid, $attachmentId)
    {
        try {
            // Gmail Email finden
            $email = GmailEmail::where('uuid', $emailUuid)->firstOrFail();
            
            if (!$email->has_attachments) {
                return $this->showError('Diese E-Mail hat keine Anhänge.');
            }

            $pdfAttachments = $email->getPdfAttachments();
            $targetAttachment = null;
            
            foreach ($pdfAttachments as $attachment) {
                if (($attachment['id'] ?? $attachment['attachmentId'] ?? null) === $attachmentId) {
                    $targetAttachment = $attachment;
                    break;
                }
            }

            if (!$targetAttachment) {
                return $this->showError('PDF-Anhang nicht gefunden.');
            }

            // Gmail Service initialisieren
            $gmailService = new GmailService();
            // Verwende die neue Retry-Methode für Rate-Limit-Behandlung
            $attachmentData = $gmailService->downloadAttachmentWithRetry($email->gmail_id, $attachmentId, 3);
            
            if (!$attachmentData) {
                return $this->showError('Anhang-Daten konnten nicht geladen werden.');
            }

            // PDF-Daten sind bereits dekodiert
            $pdfContent = $attachmentData;
            
            // PDF Parser initialisieren
            $parser = new Parser();
            $pdf = $parser->parseContent($pdfContent);
            
            // Text aus allen Seiten extrahieren
            $allText = '';
            $pages = $pdf->getPages();
            
            foreach ($pages as $pageNumber => $page) {
                $pageText = $page->getText();
                $allText .= $pageText . "\n\n";
            }

            // Variables PDF-Analyse-System anwenden
            $variableAnalysis = $this->performVariableAnalysis($allText, $email);
            
            // Grundlegende Analyse (vereinfacht)
            $analysis = [
                'filename' => $targetAttachment['filename'] ?? 'Unbekannte Datei',
                'basic_info' => [
                    'title' => $pdf->getDetails()['Title'] ?? 'Nicht verfügbar',
                    'page_count' => count($pdf->getPages()),
                ],
                'text_content' => $allText,
                'file_info' => [
                    'size' => strlen($pdfContent),
                    'size_formatted' => $this->formatBytes(strlen($pdfContent)),
                    'mime_type' => 'application/pdf',
                ],
                'variable_analysis' => $variableAnalysis,
            ];

            return response()->view('pdf-analysis.variable-show', [
                'analysis' => $analysis,
                'email' => $email
            ]);

        } catch (\Exception $e) {
            return $this->showError('Fehler bei der variablen PDF-Analyse: ' . $e->getMessage());
        }
    }
}