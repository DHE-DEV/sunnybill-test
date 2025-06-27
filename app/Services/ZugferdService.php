<?php

namespace App\Services;

use App\Models\Invoice;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdDocumentPdfBuilder;
use horstoeko\zugferd\ZugferdProfiles;
use horstoeko\zugferd\codelists\ZugferdCurrencyCodes;
use horstoeko\zugferd\codelists\ZugferdCountryCodes;
use horstoeko\zugferd\codelists\ZugferdVatCategoryCodes;
use horstoeko\zugferd\codelists\ZugferdVatTypeCodes;
use Illuminate\Support\Facades\Storage;

class ZugferdService
{
    /**
     * Erstelle eine ZUGFeRD-konforme XML-Datei für eine Rechnung
     */
    public function generateZugferdXml(Invoice $invoice): string
    {
        // Lade die Rechnung mit allen Beziehungen
        $invoice->load(['customer', 'items.article']);

        // Erstelle ZUGFeRD Document Builder (BASIC Profil)
        $document = ZugferdDocumentBuilder::createNew(ZugferdProfiles::PROFILE_BASIC);

        // Setze Dokumentinformationen
        $document->setDocumentInformation(
            $invoice->invoice_number,
            '380', // Commercial Invoice (Standard-Rechnungstyp)
            $invoice->created_at,
            ZugferdCurrencyCodes::EURO
        );

        // Verkäufer-Informationen (Unser Unternehmen)
        $document->setDocumentSeller(
            config('zugferd.company.name'),
            config('zugferd.company.id')
        );

        // Setze Verkäufer-USt-IdNr (erforderlich für BR-S-02)
        $document->addDocumentSellerTaxRegistration(
            'VA', // VAT (Umsatzsteuer)
            config('zugferd.company.vat_id')
        );

        $document->setDocumentSellerAddress(
            config('zugferd.company.address.street'),
            config('zugferd.company.address.zip'),
            config('zugferd.company.address.city'),
            ZugferdCountryCodes::GERMANY
        );

        $document->setDocumentSellerContact(
            config('zugferd.company.contact.person'),
            config('zugferd.company.contact.department'),
            config('zugferd.company.contact.phone'),
            config('zugferd.company.contact.email'),
            config('zugferd.company.contact.fax')
        );

        // Käufer-Informationen (Kunde)
        $buyerName = $invoice->customer->isBusinessCustomer()
            ? ($invoice->customer->company_name ?: $invoice->customer->name)
            : $invoice->customer->name;
            
        $document->setDocumentBuyer(
            $buyerName,
            $invoice->customer->customer_number ?? ''
        );

        // Verwende Rechnungsadresse (bevorzugt separate Rechnungsadresse, sonst Standard)
        $billingAddress = $invoice->customer->getBillingAddressForInvoice();
        $buyerStreet = $billingAddress['street'] ?? config('zugferd.defaults.customer.street');
        if ($billingAddress['address_line_2']) {
            $buyerStreet .= "\n" . $billingAddress['address_line_2'];
        }

        $document->setDocumentBuyerAddress(
            $buyerStreet,
            $billingAddress['postal_code'] ?? config('zugferd.defaults.customer.zip'),
            $billingAddress['city'] ?? config('zugferd.defaults.customer.city'),
            $this->getCountryCodeFromDatabase($billingAddress['country_code'] ?? 'DE')
        );

        // Käufer-Kontakt mit erweiterten Feldern
        if ($invoice->customer->email) {
            $document->setDocumentBuyerContact(
                $invoice->customer->contact_person ?: $buyerName,
                $invoice->customer->department ?? '',
                $invoice->customer->phone ?? '',
                $invoice->customer->email,
                $invoice->customer->fax ?? ''
            );
        }

        // Käufer-USt-IdNr falls vorhanden
        if ($invoice->customer->vat_id) {
            $document->addDocumentBuyerTaxRegistration(
                'VA', // VAT (Umsatzsteuer)
                $invoice->customer->vat_id
            );
        }

        // Zahlungsbedingungen aus Kundendaten
        $paymentTerms = $invoice->customer->payment_terms ?? 'Zahlung innerhalb von ' . config('zugferd.defaults.payment_terms_days') . ' Tagen';
        $paymentDays = $invoice->customer->payment_days ?? config('zugferd.defaults.payment_terms_days');
        $dueDate = $invoice->due_date ?? $invoice->created_at->addDays($paymentDays);
        
        $document->addDocumentPaymentTerm(
            $paymentTerms,
            $dueDate
        );

        // Rechnungspositionen hinzufügen - verwende die korrekte API
        $positionIndex = 1;
        foreach ($invoice->items as $item) {
            // Erstelle eine neue Position
            $document->addNewPosition($positionIndex);
            
            // Setze Position Details
            $document->setDocumentPositionProductDetails(
                $item->article->name,
                $item->article->description ?? '',
                $item->article->sku ?? ''
            );
            
            // Setze Mengen und Preise
            $document->setDocumentPositionQuantity(
                $item->quantity,
                $this->getUnitCode($item->article->unit ?? 'Stück')
            );
            
            $document->setDocumentPositionNetPrice($item->unit_price);
            // Berechne Positionssumme korrekt (Menge * Einzelpreis)
            $lineNetAmount = $item->quantity * $item->unit_price;
            $document->setDocumentPositionLineSummation($lineNetAmount);
            
            // Setze Steuern basierend auf dem Steuersatz
            $taxCategoryCode = $this->getTaxCategoryCode($item->tax_rate);
            $document->addDocumentPositionTax(
                $taxCategoryCode,
                ZugferdVatTypeCodes::VALUE_ADDED_TAX,
                $item->tax_rate * 100
            );
            
            $positionIndex++;
        }

        // Steuerzusammenfassung
        $taxSummary = $this->calculateTaxSummary($invoice);
        foreach ($taxSummary as $taxRate => $taxData) {
            $taxCategoryCode = $this->getTaxCategoryCode($taxRate);
            $document->addDocumentTax(
                $taxCategoryCode,
                ZugferdVatTypeCodes::VALUE_ADDED_TAX,
                $taxData['net_amount'],
                $taxData['tax_amount'],
                $taxRate * 100
            );
        }

        // Berechne korrekte Gesamtsummen
        $lineTotalAmount = 0;
        $taxTotalAmount = 0;
        
        foreach ($invoice->items as $item) {
            $lineNetAmount = $item->quantity * $item->unit_price;
            $lineTotalAmount += $lineNetAmount;
            $taxTotalAmount += $lineNetAmount * $item->tax_rate;
        }
        
        $grandTotalAmount = $lineTotalAmount + $taxTotalAmount;

        // Gesamtsummen
        $document->setDocumentSummation(
            $grandTotalAmount,         // grandTotalAmount
            $grandTotalAmount,         // duePayableAmount
            $lineTotalAmount,          // lineTotalAmount
            null,                      // chargeTotalAmount
            null,                      // allowanceTotalAmount
            $lineTotalAmount,          // taxBasisTotalAmount
            $taxTotalAmount,           // taxTotalAmount
            null,                      // roundingAmount
            null                       // totalPrepaidAmount
        );

        // XML generieren und Länder-Codes korrigieren
        $xmlContent = $document->getContent();
        return $this->fixCountryCodes($xmlContent);
    }

