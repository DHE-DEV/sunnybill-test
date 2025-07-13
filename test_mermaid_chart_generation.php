<?php

require_once 'vendor/autoload.php';

use App\Models\SolarPlant;
use App\Models\CompanySetting;
use App\Services\MermaidChartService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Mermaid Chart Generation Test ===\n\n";

try {
    // Test 1: Service-Instanziierung
    echo "1. Teste MermaidChartService-Instanziierung...\n";
    $mermaidService = new MermaidChartService();
    echo "✅ Service erfolgreich instanziiert\n\n";
    
    // Test 2: Hole erste Solaranlage
    echo "2. Teste Solaranlagen-Abfrage...\n";
    $solarPlant = SolarPlant::with([
        'participations.customer',
        'suppliers',
        'supplierContracts.supplier',
        'activeSupplierContractAssignments.supplierContract.supplier'
    ])->first();
    
    if (!$solarPlant) {
        echo "❌ Keine Solaranlage gefunden\n";
        exit(1);
    }
    
    echo "✅ Solaranlage gefunden: {$solarPlant->name}\n";
    echo "   - Beteiligungen: " . $solarPlant->participations->count() . "\n";
    echo "   - Lieferanten: " . $solarPlant->suppliers->count() . "\n";
    echo "   - Verträge: " . $solarPlant->supplierContracts->count() . "\n\n";
    
    // Test 3: Chart-Generierung
    echo "3. Teste Chart-Generierung...\n";
    $chartCode = $mermaidService->generateSolarPlantChart($solarPlant);
    
    if (empty($chartCode)) {
        echo "❌ Leerer Chart-Code generiert\n";
        exit(1);
    }
    
    echo "✅ Chart-Code erfolgreich generiert (" . strlen($chartCode) . " Zeichen)\n\n";
    
    // Test 4: Chart-Code-Vorschau
    echo "4. Chart-Code Vorschau (erste 500 Zeichen):\n";
    echo "----------------------------------------\n";
    echo substr($chartCode, 0, 500) . "...\n";
    echo "----------------------------------------\n\n";
    
    // Test 5: Template-Test
    echo "5. Teste Template-Konfiguration...\n";
    $companySetting = CompanySetting::current();
    
    if ($companySetting && !empty($companySetting->mermaid_chart_template)) {
        echo "✅ Template in CompanySetting gefunden\n";
        echo "   Template-Länge: " . strlen($companySetting->mermaid_chart_template) . " Zeichen\n";
    } else {
        echo "ℹ️  Kein Template in CompanySetting - verwende Standard-Template\n";
    }
    
    echo "\n=== Test erfolgreich abgeschlossen ===\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}