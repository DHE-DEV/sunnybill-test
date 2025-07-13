<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Supplier;

echo "=== SAUBERER TEST LÜCKENFÜLLUNG ===\n\n";

// 1. Bereinige alle Test-Supplier
echo "1. Bereinige Test-Supplier:\n";
$testSuppliers = Supplier::withTrashed()
    ->where('company_name', 'LIKE', 'Test%')
    ->orWhere('company_name', 'LIKE', 'Lückenfüller%')
    ->orWhere('company_name', 'LIKE', '%Test%')
    ->get();

foreach ($testSuppliers as $supplier) {
    echo "  Lösche permanent: " . $supplier->supplier_number . " - " . $supplier->company_name . "\n";
    $supplier->forceDelete();
}

// 2. Zeige bereinigte Situation
echo "\n2. Bereinigte Supplier-Liste:\n";
$cleanSuppliers = Supplier::orderBy('supplier_number')->get(['supplier_number', 'company_name']);
foreach ($cleanSuppliers as $supplier) {
    echo "  " . $supplier->supplier_number . " - " . $supplier->company_name . "\n";
}

// 3. Teste nächste Nummer (sollte LF-0004 sein)
echo "\n3. Teste nächste Nummer (sollte LF-0004 sein):\n";
$nextNumber = Supplier::generateUniqueSupplierNumber();
echo "  Nächste Nummer: " . $nextNumber . "\n";

// 4. Erstelle LF-0004
$supplier4 = Supplier::create([
    'company_name' => 'Neuer Supplier 4',
    'is_active' => true,
]);
echo "  ✅ Erstellt: " . $supplier4->supplier_number . " - " . $supplier4->company_name . "\n";

// 5. Erstelle LF-0005
$nextNumber2 = Supplier::generateUniqueSupplierNumber();
echo "\n4. Nächste Nummer (sollte LF-0005 sein): " . $nextNumber2 . "\n";

$supplier5 = Supplier::create([
    'company_name' => 'Neuer Supplier 5',
    'is_active' => true,
]);
echo "  ✅ Erstellt: " . $supplier5->supplier_number . " - " . $supplier5->company_name . "\n";

// 6. Lösche LF-0004 und teste Lückenfüllung
echo "\n5. Lösche LF-0004 und teste Lückenfüllung:\n";
$supplier4->delete();
echo "  🗑️ " . $supplier4->supplier_number . " gelöscht (soft delete)\n";

$nextNumber3 = Supplier::generateUniqueSupplierNumber();
echo "  Nächste Nummer (sollte LF-0004 sein): " . $nextNumber3 . "\n";

$gapFiller = Supplier::create([
    'company_name' => 'Lückenfüller',
    'is_active' => true,
]);
echo "  ✅ Lückenfüller erstellt: " . $gapFiller->supplier_number . " - " . $gapFiller->company_name . "\n";

// 7. Zeige finale Liste
echo "\n6. Finale Supplier-Liste:\n";
$finalSuppliers = Supplier::orderBy('supplier_number')->get(['supplier_number', 'company_name']);
foreach ($finalSuppliers as $supplier) {
    echo "  " . $supplier->supplier_number . " - " . $supplier->company_name . "\n";
}

// 8. Aufräumen
echo "\n7. Aufräumen:\n";
$gapFiller->delete();
$supplier5->delete();
echo "  ✅ Test-Supplier gelöscht\n";

echo "\n=== TEST ABGESCHLOSSEN ===\n";