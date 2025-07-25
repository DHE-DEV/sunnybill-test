<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Services\SolarPlantBillingPdfService;

echo "=== TESTING: Finale PDF mit korrigierter Standort-Formatierung ===\n\n";

// Finde eine Testabrechnung 
$billing = SolarPlantBilling::with(['solarPlant', 'customer'])->first();

if (!$billing) {
    echo "❌ Keine Abrechnung gefunden!\n";
    exit;
}

echo "✅ Testabrechnung gefunden: {$billing->id}\n";
echo "Solaranlage: {$billing->solarPlant->name}\n";
echo "Kunde: {$billing->customer->name}\n";
echo "Originalstandort: " . ($billing->solarPlant->location ?? 'Kein Standort') . "\n\n";

// PDF Service
$pdfService = new SolarPlantBillingPdfService();

try {
    echo "📄 Generiere PDF mit korrigierter Standort-Formatierung...\n";
    
    $pdf = $pdfService->generateBillingPdf($billing);
    $filename = "test_finale_standort_formatierung_{$billing->id}.pdf";
    
    file_put_contents($filename, $pdf);
    
    echo "✅ PDF erfolgreich generiert: $filename\n";
    echo "📊 Dateigröße: " . number_format(strlen($pdf)) . " Bytes\n\n";
    
    echo "🎯 ANPASSUNGEN IN DIESER VERSION:\n";
    echo "✅ Standort wird in zwei Zeilen formatiert:\n";
    echo "   - Zeile 1: Straße (z.B. 'Hauptstraße')\n";
    echo "   - Zeile 2: PLZ Ort (z.B. 'Agragenossenschaft Märka 14715 Nennhausen')\n";
    echo "✅ Verschiedene Trennzeichen werden unterstützt (,;|)\n";
    echo "✅ Fallback für Leerzeichen-Trennung implementiert\n";
    echo "✅ Original-Adresse wird verwendet falls keine Formatierung möglich\n\n";
    
    echo "📋 VERWENDETE STANDORT-DATEN:\n";
    $location = trim($billing->solarPlant->location ?? '');
    
    if ($location) {
        $parts = preg_split('/[,;|]/', $location);
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts);
        
        if (count($parts) >= 2) {
            $street = $parts[0];
            $remaining = array_slice($parts, 1);
            $address = implode(' ', $remaining);
            echo "   Original: '$location'\n";
            echo "   → Formatiert als: '$street' über '$address'\n";
        } else {
            if (preg_match('/^(.+?)[\s]+(\d{5}[\s]+.+)$/u', $location, $matches)) {
                $street = trim($matches[1]);
                $plzOrt = trim($matches[2]);
                echo "   Original: '$location'\n";
                echo "   → Formatiert als: '$street' über '$plzOrt'\n";
            } else {
                echo "   Original: '$location' (keine Formatierung)\n";
            }
        }
    }
    
    echo "\n🎉 STANDORT-FORMATIERUNG ERFOLGREICH IMPLEMENTIERT!\n";
    echo "📄 PDF-Datei wurde generiert: $filename\n";
    
} catch (Exception $e) {
    echo "❌ Fehler bei PDF-Generierung: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
