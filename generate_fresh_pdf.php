<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SolarPlantBilling;
use App\Services\SolarPlantBillingPdfService;

echo "=== Neue PDF generieren ===" . PHP_EOL;

$billingId = '0198568d-2e48-71c9-8145-95e70e504bf3';
$billing = SolarPlantBilling::find($billingId);

if (!$billing) {
    echo "❌ Billing nicht gefunden!" . PHP_EOL;
    exit(1);
}

try {
    $pdfService = new SolarPlantBillingPdfService();
    
    // Generiere PDF
    $pdfContent = $pdfService->generateBillingPdf($billing);
    
    // Speichere PDF
    $filename = 'test_billing_' . date('Y-m-d_H-i-s') . '.pdf';
    file_put_contents($filename, $pdfContent);
    
    echo "✅ Neue PDF erfolgreich generiert: {$filename}" . PHP_EOL;
    echo "📁 Dateigröße: " . number_format(strlen($pdfContent)) . " Bytes" . PHP_EOL;
    echo "🕒 Zeitstempel: " . date('Y-m-d H:i:s') . PHP_EOL;
    echo PHP_EOL;
    echo "Bitte öffnen Sie diese frisch generierte PDF-Datei:" . PHP_EOL;
    echo "📄 {$filename}" . PHP_EOL;
    echo PHP_EOL;
    echo "Die Beschreibungen sollten auf Seite 2 in den Aufschlüsselungs-Tabellen zu sehen sein." . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . PHP_EOL;
}
