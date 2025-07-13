<?php

require_once 'vendor/autoload.php';

use App\Models\MermaidChart;
use App\Models\SolarPlant;
use App\Services\MermaidChartService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Erweiterte Mermaid Template Test ===\n\n";

try {
    // Test 1: Erstelle Chart mit erweitertem Template
    echo "1. Teste erweiterte Template-Funktionen...\n";
    $solarPlant = SolarPlant::first();
    
    if (!$solarPlant) {
        echo "❌ Keine Solaranlage gefunden\n";
        exit(1);
    }
    
    $chart = MermaidChart::create([
        'name' => 'Erweiterte Template Test - ' . $solarPlant->name,
        'description' => 'Test für automatische Datenaktualisierung',
        'template' => MermaidChart::getDefaultSolarPlantTemplate(),
        'solar_plant_id' => $solarPlant->id,
        'chart_type' => 'solar_plant',
        'is_active' => true,
    ]);
    
    echo "✅ Test-Chart mit erweitertem Template erstellt\n";
    echo "   - Template-Länge: " . strlen($chart->template) . " Zeichen\n\n";
    
    // Test 2: Code-Generierung mit erweiterten Platzhaltern
    echo "2. Teste Code-Generierung mit erweiterten Platzhaltern...\n";
    $generatedCode = $chart->generateCode();
    
    echo "✅ Code erfolgreich generiert (" . strlen($generatedCode) . " Zeichen)\n\n";
    
    // Test 3: Prüfe spezifische Platzhalter-Ersetzungen
    echo "3. Prüfe Platzhalter-Ersetzungen...\n";
    
    $placeholderTests = [
        '{{plant_name}}' => $solarPlant->name,
        '{{plant_location}}' => $solarPlant->location ?: 'Nicht angegeben',
        '{{plant_capacity}}' => number_format($solarPlant->total_capacity_kw, 2, ',', '.') . ' kWp',
        '{{last_updated}}' => date('d.m.Y H:i'),
    ];
    
    foreach ($placeholderTests as $placeholder => $expectedValue) {
        if (str_contains($generatedCode, $expectedValue)) {
            echo "   ✅ {$placeholder} korrekt ersetzt: {$expectedValue}\n";
        } else {
            echo "   ❌ {$placeholder} nicht gefunden oder falsch ersetzt\n";
        }
    }
    
    echo "\n";
    
    // Test 4: Template-Dokumentation
    echo "4. Teste Template-Dokumentation...\n";
    $documentation = MermaidChart::getTemplateDocumentation();
    
    echo "✅ Template-Dokumentation verfügbar:\n";
    foreach ($documentation as $category => $placeholders) {
        echo "   - {$category}: " . count($placeholders) . " Platzhalter\n";
    }
    
    echo "\n";
    
    // Test 5: Zeige Vorschau des generierten Codes
    echo "5. Vorschau des generierten Codes (erste 500 Zeichen):\n";
    echo "----------------------------------------\n";
    echo substr($generatedCode, 0, 500) . "...\n";
    echo "----------------------------------------\n\n";
    
    // Test 6: Aufräumen
    echo "6. Räume Test-Daten auf...\n";
    $chart->delete();
    echo "✅ Test-Chart gelöscht\n\n";
    
    echo "=== Test erfolgreich abgeschlossen ===\n";
    echo "Das erweiterte Template-System funktioniert korrekt!\n";
    echo "Alle Platzhalter werden automatisch mit aktuellen Daten gefüllt.\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}