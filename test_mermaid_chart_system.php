<?php

require_once 'vendor/autoload.php';

use App\Models\MermaidChart;
use App\Models\SolarPlant;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Mermaid Chart System Test ===\n\n";

try {
    // Test 1: Model-Instanziierung
    echo "1. Teste MermaidChart Model...\n";
    $chartTypes = MermaidChart::getChartTypes();
    echo "✅ Chart-Typen verfügbar: " . implode(', ', array_keys($chartTypes)) . "\n\n";
    
    // Test 2: Erstelle einen Test-Chart
    echo "2. Erstelle Test-Chart...\n";
    $solarPlant = SolarPlant::first();
    
    if (!$solarPlant) {
        echo "❌ Keine Solaranlage gefunden\n";
        exit(1);
    }
    
    $chart = MermaidChart::create([
        'name' => 'Test Chart - ' . $solarPlant->name,
        'description' => 'Automatisch erstellter Test-Chart',
        'template' => MermaidChart::getDefaultSolarPlantTemplate(),
        'solar_plant_id' => $solarPlant->id,
        'chart_type' => 'solar_plant',
        'is_active' => true,
    ]);
    
    echo "✅ Test-Chart erstellt (ID: {$chart->id})\n";
    echo "   - Name: {$chart->name}\n";
    echo "   - Solaranlage: {$chart->solarPlant->name}\n";
    echo "   - Typ: {$chart->chart_type}\n\n";
    
    // Test 3: Code-Generierung
    echo "3. Teste Code-Generierung...\n";
    $generatedCode = $chart->generateCode();
    
    if (empty($generatedCode)) {
        echo "❌ Leerer Code generiert\n";
        exit(1);
    }
    
    echo "✅ Code erfolgreich generiert (" . strlen($generatedCode) . " Zeichen)\n";
    echo "   - Template-Länge: " . strlen($chart->template) . " Zeichen\n";
    echo "   - Generiert-Länge: " . strlen($chart->generated_code) . " Zeichen\n\n";
    
    // Test 4: Chart-Methoden
    echo "4. Teste Chart-Methoden...\n";
    echo "   - hasSolarPlant(): " . ($chart->hasSolarPlant() ? 'Ja' : 'Nein') . "\n";
    echo "   - getChartCode() Länge: " . strlen($chart->getChartCode()) . " Zeichen\n";
    echo "   - Aktiv: " . ($chart->is_active ? 'Ja' : 'Nein') . "\n\n";
    
    // Test 5: Scopes
    echo "5. Teste Model-Scopes...\n";
    $activeCharts = MermaidChart::active()->count();
    $solarPlantCharts = MermaidChart::ofType('solar_plant')->count();
    
    echo "   - Aktive Charts: {$activeCharts}\n";
    echo "   - Solaranlagen-Charts: {$solarPlantCharts}\n\n";
    
    // Test 6: Aufräumen
    echo "6. Räume Test-Daten auf...\n";
    $chart->delete();
    echo "✅ Test-Chart gelöscht\n\n";
    
    echo "=== Test erfolgreich abgeschlossen ===\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}