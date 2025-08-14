<?php

require_once 'vendor/autoload.php';

// Laravel bootstrapping
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Models\SolarPlant;
use App\Models\Article;

echo "=== Aktualisiere bestehende Abrechnung mit article_id ===\n\n";

// Finde eine Abrechnung mit breakdown-Daten
$billing = SolarPlantBilling::whereNotNull('cost_breakdown')
    ->orWhereNotNull('credit_breakdown')
    ->first();

if (!$billing) {
    echo "Keine Abrechnung mit breakdown-Daten gefunden.\n";
    exit;
}

echo "Aktualisiere Abrechnung: {$billing->invoice_number}\n";

// Hole die ursprünglichen breakdown-Daten
$costBreakdown = $billing->cost_breakdown ?: [];
$creditBreakdown = $billing->credit_breakdown ?: [];

echo "Original Kosten-Breakdown: " . count($costBreakdown) . " Einträge\n";
echo "Original Gutschriften-Breakdown: " . count($creditBreakdown) . " Einträge\n\n";

// Berechne die Kosten neu, um die article_id zu erhalten
$solarPlantId = $billing->solar_plant_id;
$customerId = $billing->customer_id;
$year = $billing->billing_year;
$month = $billing->billing_month;
$percentage = $billing->participation_percentage;

echo "Berechne Kosten neu für:\n";
echo "- Solaranlage: {$solarPlantId}\n";
echo "- Kunde: {$customerId}\n";
echo "- Periode: {$year}-{$month}\n";
echo "- Anteil: {$percentage}%\n\n";

