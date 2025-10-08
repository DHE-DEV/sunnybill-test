<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BulkPdfDownloadController extends Controller
{
    public function index(Request $request)
    {
        $downloads = session('bulk_pdf_downloads', []);
        $successCount = session('bulk_pdf_success_count', 0);
        $errorCount = session('bulk_pdf_error_count', 0);
        $batchId = session('bulk_pdf_batch_id');

        // Clear session data after loading
        session()->forget(['bulk_pdf_downloads', 'bulk_pdf_success_count', 'bulk_pdf_error_count', 'bulk_pdf_batch_id']);

        return view('bulk-pdf-download', compact('downloads', 'successCount', 'errorCount', 'batchId'));
    }

    public function printQrCodes(Request $request)
    {
        $billingIds = session('bulk_qr_billing_ids', []);
        $successCount = session('bulk_qr_success_count', 0);
        $errorCount = session('bulk_qr_error_count', 0);

        // Lade die Abrechnungen und bereite QR-Code-Daten vor
        $qrCodeData = [];
        $epcQrCodeService = new \App\Services\EpcQrCodeService();

        foreach ($billingIds as $billingId) {
            $billing = \App\Models\SolarPlantBilling::with(['solarPlant', 'customer'])->find($billingId);

            if (!$billing || !$epcQrCodeService->canGenerateQrCode($billing)) {
                continue;
            }

            try {
                $customer = $billing->customer;

                // Generate QR code (base64 encoded image)
                $qrCodeImage = $epcQrCodeService->generateEpcQrCode($billing);

                // Get customer and billing data for QR code
                $amount = abs($billing->net_amount);

                // Generate reference/purpose
                $reference = $epcQrCodeService->getDefaultReference($billing);

                // Prepare data structure that the template expects
                $qrCodeData[] = [
                    'solarPlantBilling' => $billing,
                    'customer' => $customer,
                    'qrCodeData' => [
                        'qrCode' => $qrCodeImage,
                        'data' => [
                            'beneficiaryName' => $customer->account_holder,
                            'beneficiaryAccount' => strtoupper(str_replace(' ', '', $customer->iban)),
                            'beneficiaryBIC' => $customer->bic ?: '',
                            'remittanceInformation' => $reference,
                            'amount' => $amount,
                        ]
                    ]
                ];
            } catch (\Exception $e) {
                // Skip this billing if QR code generation fails
                continue;
            }
        }

        // Clear session data after loading
        session()->forget(['bulk_qr_billing_ids', 'bulk_qr_success_count', 'bulk_qr_error_count']);

        return view('print-qr-codes', compact('qrCodeData', 'successCount', 'errorCount'));
    }

    public function printBillings(Request $request)
    {
        $billingIds = session('bulk_billing_ids', []);
        $successCount = session('bulk_billing_success_count', 0);

        // Lade die Abrechnungen
        $billings = \App\Models\SolarPlantBilling::with([
            'solarPlant',
            'customer'
        ])->whereIn('id', $billingIds)->get();

        // Lade Company Settings
        $companySetting = \App\Models\CompanySetting::first();

        // Generiere PDFs mit DomPDF (exakt wie einzelne PDFs)
        $pdfService = new \App\Services\SolarPlantBillingPdfService();
        $pdfContents = [];

        foreach ($billings as $billing) {
            try {
                // Generiere PDF genau wie bei einzelner Abrechnung
                $pdf = $pdfService->generateBillingPdf($billing, $companySetting);
                $pdfContents[] = [
                    'content' => base64_encode($pdf),
                    'invoice_number' => $billing->invoice_number,
                ];
            } catch (\Exception $e) {
                // PDF-Generierung fehlgeschlagen - überspringe diese Abrechnung
                continue;
            }
        }

        // Clear session data after loading
        session()->forget(['bulk_billing_ids', 'bulk_billing_success_count']);

        return view('print-billings', compact('pdfContents', 'successCount'));
    }

    public function downloadCsv(Request $request)
    {
        $tempPath = session('csv_download_path');
        $filename = session('csv_download_filename', 'export.csv');

        // Clear session data
        session()->forget(['csv_download_path', 'csv_download_filename']);

        if (!$tempPath || !Storage::disk('public')->exists($tempPath)) {
            abort(404, 'CSV-Datei nicht gefunden');
        }

        $fullPath = Storage::disk('public')->path($tempPath);

        // Download und dann Datei löschen
        return response()->download($fullPath, $filename)->deleteFileAfterSend(true);
    }
}
