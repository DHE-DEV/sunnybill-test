<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Supplier;

echo "=== FIND HARDCODED SUPPLIER CREATION ===\n\n";

// 1. Prüfe ob LF-0004 bereits existiert
echo "1. Prüfe aktuelle Supplier:\n";
$existing = Supplier::where('supplier_number', 'LF-0004')->first();
if ($existing) {
    echo "❌ LF-0004 existiert bereits!\n";
    echo "  ID: " . $existing->id . "\n";
    echo "  Firma: " . $existing->company_name . "\n";
    echo "  Erstellt: " . $existing->created_at . "\n\n";
    
    // Lösche den existierenden Supplier
    echo "🗑️ Lösche existierenden Supplier...\n";
    $existing->delete();
    echo "✅ Supplier gelöscht\n\n";
} else {
    echo "✅ LF-0004 existiert nicht\n\n";
}

// 2. Teste die neue Unique-Generierung
echo "2. Teste neue Unique-Generierung:\n";
try {
    $uniqueNumber = Supplier::generateUniqueSupplierNumber();
    echo "✅ Generierte Nummer: " . $uniqueNumber . "\n";
    
    // Prüfe ob diese Nummer verfügbar ist
    $exists = Supplier::where('supplier_number', $uniqueNumber)->exists();
    echo "  Nummer verfügbar: " . ($exists ? 'NEIN' : 'JA') . "\n\n";
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n\n";
}

// 3. Erstelle einen Test-Supplier
echo "3. Erstelle Test-Supplier:\n";
try {
    $testSupplier = Supplier::create([
        'company_name' => 'Test Westnetz GmbH',
        'is_active' => true,
    ]);
    
    echo "✅ Test-Supplier erstellt:\n";
    echo "  ID: " . $testSupplier->id . "\n";
    echo "  Nummer: " . $testSupplier->supplier_number . "\n";
    echo "  Firma: " . $testSupplier->company_name . "\n\n";
    
    // Lösche den Test-Supplier wieder
    echo "🗑️ Lösche Test-Supplier...\n";
    $testSupplier->delete();
    echo "✅ Test-Supplier gelöscht\n\n";
    
} catch (Exception $e) {
    echo "❌ Fehler beim Erstellen: " . $e->getMessage() . "\n\n";
}

// 4. Zeige alle aktuellen Supplier
echo "4. Aktuelle Supplier:\n";
$allSuppliers = Supplier::orderBy('supplier_number')->get(['supplier_number', 'company_name']);
foreach ($allSuppliers as $supplier) {
    echo "  " . ($supplier->supplier_number ?? 'NULL') . " - " . $supplier->company_name . "\n";
}

echo "\n=== FERTIG ===\n";