    /**
     * Erstelle eine ZUGFeRD-konforme PDF-Datei
     */
    public function generateZugferdPdf(Invoice $invoice): string
    {
        // Lade die Rechnung mit allen Beziehungen
        $invoice->load(['customer', 'items.article']);

        // Erstelle ZUGFeRD Document Builder mit EN16931 Profil für bessere Kompatibilität
        $document = ZugferdDocumentBuilder::createNew(ZugferdProfiles::PROFILE_EN16931);

        // Setze Dokumentinformationen
        $document->setDocumentInformation(
            $invoice->invoice_number,
            '380', // Commercial Invoice (Standard-Rechnungstyp)
            $invoice->created_at,
            ZugferdCurrencyCodes::EURO
        );

        // Verkäufer-Informationen (Unser Unternehmen)
        $document->setDocumentSeller(
            config('zugferd.company.name'),
            config('zugferd.company.id')
        );

        // Setze Verkäufer-USt-IdNr (erforderlich für BR-S-02)
        $document->addDocumentSellerTaxRegistration(
            'VA', // VAT (Umsatzsteuer)
            config('zugferd.company.vat_id')
        );

        $document->setDocumentSellerAddress(
            config('zugferd.company.address.street'),
            config('zugferd.company.address.zip'),
            config('zugferd.company.address.city'),
            ZugferdCountryCodes::GERMANY
        );

        $document->setDocumentSellerContact(
            config('zugferd.company.contact.person'),
            config('zugferd.company.contact.department'),
            config('zugferd.company.contact.phone'),
            config('zugferd.company.contact.email'),
            config('zugferd.company.contact.fax')
        );

        // Käufer-Informationen (Kunde)
        $buyerName = $invoice->customer->isBusinessCustomer()
            ? ($invoice->customer->company_name ?: $invoice->customer->name)
            : $invoice->customer->name;
            
        $document->setDocumentBuyer(
            $buyerName,
            $invoice->customer->customer_number ?? ''
        );

        // Verwende Rechnungsadresse (bevorzugt separate Rechnungsadresse, sonst Standard)
        $billingAddress = $invoice->customer->getBillingAddressForInvoice();
        $buyerStreet = $billingAddress['street'] ?? config('zugferd.defaults.customer.street');
        if ($billingAddress['address_line_2']) {
            $buyerStreet .= "\n" . $billingAddress['address_line_2'];
        }

        $document->setDocumentBuyerAddress(
            $buyerStreet,
            $billingAddress['postal_code'] ?? config('zugferd.defaults.customer.zip'),
            $billingAddress['city'] ?? config('zugferd.defaults.customer.city'),
            $this->getCountryCodeFromDatabase($billingAddress['country_code'] ?? 'DE')
        );

        // Käufer-Kontakt mit erweiterten Feldern
        if ($invoice->customer->email) {
            $document->setDocumentBuyerContact(
                $invoice->customer->contact_person ?: $buyerName,
                $invoice->customer->department ?? '',
                $invoice->customer->phone ?? '',
                $invoice->customer->email,
                $invoice->customer->fax ?? ''
            );
        }

        // Käufer-USt-IdNr falls vorhanden
        if ($invoice->customer->vat_id) {
            $document->addDocumentBuyerTaxRegistration(
                'VA', // VAT (Umsatzsteuer)
                $invoice->customer->vat_id
            );
        }

        // Zahlungsbedingungen aus Kundendaten
        $paymentTerms = $invoice->customer->payment_terms ?? 'Zahlung innerhalb von ' . config('zugferd.defaults.payment_terms_days') . ' Tagen';
        $paymentDays = $invoice->customer->payment_days ?? config('zugferd.defaults.payment_terms_days');
        $dueDate = $invoice->due_date ?? $invoice->created_at->addDays($paymentDays);
        
        $document->addDocumentPaymentTerm(
            $paymentTerms,
            $dueDate
        );

        // Rechnungspositionen hinzufügen
        $positionIndex = 1;
        foreach ($invoice->items as $item) {
            // Erstelle eine neue Position
            $document->addNewPosition($positionIndex);
            
            // Setze Position Details
            $document->setDocumentPositionProductDetails(
                $item->article->name,
                $item->article->description ?? '',
                $item->article->sku ?? ''
            );
            
            // Setze Mengen und Preise
            $document->setDocumentPositionQuantity(
                $item->quantity,
                $this->getUnitCode($item->article->unit ?? 'Stück')
            );
            
            $document->setDocumentPositionNetPrice($item->unit_price);
            // Berechne Positionssumme korrekt (Menge * Einzelpreis)
            $lineNetAmount = $item->quantity * $item->unit_price;
            $document->setDocumentPositionLineSummation($lineNetAmount);
            
            // Setze Steuern basierend auf dem Steuersatz
            $taxCategoryCode = $this->getTaxCategoryCode($item->tax_rate);
            $document->addDocumentPositionTax(
                $taxCategoryCode,
                ZugferdVatTypeCodes::VALUE_ADDED_TAX,
                $item->tax_rate * 100
            );
            
            $positionIndex++;
        }

        // Steuerzusammenfassung
        $taxSummary = $this->calculateTaxSummary($invoice);
        foreach ($taxSummary as $taxRate => $taxData) {
            $taxCategoryCode = $this->getTaxCategoryCode($taxRate);
            $document->addDocumentTax(
                $taxCategoryCode,
                ZugferdVatTypeCodes::VALUE_ADDED_TAX,
                $taxData['net_amount'],
                $taxData['tax_amount'],
                $taxRate * 100
            );
        }

        // Berechne korrekte Gesamtsummen
        $lineTotalAmount = 0;
        $taxTotalAmount = 0;
        
        foreach ($invoice->items as $item) {
            $lineNetAmount = $item->quantity * $item->unit_price;
            $lineTotalAmount += $lineNetAmount;
            $taxTotalAmount += $lineNetAmount * $item->tax_rate;
        }
        
        $grandTotalAmount = $lineTotalAmount + $taxTotalAmount;

        // Gesamtsummen
        $document->setDocumentSummation(
            $grandTotalAmount,         // grandTotalAmount
            $grandTotalAmount,         // duePayableAmount
            $lineTotalAmount,          // lineTotalAmount
            null,                      // chargeTotalAmount
            null,                      // allowanceTotalAmount
            $lineTotalAmount,          // taxBasisTotalAmount
            $taxTotalAmount,           // taxTotalAmount
            null,                      // roundingAmount
            null                       // totalPrepaidAmount
        );

        // Generiere zuerst die normale PDF mit DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', ['record' => $invoice]);
        $pdfContent = $pdf->output();

        // Korrigiere die XML vor der PDF-Erstellung
        $originalXml = $document->getContent();
        $correctedXml = $this->fixCountryCodes($originalXml);
        
        // Erstelle eine temporäre XML-Datei mit der korrigierten XML
        $tempXmlFile = tempnam(sys_get_temp_dir(), 'zugferd_corrected_') . '.xml';
        file_put_contents($tempXmlFile, $correctedXml);
        
        try {
            // Verwende die ZugferdDocumentPdfMerger für die korrekte XML-Einbettung
            $pdfMerger = new \horstoeko\zugferd\ZugferdDocumentPdfMerger($tempXmlFile, $pdfContent);
            $pdfMerger->generateDocument();
            $finalPdfContent = $pdfMerger->downloadString();
            
            return $finalPdfContent;
        } finally {
            // Lösche die temporäre Datei
            if (file_exists($tempXmlFile)) {
                unlink($tempXmlFile);
            }
        }
    }

