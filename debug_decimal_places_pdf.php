<?php

require_once 'vendor/autoload.php';

// Laravel bootstrapping
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Models\Article;

echo "=== Debug: Nachkommastellen in PDF-Vorlage ===\n\n";

// Finde eine Abrechnung mit breakdown-Daten
$billing = SolarPlantBilling::whereNotNull('cost_breakdown')
    ->orWhereNotNull('credit_breakdown')
    ->first();

if (!$billing) {
    echo "Keine Abrechnung mit breakdown-Daten gefunden.\n";
    exit;
}

echo "Test-Abrechnung: {$billing->invoice_number}\n";

// Test 1: Prüfe ob article_id in breakdown vorhanden ist
echo "\n1. Prüfe article_id in breakdown-Daten:\n";
echo "==========================================\n";

$hasArticleId = false;

if (!empty($billing->cost_breakdown)) {
    echo "Kosten-Breakdown:\n";
    foreach ($billing->cost_breakdown as $index => $cost) {
        echo "- Kosten #{$index}: " . ($cost['supplier_name'] ?? 'Unbekannt') . "\n";
        if (isset($cost['articles']) && is_array($cost['articles'])) {
            foreach ($cost['articles'] as $articleIndex => $article) {
                echo "  * Artikel #{$articleIndex}: " . ($article['article_name'] ?? 'Unbekannt') . "\n";
                if (isset($article['article_id'])) {
                    echo "    - article_id: {$article['article_id']}\n";
                    $hasArticleId = true;
                } else {
                    echo "    - article_id: NICHT VORHANDEN!\n";
                }
            }
        }
    }
}

if (!empty($billing->credit_breakdown)) {
    echo "\nGutschriften-Breakdown:\n";
    foreach ($billing->credit_breakdown as $index => $credit) {
        echo "- Gutschrift #{$index}: " . ($credit['supplier_name'] ?? 'Unbekannt') . "\n";
        if (isset($credit['articles']) && is_array($credit['articles'])) {
            foreach ($credit['articles'] as $articleIndex => $article) {
                echo "  * Artikel #{$articleIndex}: " . ($article['article_name'] ?? 'Unbekannt') . "\n";
                if (isset($article['article_id'])) {
                    echo "    - article_id: {$article['article_id']}\n";
                    $hasArticleId = true;
                } else {
                    echo "    - article_id: NICHT VORHANDEN!\n";
                }
            }
        }
    }
}

// Test 2: Simuliere PDF-Vorlage Logik
echo "\n2. Simuliere PDF-Vorlage Logik:\n";
echo "===============================\n";

if (!empty($billing->cost_breakdown)) {
    foreach ($billing->cost_breakdown as $cost) {
        if (isset($cost['articles']) && is_array($cost['articles'])) {
            foreach ($cost['articles'] as $article) {
                echo "Artikel: " . ($article['article_name'] ?? 'Unbekannt') . "\n";
                
                // Simuliere die PDF-Vorlage Logik
                $articleModel = null;
                $decimalPlaces = 2;
                $totalDecimalPlaces = 2;
                
                if (isset($article['article_id']) && $article['article_id']) {
                    $articleModel = Article::find($article['article_id']);
                    if ($articleModel) {
                        $decimalPlaces = $articleModel->getDecimalPlaces();
                        $totalDecimalPlaces = $articleModel->getTotalDecimalPlaces();
                        echo "- Artikel-Model gefunden!\n";
                    } else {
                        echo "- Artikel-Model NICHT gefunden!\n";
                    }
                } else {
                    echo "- Keine article_id vorhanden -> Fallback auf 2 Nachkommastellen\n";
                }
                
                echo "- Verwendete Nachkommastellen (Preis): {$decimalPlaces}\n";
                echo "- Verwendete Nachkommastellen (Gesamt): {$totalDecimalPlaces}\n";
                
                $unitPrice = $article['unit_price'] ?? 0;
                $totalPrice = $article['total_price_net'] ?? 0;
                
                echo "- Original unit_price: {$unitPrice}\n";
                echo "- Original total_price_net: {$totalPrice}\n";
                echo "- Formatiert unit_price: " . number_format($unitPrice, $decimalPlaces, ',', '.') . " €\n";
                echo "- Formatiert total_price_net: " . number_format($totalPrice, $totalDecimalPlaces, ',', '.') . " €\n";
                echo "\n";
            }
        }
    }
}

// Test 3: Teste mit einem bekannten Artikel mit 6 Nachkommastellen
echo "3. Teste mit bekanntem Artikel (6 Nachkommastellen):\n";
echo "==================================================\n";

$testArticle = Article::where('decimal_places', 6)->first();
if ($testArticle) {
    echo "Test-Artikel: {$testArticle->name}\n";
    echo "- decimal_places: {$testArticle->decimal_places}\n";
    echo "- total_decimal_places: " . ($testArticle->total_decimal_places ?? 'null') . "\n";
    
    $testPrice = 0.019970;
    $testTotal = 836.123456;
    
    echo "- Test-Preis: {$testPrice}\n";
    echo "- Test-Gesamt: {$testTotal}\n";
    echo "- Formatiert mit Artikel-Nachkommastellen (Preis): " . number_format($testPrice, $testArticle->getDecimalPlaces(), ',', '.') . " €\n";
    echo "- Formatiert mit Artikel-Nachkommastellen (Gesamt): " . number_format($testTotal, $testArticle->getTotalDecimalPlaces(), ',', '.') . " €\n";
    echo "- Formatiert mit Standard (2): " . number_format($testPrice, 2, ',', '.') . " €\n";
} else {
    echo "Kein Artikel mit 6 Nachkommastellen gefunden.\n";
}

echo "\n4. Fazit:\n";
echo "========\n";
if ($hasArticleId) {
    echo "✓ article_id ist in den breakdown-Daten vorhanden.\n";
    echo "Das Problem liegt wahrscheinlich in der PDF-Vorlage oder im Abruf der Daten.\n";
} else {
    echo "✗ article_id fehlt in den breakdown-Daten.\n";
    echo "Die Abrechnung muss neu erstellt werden, damit die article_id gespeichert wird.\n";
}

echo "\n=== Debug abgeschlossen ===\n";
