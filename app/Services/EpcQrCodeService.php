<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use App\Models\SolarPlantBilling;

class EpcQrCodeService
{
    /**
     * Prüft ob QR-Code generiert werden kann
     */
    public function canGenerateQrCode(SolarPlantBilling $billing): bool
    {
        $customer = $billing->customer;
        
        return $customer && 
               $customer->iban && 
               $customer->account_holder && 
               $billing->net_amount != 0;
    }
    
    /**
     * Gibt Fehlermeldung zurück warum QR-Code nicht generiert werden kann
     */
    public function getQrCodeErrorMessage(SolarPlantBilling $billing): string
    {
        $customer = $billing->customer;
        
        if (!$customer) {
            return 'Kein Kunde zugeordnet.';
        }
        
        if (!$customer->iban) {
            return 'IBAN nicht hinterlegt.';
        }
        
        if (!$customer->account_holder) {
            return 'Kontoinhaber nicht hinterlegt.';
        }
        
        if ($billing->net_amount == 0) {
            return 'QR-Code kann nicht für Betrag 0 generiert werden.';
        }
        
        return 'QR-Code kann nicht generiert werden.';
    }

    /**
     * Generiert einen EPC-QR-Code für eine Solaranlagen-Abrechnung
     */
    public function generateEpcQrCode(SolarPlantBilling $billing): string
    {
        return $this->generateDynamicEpcQrCode($billing);
    }

    /**
     * Generiert einen EPC-QR-Code mit dynamischen Daten (für Live-Updates in Formularen)
     */
    public function generateDynamicEpcQrCode(SolarPlantBilling $billing, ?float $amount = null, ?string $reference = null): string
    {
        if (!$this->canGenerateQrCode($billing)) {
            throw new \Exception($this->getQrCodeErrorMessage($billing));
        }

        $customer = $billing->customer;
        
        // Verwende übergebenen Betrag oder Standard
        $finalAmount = $amount !== null ? abs($amount) : abs($billing->net_amount);
        
        // Verwende übergebene Referenz oder generiere Standard
        if ($reference !== null) {
            $finalReference = $reference;
        } else {
            $solarPlant = $billing->solarPlant;
            $referenceArray = [];
            if ($billing->invoice_number) {
                $referenceArray[] = $billing->invoice_number;
            }
            if ($customer && $customer->customer_number) {
                $referenceArray[] = "Kunde: {$customer->customer_number}";
            }
            if ($solarPlant && $solarPlant->name) {
                $referenceArray[] = $solarPlant->name;
            }
            
            // Zeitraum hinzufügen
            $month = \Carbon\Carbon::createFromDate($billing->billing_year, $billing->billing_month, 1);
            $referenceArray[] = "Zeitraum: " . $month->locale('de')->translatedFormat('m/Y');
            
            $finalReference = implode(' | ', $referenceArray);
        }
        
        return $this->generateEpcQrCodeData(
            $customer->account_holder,
            $customer->iban,
            $customer->bic,
            $finalAmount,
            $finalReference
        );
    }

    /**
     * Generiert den Standard-Verwendungszweck für eine Abrechnung
     */
    public function getDefaultReference(SolarPlantBilling $billing): string
    {
        $customer = $billing->customer;
        $solarPlant = $billing->solarPlant;
        
        $reference = [];
        if ($billing->invoice_number) {
            $reference[] = $billing->invoice_number;
        }
        if ($customer && $customer->customer_number) {
            $reference[] = "Kunde: {$customer->customer_number}";
        }
        if ($solarPlant && $solarPlant->name) {
            $reference[] = $solarPlant->name;
        }
        
        // Zeitraum hinzufügen
        $month = \Carbon\Carbon::createFromDate($billing->billing_year, $billing->billing_month, 1);
        $reference[] = "Zeitraum: " . $month->locale('de')->translatedFormat('m/Y');
        
        return implode(' | ', $reference);
    }

    /**
     * Generiert EPC QR-Code Daten
     */
    private function generateEpcQrCodeData(string $accountHolder, string $iban, ?string $bic, float $amount, string $reference): string
    {
        // EPC QR-Code Datenstruktur
        $lines = [
            'BCD',                              // Service Tag
            '002',                              // Version
            '1',                                // Character Set (UTF-8)
            'SCT',                              // Identification (SEPA Credit Transfer)
            $bic ?: '',                         // BIC (kann leer sein)
            $accountHolder,                     // Account Holder Name
            $this->formatIban($iban),           // IBAN (ohne Leerzeichen)
            'EUR' . number_format($amount, 2, '.', ''), // Currency + Amount
            '',                                 // Purpose (leer)
            '',                                 // Structured Reference (leer)
            $reference,                         // Unstructured Reference/Verwendungszweck
            ''                                  // Beneficiary to originator information (leer)
        ];
        
        $epcData = implode("\n", $lines);
        
        // QR-Code generieren
        $qrCode = new QrCode(
            $epcData,
            new Encoding('UTF-8'),
            ErrorCorrectionLevel::Medium,
            300, // size
            10   // margin
        );
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        return base64_encode($result->getString());
    }
    
    /**
     * Formatiert IBAN für QR-Code (entfernt Leerzeichen)
     */
    private function formatIban(string $iban): string
    {
        return strtoupper(str_replace(' ', '', $iban));
    }
}