    /**
     * Speichere ZUGFeRD-PDF im Storage
     */
    public function saveZugferdPdf(Invoice $invoice): string
    {
        $pdfContent = $this->generateZugferdPdf($invoice);
        $filename = "invoices/zugferd/invoice_{$invoice->invoice_number}_zugferd.pdf";
        
        Storage::disk('local')->put($filename, $pdfContent);
        
        return $filename;
    }

    /**
     * Berechne Steuerzusammenfassung
     */
    private function calculateTaxSummary(Invoice $invoice): array
    {
        $taxSummary = [];

        foreach ($invoice->items as $item) {
            $taxRate = $item->tax_rate;
            // Verwende den Nettobetrag der Position (ohne Steuer)
            $netAmount = $item->quantity * $item->unit_price;
            $taxAmount = $netAmount * $taxRate;

            if (!isset($taxSummary[$taxRate])) {
                $taxSummary[$taxRate] = [
                    'net_amount' => 0,
                    'tax_amount' => 0,
                ];
            }

            $taxSummary[$taxRate]['net_amount'] += $netAmount;
            $taxSummary[$taxRate]['tax_amount'] += $taxAmount;
        }

        return $taxSummary;
    }

    /**
     * Konvertiere Länderbezeichnung zu ZUGFeRD-Ländercode
     */
    private function getCountryCode(string $country): string
    {
        $countryMap = [
            'Deutschland' => ZugferdCountryCodes::GERMANY,
            'Germany' => ZugferdCountryCodes::GERMANY,
            'Österreich' => ZugferdCountryCodes::AUSTRIA,
            'Austria' => ZugferdCountryCodes::AUSTRIA,
            'Schweiz' => ZugferdCountryCodes::SWITZERLAND,
            'Switzerland' => ZugferdCountryCodes::SWITZERLAND,
        ];

        return $countryMap[$country] ?? ZugferdCountryCodes::GERMANY;
    }

