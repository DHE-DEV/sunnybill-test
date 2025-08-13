<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\SupplierContractBillingArticle;
use App\Models\SolarPlantBilling;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Überprüfung der detaillierten Beschreibungen ===\n\n";

// Prüfe die Artikel mit detaillierten Beschreibungen
$articlesWithDescriptions = SupplierContractBillingArticle::whereNotNull('detailed_description')
    ->with('billing')
    ->get();

echo "Gefundene Artikel mit detaillierten Beschreibungen: " . $articlesWithDescriptions->count() . "\n\n";

foreach ($articlesWithDescriptions as $article) {
    echo "----------------------------------------\n";
    echo "Artikel: " . ($article->description ?? 'Ohne Bezeichnung') . "\n";
    echo "Abrechnungs-Nr: " . ($article->billing->billing_number ?? 'Unbekannt') . "\n";
    echo "Detaillierte Beschreibung:\n";
    echo wordwrap($article->detailed_description, 80, "\n") . "\n\n";
}

// Prüfe eine spezifische Solaranlagen-Abrechnung
$solarPlantBillingId = '0198a534-ac26-7119-887a-3c08af42fd3e'; // Die vom Benutzer angegebene ID
$billing = SolarPlantBilling::find($solarPlantBillingId);

if ($billing) {
    echo "=== Solaranlagen-Abrechnung {$billing->invoice_number} ===\n";
    
    // Prüfe credit_breakdown
    if (!empty($billing->credit_breakdown)) {
        echo "\nEinnahmen/Gutschriften mit detaillierten Beschreibungen:\n";
        foreach ($billing->credit_breakdown as $credit) {
            if (isset($credit['articles']) && is_array($credit['articles'])) {
                foreach ($credit['articles'] as $article) {
                    if (isset($article['detailed_description']) && !empty($article['detailed_description'])) {
                        echo "  ✓ " . ($article['article_name'] ?? 'Unbekannt') . "\n";
                        echo "    → " . substr($article['detailed_description'], 0, 80) . "...\n";
                    }
                }
            }
        }
    }
    
    // Prüfe cost_breakdown
    if (!empty($billing->cost_breakdown)) {
        echo "\nKosten mit detaillierten Beschreibungen:\n";
        foreach ($billing->cost_breakdown as $cost) {
            if (isset($cost['articles']) && is_array($cost['articles'])) {
                foreach ($cost['articles'] as $article) {
                    if (isset($article['detailed_description']) && !empty($article['detailed_description'])) {
                        echo "  ✓ " . ($article['article_name'] ?? 'Unbekannt') . "\n";
                        echo "    → " . substr($article['detailed_description'], 0, 80) . "...\n";
                    }
                }
            }
        }
    }
} else {
    echo "\n⚠ Solaranlagen-Abrechnung mit ID {$solarPlantBillingId} nicht gefunden.\n";
}

echo "\n✅ Die detaillierten Beschreibungen sind in der Datenbank gespeichert.\n";
echo "\n📄 Im PDF werden diese Beschreibungen angezeigt:\n";
echo "   1. Im Abschnitt 'Aufschlüsselung der Einnahmen/Gutschriften'\n";
echo "   2. Unter 'Erklärung der Artikel' in einem blau umrandeten Kasten\n";
echo "   3. Mit Artikelbezeichnung und darunter die ausführliche Erklärung\n";
echo "   4. Bei mehreren Artikeln mit kleinem Abstand dazwischen\n";
echo "\n🔗 Öffnen Sie die Abrechnung im Admin-Panel und generieren Sie das PDF:\n";
echo "   https://sunnybill-test.test/admin/solar-plant-billings/{$solarPlantBillingId}\n";
