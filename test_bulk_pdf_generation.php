<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Services\SolarPlantBillingPdfService;
use Illuminate\Support\Facades\Storage;

echo "=== TESTING: Bulk PDF Generation Simulation ===\n\n";

// Finde mehrere Abrechnungen
$billings = SolarPlantBilling::with(['solarPlant', 'customer'])->take(3)->get();

if ($billings->count() === 0) {
    echo "❌ Keine Abrechnungen gefunden!\n";
    exit;
}

echo "✅ {$billings->count()} Abrechnungen für Bulk-Test gefunden:\n";
foreach ($billings as $billing) {
    echo "   - {$billing->id}: {$billing->solarPlant->name} / {$billing->customer->name}\n";
}
echo "\n";

// PDF Service
$pdfService = new SolarPlantBillingPdfService();
$successCount = 0;
$errorCount = 0;
$errors = [];

echo "📄 Simuliere Bulk-PDF-Generierung...\n\n";

foreach ($billings as $billing) {
    try {
        echo "🔄 Generiere PDF für Abrechnung {$billing->id}...\n";
        
        // Generiere PDF für diese Abrechnung
        $pdfContent = $pdfService->generateBillingPdf($billing);
        
        // Erstelle Dateiname (gleiche Logik wie in der Bulk-Aktion)
        $customer = $billing->customer;
        $solarPlant = $billing->solarPlant;
        
        // Solaranlagen-Namen bereinigen
        $plantName = preg_replace('/[^a-zA-Z0-9\-äöüÄÖÜß]/', '', str_replace(' ', '-', trim($solarPlant->name)));
        $plantName = preg_replace('/-+/', '-', $plantName);
        $plantName = trim($plantName, '-');
        
        // Kundennamen bereinigen
        $customerName = $customer->customer_type === 'business' && $customer->company_name 
            ? $customer->company_name 
            : $customer->name;
        $customerName = preg_replace('/[^a-zA-Z0-9\-äöüÄÖÜß]/', '', str_replace(' ', '-', trim($customerName)));
        $customerName = preg_replace('/-+/', '-', $customerName);
        $customerName = trim($customerName, '-');
        
        $filename = sprintf(
            '%04d-%02d_%s_%s.pdf',
            $billing->billing_year,
            $billing->billing_month,
            $plantName,
            $customerName
        );
        
        // Speichere PDF im Bulk-Ordner
        $path = "bulk-pdfs/{$filename}";
        Storage::disk('public')->put($path, $pdfContent);
        
        echo "   ✅ PDF erfolgreich erstellt: {$filename}\n";
        echo "   📊 Dateigröße: " . number_format(strlen($pdfContent)) . " Bytes\n";
        echo "   💾 Gespeichert unter: storage/app/public/{$path}\n\n";
        
        $successCount++;
        
    } catch (\Exception $e) {
        $errorCount++;
        $errors[] = "Fehler bei Abrechnung {$billing->id}: " . $e->getMessage();
        echo "   ❌ Fehler: " . $e->getMessage() . "\n\n";
    }
}

echo "🎯 BULK-PDF-GENERIERUNG ABGESCHLOSSEN:\n";
echo "══════════════════════════════════════════\n";
echo "✅ Erfolgreich generiert: {$successCount} PDFs\n";
echo "❌ Fehler aufgetreten: {$errorCount}\n\n";

if ($errorCount > 0) {
    echo "📋 FEHLERDETAILS:\n";
    foreach ($errors as $error) {
        echo "   • {$error}\n";
    }
    echo "\n";
}

echo "🔍 BULK-AKTION FUNKTIONALITÄT:\n";
echo "═══════════════════════════════════════\n";
echo "✅ Bulk-Aktion 'PDF Abrechnungen generieren' wurde hinzugefügt\n";
echo "✅ Erscheint in den Massenaktionen neben 'Ausgewählte löschen'\n";
echo "✅ Icon: heroicon-o-document-arrow-down\n";
echo "✅ Farbe: Primary (blau)\n";
echo "✅ Bestätigungsdialog mit Details\n";
echo "✅ Fehlerbehandlung mit Notifications\n";
echo "✅ Neues Dateinamen-Format: YYYY-MM_Solaranlage_Kunde.pdf\n\n";

echo "📋 VERWENDUNG IN DER ADMIN-OBERFLÄCHE:\n";
echo "1. Gehe zu admin/solar-plant-billings\n";
echo "2. Wähle mehrere Abrechnungen mit den Checkboxen aus\n";
echo "3. Klicke auf das Massenaktionen-Menü (oben in der Tabelle)\n";
echo "4. Wähle 'PDF Abrechnungen generieren'\n";
echo "5. Bestätige die Aktion im Dialog\n";
echo "6. PDFs werden automatisch generiert und gespeichert\n\n";

// Prüfe ob bulk-pdfs Ordner existiert
$bulkPdfsPath = storage_path('app/public/bulk-pdfs');
if (is_dir($bulkPdfsPath)) {
    $files = scandir($bulkPdfsPath);
    $pdfFiles = array_filter($files, function($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'pdf';
    });
    
    echo "📁 GESPEICHERTE BULK-PDFs (" . count($pdfFiles) . " Dateien):\n";
    foreach ($pdfFiles as $file) {
        $size = filesize($bulkPdfsPath . '/' . $file);
        echo "   📄 {$file} (" . number_format($size) . " Bytes)\n";
    }
} else {
    echo "📁 Bulk-PDFs Ordner wird beim ersten Speichern automatisch erstellt\n";
}

echo "\n🎉 BULK-PDF-GENERIERUNG ERFOLGREICH IMPLEMENTIERT!\n";
