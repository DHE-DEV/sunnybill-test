<?php

require_once 'vendor/autoload.php';

use App\Models\SolarPlantBilling;
use App\Services\SolarPlantBillingPdfService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING CORRECTED ADDRESS IN PDF ===\n\n";

// ID aus der URL: admin/solar-plant-billings/019840c8-5157-7316-aa18-adc39afa124e
$billingId = '019840c8-5157-7316-aa18-adc39afa124e';

$billing = SolarPlantBilling::find($billingId);

if (!$billing) {
    echo "Billing not found!\n";
    exit(1);
}

echo "Generating PDF for billing: {$billing->id}\n";
echo "Customer: {$billing->customer->name}\n";
echo "Street: {$billing->customer->street}\n";
echo "Postal/City: {$billing->customer->postal_code} {$billing->customer->city}\n\n";

$pdfService = new SolarPlantBillingPdfService();

try {
    $pdfPath = $pdfService->saveBillingPdf($billing);
    echo "✅ PDF successfully generated and saved: {$pdfPath}\n";
    
    // Full path for local file check
    $fullPath = storage_path('app/public/' . $pdfPath);
    
    // Check if file exists and get size
    if (file_exists($fullPath)) {
        $fileSize = filesize($fullPath);
        echo "File size: " . number_format($fileSize) . " bytes\n";
        echo "Full path: {$fullPath}\n";
        echo "You can now check the PDF to see if the address is correctly formatted.\n";
    } else {
        echo "❌ PDF file not found at: {$fullPath}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error generating PDF: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
