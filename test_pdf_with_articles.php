<?php

require_once 'vendor/autoload.php';

use App\Models\SolarPlantBilling;
use App\Services\SolarPlantBillingPdfService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING PDF GENERATION WITH ARTICLES ===\n\n";

// ID aus der URL: admin/solar-plant-billings/019840c8-5157-7316-aa18-adc39afa124e
$billingId = '019840c8-5157-7316-aa18-adc39afa124e';

$billing = SolarPlantBilling::with(['customer', 'solarPlant'])->find($billingId);

if (!$billing) {
    echo "Billing not found!\n";
    exit(1);
}

echo "Billing ID: {$billing->id}\n";
echo "Customer: {$billing->customer->name}\n";
echo "Solar Plant: {$billing->solarPlant->name}\n";
echo "Period: {$billing->billing_month}/{$billing->billing_year}\n\n";

// Prüfe die verfügbaren Artikel
echo "Credit Breakdown Articles:\n";
if (!empty($billing->credit_breakdown)) {
    foreach ($billing->credit_breakdown as $index => $credit) {
        echo "  Credit {$index}: {$credit['supplier_name']} - ";
        if (isset($credit['articles']) && !empty($credit['articles'])) {
            echo count($credit['articles']) . " Artikel\n";
        } else {
            echo "Keine Artikel\n";
        }
    }
}

echo "\nCost Breakdown Articles:\n";
if (!empty($billing->cost_breakdown)) {
    foreach ($billing->cost_breakdown as $index => $cost) {
        echo "  Cost {$index}: {$cost['supplier_name']} - ";
        if (isset($cost['articles']) && !empty($cost['articles'])) {
            echo count($cost['articles']) . " Artikel\n";
        } else {
            echo "Keine Artikel\n";
        }
    }
}

// PDF Service initialisieren
$pdfService = new SolarPlantBillingPdfService();

try {
    // PDF generieren
    echo "\nGenerating PDF...\n";
    $pdfContent = $pdfService->generateBillingPdf($billing);
    
    // PDF speichern
    $filename = "test_billing_with_articles_{$billing->id}.pdf";
    file_put_contents($filename, $pdfContent);
    
    echo "PDF generated successfully: {$filename}\n";
    echo "File size: " . number_format(strlen($pdfContent)) . " bytes\n";
    
    // Prüfe ob PDF gültig ist (beginnt mit %PDF)
    if (strpos($pdfContent, '%PDF') === 0) {
        echo "✓ PDF appears to be valid\n";
    } else {
        echo "✗ PDF may be corrupted\n";
    }
    
} catch (Exception $e) {
    echo "Error generating PDF: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
