<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use App\Models\SolarPlantBilling;

class EpcQrCodeService
{
    /**
     * Generiert einen EPC QR-Code für Banking-Apps
     * 
     * @param SolarPlantBilling $billing
     * @return string Base64-encoded PNG
     */
    public function generateEpcQrCode(SolarPlantBilling $billing): string
    {
        $customer = $billing->customer;
        $solarPlant = $billing->solarPlant;
        
        // Validierung der notwendigen Daten
        if (!$customer->iban || !$customer->account_holder) {
            throw new \Exception('IBAN und Kontoinhaber müssen hinterlegt sein, um einen QR-Code zu generieren.');
        }
        
        if ($billing->net_amount == 0) {
            throw new \Exception('QR-Code kann nicht für Betrag 0 generiert werden.');
        }
        
        // Betrag immer als positiver Wert verwenden (auch bei Gutschriften)
        $amount = abs($billing->net_amount);
        
        // Verwendungszweck zusammenstellen
        $reference = $this->buildPaymentReference($billing, $solarPlant, $customer);
        
        // EPC QR-Code Datenformat erstellen
        $epcData = $this->buildEpcData(
            bic: $customer->bic ?: '',
            accountHolder: $customer->account_holder,
            iban: $customer->iban,
            amount: $amount,
            reference: $reference
        );
        
        // QR-Code generieren
        $qrCode = new QrCode($epcData);
        $qrCode->setEncoding(new Encoding('UTF-8'));
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::Medium);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $qrCode->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        return base64_encode($result->getString());
    }
    
    /**
     * Erstellt den Verwendungszweck für die Zahlung
     */
    private function buildPaymentReference(SolarPlantBilling $billing, $solarPlant, $customer): string
    {
        $parts = [];
        
        // Kundennummer (statt UUID)
        if ($customer && $customer->customer_number) {
            $parts[] = 'Kunde: ' . $customer->customer_number;
        }
        
        // Solaranlage Name
        if ($solarPlant && $solarPlant->name) {
            $parts[] = $solarPlant->name;
        }
        
        // Rechnungsnummer
        if ($billing->invoice_number) {
            $parts[] = 'Rechnung: ' . $billing->invoice_number;
        }
        
        // Zeitraum
        $month = \Carbon\Carbon::createFromDate($billing->billing_year, $billing->billing_month, 1);
        $parts[] = 'Zeitraum: ' . $month->locale('de')->translatedFormat('m/Y');
        
        $reference = implode(' | ', $parts);
        
        // EPC Standard erlaubt max. 140 Zeichen für Verwendungszweck
        if (strlen($reference) > 140) {
            $reference = substr($reference, 0, 137) . '...';
        }
        
        return $reference;
    }
    
    /**
     * Erstellt die EPC QR-Code Datenstruktur
     * 
     * EPC QR-Code Format:
     * BCD = Service Tag
     * 002 = Version
     * 1 = Character Set (UTF-8)
     * SCT = Identification
     * BIC = Bank Identifier Code (optional)
     * Name = Account Holder Name
     * IBAN = International Bank Account Number
     * EUR[Amount] = Currency and Amount
     * [Reference] = Payment Reference
     */
    private function buildEpcData(string $bic, string $accountHolder, string $iban, float $amount, string $reference): string
    {
        $lines = [
            'BCD',                              // Service Tag
            '002',                              // Version
            '1',                                // Character Set (UTF-8)
            'SCT',                              // Identification (SEPA Credit Transfer)
            $bic,                               // BIC (kann leer sein)
            $accountHolder,                     // Account Holder Name
            $this->formatIban($iban),           // IBAN (ohne Leerzeichen)
            'EUR' . number_format($amount, 2, '.', ''), // Currency + Amount
            '',                                 // Purpose (leer)
            '',                                 // Structured Reference (leer, da wir unstructured nutzen)
            $reference,                         // Unstructured Reference/Verwendungszweck
            ''                                  // Beneficiary to originator information (leer)
        ];
        
        return implode("\n", $lines);
    }
    
    /**
     * Formatiert IBAN für QR-Code (entfernt Leerzeichen)
     */
    private function formatIban(string $iban): string
    {
        return strtoupper(str_replace(' ', '', $iban));
    }
    
    /**
     * Validiert ob alle notwendigen Daten für QR-Code vorhanden sind
     */
    public function canGenerateQrCode(SolarPlantBilling $billing): bool
    {
        $customer = $billing->customer;
        
        return $customer->iban && 
               $customer->account_holder && 
               $billing->net_amount != 0; // Auch negative Beträge (Gutschriften) erlauben
    }
    
    /**
     * Gibt Fehlermeldung zurück falls QR-Code nicht generiert werden kann
     */
    public function getQrCodeErrorMessage(SolarPlantBilling $billing): ?string
    {
        $customer = $billing->customer;
        $errors = [];
        
        if (!$customer->iban) {
            $errors[] = 'IBAN fehlt';
        }
        
        if (!$customer->account_holder) {
            $errors[] = 'Kontoinhaber fehlt';
        }
        
        if ($billing->net_amount == 0) {
            $errors[] = 'Betrag ist 0';
        }
        
        if (empty($errors)) {
            return null;
        }
        
        return 'QR-Code kann nicht generiert werden: ' . implode(', ', $errors);
    }
}
