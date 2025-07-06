<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;
use App\Models\SolarPlant;

echo "=== Test der korrigierten Kostenaufteilung ===" . PHP_EOL;

// Teste die spezifische Solaranlage aus der Analyse
$plantId = '0197d1f5-9e0b-72f4-8874-899319b69234';
$customerId = '0197cf8d-f14a-73d4-b7f3-4b35cfc9ed40'; // Anouar Bentaieb

echo "1. Teste korrigierte Berechnung für Aurich 2:" . PHP_EOL;
echo "Solaranlage ID: $plantId" . PHP_EOL;
echo "Kunde ID: $customerId" . PHP_EOL;

try {
    $costs = SolarPlantBilling::calculateCostsForCustomer($plantId, $customerId, 2024, 4);
    
    echo "Neue Berechnungsergebnisse:" . PHP_EOL;
    echo "  Gesamtkosten: " . number_format($costs['total_costs'], 2, ',', '.') . " €" . PHP_EOL;
    echo "  Gesamtgutschriften: " . number_format($costs['total_credits'], 2, ',', '.') . " €" . PHP_EOL;
    echo "  Nettobetrag: " . number_format($costs['net_amount'], 2, ',', '.') . " €" . PHP_EOL;
    
    echo PHP_EOL . "Detaillierte Kostenaufschlüsselung:" . PHP_EOL;
    foreach ($costs['cost_breakdown'] as $cost) {
        echo "  - " . $cost['contract_title'] . ":" . PHP_EOL;
        echo "    Vertragsbetrag: " . number_format($cost['total_amount'], 2, ',', '.') . " €" . PHP_EOL;
        echo "    Solaranlagen-Anteil: " . $cost['solar_plant_percentage'] . "%" . PHP_EOL;
        echo "    Kunden-Anteil: " . $cost['customer_percentage'] . "%" . PHP_EOL;
        echo "    Kunden-Kosten: " . number_format($cost['customer_share'], 2, ',', '.') . " €" . PHP_EOL;
        echo "    Berechnung: " . number_format($cost['total_amount'], 2, ',', '.') . " × " . $cost['solar_plant_percentage'] . "% × " . $cost['customer_percentage'] . "% = " . number_format($cost['customer_share'], 2, ',', '.') . " €" . PHP_EOL;
        echo PHP_EOL;
    }
    
    echo "Detaillierte Gutschriftenaufschlüsselung:" . PHP_EOL;
    foreach ($costs['credit_breakdown'] as $credit) {
        echo "  - " . $credit['contract_title'] . ":" . PHP_EOL;
        echo "    Vertragsbetrag: " . number_format($credit['total_amount'], 2, ',', '.') . " €" . PHP_EOL;
        echo "    Solaranlagen-Anteil: " . $credit['solar_plant_percentage'] . "%" . PHP_EOL;
        echo "    Kunden-Anteil: " . $credit['customer_percentage'] . "%" . PHP_EOL;
        echo "    Kunden-Gutschrift: " . number_format($credit['customer_share'], 2, ',', '.') . " €" . PHP_EOL;
        echo "    Berechnung: " . number_format($credit['total_amount'], 2, ',', '.') . " × " . $credit['solar_plant_percentage'] . "% × " . $credit['customer_percentage'] . "% = " . number_format($credit['customer_share'], 2, ',', '.') . " €" . PHP_EOL;
        echo PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Fehler bei der Berechnung: " . $e->getMessage() . PHP_EOL;
    echo "Stack Trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "2. Vergleich mit vorheriger Analyse:" . PHP_EOL;
echo "Erwartete Korrektur für E.ON Vertrag:" . PHP_EOL;
echo "  Vorher: 758,90 € (100% des Vertrags)" . PHP_EOL;
echo "  Nachher: 758,90 € × 35,53% × 100% = 269,64 €" . PHP_EOL;

echo PHP_EOL . "=== Test abgeschlossen ===" . PHP_EOL;