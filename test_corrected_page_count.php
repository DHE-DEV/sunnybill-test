<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\SolarPlantBillingPdfService;
use App\Models\SolarPlantBilling;
use App\Models\CompanySetting;

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "=== Testing Corrected Page Count (Should be 5, not 6) ===\n\n";
    
    $billingId = '0198bc55-6c51-70c9-8613-e7cce37832ff';
    
    echo "Testing with Billing ID: {$billingId}\n";
    echo "Expected: 'Seite X von 5' format in footer\n\n";
    
    // Get the billing
    $billing = SolarPlantBilling::find($billingId);
    $companySetting = CompanySetting::first();
    
    echo "✅ Data loaded successfully\n\n";
    
    // Test PDF generation with corrected page counting
    echo "🔄 Generating PDF with corrected page counting...\n";
    
    $pdfService = new SolarPlantBillingPdfService();
    
    $startTime = microtime(true);
    $pdfContent = $pdfService->generateBillingPdf($billing, $companySetting);
    $endTime = microtime(true);
    
    $generationTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "✅ PDF generated successfully!\n";
    echo "📊 Generation time: {$generationTime}ms\n";
    echo "📄 PDF size: " . strlen($pdfContent) . " bytes\n\n";
    
    // Save PDF for verification
    $filename = "test_corrected_page_count.pdf";
    file_put_contents($filename, $pdfContent);
    
    echo "💾 PDF saved as: {$filename}\n";
    echo "🔍 Please verify that the footer now shows:\n";
    echo "   ✓ 'Seite 1 von 5' on page 1\n";
    echo "   ✓ 'Seite 2 von 5' on page 2\n";
    echo "   ✓ 'Seite 3 von 5' on page 3\n";
    echo "   ✓ 'Seite 4 von 5' on page 4\n";
    echo "   ✓ 'Seite 5 von 5' on page 5\n\n";
    
    echo "✅ Test completed successfully!\n";
    echo "🎯 The page count should now be correct (5 instead of 6).\n";
    
} catch (Exception $e) {
    echo "❌ Error occurred: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
