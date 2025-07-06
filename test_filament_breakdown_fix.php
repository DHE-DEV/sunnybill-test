<?php

require_once 'vendor/autoload.php';

use App\Models\SolarPlantBilling;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test: Filament Breakdown Fix ===" . PHP_EOL;

// Hole die spezifische Abrechnung
$billingId = '0197d1f5-9e0b-72f4-8874-899319b69234';
$billing = SolarPlantBilling::find($billingId);

if (!$billing) {
    echo "Abrechnung nicht gefunden!" . PHP_EOL;
    exit;
}

echo "Abrechnung gefunden: {$billing->id}" . PHP_EOL;
echo "Solaranlage: {$billing->solarPlant->name}" . PHP_EOL;
echo "Kunde: {$billing->customer->name}" . PHP_EOL;
echo PHP_EOL;

// Simuliere die Filament Resource Logik für cost_breakdown
echo "=== Teste Cost Breakdown Tabelle (wie in Filament Resource) ===" . PHP_EOL;
if ($billing->cost_breakdown && !empty($billing->cost_breakdown)) {
    echo "Cost Breakdown verfügbar: " . count($billing->cost_breakdown) . " Einträge" . PHP_EOL;
    
    foreach ($billing->cost_breakdown as $index => $item) {
        echo "Eintrag {$index}:" . PHP_EOL;
        echo "  Vertragstitel: " . $item['contract_title'] . PHP_EOL;
        echo "  Lieferant: " . $item['supplier_name'] . PHP_EOL;
        
        // Teste den korrigierten Zugriff auf solar_plant_percentage
        try {
            $percentage = number_format($item['solar_plant_percentage'], 2, ',', '.');
            echo "  ✓ Solar Plant Percentage: {$percentage}%" . PHP_EOL;
        } catch (Exception $e) {
            echo "  ✗ Fehler bei solar_plant_percentage: " . $e->getMessage() . PHP_EOL;
        }
        
        // Teste auch customer_share
        try {
            $customerShare = number_format($item['customer_share'], 2, ',', '.');
            echo "  ✓ Customer Share: {$customerShare} €" . PHP_EOL;
        } catch (Exception $e) {
            echo "  ✗ Fehler bei customer_share: " . $e->getMessage() . PHP_EOL;
        }
        
        echo "---" . PHP_EOL;
    }
} else {
    echo "Keine Cost Breakdown verfügbar" . PHP_EOL;
}

echo PHP_EOL;

// Simuliere die Filament Resource Logik für credit_breakdown
echo "=== Teste Credit Breakdown Tabelle (wie in Filament Resource) ===" . PHP_EOL;
if ($billing->credit_breakdown && !empty($billing->credit_breakdown)) {
    echo "Credit Breakdown verfügbar: " . count($billing->credit_breakdown) . " Einträge" . PHP_EOL;
    
    foreach ($billing->credit_breakdown as $index => $item) {
        echo "Eintrag {$index}:" . PHP_EOL;
        echo "  Vertragstitel: " . $item['contract_title'] . PHP_EOL;
        echo "  Lieferant: " . $item['supplier_name'] . PHP_EOL;
        
        // Teste den korrigierten Zugriff auf solar_plant_percentage
        try {
            $percentage = number_format($item['solar_plant_percentage'], 2, ',', '.');
            echo "  ✓ Solar Plant Percentage: {$percentage}%" . PHP_EOL;
        } catch (Exception $e) {
            echo "  ✗ Fehler bei solar_plant_percentage: " . $e->getMessage() . PHP_EOL;
        }
        
        // Teste auch customer_share
        try {
            $customerShare = number_format($item['customer_share'], 2, ',', '.');
            echo "  ✓ Customer Share: {$customerShare} €" . PHP_EOL;
        } catch (Exception $e) {
            echo "  ✗ Fehler bei customer_share: " . $e->getMessage() . PHP_EOL;
        }
        
        echo "---" . PHP_EOL;
    }
} else {
    echo "Keine Credit Breakdown verfügbar" . PHP_EOL;
}

echo PHP_EOL;

// Simuliere die HTML-Tabellen-Generierung (vereinfacht)
echo "=== Teste HTML-Tabellen-Generierung ===" . PHP_EOL;

function generateBreakdownTable($breakdown, $type = 'cost') {
    if (!$breakdown || empty($breakdown)) {
        return "Keine {$type}positionen verfügbar";
    }
    
    $html = "<table>\n";
    $html .= "<thead><tr><th>Bezeichnung</th><th>Anteil</th><th>Gesamtbetrag</th></tr></thead>\n";
    $html .= "<tbody>\n";
    
    foreach ($breakdown as $item) {
        $html .= "<tr>\n";
        $html .= "<td>" . htmlspecialchars($item['contract_title']) . " (" . htmlspecialchars($item['supplier_name']) . ")</td>\n";
        $html .= "<td>" . number_format($item['solar_plant_percentage'], 2, ',', '.') . "%</td>\n";
        $html .= "<td>" . number_format($item['customer_share'], 2, ',', '.') . " €</td>\n";
        $html .= "</tr>\n";
    }
    
    $html .= "</tbody>\n";
    $html .= "</table>\n";
    
    return $html;
}

try {
    echo "Cost Breakdown HTML:" . PHP_EOL;
    $costHtml = generateBreakdownTable($billing->cost_breakdown, 'cost');
    echo $costHtml . PHP_EOL;
    
    echo "Credit Breakdown HTML:" . PHP_EOL;
    $creditHtml = generateBreakdownTable($billing->credit_breakdown, 'credit');
    echo $creditHtml . PHP_EOL;
    
    echo "✅ HTML-Generierung erfolgreich!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Fehler bei HTML-Generierung: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;
echo "=== Test abgeschlossen ===" . PHP_EOL;