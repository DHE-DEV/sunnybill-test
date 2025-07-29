<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SolarPlantBilling;
use App\Services\SolarPlantBillingPdfService;

echo "=== Test der PDF-Generierung ===" . PHP_EOL;

$billingId = '0198568d-2e48-71c9-8145-95e70e504bf3';
$billing = SolarPlantBilling::find($billingId);

if (!$billing) {
    echo "❌ Billing mit ID {$billingId} nicht gefunden!" . PHP_EOL;
    exit(1);
}

echo "✅ Billing gefunden: {$billing->id}" . PHP_EOL;

// Prüfe ob Beschreibungen in den Breakdown-Daten vorhanden sind
echo PHP_EOL . "=== BREAKDOWN-DATEN PRÜFEN ===" . PHP_EOL;
$hasDescriptions = false;

if (!empty($billing->credit_breakdown)) {
    foreach ($billing->credit_breakdown as $i => $credit) {
        if (!empty($credit['billing_description']) && $credit['billing_description'] !== 'LEER') {
            echo "✅ Credit #{$i} hat Beschreibung: " . substr($credit['billing_description'], 0, 50) . "..." . PHP_EOL;
            $hasDescriptions = true;
        }
    }
}

if (!empty($billing->cost_breakdown)) {
    foreach ($billing->cost_breakdown as $i => $cost) {
        if (!empty($cost['billing_description']) && $cost['billing_description'] !== 'LEER') {
            echo "✅ Cost #{$i} hat Beschreibung: " . substr($cost['billing_description'], 0, 50) . "..." . PHP_EOL;
            $hasDescriptions = true;
        }
    }
}

if (!$hasDescriptions) {
    echo "❌ Keine Beschreibungen in den Breakdown-Daten gefunden!" . PHP_EOL;
}

// Teste PDF Service
echo PHP_EOL . "=== PDF SERVICE TEST ===" . PHP_EOL;
try {
    $pdfService = new SolarPlantBillingPdfService();
    
    // Bereite PDF-Daten vor (verwende die private preparePdfData Methode indirekt)
    $companySetting = \App\Models\CompanySetting::current();
    
    // Verwende Reflection um auf private Methode zuzugreifen
    $reflection = new ReflectionClass($pdfService);
    $method = $reflection->getMethod('preparePdfData');
    $method->setAccessible(true);
    $pdfData = $method->invoke($pdfService, $billing, $companySetting);
    
    echo "✅ PDF-Daten erfolgreich vorbereitet" . PHP_EOL;
    echo "PDF-Daten enthalten:" . PHP_EOL;
    echo "- Billing: " . ($pdfData['billing'] ? 'Yes' : 'No') . PHP_EOL;
    echo "- Customer: " . ($pdfData['customer'] ? 'Yes' : 'No') . PHP_EOL;
    echo "- Solar Plant: " . ($pdfData['solarPlant'] ? 'Yes' : 'No') . PHP_EOL;
    
    // Prüfe ob die Breakdown-Daten in den PDF-Daten enthalten sind
    echo PHP_EOL . "=== PDF-DATEN BREAKDOWN PRÜFEN ===" . PHP_EOL;
    $pdfBilling = $pdfData['billing'];
    
    if (!empty($pdfBilling->credit_breakdown)) {
        foreach ($pdfBilling->credit_breakdown as $i => $credit) {
            if (!empty($credit['billing_description'])) {
                echo "✅ PDF Credit #{$i}: " . substr($credit['billing_description'], 0, 50) . "..." . PHP_EOL;
            }
        }
    }
    
    if (!empty($pdfBilling->cost_breakdown)) {
        foreach ($pdfBilling->cost_breakdown as $i => $cost) {
            if (!empty($cost['billing_description'])) {
                echo "✅ PDF Cost #{$i}: " . substr($cost['billing_description'], 0, 50) . "..." . PHP_EOL;
            }
        }
    }
    
    // Versuche das PDF zu generieren und prüfe den HTML-Inhalt
    echo PHP_EOL . "=== HTML TEMPLATE TEST ===" . PHP_EOL;
    
    // Rendere das Blade Template direkt
    $htmlContent = view('pdf.solar-plant-billing', $pdfData)->render();
    
    // Prüfe ob die Beschreibungen im HTML enthalten sind
    $testDescriptions = [
        'Gutschrift node.energy Einspeisung MaLo 06-2025',
        'weiterberechnetes Dienstleistungsentgelt 06-2025',
        'Zusätzliche, außerordentliche Gutschrift'
    ];
    
    foreach ($testDescriptions as $desc) {
        if (strpos($htmlContent, $desc) !== false) {
            echo "✅ Beschreibung '{$desc}' im HTML gefunden" . PHP_EOL;
        } else {
            echo "❌ Beschreibung '{$desc}' NICHT im HTML gefunden" . PHP_EOL;
        }
    }
    
    // Suche nach dem spezifischen Blade-Code für Beschreibungen
    if (strpos($htmlContent, 'billing_description') !== false) {
        echo "✅ 'billing_description' im HTML Template gefunden" . PHP_EOL;
    } else {
        echo "❌ 'billing_description' NICHT im HTML Template gefunden" . PHP_EOL;
    }
    
    // Suche nach dem <em> Tag für Beschreibungen
    if (strpos($htmlContent, '<em style="color: #666; font-size: 8pt;">') !== false) {
        echo "✅ Beschreibungs-Styling im HTML gefunden" . PHP_EOL;
    } else {
        echo "❌ Beschreibungs-Styling NICHT im HTML gefunden" . PHP_EOL;
    }
    
    echo PHP_EOL . "=== PDF GENERIERUNG TEST ===" . PHP_EOL;
    $pdfContent = $pdfService->generateBillingPdf($billing);
    echo "✅ PDF erfolgreich generiert (" . strlen($pdfContent) . " Bytes)" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Fehler beim PDF-Test: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== Test beendet ===" . PHP_EOL;
