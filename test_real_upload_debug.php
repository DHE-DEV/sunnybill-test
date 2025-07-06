<?php

require_once 'vendor/autoload.php';

use App\Models\SupplierContract;
use App\Models\Document;
use App\Services\DocumentUploadConfig;
use App\Services\DocumentStorageService;
use Illuminate\Support\Facades\Storage;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debug: Echte Upload-Situation für SupplierContract ===\n\n";

try {
    // 1. Hole den spezifischen SupplierContract aus der URL
    $contractId = '0197cf8d-f15e-7234-9dad-e6d7bc5b1e49';
    $contract = SupplierContract::with(['supplier'])->find($contractId);
    
    if (!$contract) {
        echo "❌ SupplierContract mit ID {$contractId} nicht gefunden!\n";
        exit(1);
    }
    
    echo "1. SupplierContract aus URL:\n";
    echo "   ID: " . $contract->id . "\n";
    echo "   Contract Number: " . ($contract->contract_number ?? 'N/A') . "\n";
    echo "   Supplier: " . ($contract->supplier?->company_name ?? 'N/A') . "\n";
    echo "   Supplier Number: " . ($contract->supplier?->supplier_number ?? 'N/A') . "\n\n";
    
    // 2. Prüfe aktuelle Dokumente
    echo "2. Aktuelle Dokumente:\n";
    $existingDocs = $contract->documents()->get();
    
    if ($existingDocs->isEmpty()) {
        echo "   Keine Dokumente vorhanden\n";
    } else {
        foreach ($existingDocs as $doc) {
            echo "   - " . $doc->name . " (" . $doc->path . ")\n";
            echo "     Kategorie: " . ($doc->category ?? 'keine') . "\n";
            echo "     Disk: " . ($doc->disk ?? 'default') . "\n";
            
            // Prüfe ob Datei existiert
            $diskName = $doc->disk ?? config('filesystems.default');
            if (Storage::disk($diskName)->exists($doc->path)) {
                echo "     ✅ Datei existiert auf Disk\n";
            } else {
                echo "     ❌ Datei existiert NICHT auf Disk\n";
            }
            echo "\n";
        }
    }
    
    // 3. Teste DocumentUploadConfig
    echo "3. DocumentUploadConfig Test:\n";
    
    $config = DocumentUploadConfig::forSupplierContracts($contract)
        ->setAdditionalData([
            'supplier_contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'contract_internal_number' => $contract->contract_internal_number,
            'supplier_name' => $contract->supplier?->company_name,
            'supplier_number' => $contract->supplier?->supplier_number,
        ]);
    
    echo "   Config Storage Directory: " . $config->getStorageDirectory() . "\n";
    echo "   Config Disk: " . $config->getDiskName() . "\n";
    echo "   Config Path Type: " . $config->get('pathType') . "\n";
    echo "   Config Model: " . get_class($config->get('model')) . "\n\n";
    
    // 4. Teste verschiedene Upload-Pfade
    echo "4. Upload-Pfade für verschiedene Kategorien:\n";
    
    $testCategories = [
        null => 'Standard',
        'contracts' => 'Verträge',
        'correspondence' => 'Korrespondenz',
        'invoices' => 'Rechnungen'
    ];
    
    foreach ($testCategories as $category => $categoryName) {
        $additionalData = array_merge(
            $config->get('additionalData') ?? [],
            $category ? ['category' => $category] : []
        );
        
        $uploadDir = DocumentStorageService::getUploadDirectoryForModel(
            $config->get('pathType'),
            $config->get('model'),
            $additionalData
        );
        
        echo "   " . $categoryName . ": " . $uploadDir . "\n";
        
        // Prüfe ob Verzeichnis existiert
        $diskName = $config->getDiskName();
        if (Storage::disk($diskName)->exists($uploadDir)) {
            echo "     ✅ Verzeichnis existiert\n";
        } else {
            echo "     ⚠️  Verzeichnis existiert noch nicht (wird bei Upload erstellt)\n";
        }
    }
    
    // 5. Prüfe DocumentPathSettings
    echo "\n5. DocumentPathSettings:\n";
    
    $pathSettings = \App\Models\DocumentPathSetting::where('documentable_type', 'App\Models\SupplierContract')->get();
    
    if ($pathSettings->isEmpty()) {
        echo "   ❌ Keine DocumentPathSettings für SupplierContract gefunden!\n";
    } else {
        foreach ($pathSettings as $setting) {
            echo "   - " . ($setting->category ?? 'Standard') . ": " . $setting->path_template . "\n";
        }
    }
    
    // 6. Simuliere echten Upload
    echo "\n6. Simuliere Upload-Prozess:\n";
    
    // Simuliere Filament FileUpload Verhalten
    $testFilePath = 'test-document.pdf';
    
    // Teste für Standard-Kategorie
    $uploadPath = DocumentStorageService::getUploadDirectoryForModel(
        'supplier_contracts',
        $contract,
        [
            'supplier_contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'supplier_number' => $contract->supplier?->supplier_number,
        ]
    );
    
    echo "   Upload würde erfolgen in: " . $uploadPath . "\n";
    echo "   Vollständiger Pfad: " . $uploadPath . "/" . $testFilePath . "\n";
    
    // Prüfe Format
    if (str_starts_with($uploadPath, 'vertraege/')) {
        echo "   ✅ Verwendet neues Format (vertraege/...)\n";
    } elseif (str_starts_with($uploadPath, 'supplier_contracts-documents/')) {
        echo "   ❌ Verwendet altes Format (supplier_contracts-documents/...)\n";
    } else {
        echo "   ⚠️  Unbekanntes Format: " . $uploadPath . "\n";
    }
    
    echo "\n✅ Debug abgeschlossen!\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