try {
    $newCostData = SolarPlantBilling::calculateCostsForCustomer($solarPlantId, $customerId, $year, $month, $percentage);
    
    echo "Neue Breakdown-Daten berechnet:\n";
    echo "- Kosten-Breakdown: " . count($newCostData['cost_breakdown']) . " Einträge\n";
    echo "- Gutschriften-Breakdown: " . count($newCostData['credit_breakdown']) . " Einträge\n";
    
    // Prüfe ob die neuen Daten article_id enthalten
    $hasArticleId = false;
    
    if (!empty($newCostData['cost_breakdown'])) {
        echo "\nÜberprüfe Kosten-Breakdown:\n";
        foreach ($newCostData['cost_breakdown'] as $index => $cost) {
            echo "- Kosten #{$index}: " . ($cost['supplier_name'] ?? 'Unbekannt') . "\n";
            if (isset($cost['articles']) && is_array($cost['articles'])) {
                foreach ($cost['articles'] as $articleIndex => $article) {
                    echo "  * Artikel #{$articleIndex}: " . ($article['article_name'] ?? 'Unbekannt') . "\n";
                    if (isset($article['article_id']) && $article['article_id']) {
                        echo "    - article_id: {$article['article_id']}\n";
                        $hasArticleId = true;
                        
                        // Teste die Nachkommastellen
                        $articleModel = Article::find($article['article_id']);
                        if ($articleModel) {
                            echo "    - decimal_places: " . $articleModel->getDecimalPlaces() . "\n";
                            echo "    - total_decimal_places: " . $articleModel->getTotalDecimalPlaces() . "\n";
                        }
                    } else {
                        echo "    - article_id: NICHT GESETZT\n";
                    }
                }
            }
        }
    }
    
    if (!empty($newCostData['credit_breakdown'])) {
        echo "\nÜberprüfe Gutschriften-Breakdown:\n";
        foreach ($newCostData['credit_breakdown'] as $index => $credit) {
            echo "- Gutschrift #{$index}: " . ($credit['supplier_name'] ?? 'Unbekannt') . "\n";
            if (isset($credit['articles']) && is_array($credit['articles'])) {
                foreach ($credit['articles'] as $articleIndex => $article) {
                    echo "  * Artikel #{$articleIndex}: " . ($article['article_name'] ?? 'Unbekannt') . "\n";
                    if (isset($article['article_id']) && $article['article_id']) {
                        echo "    - article_id: {$article['article_id']}\n";
                        $hasArticleId = true;
                        
                        // Teste die Nachkommastellen
                        $articleModel = Article::find($article['article_id']);
                        if ($articleModel) {
                            echo "    - decimal_places: " . $articleModel->getDecimalPlaces() . "\n";
                            echo "    - total_decimal_places: " . $articleModel->getTotalDecimalPlaces() . "\n";
                        }
                    } else {
                        echo "    - article_id: NICHT GESETZT\n";
                    }
                }
            }
        }
    }
    
    if ($hasArticleId) {
        echo "\n✓ Neue breakdown-Daten enthalten article_id!\n";
        echo "Aktualisiere Abrechnung...\n";
        
        $billing->cost_breakdown = $newCostData['cost_breakdown'];
        $billing->credit_breakdown = $newCostData['credit_breakdown'];
        $billing->save();
        
        echo "✓ Abrechnung erfolgreich aktualisiert!\n";
        
        // Teste jetzt die PDF-Formatierung
        echo "\nTeste PDF-Formatierung mit den aktualisierten Daten:\n";
        echo "=====================================================\n";
        
        foreach ($newCostData['cost_breakdown'] as $cost) {
            if (isset($cost['articles']) && is_array($cost['articles'])) {
                foreach ($cost['articles'] as $article) {
                    if (isset($article['article_id']) && $article['article_id']) {
                        $articleModel = Article::find($article['article_id']);
                        if ($articleModel) {
                            $decimalPlaces = $articleModel->getDecimalPlaces();
                            $totalDecimalPlaces = $articleModel->getTotalDecimalPlaces();
                            
                            $unitPrice = $article['unit_price'] ?? 0;
                            $totalPrice = $article['total_price_net'] ?? 0;
                            
                            echo "Artikel: {$article['article_name']}\n";
                            echo "- Original unit_price: {$unitPrice}\n";
                            echo "- Original total_price_net: {$totalPrice}\n";
                            echo "- Formatiert (Preis): " . number_format($unitPrice, $decimalPlaces, ',', '.') . " €\n";
                            echo "- Formatiert (Gesamt): " . number_format($totalPrice, $totalDecimalPlaces, ',', '.') . " €\n";
                            echo "- Standard (2 Stellen): " . number_format($unitPrice, 2, ',', '.') . " €\n";
                            echo "\n";
                        }
                    }
                }
            }
        }
        
        foreach ($newCostData['credit_breakdown'] as $credit) {
            if (isset($credit['articles']) && is_array($credit['articles'])) {
                foreach ($credit['articles'] as $article) {
                    if (isset($article['article_id']) && $article['article_id']) {
                        $articleModel = Article::find($article['article_id']);
                        if ($articleModel) {
                            $decimalPlaces = $articleModel->getDecimalPlaces();
                            $totalDecimalPlaces = $articleModel->getTotalDecimalPlaces();
                            
                            $unitPrice = $article['unit_price'] ?? 0;
                            $totalPrice = $article['total_price_net'] ?? 0;
                            
                            echo "Artikel: {$article['article_name']}\n";
                            echo "- Original unit_price: {$unitPrice}\n";
                            echo "- Original total_price_net: {$totalPrice}\n";
                            echo "- Formatiert (Preis): " . number_format($unitPrice, $decimalPlaces, ',', '.') . " €\n";
                            echo "- Formatiert (Gesamt): " . number_format($totalPrice, $totalDecimalPlaces, ',', '.') . " €\n";
                            echo "- Standard (2 Stellen): " . number_format($unitPrice, 2, ',', '.') . " €\n";
                            echo "\n";
                        }
                    }
                }
            }
        }
        
    } else {
        echo "\n✗ Neue breakdown-Daten enthalten immer noch keine article_id!\n";
        echo "Es liegt ein Problem in der calculateCostsForCustomer Methode vor.\n";
    }
    
} catch (Exception $e) {
    echo "Fehler beim Berechnen der Kosten: " . $e->getMessage() . "\n";
}

echo "\n=== Aktualisierung abgeschlossen ===\n";
