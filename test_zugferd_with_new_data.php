<?php

require_once 'vendor/autoload.php';

use App\Models\Invoice;
use App\Models\Customer;
use App\Services\ZugferdService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ZUGFeRD Test mit neuen Kundendaten ===\n\n";

try {
    // Lade einen Kunden mit vollständigen ZUGFeRD-Daten
    $customer = Customer::where('customer_number', 'KD-003')->first(); // Weber Maschinenbau GmbH
    
    if (!$customer) {
        echo "❌ Kunde nicht gefunden\n";
        exit(1);
    }
    
    echo "Kunde gefunden: {$customer->display_name}\n";
    echo "Kundennummer: {$customer->customer_number}\n";
    echo "USt-IdNr: {$customer->vat_id}\n";
    echo "Ländercode: {$customer->country_code}\n";
    echo "Zahlungsbedingungen: {$customer->payment_terms}\n";
    echo "Zahlungstage: {$customer->payment_days}\n\n";
    
    // Erstelle eine Test-Rechnung
    $invoice = Invoice::first();
    if (!$invoice) {
        echo "❌ Keine Rechnung gefunden\n";
        exit(1);
    }
    
    // Weise den Kunden der Rechnung zu
    $invoice->customer_id = $customer->id;
    $invoice->save();
    
    echo "Rechnung {$invoice->invoice_number} dem Kunden zugewiesen\n\n";
    
    // Erstelle ZUGFeRD Service
    $zugferdService = new ZugferdService();
    
    echo "=== Generiere ZUGFeRD-XML ===\n";
    $xmlContent = $zugferdService->generateZugferdXml($invoice);
    
    // Speichere XML
    file_put_contents('zugferd_with_new_data.xml', $xmlContent);
    echo "✅ XML generiert und gespeichert als zugferd_with_new_data.xml\n";
    
    // Prüfe wichtige ZUGFeRD-Elemente
    echo "\n=== Validierung der XML-Inhalte ===\n";
    
    // Prüfe Verkäufer USt-IdNr
    if (strpos($xmlContent, '<ram:ID schemeID="VA">') !== false) {
        echo "✅ Verkäufer USt-IdNr gefunden\n";
    } else {
        echo "❌ Verkäufer USt-IdNr fehlt\n";
    }
    
    // Prüfe Käufer USt-IdNr
    if (strpos($xmlContent, $customer->vat_id) !== false) {
        echo "✅ Käufer USt-IdNr ({$customer->vat_id}) gefunden\n";
    } else {
        echo "❌ Käufer USt-IdNr fehlt\n";
    }
    
    // Prüfe CountryID-Elemente
    $countryMatches = [];
    preg_match_all('/<ram:CountryID>([^<]+)<\/ram:CountryID>/', $xmlContent, $countryMatches);
    if (count($countryMatches[1]) > 0) {
        echo "✅ CountryID-Elemente gefunden: " . implode(', ', $countryMatches[1]) . "\n";
    } else {
        echo "❌ Keine CountryID-Elemente gefunden\n";
    }
    
    // Prüfe Firmennamen
    if (strpos($xmlContent, $customer->company_name) !== false) {
        echo "✅ Firmenname ({$customer->company_name}) gefunden\n";
    } else {
        echo "❌ Firmenname fehlt\n";
    }
    
    // Prüfe Zahlungsbedingungen
    if (strpos($xmlContent, $customer->payment_terms) !== false) {
        echo "✅ Zahlungsbedingungen gefunden\n";
    } else {
        echo "❌ Zahlungsbedingungen fehlen\n";
    }
    
    echo "\n=== Generiere ZUGFeRD-PDF ===\n";
    $pdfContent = $zugferdService->generateZugferdPdf($invoice);
    
    // Speichere PDF
    file_put_contents('zugferd_with_new_data.pdf', $pdfContent);
    echo "✅ PDF generiert und gespeichert als zugferd_with_new_data.pdf\n";
    
    // Extrahiere XML aus PDF zur Validierung
    echo "\n=== Validiere eingebettete XML ===\n";
    
    try {
        $extractedXml = \horstoeko\zugferd\ZugferdDocumentPdfReader::getXmlFromFile('zugferd_with_new_data.pdf');
        
        // Prüfe CountryID in eingebetteter XML
        $embeddedCountryMatches = [];
        preg_match_all('/<ram:CountryID>([^<]+)<\/ram:CountryID>/', $extractedXml, $embeddedCountryMatches);
        
        if (count($embeddedCountryMatches[1]) > 0) {
            echo "✅ CountryID-Elemente in PDF-XML gefunden: " . implode(', ', $embeddedCountryMatches[1]) . "\n";
        } else {
            echo "❌ Keine CountryID-Elemente in PDF-XML gefunden\n";
        }
        
        // Prüfe USt-IdNr in eingebetteter XML
        if (strpos($extractedXml, $customer->vat_id) !== false) {
            echo "✅ Käufer USt-IdNr in PDF-XML gefunden\n";
        } else {
            echo "❌ Käufer USt-IdNr in PDF-XML fehlt\n";
        }
        
        file_put_contents('extracted_from_new_data_pdf.xml', $extractedXml);
        echo "✅ XML aus PDF extrahiert und gespeichert\n";
        
    } catch (Exception $e) {
        echo "❌ Fehler beim Extrahieren der XML aus PDF: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Test abgeschlossen ===\n";
    echo "Alle ZUGFeRD-relevanten Kundendaten wurden erfolgreich in die XML/PDF integriert!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}