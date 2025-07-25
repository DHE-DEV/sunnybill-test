<?php

require_once 'vendor/autoload.php';

use App\Models\SolarPlantBilling;
use App\Services\SolarPlantBillingPdfService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING NEW PDF FILENAME FORMAT ===\n\n";

// ID aus der URL: admin/solar-plant-billings/019840c8-5157-7316-aa18-adc39afa124e
$billingId = '019840c8-5157-7316-aa18-adc39afa124e';

$billing = SolarPlantBilling::with(['customer', 'solarPlant'])->find($billingId);

if (!$billing) {
    echo "Billing not found!\n";
    exit(1);
}

echo "Original Data:\n";
echo "- Solar Plant Name: '{$billing->solarPlant->name}'\n";
echo "- Customer Name: '{$billing->customer->name}'\n";
echo "- Company Name: '{$billing->customer->company_name}'\n";
echo "- Period: {$billing->billing_month}/{$billing->billing_year}\n\n";

// PDF Service initialisieren
$pdfService = new SolarPlantBillingPdfService();

// Reflection verwenden, um die private generatePdfFilename Methode zu testen
$reflection = new ReflectionClass($pdfService);
$method = $reflection->getMethod('generatePdfFilename');
$method->setAccessible(true);

// Dateinamen generieren
$filename = $method->invoke($pdfService, $billing);

echo "Generated Filename: {$filename}\n";
echo "Expected Format: [Solaranlagen-Name]_[Kunden-Name]_[YYYY-MM].pdf\n\n";

// Aufschlüsselung des Dateinamens
$parts = explode('_', basename($filename, '.pdf'));
if (count($parts) >= 3) {
    echo "Filename Parts Analysis:\n";
    echo "1. Solar Plant Name: '{$parts[0]}'\n";
    echo "2. Customer Name: '{$parts[1]}'\n";
    echo "3. Period: '{$parts[2]}'\n";
    
    // Prüfe das Periodenformat (sollte YYYY-MM sein)
    if (preg_match('/^\d{4}-\d{2}$/', $parts[2])) {
        echo "✓ Period format is correct (YYYY-MM)\n";
    } else {
        echo "✗ Period format is incorrect\n";
    }
} else {
    echo "✗ Filename structure is unexpected\n";
}

// Test PDF-Generierung mit neuem Dateinamen
try {
    echo "\nGenerating PDF with new filename...\n";
    $pdfContent = $pdfService->generateBillingPdf($billing);
    
    // PDF mit neuem Dateinamen speichern
    file_put_contents($filename, $pdfContent);
    
    echo "✓ PDF saved successfully as: {$filename}\n";
    echo "File size: " . number_format(strlen($pdfContent)) . " bytes\n";
    
    // Prüfe ob PDF gültig ist
    if (strpos($pdfContent, '%PDF') === 0) {
        echo "✓ PDF appears to be valid\n";
    } else {
        echo "✗ PDF may be corrupted\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error generating PDF: " . $e->getMessage() . "\n";
}
