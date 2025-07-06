<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentPathSetting;

echo "=== Hinzufügen: Supplier DocumentPathSettings für alle Kategorien ===\n\n";

// Kategorien aus DocumentUploadConfig::forSuppliers()
$supplierCategories = [
    'contract' => 'vertraege',
    'invoice' => 'rechnungen', 
    'certificate' => 'zertifikate',
    'correspondence' => 'korrespondenz',
    'technical' => 'technische-unterlagen',
    'quality' => 'qualitaetsdokumente',
    'other' => 'sonstiges',
];

echo "1. Hinzufügen der fehlenden Supplier-Kategorien:\n";

foreach ($supplierCategories as $category => $folderName) {
    $setting = DocumentPathSetting::updateOrCreate(
        [
            'documentable_type' => 'App\Models\Supplier',
            'category' => $category,
        ],
        [
            'path_template' => "lieferanten/{supplier_number}/{$folderName}",
            'description' => "Pfad für Lieferanten-{$category}-Dokumente",
            'placeholders' => ['supplier_number', 'supplier_name', 'supplier_id'],
            'is_active' => true,
        ]
    );
    
    echo "   ✅ {$category}: lieferanten/{supplier_number}/{$folderName}\n";
}

echo "\n2. Überprüfung der neuen Settings:\n";
$supplierSettings = DocumentPathSetting::where('documentable_type', 'App\Models\Supplier')->get();
foreach ($supplierSettings as $setting) {
    $category = $setting->category ?? 'NULL';
    echo "   - {$category}: {$setting->path_template}\n";
}

echo "\n✅ Alle Supplier DocumentPathSettings hinzugefügt!\n";