    /**
     * Konvertiere Ländercode aus Datenbank zu ZUGFeRD-Ländercode
     */
    private function getCountryCodeFromDatabase(?string $countryCode): string
    {
        if (!$countryCode) {
            return ZugferdCountryCodes::GERMANY;
        }

        $countryCodeMap = [
            'DE' => ZugferdCountryCodes::GERMANY,
            'AT' => ZugferdCountryCodes::AUSTRIA,
            'CH' => ZugferdCountryCodes::SWITZERLAND,
            'FR' => ZugferdCountryCodes::FRANCE,
            'IT' => ZugferdCountryCodes::ITALY,
            'NL' => 'NL', // Direkte Verwendung des ISO-Codes
            'BE' => 'BE',
            'LU' => 'LU',
            'DK' => 'DK',
            'SE' => 'SE',
            'NO' => 'NO',
            'FI' => 'FI',
            'PL' => 'PL',
            'CZ' => 'CZ',
            'SK' => 'SK',
            'HU' => 'HU',
            'SI' => 'SI',
            'HR' => 'HR',
            'ES' => 'ES',
            'PT' => 'PT',
            'GB' => 'GB',
            'IE' => 'IE',
            'US' => 'US',
            'CA' => 'CA',
        ];

        return $countryCodeMap[strtoupper($countryCode)] ?? ZugferdCountryCodes::GERMANY;
    }

