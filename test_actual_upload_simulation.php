<?php

require_once 'vendor/autoload.php';

use App\Models\SupplierContract;
use App\Models\Document;
use App\Services\DocumentUploadConfig;
use App\Traits\DocumentUploadTrait;
use Illuminate\Support\Facades\Storage;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: Simuliere echten Upload-Prozess ===\n\n";

// Erstelle eine Test-Klasse die das DocumentUploadTrait verwendet
class TestSupplierContractDocumentsManager
{
    use DocumentUploadTrait;
    
    protected $contract;
    
    public function __construct($contract)
    {
        $this->contract = $contract;
    }
    
    protected function getDocumentUploadConfig()
    {
        return DocumentUploadConfig::forSupplierContracts($this->contract)
            ->setAdditionalData([
                'supplier_contract_id' => $this->contract->id,
                'contract_number' => $this->contract->contract_number,
                'contract_internal_number' => $this->contract->contract_internal_number,
                'supplier_name' => $this->contract->supplier?->company_name,
                'supplier_number' => $this->contract->supplier?->supplier_number,
            ]);
    }
    
    public function getOwnerRecord()
    {
        return $this->contract;
    }
    
    // Simuliere den Upload-Prozess
    public function simulateUpload($category = null)
    {
        echo "Simuliere Upload für Kategorie: " . ($category ?? 'Standard') . "\n";
        
        // 1. Hole die Konfiguration (wie das Trait es macht)
        $config = $this->getDocumentUploadConfig();
        
        echo "   Config Type: " . get_class($config) . "\n";
        echo "   Storage Directory: " . $config->getStorageDirectory() . "\n";
        
        // 2. Konvertiere zu Array (wie DocumentUploadTrait::form() es macht)
        $configArray = $config->toArray();
        
        // Füge dynamische Properties hinzu
        $configArray['storageDirectory'] = $config->getStorageDirectory();
        $configArray['diskName'] = $config->getDiskName();
        $configArray['pathType'] = $config->get('pathType');
        $configArray['model'] = $config->get('model');
        $configArray['additionalData'] = $config->get('additionalData');
        
        echo "   Array Storage Directory: " . ($configArray['storageDirectory'] ?? 'nicht gesetzt') . "\n";
        echo "   Array Path Type: " . ($configArray['pathType'] ?? 'nicht gesetzt') . "\n";
        echo "   Array Has Model: " . (isset($configArray['model']) ? 'ja' : 'nein') . "\n";
        
        // 3. Simuliere DocumentFormBuilder::createFileUploadField() Logik
        if ($configArray['pathType'] && $configArray['model']) {
            echo "   Verwende dynamische Pfad-Generierung\n";
            
            $pathType = $configArray['pathType'];
            $model = $configArray['model'];
            $additionalData = array_merge(
                $configArray['additionalData'] ?? [],
                $category ? ['category' => $category] : []
            );
            
            $uploadDir = \App\Services\DocumentStorageService::getUploadDirectoryForModel(
                $pathType,
                $model,
                $additionalData
            );
            
            echo "   Finaler Upload-Pfad: " . $uploadDir . "\n";
            
            // Prüfe Format
            if (str_starts_with($uploadDir, 'vertraege/')) {
                echo "   ✅ Verwendet neues Format\n";
            } else {
                echo "   ❌ Verwendet altes Format!\n";
            }
        } else {
            echo "   Verwende Fallback auf storageDirectory\n";
            $uploadDir = $configArray['storageDirectory'] ?? 'documents';
            echo "   Fallback Upload-Pfad: " . $uploadDir . "\n";
        }
        
        echo "\n";
        return $uploadDir;
    }
}

try {
    // 1. Hole einen SupplierContract
    $contract = SupplierContract::with(['supplier'])->first();
    
    if (!$contract) {
        echo "❌ Kein SupplierContract gefunden!\n";
        exit(1);
    }
    
    echo "1. Test-SupplierContract:\n";
    echo "   ID: " . $contract->id . "\n";
    echo "   Contract Number: " . ($contract->contract_number ?? 'N/A') . "\n";
    echo "   Supplier: " . ($contract->supplier?->company_name ?? 'N/A') . "\n";
    echo "   Supplier Number: " . ($contract->supplier?->supplier_number ?? 'N/A') . "\n\n";
    
    // 2. Erstelle Test-Manager
    echo "2. Erstelle Test-Manager:\n";
    $manager = new TestSupplierContractDocumentsManager($contract);
    echo "   ✅ Manager erstellt\n\n";
    
    // 3. Teste verschiedene Upload-Szenarien
    echo "3. Teste Upload-Szenarien:\n\n";
    
    $testCategories = [
        null => 'Standard (keine Kategorie)',
        'contracts' => 'Verträge',
        'correspondence' => 'Korrespondenz',
        'invoices' => 'Rechnungen'
    ];
    
    foreach ($testCategories as $category => $categoryName) {
        echo "--- " . $categoryName . " ---\n";
        $uploadPath = $manager->simulateUpload($category);
        echo "\n";
    }
    
    // 4. Teste auch die direkte Konfiguration
    echo "4. Direkte Konfiguration (ohne Trait):\n";
    
    $directConfig = DocumentUploadConfig::forSupplierContracts($contract)
        ->setAdditionalData([
            'supplier_contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'supplier_number' => $contract->supplier?->supplier_number,
        ]);
    
    echo "   Direct Storage Directory: " . $directConfig->getStorageDirectory() . "\n";
    echo "   Direct Path Type: " . $directConfig->get('pathType') . "\n";
    echo "   Direct Model: " . get_class($directConfig->get('model')) . "\n";
    
    echo "\n✅ Simulation abgeschlossen!\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
