<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test: Anteilige Betragsberechnung ===\n\n";

// Simuliere EON-Rechnungsdaten
$totalAmount = 1000.00; // 1000€ Gesamtbetrag
$plantPercentage = 100; // 100% Anlagenprozentsatz
$customerPercentages = [
    'Bentaieb & Boukentar PV GbR' => 49,
    'Soumaya Boukentar' => 51
];

echo "Gesamtbetrag: " . number_format($totalAmount, 2, ',', '.') . " €\n";
echo "Anlagenprozentsatz: {$plantPercentage}%\n\n";

echo "Betragsaufteilung:\n";
echo str_repeat('-', 50) . "\n";

$totalCalculated = 0;

foreach ($customerPercentages as $customerName => $percentage) {
    // Formel: Gesamtbetrag × Anlagenprozentsatz × Kostenträger-Prozentsatz / 10000
    $participationAmount = ($totalAmount * $plantPercentage * $percentage) / 10000;
    $totalCalculated += $participationAmount;
    
    echo sprintf(
        "%-30s %3d%% = %s\n",
        $customerName,
        $percentage,
        number_format($participationAmount, 2, ',', '.') . ' €'
    );
}

echo str_repeat('-', 50) . "\n";
echo sprintf("Summe berechnet: %s\n", number_format($totalCalculated, 2, ',', '.') . ' €');
echo sprintf("Differenz: %s\n", number_format($totalAmount - $totalCalculated, 2, ',', '.') . ' €');

// Validierung
if (abs($totalAmount - $totalCalculated) < 0.01) {
    echo "\n✅ Berechnung korrekt!\n";
} else {
    echo "\n❌ Berechnung fehlerhaft!\n";
}

echo "\n=== Test: Deutsche Zahlenformate ===\n\n";

// Teste verschiedene Eingabeformate
$testAmounts = [
    '1000',
    '1.000',
    '1.000,00',
    '1,000.00',
    '1000.00',
    '1000,00',
    '1.234,56 €',
    '€ 1.234,56',
    '1,234.56 $'
];

$formatGermanAmount = function($amount) {
    if (empty($amount) || $amount == 0) return '0,00 €';
    return number_format($amount, 2, ',', '.') . ' €';
};

foreach ($testAmounts as $testAmount) {
    echo "Input: '{$testAmount}' -> ";
    
    // Bereinige den Betrag (entferne Währungssymbole und normalisiere)
    $cleaned = preg_replace('/[€$£¥\s]/', '', $testAmount);
    
    $parsedAmount = 0;
    if (is_numeric($cleaned)) {
        $parsedAmount = floatval($cleaned);
    } else {
        // Behandle deutsche Zahlenformate (1.234,56) und englische (1,234.56)
        if (preg_match('/^\d{1,3}(?:\.\d{3})*,\d{2}$/', $cleaned)) {
            // Deutsches Format: 1.234,56
            $parsedAmount = floatval(str_replace(['.', ','], ['', '.'], $cleaned));
        } elseif (preg_match('/^\d{1,3}(?:,\d{3})*\.\d{2}$/', $cleaned)) {
            // Englisches Format: 1,234.56
            $parsedAmount = floatval(str_replace(',', '', $cleaned));
        } else {
            // Fallback: Versuche direkte Konvertierung
            $parsedAmount = floatval(str_replace(',', '.', $cleaned));
        }
    }
    
    echo "Parsed: {$parsedAmount} -> Formatted: " . $formatGermanAmount($parsedAmount) . "\n";
}

echo "\n=== Test abgeschlossen ===\n";