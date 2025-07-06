<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\SupplierContract;
use App\Services\DocumentUploadConfig;
use App\Services\DocumentFormBuilder;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: DocumentFormBuilder Directory Resolution ===\n\n";

try {
    // 1. Lade den SupplierContract
    $contract = SupplierContract::with('supplier')->find('0197cf8d-f15e-7234-9dad-e6d7bc5b1e49');
    
    if (!$contract) {
        echo "❌ SupplierContract nicht gefunden!\n";
        exit(1);
    }
    
    echo "1. Test-Contract geladen:\n";
    echo "   Contract: {$contract->contract_number}\n";
    echo "   Supplier: {$contract->supplier->supplier_number}\n\n";
    
    // 2. Erstelle DocumentUploadConfig
    $config = DocumentUploadConfig::forSupplierContracts($contract);
    
    echo "2. DocumentUploadConfig erstellt:\n";
    echo "   PathType: " . $config->get('pathType') . "\n";
    echo "   Model: " . get_class($config->get('model')) . "\n";
    echo "   DiskName: " . $config->getDiskName() . "\n\n";
    
    // 3. Konvertiere zu Array (wie im DocumentUploadTrait)
    $configArray = $config->toArray();
    
    // Füge dynamische Properties hinzu (wie im DocumentUploadTrait nach der Behebung)
    if ($config->get('pathType')) {
        $configArray['pathType'] = $config->get('pathType');
    }
    if ($config->get('model')) {
        $configArray['model'] = $config->get('model');
    }
    if ($config->get('additionalData')) {
        $configArray['additionalData'] = $config->get('additionalData');
    }
    $configArray['diskName'] = $config->getDiskName();
    
    echo "3. Config-Array nach Trait-Konvertierung:\n";
    echo "   PathType: " . ($configArray['pathType'] ?? 'nicht gesetzt') . "\n";
    echo "   Model: " . (isset($configArray['model']) ? get_class($configArray['model']) : 'nicht gesetzt') . "\n";
    echo "   DiskName: " . ($configArray['diskName'] ?? 'nicht gesetzt') . "\n";
    echo "   StorageDirectory: " . ($configArray['storageDirectory'] ?? 'NICHT GESETZT - Das ist korrekt!') . "\n\n";
    
    // 4. Erstelle DocumentFormBuilder
    $formBuilder = DocumentFormBuilder::make($configArray);
    
    echo "4. DocumentFormBuilder erstellt\n\n";
    
    // 5. Teste getUploadDirectory() Methode (Reflection für private Methode)
    $reflection = new ReflectionClass($formBuilder);
    $method = $reflection->getMethod('getUploadDirectory');
    $method->setAccessible(true);
    
    $uploadDirectory = $method->invoke($formBuilder);
    
    echo "5. Upload Directory Resolution:\n";
    echo "   Ergebnis: {$uploadDirectory}\n\n";
    
    // 6. Teste auch die dynamische Directory-Funktion (simuliere Kategorie-Auswahl)
    echo "6. Dynamische Directory-Resolution (mit Kategorie):\n";
    
    // Simuliere die Closure aus createFileUploadField()
    if ($configArray['pathType'] && $configArray['model']) {
        $category = 'contract'; // Simuliere Kategorie-Auswahl
        
        $pathType = $configArray['pathType'];
        $model = $configArray['model'];
        $additionalData = array_merge(
            $configArray['additionalData'] ?? [],
            ['category' => $category]
        );
        
        $dynamicDirectory = \App\Services\DocumentStorageService::getUploadDirectoryForModel(
            $pathType,
            $model,
            $additionalData
        );
        
        echo "   Mit Kategorie '{$category}': {$dynamicDirectory}\n";
    }
    
    echo "\n✅ Test abgeschlossen!\n";
    echo "\n=== Erwartung ===\n";
    echo "Das Upload Directory sollte 'vertraege/LF-0003/VTR-001' sein,\n";
    echo "NICHT 'supplier_contracts-documents'!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}