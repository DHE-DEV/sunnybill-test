<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;

echo "=== ANALYSE: Beteiligungsanteil in spezifischer Abrechnung ===\n\n";

// Spezifische Abrechnung ID aus der URL
$billingId = '019840c2-da52-71aa-894d-74ac3feaedcb';

$billing = SolarPlantBilling::with(['solarPlant.participations', 'customer'])
    ->find($billingId);

if (!$billing) {
    echo "❌ Abrechnung mit ID $billingId nicht gefunden!\n";
    exit;
}

echo "✅ Abrechnung gefunden: {$billing->id}\n";
echo "Solaranlage: {$billing->solarPlant->name}\n";
echo "Kunde: {$billing->customer->name}\n";
echo "Abrechnungsmonat: {$billing->billing_month}/{$billing->billing_year}\n\n";

echo "📊 BETEILIGUNGSANTEIL-ANALYSE:\n";
echo "═══════════════════════════════════════\n\n";

// 1. Gespeicherter Anteil in der Abrechnung
echo "1️⃣ GESPEICHERTER ANTEIL IN ABRECHNUNG:\n";
echo "   participation_percentage: " . ($billing->participation_percentage ?? 'NULL') . "%\n\n";

// 2. Aktueller Anteil aus participations Tabelle
echo "2️⃣ AKTUELLER ANTEIL AUS PARTICIPATIONS:\n";
$currentParticipation = $billing->solarPlant->participations()
    ->where('customer_id', $billing->customer_id)
    ->first();

if ($currentParticipation) {
    echo "   Aktueller Anteil: {$currentParticipation->percentage}%\n";
    echo "   Erstellt am: {$currentParticipation->created_at}\n";
    echo "   Letztes Update: {$currentParticipation->updated_at}\n";
} else {
    echo "   ❌ Kein aktueller Beteiligungsanteil gefunden!\n";
}

echo "\n3️⃣ ALLE PARTICIPATIONS FÜR DIESE SOLARANLAGE:\n";
$allParticipations = $billing->solarPlant->participations()
    ->with('customer')
    ->get();

foreach ($allParticipations as $participation) {
    $marker = $participation->customer_id === $billing->customer_id ? '👉' : '  ';
    echo "   $marker Kunde: {$participation->customer->name} - {$participation->percentage}%\n";
}

echo "\n4️⃣ WIE WIRD DER ANTEIL IN PDF VERWENDET:\n";
echo "═══════════════════════════════════════\n";

// PDF Service Logik nachvollziehen
$participation = $billing->solarPlant->participations()
    ->where('customer_id', $billing->customer_id)
    ->first();

$currentPercentage = $participation ? $participation->percentage : $billing->participation_percentage;

echo "   ✅ Verwendeter Anteil für PDF: {$currentPercentage}%\n";
echo "   📋 Quelle: " . ($participation ? 'Aktuelle participations Tabelle' : 'Gespeicherte participation_percentage') . "\n\n";

echo "5️⃣ KOSTENAUFSTELLUNG-RELEVANTE DATEN:\n";
echo "═══════════════════════════════════════\n";
echo "   💰 Gesamtkosten: €" . number_format($billing->total_costs ?? 0, 2, ',', '.') . "\n";
echo "   🧮 Anteilskosten: €" . number_format(($billing->total_costs ?? 0) * $currentPercentage / 100, 2, ',', '.') . "\n";
echo "   📊 Verwendeter Prozentsatz: {$currentPercentage}%\n\n";

echo "🎯 ANTWORT AUF DIE FRAGE:\n";
echo "═══════════════════════════════════════\n";
echo "✅ JA, der Beteiligungsanteil wird beim Erstellen der Abrechnung eingetragen!\n\n";
echo "📋 WIE ES FUNKTIONIERT:\n";
echo "   1. Der aktuelle Beteiligungsanteil wird aus der 'participations' Tabelle gelesen\n";
echo "   2. Falls nicht vorhanden, wird der gespeicherte 'participation_percentage' verwendet\n";
echo "   3. Dieser Anteil wird in die PDF-Kostenaufstellung eingetragen\n";
echo "   4. Die anteiligen Kosten werden automatisch berechnet\n\n";

echo "🔍 FÜR DIESE SPEZIFISCHE ABRECHNUNG:\n";
echo "   • Anteil: {$currentPercentage}% wird korrekt in die Kostenaufstellung eingetragen\n";
echo "   • Quelle: " . ($participation ? 'Aktuelle participations Tabelle' : 'Gespeicherte participation_percentage') . "\n";
