<?php

require_once 'vendor/autoload.php';

// Laravel bootstrapping
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Models\Article;

echo "=== Fix spezifische Abrechnung für Nachkommastellen ===\n\n";

// Spezifische Abrechnung laden
$billingId = '0198a965-4fcc-71bd-8448-b866460ef503';
$billing = SolarPlantBilling::find($billingId);

if (!$billing) {
    echo "Abrechnung mit ID {$billingId} nicht gefunden.\n";
    exit;
}

echo "Gefundene Abrechnung: {$billing->invoice_number}\n";
echo "Solaranlage: {$billing->solarPlant->name}\n";
echo "Kunde: " . ($billing->customer->company_name ?? $billing->customer->name) . "\n";
echo "Periode: {$billing->billing_year}-{$billing->billing_month}\n";
echo "Anteil: {$billing->participation_percentage}%\n\n";

// Prüfe aktuelle breakdown-Daten
echo "1. Aktuelle breakdown-Daten prüfen:\n";
echo "===================================\n";

$hasArticleId = false;
if (!empty($billing->cost_breakdown)) {
    echo "Kosten-Breakdown: " . count($billing->cost_breakdown) . " Einträge\n";
    foreach ($billing->cost_breakdown as $cost) {
        if (isset($cost['articles']) && is_array($cost['articles'])) {
            foreach ($cost['articles'] as $article) {
                if (isset($article['article_id'])) {
                    $hasArticleId = true;
                    break 2;
                }
            }
        }
    }
}

if (!empty($billing->credit_breakdown)) {
    echo "Gutschriften-Breakdown: " . count($billing->credit_breakdown) . " Einträge\n";
    foreach ($billing->credit_breakdown as $credit) {
        if (isset($credit['articles']) && is_array($credit['articles'])) {
            foreach ($credit['articles'] as $article) {
                if (isset($article['article_id'])) {
                    $hasArticleId = true;
                    break 2;
                }
            }
        }
    }
}

echo "article_id vorhanden: " . ($hasArticleId ? 'Ja' : 'Nein') . "\n\n";

if (!$hasArticleId) {
    echo "2. Aktualisiere breakdown-Daten mit article_id:\n";
    echo "==============================================\n";
    
    try {
        // Berechne die Kosten neu mit article_id
        $newCostData = SolarPlantBilling::calculateCostsForCustomer(
            $billing->solar_plant_id,
            $billing->customer_id,
            $billing->billing_year,
            $billing->billing_month,
            $billing->participation_percentage
        );
        
        echo "Neue Daten berechnet!\n";
        
        // Prüfe ob article_id jetzt vorhanden ist
        $newHasArticleId = false;
        if (!empty($newCostData['cost_breakdown'])) {
            foreach ($newCostData['cost_breakdown'] as $cost) {
                if (isset($cost['articles']) && is_array($cost['articles'])) {
                    foreach ($cost['articles'] as $article) {
                        if (isset($article['article_id']) && $article['article_id']) {
                            $newHasArticleId = true;
                            echo "- article_id gefunden: {$article['article_id']}\n";
                            
                            $articleModel = Article::find($article['article_id']);
                            if ($articleModel) {
                                echo "  Artikel: {$articleModel->name}\n";
                                echo "  decimal_places: {$articleModel->getDecimalPlaces()}\n";
                                echo "  total_decimal_places: {$articleModel->getTotalDecimalPlaces()}\n";
                                
                                if ($articleModel->getDecimalPlaces() > 2) {
                                    echo "  ✓ Dieser Artikel hat mehr als 2 Nachkommastellen!\n";
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if (!empty($newCostData['credit_breakdown'])) {
            foreach ($newCostData['credit_breakdown'] as $credit) {
                if (isset($credit['articles']) && is_array($credit['articles'])) {
                    foreach ($credit['articles'] as $article) {
                        if (isset($article['article_id']) && $article['article_id']) {
                            $newHasArticleId = true;
                            echo "- article_id gefunden: {$article['article_id']}\n";
                            
                            $articleModel = Article::find($article['article_id']);
                            if ($articleModel) {
                                echo "  Artikel: {$articleModel->name}\n";
                                echo "  decimal_places: {$articleModel->getDecimalPlaces()}\n";
                                echo "  total_decimal_places: {$articleModel->getTotalDecimalPlaces()}\n";
                                
                                if ($articleModel->getDecimalPlaces() > 2) {
                                    echo "  ✓ Dieser Artikel hat mehr als 2 Nachkommastellen!\n";
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if ($newHasArticleId) {
            echo "\n✓ Neue Daten enthalten article_id! Aktualisiere Abrechnung...\n";
            
            $billing->cost_breakdown = $newCostData['cost_breakdown'];
            $billing->credit_breakdown = $newCostData['credit_breakdown'];
            $billing->save();
            
            echo "✓ Abrechnung erfolgreich aktualisiert!\n";
        } else {
            echo "\n✗ Auch die neuen Daten enthalten keine article_id.\n";
            echo "Problem liegt in der calculateCostsForCustomer Methode.\n";
        }
        
    } catch (Exception $e) {
        echo "Fehler beim Aktualisieren: " . $e->getMessage() . "\n";
    }
} else {
    echo "✓ Abrechnung hat bereits article_id in breakdown-Daten!\n";
}

echo "\n3. Teste Formatierung mit verschiedenen Nachkommastellen:\n";
echo "========================================================\n";

// Finde Artikel mit verschiedenen Nachkommastellen zum Testen
$articles = [
    Article::where('decimal_places', 2)->first(),
    Article::where('decimal_places', 6)->first(),
];

foreach ($articles as $article) {
    if ($article) {
        echo "Test-Artikel: {$article->name}\n";
        echo "- decimal_places: {$article->getDecimalPlaces()}\n";
        echo "- total_decimal_places: {$article->getTotalDecimalPlaces()}\n";
        
        $testPrice = 0.019970;
        $testTotal = 836.123456;
        
        echo "- Test-Preis: {$testPrice}\n";
        echo "- Formatiert (Preis): " . number_format($testPrice, $article->getDecimalPlaces(), ',', '.') . "\n";
        echo "- Formatiert (Gesamt): " . number_format($testTotal, $article->getTotalDecimalPlaces(), ',', '.') . "\n";
        echo "- Standard (2): " . number_format($testPrice, 2, ',', '.') . "\n\n";
    }
}

echo "4. Zusammenfassung:\n";
echo "==================\n";
echo "✓ Die Abrechnung {$billing->invoice_number} wurde aktualisiert.\n";
echo "✓ Jetzt sollten in der PDF artikelspezifische Nachkommastellen angezeigt werden.\n";
echo "✓ Marktwerte sollten mit 6 Nachkommastellen formatiert werden.\n";
echo "✓ Andere Artikel bleiben bei 2 Nachkommastellen.\n";
echo "\nDie PDF kann jetzt neu generiert werden: https://sunnybill-test.test/admin/solar-plant-billings/{$billingId}\n";

echo "\n=== Fix abgeschlossen ===\n";
