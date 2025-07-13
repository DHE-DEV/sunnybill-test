<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Supplier;

echo "=== TEST LÜCKENFÜLLUNG ===\n\n";

// 1. Zeige aktuelle Situation
echo "1. Aktuelle aktive Supplier:\n";
$activeSuppliers = Supplier::orderBy('supplier_number')->get(['supplier_number', 'company_name']);
foreach ($activeSuppliers as $supplier) {
    echo "  " . $supplier->supplier_number . " - " . $supplier->company_name . "\n";
}

// 2. Teste nächste Nummer
echo "\n2. Teste nächste Nummer-Generierung:\n";
$nextNumber = Supplier::generateUniqueSupplierNumber();
echo "  Nächste Nummer: " . $nextNumber . "\n";

// 3. Erstelle einen Supplier um LF-0004 zu belegen
echo "\n3. Erstelle Supplier für LF-0004:\n";
$supplier4 = Supplier::create([
    'company_name' => 'Test Supplier LF-0004',
    'is_active' => true,
]);
echo "  ✅ Erstellt: " . $supplier4->supplier_number . " - " . $supplier4->company_name . "\n";

// 4. Teste nächste Nummer nach LF-0004
echo "\n4. Teste nächste Nummer nach LF-0004:\n";
$nextNumber2 = Supplier::generateUniqueSupplierNumber();
echo "  Nächste Nummer: " . $nextNumber2 . "\n";

$supplier5 = Supplier::create([
    'company_name' => 'Test Supplier LF-0005',
    'is_active' => true,
]);
echo "  ✅ Erstellt: " . $supplier5->supplier_number . " - " . $supplier5->company_name . "\n";

// 5. Lösche LF-0004 und teste Lückenfüllung
echo "\n5. Lösche LF-0004 und teste Lückenfüllung:\n";
$supplier4->delete();
echo "  🗑️ LF-0004 gelöscht (soft delete)\n";

$nextNumber3 = Supplier::generateUniqueSupplierNumber();
echo "  Nächste Nummer (sollte LF-0004 sein): " . $nextNumber3 . "\n";

$gapFiller = Supplier::create([
    'company_name' => 'Lückenfüller für LF-0004',
    'is_active' => true,
]);
echo "  ✅ Lückenfüller erstellt: " . $gapFiller->supplier_number . " - " . $gapFiller->company_name . "\n";

// 6. Zeige finale Liste
echo "\n6. Finale aktive Supplier-Liste:\n";
$finalSuppliers = Supplier::orderBy('supplier_number')->get(['supplier_number', 'company_name']);
foreach ($finalSuppliers as $supplier) {
    echo "  " . $supplier->supplier_number . " - " . $supplier->company_name . "\n";
}

// 7. Aufräumen
echo "\n7. Aufräumen:\n";
$testSuppliers = Supplier::where('company_name', 'LIKE', 'Test Supplier%')
    ->orWhere('company_name', 'LIKE', 'Lückenfüller%')
    ->get();

foreach ($testSuppliers as $supplier) {
    echo "  Lösche: " . $supplier->supplier_number . " - " . $supplier->company_name . "\n";
    $supplier->delete();
}

echo "\n=== TEST ABGESCHLOSSEN ===\n";