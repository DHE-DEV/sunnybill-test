<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Fix: SupplierContract Standard-Pfad korrigieren ===\n\n";

try {
    // 1. Finde den Standard-Pfad für SupplierContract (ID 29)
    $standardPath = \Illuminate\Support\Facades\DB::table('document_path_settings')
        ->where('id', 29)
        ->first();
    
    if (!$standardPath) {
        echo "❌ Standard-Pfad (ID 29) nicht gefunden!\n";
        exit(1);
    }
    
    echo "1. Aktueller Standard-Pfad:\n";
    echo "   ID: " . $standardPath->id . "\n";
    echo "   Path Template: " . $standardPath->path_template . "\n";
    echo "   Documentable Type: " . ($standardPath->documentable_type ?? 'N/A') . "\n\n";
    
    // 2. Korrigiere den Pfad
    $newPathTemplate = 'vertraege/{supplier_number}/{contract_number}';
    
    echo "2. Korrigiere Standard-Pfad:\n";
    echo "   Neuer Path Template: " . $newPathTemplate . "\n";
    
    $updated = \Illuminate\Support\Facades\DB::table('document_path_settings')
        ->where('id', 29)
        ->update([
            'path_template' => $newPathTemplate,
            'documentable_type' => 'App\\Models\\SupplierContract',
            'updated_at' => now()
        ]);
    
    if ($updated) {
        echo "   ✅ Standard-Pfad erfolgreich aktualisiert\n\n";
    } else {
        echo "   ❌ Fehler beim Aktualisieren des Standard-Pfads\n\n";
        exit(1);
    }
    
    // 3. Korrigiere auch die kategorie-spezifischen Pfade
    echo "3. Korrigiere kategorie-spezifische Pfade:\n";
    
    $categoriesToFix = [
        30 => ['category' => 'contracts', 'new_path' => 'vertraege/{supplier_number}/{contract_number}/vertragsdokumente'],
        31 => ['category' => 'correspondence', 'new_path' => 'vertraege/{supplier_number}/{contract_number}/korrespondenz'],
        32 => ['category' => 'invoices', 'new_path' => 'vertraege/{supplier_number}/{contract_number}/abrechnungen/{year}']
    ];
    
    foreach ($categoriesToFix as $id => $config) {
        $currentPath = \Illuminate\Support\Facades\DB::table('document_path_settings')
            ->where('id', $id)
            ->first();
        
        if ($currentPath) {
            echo "   Kategorie '" . $config['category'] . "' (ID " . $id . "):\n";
            echo "     Alt: " . $currentPath->path_template . "\n";
            echo "     Neu: " . $config['new_path'] . "\n";
            
            $updated = \Illuminate\Support\Facades\DB::table('document_path_settings')
                ->where('id', $id)
                ->update([
                    'path_template' => $config['new_path'],
                    'documentable_type' => 'App\\Models\\SupplierContract',
                    'updated_at' => now()
                ]);
            
            if ($updated) {
                echo "     ✅ Erfolgreich aktualisiert\n";
            } else {
                echo "     ❌ Fehler beim Aktualisieren\n";
            }
        } else {
            echo "   ❌ Pfad mit ID " . $id . " nicht gefunden\n";
        }
        echo "\n";
    }
    
    // 4. Teste die Änderungen
    echo "4. Teste die Änderungen:\n";
    
    $contract = \App\Models\SupplierContract::with(['supplier'])->first();
    
    if ($contract) {
        echo "   Test-Contract: " . $contract->contract_number . " (" . ($contract->supplier?->supplier_number ?? 'N/A') . ")\n";
        
        $config = \App\Services\DocumentUploadConfig::forSupplierContracts($contract)
            ->setAdditionalData([
                'supplier_contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'supplier_number' => $contract->supplier?->supplier_number,
            ]);
        
        echo "   Neuer Storage Directory: " . $config->getStorageDirectory() . "\n";
        
        // Prüfe Format
        if (str_starts_with($config->getStorageDirectory(), 'vertraege/')) {
            echo "   ✅ Verwendet jetzt das neue Format!\n";
        } else {
            echo "   ❌ Verwendet immer noch das alte Format: " . $config->getStorageDirectory() . "\n";
        }
    } else {
        echo "   ❌ Kein Test-Contract gefunden\n";
    }
    
    echo "\n✅ Korrektur abgeschlossen!\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
