<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Supplier;

echo "=== FINALER LÜCKENFÜLLUNGS-TEST ===\n\n";

// 1. Zeige aktuelle Situation
echo "1. Aktuelle aktive Supplier:\n";
$activeSuppliers = Supplier::orderBy('supplier_number')->get(['supplier_number', 'company_name']);
foreach ($activeSuppliers as $supplier) {
    echo "  " . $supplier->supplier_number . " - " . $supplier->company_name . "\n";
}

// 2. Teste nächste Nummer (sollte LF-0004 sein, da es eine Lücke gibt)
echo "\n2. Teste nächste Nummer (sollte LF-0004 sein):\n";
$nextNumber = Supplier::generateUniqueSupplierNumber();
echo "  Nächste Nummer: " . $nextNumber . "\n";

// 3. Erstelle Supplier für die Lücke
$gapSupplier = Supplier::create([
    'company_name' => 'Lückenfüller für LF-0004',
    'is_active' => true,
]);
echo "  ✅ Lückenfüller erstellt: " . $gapSupplier->supplier_number . " - " . $gapSupplier->company_name . "\n";

// 4. Teste nächste Nummer (sollte LF-0006 sein)
echo "\n3. Teste nächste Nummer (sollte LF-0006 sein):\n";
$nextNumber2 = Supplier::generateUniqueSupplierNumber();
echo "  Nächste Nummer: " . $nextNumber2 . "\n";

$nextSupplier = Supplier::create([
    'company_name' => 'Nächster Supplier',
    'is_active' => true,
]);
echo "  ✅ Nächster Supplier erstellt: " . $nextSupplier->supplier_number . " - " . $nextSupplier->company_name . "\n";

// 5. Lösche den mittleren Supplier und teste erneut
echo "\n4. Lösche mittleren Supplier und teste Lückenfüllung:\n";
$gapSupplier->delete();
echo "  🗑️ " . $gapSupplier->supplier_number . " gelöscht (soft delete)\n";

$nextNumber3 = Supplier::generateUniqueSupplierNumber();
echo "  Nächste Nummer (sollte die Lücke füllen): " . $nextNumber3 . "\n";

$newGapFiller = Supplier::create([
    'company_name' => 'Neuer Lückenfüller',
    'is_active' => true,
]);
echo "  ✅ Neuer Lückenfüller erstellt: " . $newGapFiller->supplier_number . " - " . $newGapFiller->company_name . "\n";

// 6. Zeige finale Liste
echo "\n5. Finale aktive Supplier-Liste:\n";
$finalSuppliers = Supplier::orderBy('supplier_number')->get(['supplier_number', 'company_name']);
foreach ($finalSuppliers as $supplier) {
    echo "  " . $supplier->supplier_number . " - " . $supplier->company_name . "\n";
}

// 7. Aufräumen
echo "\n6. Aufräumen:\n";
$newGapFiller->delete();
$nextSupplier->delete();
echo "  ✅ Test-Supplier gelöscht\n";

echo "\n=== TEST ABGESCHLOSSEN ===\n";
echo "✅ Lückenfüllung sollte jetzt korrekt funktionieren\n";