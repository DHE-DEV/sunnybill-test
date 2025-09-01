<?php

namespace App\Http\Controllers;

use App\Models\SolarPlantBilling;
use App\Services\EpcQrCodeService;
use Illuminate\Http\Request;

class SolarPlantBillingController extends Controller
{
    protected $epcQrCodeService;

    public function __construct(EpcQrCodeService $epcQrCodeService)
    {
        $this->epcQrCodeService = $epcQrCodeService;
    }

    public function printQrCode(SolarPlantBilling $solarPlantBilling)
    {
        // Check if QR code can be generated for this billing
        if (!$this->epcQrCodeService->canGenerateQrCode($solarPlantBilling)) {
            return redirect()->back()->with('error', 'QR-Code kann für diese Abrechnung nicht generiert werden.');
        }

        // Generate QR code (base64 encoded image)
        $qrCodeImage = $this->epcQrCodeService->generateEpcQrCode($solarPlantBilling);
        
        // Get customer and billing data
        $customer = $solarPlantBilling->customer;
        $solarPlant = $solarPlantBilling->solarPlant;
        $amount = abs($solarPlantBilling->net_amount);
        
        // Generate reference/purpose
        $reference = $this->epcQrCodeService->getDefaultReference($solarPlantBilling);
        
        // Prepare data structure that the template expects
        $qrCodeData = [
            'qrCode' => $qrCodeImage,
            'data' => [
                'beneficiaryName' => $customer->account_holder,
                'beneficiaryAccount' => strtoupper(str_replace(' ', '', $customer->iban)),
                'beneficiaryBIC' => $customer->bic ?: '',
                'remittanceInformation' => $reference,
                'amount' => $amount,
            ]
        ];
        
        return view('print.qr-code-banking', [
            'solarPlantBilling' => $solarPlantBilling,
            'qrCodeData' => $qrCodeData,
        ]);
    }
}
