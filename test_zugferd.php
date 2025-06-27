<?php

require_once 'vendor/autoload.php';

use App\Models\Invoice;
use App\Services\ZugferdService;
use horstoeko\zugferd\ZugferdDocumentPdfReader;

// Laravel Bootstrap für Standalone-Test
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ZUGFeRD Test Script ===\n\n";

try {
    // Finde eine Test-Rechnung
    $invoice = Invoice::with(['customer', 'items.article'])->first();
    
    if (!$invoice) {
        echo "❌ Keine Rechnung gefunden. Bitte erstellen Sie zuerst eine Rechnung.\n";
        exit(1);
    }
    
    echo "✅ Test-Rechnung gefunden: {$invoice->invoice_number}\n";
    echo "   Kunde: {$invoice->customer->name}\n";
    echo "   Positionen: " . $invoice->items->count() . "\n";
    echo "   Gesamtbetrag: " . number_format($invoice->total, 2, ',', '.') . " €\n\n";
    
    // Erstelle ZugferdService
    $zugferdService = new ZugferdService();
    
    // Test 1: XML-Generierung
    echo "🔄 Teste XML-Generierung...\n";
    $xmlContent = $zugferdService->generateZugferdXml($invoice);
    
    if (strlen($xmlContent) > 0) {
        echo "✅ XML erfolgreich generiert (" . strlen($xmlContent) . " Zeichen)\n";
        
        // Speichere XML für Inspektion
        file_put_contents("test_zugferd_output.xml", $xmlContent);
        echo "   XML gespeichert als: test_zugferd_output.xml\n";
    } else {
        echo "❌ XML-Generierung fehlgeschlagen\n";
        exit(1);
    }
    
    // Test 2: XML-Validierung
    echo "\n🔄 Teste XML-Validierung...\n";
    $validation = $zugferdService->validateZugferdXml($xmlContent);
    
    if ($validation['valid']) {
        echo "✅ XML ist gültig\n";
    } else {
        echo "❌ XML-Validierung fehlgeschlagen:\n";
        foreach ($validation['errors'] as $error) {
            echo "   - $error\n";
        }
    }
    
    // Test 3: PDF-Generierung
    echo "\n🔄 Teste ZUGFeRD-PDF-Generierung...\n";
    $pdfContent = $zugferdService->generateZugferdPdf($invoice);
    
    if (strlen($pdfContent) > 0) {
        echo "✅ ZUGFeRD-PDF erfolgreich generiert (" . strlen($pdfContent) . " Bytes)\n";
        
        // Speichere PDF für Inspektion
        $pdfFilename = "test_zugferd_output.pdf";
        file_put_contents($pdfFilename, $pdfContent);
        echo "   PDF gespeichert als: $pdfFilename\n";
        
        // Test 4: PDF-Validierung
        echo "\n🔄 Teste PDF-Validierung...\n";
        
        try {
            // Versuche XML aus PDF zu extrahieren
            $extractedXml = ZugferdDocumentPdfReader::getXmlFromFile($pdfFilename);
            
            if ($extractedXml) {
                echo "✅ XML erfolgreich aus PDF extrahiert\n";
                echo "   Extrahierte XML-Länge: " . strlen($extractedXml) . " Zeichen\n";
                
                // Vergleiche Original-XML mit extrahierter XML
                if (trim($xmlContent) === trim($extractedXml)) {
                    echo "✅ Extrahierte XML stimmt mit Original überein\n";
                } else {
                    echo "⚠️  Extrahierte XML unterscheidet sich vom Original\n";
                }
                
                // Teste ZUGFeRD-Reader
                $zugferdReader = ZugferdDocumentPdfReader::readAndGuessFromFile($pdfFilename);
                if ($zugferdReader) {
                    echo "✅ ZUGFeRD-Reader kann PDF erfolgreich lesen\n";
                    
                    // Hole Dokumentinformationen
                    $zugferdReader->getDocumentInformation($docNo, $docTypeCode, $docDate, $invoiceCurrency, $taxCurrency, $documentName, $documentLanguage, $effectiveSpecifiedPeriod);
                    echo "   Rechnungsnummer: $docNo\n";
                    echo "   Dokumenttyp: $docTypeCode\n";
                    echo "   Datum: " . $docDate->format('d.m.Y') . "\n";
                    echo "   Währung: $invoiceCurrency\n";
                    
                    echo "\n🎉 Alle Tests erfolgreich! ZUGFeRD-PDF ist korrekt erstellt.\n";
                } else {
                    echo "❌ ZUGFeRD-Reader kann PDF nicht lesen\n";
                }
                
            } else {
                echo "❌ Keine XML aus PDF extrahiert - PDF ist nicht ZUGFeRD-konform\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Fehler bei PDF-Validierung: " . $e->getMessage() . "\n";
            echo "   Dies deutet darauf hin, dass die PDF nicht ZUGFeRD-konform ist.\n";
        }
        
    } else {
        echo "❌ PDF-Generierung fehlgeschlagen\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stacktrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Test abgeschlossen ===\n";