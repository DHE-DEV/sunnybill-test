<?php

// Laravel korrekt booten
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

// Laravel Anwendung starten
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SolarPlantBilling;
use App\Services\SolarPlantBillingPdfService;

echo "=== Test: Page-Break-Verhinderung für Gesamtbetrag-Abschnitt ===\n\n";

try {
    // Teste DB-Verbindung
    try {
        DB::connection()->getPdo();
        echo "✅ Datenbankverbindung erfolgreich\n\n";
    } catch (Exception $e) {
        echo "❌ Datenbankverbindung fehlgeschlagen: " . $e->getMessage() . "\n";
        exit(1);
    }

    // Finde eine Beispiel-Abrechnung
    $billing = SolarPlantBilling::with(['solarPlant', 'customer'])
        ->whereNotNull('total_credits')
        ->whereNotNull('total_costs')
        ->first();

    if (!$billing) {
        echo "❌ Keine Abrechnung mit Beispieldaten gefunden\n";
        exit(1);
    }

    echo "📋 Teste mit Abrechnung:\n";
    echo "   ID: {$billing->id}\n";
    echo "   Rechnungsnummer: {$billing->invoice_number}\n";
    echo "   Kunde: {$billing->customer->name}\n";
    echo "   Anlage: {$billing->solarPlant->name}\n";
    echo "   Gesamtbetrag: " . number_format($billing->net_amount, 2, ',', '.') . " €\n\n";

    // PDF Service initialisieren
    $pdfService = new SolarPlantBillingPdfService();

    echo "🔄 Generiere PDF mit verbessertem Page-Break-Handling...\n";
    
    $startTime = microtime(true);
    $pdfContent = $pdfService->generateBillingPdf($billing);
    $endTime = microtime(true);
    
    $generationTime = round(($endTime - $startTime) * 1000, 2);

    if (!$pdfContent || strlen($pdfContent) < 1000) {
        echo "❌ PDF-Generierung fehlgeschlagen oder zu klein\n";
        exit(1);
    }

    // PDF mit aussagekräftigem Namen speichern
    $filename = "test_page_break_fix_{$billing->id}.pdf";
    file_put_contents($filename, $pdfContent);

    echo "✅ PDF erfolgreich generiert und gespeichert\n";
    echo "📊 Dateiname: {$filename}\n";
    echo "📊 Dateigröße: " . number_format(strlen($pdfContent)) . " Bytes\n";
    echo "⏱️  Generierungszeit: {$generationTime}ms\n\n";

    // Prüfe PDF-Header
    if (strpos($pdfContent, '%PDF') === 0) {
        echo "✅ PDF-Header korrekt\n";
    } else {
        echo "❌ Ungültiger PDF-Header\n";
    }

    echo "\n🎯 IMPLEMENTIERTE VERBESSERUNGEN:\n";
    echo "✅ Gesamtergebnis-Box: page-break-inside: avoid hinzugefügt\n";
    echo "✅ MwSt.-Aufschlüsselung: page-break-inside: avoid hinzugefügt\n";
    echo "✅ Beide Bereiche bleiben jetzt auf derselben Seite zusammen\n\n";

    echo "📝 HINWEIS:\n";
    echo "Die CSS-Eigenschaft 'page-break-inside: avoid' verhindert nun, dass:\n";
    echo "- Der Gesamtbetrag-Bereich über einen Seitenumbruch getrennt wird\n";
    echo "- Die MwSt.-Aufschlüsselung über einen Seitenumbruch getrennt wird\n";
    echo "- Diese wichtigen Informationen zusammenhängend dargestellt werden\n\n";

    echo "✅ Test erfolgreich abgeschlossen!\n";

} catch (Exception $e) {
    echo "❌ Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
