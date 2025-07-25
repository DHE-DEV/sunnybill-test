<?php

namespace App\Services;

use App\Models\SolarPlantBilling;
use App\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class SolarPlantBillingPdfService
{
    /**
     * Generiert eine PDF-Abrechnung für eine Solaranlagen-Beteiligung
     */
    public function generateBillingPdf(SolarPlantBilling $billing): string
    {
        $companySetting = CompanySetting::current();
        
        // Daten für die PDF vorbereiten
        $data = $this->preparePdfData($billing, $companySetting);
        
        // PDF generieren
        $pdf = Pdf::loadView('pdf.solar-plant-billing', $data);
        
        // PDF-Konfiguration
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'Arial',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
            'debugKeepTemp' => false,
        ]);
        
        // PDF als String zurückgeben
        return $pdf->output();
    }

    /**
     * Speichert die PDF-Abrechnung und gibt den Pfad zurück
     */
    public function saveBillingPdf(SolarPlantBilling $billing): string
    {
        $pdfContent = $this->generateBillingPdf($billing);
        
        // Dateiname generieren
        $filename = $this->generatePdfFilename($billing);
        $path = "billing-pdfs/{$filename}";
        
        // PDF speichern
        Storage::disk('public')->put($path, $pdfContent);
        
        return $path;
    }

    /**
     * Bereitet die Daten für die PDF-Generierung vor
     */
    private function preparePdfData(SolarPlantBilling $billing, CompanySetting $companySetting): array
    {
        $customer = $billing->customer;
        $solarPlant = $billing->solarPlant;
        
        // Beteiligungsprozentsatz aus der aktuellen participation Tabelle holen
        $participation = $solarPlant->participations()
            ->where('customer_id', $customer->id)
            ->first();
        
        $currentPercentage = $participation ? $participation->percentage : $billing->participation_percentage;
        
        // Monatsnamen
        $monthNames = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
        ];
        
        // Logo als base64 für PDF konvertieren
        $logoBase64 = null;
        if ($companySetting->hasLogo()) {
            try {
                $logoPath = storage_path('app/public/' . $companySetting->logo_path);
                if (file_exists($logoPath)) {
                    $logoContent = file_get_contents($logoPath);
                    $mimeType = mime_content_type($logoPath);
                    $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoContent);
                }
            } catch (\Exception $e) {
                // Logo konnte nicht geladen werden - wird ignoriert
            }
        }
        
        return [
            'billing' => $billing,
            'customer' => $customer,
            'solarPlant' => $solarPlant,
            'companySetting' => $companySetting,
            'currentPercentage' => $currentPercentage,
            'monthName' => $monthNames[$billing->billing_month],
            'billingDate' => Carbon::createFromDate($billing->billing_year, $billing->billing_month, 1),
            'generatedAt' => now(),
            'logoBase64' => $logoBase64,
        ];
    }

    /**
     * Generiert einen Dateinamen für die PDF
     */
    private function generatePdfFilename(SolarPlantBilling $billing): string
    {
        $customer = $billing->customer;
        $solarPlant = $billing->solarPlant;
        
        // Solaranlagen-Namen bereinigen (Leerzeichen durch Bindestriche ersetzen)
        $plantName = $this->sanitizeForFilename($solarPlant->name);
        
        // Kundennamen bereinigen
        $customerName = $customer->customer_type === 'business' && $customer->company_name 
            ? $customer->company_name 
            : $customer->name;
        $customerName = $this->sanitizeForFilename($customerName);
        
        return sprintf(
            '%04d-%02d_%s_%s.pdf',
            $billing->billing_year,
            $billing->billing_month,
            $plantName,
            $customerName
        );
    }

    /**
     * Erstellt eine Download-Response für die PDF
     */
    public function downloadBillingPdf(SolarPlantBilling $billing)
    {
        // Lade alle notwendigen Beziehungen
        $billing->load(['solarPlant', 'customer']);

        $companySetting = CompanySetting::first();
        if (!$companySetting) {
            throw new \Exception('Firmeneinstellungen nicht gefunden');
        }

        // Aktueller Beteiligungsanteil aus der participation Tabelle
        $currentParticipation = $billing->solarPlant->participations()
            ->where('customer_id', $billing->customer_id)
            ->first();
        
        $currentPercentage = $currentParticipation 
            ? $currentParticipation->percentage 
            : $billing->participation_percentage;

        // Generiere aktuelles Datum
        $generatedAt = now();
        
        // Monatsnamen
        $monthNames = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
        ];
        
        $monthName = $monthNames[$billing->billing_month];

        // PDF generieren
        $pdf = Pdf::loadView('pdf.solar-plant-billing', [
            'billing' => $billing,
            'solarPlant' => $billing->solarPlant,
            'customer' => $billing->customer,
            'companySetting' => $companySetting,
            'currentPercentage' => $currentPercentage,
            'generatedAt' => $generatedAt,
            'monthName' => $monthName,
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'dpi' => 150,
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ]);

        // Dateiname generieren (neues Format: Solaranlagen-Name_Kunden-Name_YYYY-MM.pdf)
        $filename = $this->generatePdfFilename($billing);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename
        );
    }

    /**
     * Bereinigt Dateinamen von ungültigen Zeichen
     */
    private function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9\-_]/', '_', $filename);
    }

    /**
     * Bereinigt Text für Dateinamen (behält Bindestriche, ersetzt Leerzeichen)
     */
    private function sanitizeForFilename(string $text): string
    {
        // Entferne führende/trailing Leerzeichen
        $text = trim($text);
        
        // Ersetze mehrere Leerzeichen durch eines
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Ersetze Leerzeichen durch Bindestriche
        $text = str_replace(' ', '-', $text);
        
        // Entferne ungültige Zeichen (behalte Buchstaben, Zahlen, Bindestriche)
        $text = preg_replace('/[^a-zA-Z0-9\-äöüÄÖÜß]/', '', $text);
        
        // Entferne mehrfache Bindestriche
        $text = preg_replace('/-+/', '-', $text);
        
        // Entferne Bindestriche am Anfang und Ende
        $text = trim($text, '-');
        
        return $text;
    }

    /**
     * Erstellt eine Inline-Anzeige-Response für die PDF
     */
    public function previewBillingPdf(SolarPlantBilling $billing): \Illuminate\Http\Response
    {
        $pdfContent = $this->generateBillingPdf($billing);
        $filename = $this->generatePdfFilename($billing);
        
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