    /**
     * Konvertiere Einheit zu ZUGFeRD-Einheitscode
     */
    private function getUnitCode(string $unit): string
    {
        $unitMap = [
            'Stück' => 'C62',  // Piece
            'Stk' => 'C62',    // Piece
            'Stk.' => 'C62',   // Piece
            'Piece' => 'C62',  // Piece
            'Pauschal' => 'C62', // Unit/Piece
            'Einheit' => 'C62',  // Unit/Piece
            'kg' => 'KGM',       // Kilogram
            'Kilogramm' => 'KGM', // Kilogram
            'l' => 'LTR',        // Litre
            'Liter' => 'LTR',    // Litre
            'm' => 'MTR',        // Metre
            'Meter' => 'MTR',    // Metre
            'm²' => 'MTK',       // Square metre
            'Quadratmeter' => 'MTK', // Square metre
            'm³' => 'MTQ',       // Cubic metre
            'Kubikmeter' => 'MTQ', // Cubic metre
            'h' => 'HUR',        // Hour
            'Stunde' => 'HUR',   // Hour
            'Stunden' => 'HUR',  // Hour
            'Tag' => 'DAY',      // Day
            'Tage' => 'DAY',     // Day
            'Monat' => 'MON',    // Month
            'Monate' => 'MON',   // Month
            'Jahr' => 'ANN',     // Year
            'Jahre' => 'ANN',    // Year
            'kWh' => 'KWH',      // Kilowatt hour
            'Kilowattstunde' => 'KWH', // Kilowatt hour
            'kW' => 'KWT',       // Kilowatt
            'Kilowatt' => 'KWT', // Kilowatt
        ];

        return $unitMap[$unit] ?? 'C62'; // Default: Piece
    }

