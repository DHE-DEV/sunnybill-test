<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentPathSetting;

echo "=== Check: Supplier DocumentPathSettings ===\n\n";

// Prüfe vorhandene DocumentPathSettings für Suppliers
$supplierSettings = DocumentPathSetting::where('documentable_type', 'App\Models\Supplier')->get();

echo "1. Vorhandene Supplier DocumentPathSettings:\n";
if ($supplierSettings->isEmpty()) {
    echo "   ❌ Keine DocumentPathSettings für 'App\Models\Supplier' gefunden!\n";
} else {
    foreach ($supplierSettings as $setting) {
        echo "   - {$setting->documentable_type} / {$setting->category}: {$setting->path_template}\n";
    }
}

echo "\n2. Alle DocumentPathSettings:\n";
$allSettings = DocumentPathSetting::all();
foreach ($allSettings as $setting) {
    echo "   - {$setting->documentable_type} / {$setting->category}: {$setting->path_template}\n";
}

echo "\n=== Erwartung ===\n";
echo "Es sollten DocumentPathSettings für 'suppliers' mit verschiedenen Kategorien existieren!\n";