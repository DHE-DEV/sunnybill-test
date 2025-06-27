<?php

require_once 'vendor/autoload.php';

use horstoeko\zugferd\ZugferdDocumentPdfReader;

echo "=== Prüfe PDF-XML auf CountryID-Elemente ===\n\n";

try {
    $xmlContent = ZugferdDocumentPdfReader::getXmlFromFile('final_test_output.pdf');
    file_put_contents('extracted_from_pdf.xml', $xmlContent);
    echo "✅ XML aus PDF extrahiert (" . strlen($xmlContent) . " Zeichen)\n";
    
    // Prüfe auf CountryID-Elemente
    if (strpos($xmlContent, '<ram:CountryID>') !== false) {
        echo "✅ CountryID-Elemente in PDF-XML gefunden\n";
        $matches = [];
        preg_match_all('/<ram:CountryID>([^<]+)<\/ram:CountryID>/', $xmlContent, $matches);
        echo "Gefundene CountryID-Werte: " . implode(', ', $matches[1]) . "\n";
    } else {
        echo "❌ Keine CountryID-Elemente in PDF-XML gefunden\n";
    }
    
    // Zeige Adress-Strukturen
    echo "\n🔍 Adress-Strukturen in PDF-XML:\n";
    $dom = new DOMDocument();
    $dom->loadXML($xmlContent);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');
    
    $addressNodes = $xpath->query('//ram:PostalTradeAddress');
    foreach ($addressNodes as $i => $addressNode) {
        echo "Adresse " . ($i + 1) . ":\n";
        echo $dom->saveXML($addressNode) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}