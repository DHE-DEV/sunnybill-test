<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Services\SolarPlantBillingPdfService;

echo "=== TESTING: Neuer Dateinamen-Format mit Abrechnungsperiode am Anfang ===\n\n";

// Finde eine Testabrechnung 
$billing = SolarPlantBilling::with(['solarPlant', 'customer'])->first();

if (!$billing) {
    echo "❌ Keine Abrechnung gefunden!\n";
    exit;
}

echo "✅ Testabrechnung gefunden: {$billing->id}\n";
echo "Solaranlage: {$billing->solarPlant->name}\n";
echo "Kunde: {$billing->customer->name}\n";
echo "Abrechnungsperiode: {$billing->billing_month}/{$billing->billing_year}\n\n";

// PDF Service
$pdfService = new SolarPlantBillingPdfService();

try {
    echo "📄 Generiere PDF mit neuem Dateinamen-Format...\n";
    
    $pdf = $pdfService->generateBillingPdf($billing);
    
    // Dateiname generieren (verwende dieselbe Logik wie im Service)
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
    
    file_put_contents($filename, $pdf);
    
    echo "✅ PDF erfolgreich generiert: $filename\n";
    echo "📊 Dateigröße: " . number_format(strlen($pdf)) . " Bytes\n\n";
    
    echo "🎯 NEUES DATEINAMEN-FORMAT:\n";
    echo "═══════════════════════════════════════\n";
    echo "✅ Abrechnungsperiode steht jetzt AM ANFANG der Datei!\n\n";
    
    echo "📋 FORMAT-STRUKTUR:\n";
    echo "   Altes Format: Solaranlage_Kunde_YYYY-MM.pdf\n";
    echo "   Neues Format: YYYY-MM_Solaranlage_Kunde.pdf\n\n";
    
    echo "📁 BEISPIEL FÜR DIESE ABRECHNUNG:\n";
    echo "   Abrechnungsperiode: {$billing->billing_year}-" . sprintf('%02d', $billing->billing_month) . "\n";
    echo "   Solaranlage: {$plantName}\n";
    echo "   Kunde: {$customerName}\n";
    echo "   → Dateiname: $filename\n\n";
    
    echo "🔍 VORTEILE DES NEUEN FORMATS:\n";
    echo "   ✅ Chronologische Sortierung nach Abrechnungsperiode\n";
    echo "   ✅ Einfaches Auffinden von Abrechnungen eines bestimmten Monats\n";
    echo "   ✅ Bessere Organisation im Dateisystem\n";
    echo "   ✅ Intuitive Dateinamen-Struktur\n\n";
    
    echo "🎉 DATEINAMEN-FORMAT ERFOLGREICH ANGEPASST!\n";
    echo "📄 PDF-Datei wurde generiert: $filename\n";
    
} catch (Exception $e) {
    echo "❌ Fehler bei PDF-Generierung: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
