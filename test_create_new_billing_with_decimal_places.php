<?php

require_once 'vendor/autoload.php';

// Laravel bootstrapping
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlant;
use App\Models\SolarPlantBilling;

echo "=== Test: Neue Abrechnung mit article_id erstellen ===\n\n";

// Finde eine Solaranlage mit Beteiligungen
$solarPlant = SolarPlant::whereHas('participations')->first();

if (!$solarPlant) {
    echo "Keine Solaranlage mit Beteiligungen gefunden.\n";
    exit;
}

echo "Verwende Solaranlage: {$solarPlant->name} (ID: {$solarPlant->id})\n";

// Prüfe ob Abrechnungen für August 2025 erstellt werden können
try {
    $canCreate = SolarPlantBilling::canCreateBillingForMonth($solarPlant->id, 2025, 8);
    echo "Kann Abrechnung für 2025-08 erstellen: " . ($canCreate ? 'Ja' : 'Nein') . "\n";
    
    if ($canCreate) {
        echo "Erstelle Abrechnungen für August 2025...\n";
        
        // Lösche eventuell bestehende Abrechnungen für August 2025
        SolarPlantBilling::where('solar_plant_id', $solarPlant->id)
            ->where('billing_year', 2025)
            ->where('billing_month', 8)
            ->delete();
        
        $createdBillings = SolarPlantBilling::createBillingsForMonth($solarPlant->id, 2025, 8);
        
        echo "Anzahl erstellter Abrechnungen: " . count($createdBillings) . "\n\n";
        
        // Überprüfe die erste Abrechnung
        if (!empty($createdBillings)) {
            $billing = $createdBillings[0];
            echo "Test-Abrechnung: {$billing->invoice_number}\n";
            
            // Prüfe cost_breakdown
            if (!empty($billing->cost_breakdown)) {
                echo "\nKosten-Breakdown:\n";
                foreach ($billing->cost_breakdown as $cost) {
                    echo "- Lieferant: " . ($cost['supplier_name'] ?? 'Unbekannt') . "\n";
                    if (isset($cost['articles']) && is_array($cost['articles'])) {
                        foreach ($cost['articles'] as $article) {
                            echo "  * Artikel: " . ($article['article_name'] ?? 'Unbekannt') . "\n";
                            echo "    - article_id: " . ($article['article_id'] ?? 'NICHT GESETZT!') . "\n";
                            
                            if (isset($article['article_id']) && $article['article_id']) {
                                $articleModel = \App\Models\Article::find($article['article_id']);
                                if ($articleModel) {
                                    echo "    - Artikel gefunden: {$articleModel->name}\n";
                                    echo "    - decimal_places: " . ($articleModel->decimal_places ?? 'null') . "\n";
                                    echo "    - total_decimal_places: " . ($articleModel->total_decimal_places ?? 'null') . "\n";
                                    echo "    - getDecimalPlaces(): {$articleModel->getDecimalPlaces()}\n";
                                    echo "    - getTotalDecimalPlaces(): {$articleModel->getTotalDecimalPlaces()}\n";
                                } else {
                                    echo "    - Artikel NICHT GEFUNDEN!\n";
                                }
                            }
                            echo "\n";
                        }
                    } else {
                        echo "    Keine Artikel gefunden in cost breakdown\n";
                    }
                }
            }
            
            // Prüfe credit_breakdown
            if (!empty($billing->credit_breakdown)) {
                echo "\nGutschriften-Breakdown:\n";
                foreach ($billing->credit_breakdown as $credit) {
                    echo "- Lieferant: " . ($credit['supplier_name'] ?? 'Unbekannt') . "\n";
                    if (isset($credit['articles']) && is_array($credit['articles'])) {
                        foreach ($credit['articles'] as $article) {
                            echo "  * Artikel: " . ($article['article_name'] ?? 'Unbekannt') . "\n";
                            echo "    - article_id: " . ($article['article_id'] ?? 'NICHT GESETZT!') . "\n";
                            
                            if (isset($article['article_id']) && $article['article_id']) {
                                $articleModel = \App\Models\Article::find($article['article_id']);
                                if ($articleModel) {
                                    echo "    - Artikel gefunden: {$articleModel->name}\n";
                                    echo "    - decimal_places: " . ($articleModel->decimal_places ?? 'null') . "\n";
                                    echo "    - total_decimal_places: " . ($articleModel->total_decimal_places ?? 'null') . "\n";
                                    echo "    - getDecimalPlaces(): {$articleModel->getDecimalPlaces()}\n";
                                    echo "    - getTotalDecimalPlaces(): {$articleModel->getTotalDecimalPlaces()}\n";
                                } else {
                                    echo "    - Artikel NICHT GEFUNDEN!\n";
                                }
                            }
                            echo "\n";
                        }
                    } else {
                        echo "    Keine Artikel gefunden in credit breakdown\n";
                    }
                }
            }
        }
    } else {
        echo "Kann keine Abrechnung erstellen. Sind alle Vertragsabrechnungen für August 2025 vorhanden?\n";
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
}

echo "\n=== Test abgeschlossen ===\n";
