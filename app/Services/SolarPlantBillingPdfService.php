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
    public function generateBillingPdf(SolarPlantBilling $billing, CompanySetting $companySetting = null): string
    {
        $companySetting = $companySetting ?? CompanySetting::current();

        // Two-Pass Rendering für korrekte Seitenzahlen:

        // 1. Pass: PDF ohne Gesamtseitenzahl generieren um Seitenzahl zu ermitteln
        $data = $this->preparePdfData($billing, $companySetting);
        $data['totalPages'] = 0; // Erstmal 0 setzen

        $pdf = Pdf::loadView('pdf.solar-plant-billing', $data);
        $this->configurePdf($pdf);

        // Seitenzahl aus erster PDF extrahieren
        $tempPdfContent = $pdf->output();
        $totalPages = $this->extractPageCount($tempPdfContent);

        // 2. Pass: PDF mit korrekter Gesamtseitenzahl generieren
        $data['totalPages'] = $totalPages;

        $finalPdf = Pdf::loadView('pdf.solar-plant-billing', $data);
        $this->configurePdf($finalPdf);

        return $finalPdf->output();
    }

    /**
     * Konfiguriert die PDF-Einstellungen
     */
    protected function configurePdf($pdf): void
    {
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'Arial',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
            'debugKeepTemp' => false,
        ]);
    }

    /**
     * Extrahiert die Seitenzahl aus dem PDF-Inhalt
     */
    private function extractPageCount(string $pdfContent): int
    {
        try {
            // Methode 1: Suche nach /Count in Pages-Objekten (am präzisesten)
            if (preg_match('/\/Type\s*\/Pages[^}]*\/Count\s*(\d+)/', $pdfContent, $matches)) {
                return (int) $matches[1];
            }

            // Methode 2: Zähle Kids array references (sehr zuverlässig)
            if (preg_match('/\/Kids\s*\[([^\]]*)\]/', $pdfContent, $kidsMatch)) {
                $kidsContent = $kidsMatch[1];
                $pageRefs = preg_match_all('/(\d+)\s+0\s+R/', $kidsContent, $refMatches);
                if ($pageRefs > 0) {
                    return $pageRefs;
                }
            }

            // Methode 3: Zähle tatsächliche Page-Objekte (nicht /Type /Pages)
            $pageRefs = preg_match_all('/(\d+)\s+0\s+obj\s*<<[^>]*\/Type\s*\/Page[^s]/', $pdfContent, $matches);
            if ($pageRefs > 0) {
                return $pageRefs;
            }

            // Fallback: Mindestens 1 Seite
            return 1;
        } catch (\Exception $e) {
            // Bei Fehlern Fallback auf 1 Seite
            return 1;
        }
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
    protected function preparePdfData(SolarPlantBilling $billing, CompanySetting $companySetting): array
    {
        $customer = $billing->customer;
        $solarPlant = $billing->solarPlant;

        // Beteiligungsprozentsatz und kWp aus der aktuellen participation Tabelle holen
        $participation = $solarPlant?->participations()
            ->where('customer_id', $customer->id)
            ->first();

        $currentPercentage = $participation ? $participation->percentage : $billing->participation_percentage;
        $currentParticipationKwp = $participation ? $participation->participation_kwp : null;

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
            'currentParticipationKwp' => $currentParticipationKwp,
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
        $plantName = $this->sanitizeForFilename($solarPlant?->name ?? 'Unbekannt');

        // Kundennamen bereinigen
        $customerName = $customer->customer_type === 'business' && $customer->company_name
            ? $customer->company_name
            : $customer->name;
        $customerName = $this->sanitizeForFilename($customerName);

        $statusSuffix = match ($billing->status) {
            'cancelled' => '_STORNIERT',
            'draft' => '_ENTWURF',
            default => '',
        };

        return sprintf(
            '%04d-%02d_%s_%s_%s%s.pdf',
            $billing->billing_year,
            $billing->billing_month,
            $plantName,
            $customerName,
            $billing->invoice_number,
            $statusSuffix
        );
    }

    /**
     * Erstellt eine Download-Response für die PDF
     */
    public function downloadBillingPdf(SolarPlantBilling $billing)
    {
        $pdfContent = $this->generateBillingPdf($billing);
        $filename = $this->generatePdfFilename($billing);

        return response()->streamDownload(
            fn () => print($pdfContent),
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
