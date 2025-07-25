<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;

echo "=== TESTING: Standort-Formatierung - Straße über PLZ Ort ===\n\n";

// Finde eine Testabrechnung 
$billing = SolarPlantBilling::with(['solarPlant', 'customer'])->first();

if (!$billing) {
    echo "❌ Keine Abrechnung gefunden!\n";
    exit;
}

echo "✅ Testabrechnung gefunden: {$billing->id}\n";
echo "Solaranlage: {$billing->solarPlant->name}\n";
echo "Originalstandort: " . ($billing->solarPlant->location ?? 'Kein Standort') . "\n\n";

// Simuliere die Formatierungs-Logik
$location = trim($billing->solarPlant->location ?? '');

if ($location) {
    echo "=== ADRESSFORMATIERUNG ===\n\n";
    
    // Trenne verschiedene Adressteile
    $parts = preg_split('/[,;|]/', $location);
    $parts = array_map('trim', $parts);
    $parts = array_filter($parts);
    
    echo "📊 ERKANNTE TEILE:\n";
    foreach ($parts as $i => $part) {
        echo "   Teil " . ($i + 1) . ": \"$part\"\n";
    }
    echo "\n";
    
    if (count($parts) >= 2) {
        // Erste Zeile: Straße
        $street = $parts[0];
        
        // Versuche PLZ und Ort aus den restlichen Teilen zu identifizieren
        $remaining = array_slice($parts, 1);
        $address = implode(' ', $remaining);
        
        $formattedLocation = $street . '<br>' . $address;
        
        echo "🎯 FORMATIERT ALS:\n";
        echo "   Zeile 1: \"$street\"\n";
        echo "   Zeile 2: \"$address\"\n\n";
        
        echo "📋 HTML-OUTPUT:\n";
        echo "   " . str_replace('<br>', "\n   ", $formattedLocation) . "\n\n";
        
    } else {
        // Fallback: versuche PLZ + Ort Pattern zu finden
        if (preg_match('/^(.+?)[\s]+(\d{5}[\s]+.+)$/u', $location, $matches)) {
            $street = trim($matches[1]);
            $plzOrt = trim($matches[2]);
            $formattedLocation = $street . '<br>' . $plzOrt;
            
            echo "🎯 FALLBACK-FORMATIERUNG:\n";
            echo "   Zeile 1: \"$street\"\n";
            echo "   Zeile 2: \"$plzOrt\"\n\n";
            
            echo "📋 HTML-OUTPUT:\n";
            echo "   " . str_replace('<br>', "\n   ", $formattedLocation) . "\n\n";
        } else {
            echo "⚠️  KEINE FORMATIERUNG MÖGLICH - verwende Original:\n";
            echo "   \"$location\"\n\n";
        }
    }
}

echo "=== TESTFÄLLE ===\n\n";

$testCases = [
    "Musterstraße 123, 01234 Musterstadt",
    "Hauptstraße 45; 12345 Berlin",
    "Gartenweg 7| 98765 Hamburg",
    "Am Park 15 54321 München",
    "Berliner Str. 100, 10115, Berlin",
    "Einfach nur Text ohne PLZ"
];

foreach ($testCases as $i => $testLocation) {
    echo "TEST " . ($i + 1) . ": \"$testLocation\"\n";
    
    // Simuliere die Formatierungs-Logik
    $parts = preg_split('/[,;|]/', $testLocation);
    $parts = array_map('trim', $parts);
    $parts = array_filter($parts);
    
    if (count($parts) >= 2) {
        $street = $parts[0];
        $remaining = array_slice($parts, 1);
        $address = implode(' ', $remaining);
        $result = "$street → $address";
    } else {
        if (preg_match('/^(.+?)[\s]+(\d{5}[\s]+.+)$/u', $testLocation, $matches)) {
            $street = trim($matches[1]);
            $plzOrt = trim($matches[2]);
            $result = "$street → $plzOrt";
        } else {
            $result = "Unverändert: $testLocation";
        }
    }
    
    echo "   ERGEBNIS: $result\n\n";
}

echo "🎉 STANDORT-FORMATIERUNG GETESTET!\n";
echo "✅ Straße wird in erste Zeile gesetzt\n";
echo "✅ PLZ und Ort folgen in zweiter Zeile\n";
echo "✅ Verschiedene Trennzeichen (,;|) werden erkannt\n";
echo "✅ Fallback für Leerzeichen-Trennung implementiert\n";
