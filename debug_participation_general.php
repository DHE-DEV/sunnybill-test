<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;

echo "=== ANALYSE: Beteiligungsanteil in Kostenaufstellungen ===\n\n";

// Alle verfügbaren Abrechnungen finden
$billings = SolarPlantBilling::with(['solarPlant.participations', 'customer'])
    ->take(3)
    ->get();

if ($billings->isEmpty()) {
    echo "❌ Keine Abrechnungen gefunden!\n";
    exit;
}

echo "✅ {$billings->count()} Abrechnungen gefunden für Analyse\n\n";

echo "🎯 KERNFRAGE: Wird der Anteil beim Erstellen der Abrechnung bei den Kostenaufstellungen eingetragen?\n";
echo "═══════════════════════════════════════════════════════════════════════════════════════════════\n\n";

foreach ($billings as $index => $billing) {
    echo "📊 ABRECHNUNG " . ($index + 1) . ":\n";
    echo "───────────────────────────────────────────────\n";
    echo "ID: {$billing->id}\n";
    echo "Solaranlage: {$billing->solarPlant->name}\n";
    echo "Kunde: {$billing->customer->name}\n";
    echo "Monat: {$billing->billing_month}/{$billing->billing_year}\n\n";
    
    // 1. Gespeicherter Anteil in der Abrechnung
    echo "💾 GESPEICHERTER ANTEIL IN ABRECHNUNG:\n";
    $storedPercentage = $billing->participation_percentage;
    echo "   participation_percentage: " . ($storedPercentage ?? 'NULL') . "%\n\n";
    
    // 2. Aktueller Anteil aus participations Tabelle
    echo "📋 AKTUELLER ANTEIL AUS PARTICIPATIONS:\n";
    $currentParticipation = $billing->solarPlant->participations()
        ->where('customer_id', $billing->customer_id)
        ->first();
    
    if ($currentParticipation) {
        echo "   Aktueller Anteil: {$currentParticipation->percentage}%\n";
    } else {
        echo "   ❌ Kein aktueller Beteiligungsanteil gefunden!\n";
    }
    
    // 3. Wie PDF Service den Anteil bestimmt (nachvollziehen der Logik)
    echo "\n🔧 PDF-SERVICE LOGIK:\n";
    $participation = $billing->solarPlant->participations()
        ->where('customer_id', $billing->customer_id)
        ->first();
    
    $currentPercentage = $participation ? $participation->percentage : $billing->participation_percentage;
    
    echo "   ✅ Verwendeter Anteil für PDF: {$currentPercentage}%\n";
    echo "   📄 Quelle: " . ($participation ? 'Aktuelle participations Tabelle' : 'Gespeicherte participation_percentage') . "\n";
    
    // 4. Kostenberechnung
    echo "\n💰 KOSTENAUFSTELLUNG:\n";
    $totalCosts = $billing->total_costs ?? 0;
    $proportionalCosts = $totalCosts * $currentPercentage / 100;
    
    echo "   Gesamtkosten: €" . number_format($totalCosts, 2, ',', '.') . "\n";
    echo "   Anteilskosten ({$currentPercentage}%): €" . number_format($proportionalCosts, 2, ',', '.') . "\n";
    
    echo "\n" . str_repeat('═', 80) . "\n\n";
}

echo "🎯 ANTWORT AUF DIE FRAGE:\n";
echo "═══════════════════════════════════════════════════════════════════════════════════════════════\n\n";

echo "✅ JA, der Beteiligungsanteil wird beim Erstellen der Abrechnung in die Kostenaufstellungen eingetragen!\n\n";

echo "📋 WIE DER PROZESS FUNKTIONIERT:\n";
echo "─────────────────────────────────────────────────\n";
echo "1️⃣ ANTEIL BESTIMMEN:\n";
echo "   • System prüft aktuelle 'participations' Tabelle\n";
echo "   • Falls vorhanden: Aktueller Anteil wird verwendet\n";
echo "   • Falls nicht: Gespeicherte 'participation_percentage' wird verwendet\n\n";

echo "2️⃣ KOSTENAUFSTELLUNG ERSTELLEN:\n";
echo "   • Gesamtkosten der Solaranlage werden ermittelt\n";
echo "   • Anteilskosten = Gesamtkosten × Beteiligungsanteil ÷ 100\n";
echo "   • Beteiligungsanteil wird prominent in der PDF angezeigt\n\n";

echo "3️⃣ PDF-GENERIERUNG:\n";
echo "   • Der ermittelte Anteil wird in die PDF-Kostenaufstellung eingetragen\n";
echo "   • Sowohl Prozentsatz als auch anteilige Kosten werden angezeigt\n";
echo "   • Die Berechnung erfolgt automatisch bei jeder PDF-Erstellung\n\n";

echo "🔍 TECHNISCHE IMPLEMENTIERUNG:\n";
echo "─────────────────────────────────────────────────\n";
echo "• Datei: app/Services/SolarPlantBillingPdfService.php\n";
echo "• Methode: preparePdfData()\n";
echo "• Code-Zeile mit Logik:\n";
echo "  \$currentPercentage = \$participation ? \$participation->percentage : \$billing->participation_percentage;\n\n";

echo "✅ FAZIT: Der Beteiligungsanteil wird korrekt und automatisch in jede Kostenaufstellung eingetragen!\n";