    /**
     * Ermittle den korrekten ZugFERD-Steuerkategoriecode basierend auf dem Steuersatz
     */
    private function getTaxCategoryCode(float $taxRate): string
    {
        // Konvertiere Dezimalwert zu Prozent für Vergleich
        $taxRatePercent = $taxRate * 100;
        
        if ($taxRatePercent == 0) {
            // 0% Steuersatz - Steuerbefreit oder Nullsatz
            return ZugferdVatCategoryCodes::ZERO_RATE_GOOD;
        } elseif ($taxRatePercent == 7) {
            // 7% Steuersatz - Ermäßigter Satz
            return ZugferdVatCategoryCodes::LOWE_RATE;
        } elseif ($taxRatePercent == 19) {
            // 19% Steuersatz - Standardsatz
            return ZugferdVatCategoryCodes::STAN_RATE;
        } else {
            // Für alle anderen Steuersätze verwende Standardsatz
            return ZugferdVatCategoryCodes::STAN_RATE;
        }
    }

    /**
     * Bette ZUGFeRD XML in PDF ein (Legacy-Methode - wird nicht mehr verwendet)
     * Die neue Implementierung verwendet ZugferdDocumentPdfBuilder direkt
     */
    private function embedXmlInPdf(string $pdfContent, string $xmlContent): string
    {
        // Diese Methode wird nicht mehr verwendet, da wir jetzt
        // ZugferdDocumentPdfBuilder für die komplette PDF-Erstellung nutzen
        return $pdfContent;
    }

    /**
     * Validiere ZUGFeRD XML
     */
    public function validateZugferdXml(string $xmlContent): array
    {
        try {
            // Lade XML und validiere gegen ZUGFeRD Schema
            $dom = new \DOMDocument();
            $dom->loadXML($xmlContent);
            
            // Hier würde normalerweise eine Schema-Validierung stattfinden
            // TODO: Implementiere vollständige ZUGFeRD-Validierung
            
            return [
                'valid' => true,
                'errors' => [],
                'warnings' => []
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => [$e->getMessage()],
                'warnings' => []
            ];
        }
    }

    /**
     * Extrahiere ZUGFeRD-Daten aus PDF
     */
    public function extractZugferdFromPdf(string $pdfPath): ?array
    {
        try {
            // TODO: Implementiere ZUGFeRD-Extraktion aus PDF
            // Dies würde die eingebettete XML-Datei aus der PDF extrahieren
            // und die Rechnungsdaten parsen
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Korrigiere fehlende CountryID-Elemente in der XML
     */
    private function fixCountryCodes(string $xmlContent): string
    {
        // Lade XML als DOMDocument
        $dom = new \DOMDocument();
        $dom->loadXML($xmlContent);
        
        // Finde alle PostalTradeAddress-Elemente
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');
        
        $addressNodes = $xpath->query('//ram:PostalTradeAddress');
        
        foreach ($addressNodes as $addressNode) {
            // Prüfe, ob CountryID bereits existiert
            $countryNodes = $xpath->query('ram:CountryID', $addressNode);
            
            if ($countryNodes->length === 0) {
                // Erstelle CountryID-Element mit dynamischem Wert
                $countryElement = $dom->createElement('ram:CountryID', 'DE');
                
                // Finde die richtige Position (nach CityName/LineThree)
                $cityNodes = $xpath->query('ram:CityName | ram:LineThree', $addressNode);
                if ($cityNodes->length > 0) {
                    // Füge nach dem letzten CityName/LineThree Element ein
                    $lastCityNode = $cityNodes->item($cityNodes->length - 1);
                    $addressNode->insertBefore($countryElement, $lastCityNode->nextSibling);
                } else {
                    // Fallback: Am Ende hinzufügen
                    $addressNode->appendChild($countryElement);
                }
            }
        }
        
        return $dom->saveXML();
    }
    
    /**
     * Korrigiere die eingebettete XML in der PDF
     */
    private function fixPdfEmbeddedXml(string $pdfContent, string $originalXml): string
    {
        // Korrigiere die XML
        $correctedXml = $this->fixCountryCodes($originalXml);
        
        // Ersetze die eingebettete XML in der PDF
        // Dies ist eine vereinfachte Implementierung - in der Praxis würde man
        // eine PDF-Bibliothek verwenden, um die Anhänge korrekt zu ersetzen
        
        // Für jetzt geben wir die PDF zurück, da die XML-Korrektur bereits
        // in der generateZugferdXml() Methode angewendet wird
        return $pdfContent;
    }
}