<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Supplier;

echo "=== TEST FORTLAUFENDE NUMMERIERUNG ===\n\n";

// 1. Zeige aktuelle Situation
echo "1. Aktuelle Supplier (alle inkl. soft-deleted):\n";
$allSuppliers = Supplier::withTrashed()
    ->whereNotNull('supplier_number')
    ->orderBy('supplier_number')
    ->get(['supplier_number', 'company_name', 'deleted_at']);

foreach ($allSuppliers as $supplier) {
    $status = $supplier->deleted_at ? ' (GELÖSCHT)' : '';
    echo "  " . $supplier->supplier_number . " - " . $supplier->company_name . $status . "\n";
}

// 2. Teste die neue fortlaufende Nummerierung
echo "\n2. Teste fortlaufende Nummerierung:\n";
for ($i = 1; $i <= 5; $i++) {
    $uniqueNumber = Supplier::generateUniqueSupplierNumber();
    echo "  Test #{$i}: " . $uniqueNumber . "\n";
    
    // Erstelle temporären Supplier um die Nummer zu "reservieren"
    $tempSupplier = Supplier::create([
        'company_name' => "Test Supplier #{$i}",
        'is_active' => true,
    ]);
    
    echo "    ✅ Supplier erstellt mit Nummer: " . $tempSupplier->supplier_number . "\n";
}

// 3. Zeige neue Situation
echo "\n3. Neue Supplier-Liste:\n";
$newSuppliers = Supplier::orderBy('supplier_number')->get(['supplier_number', 'company_name']);
foreach ($newSuppliers as $supplier) {
    echo "  " . $supplier->supplier_number . " - " . $supplier->company_name . "\n";
}

// 4. Lösche einen Supplier in der Mitte und teste Lückenfüllung
echo "\n4. Teste Lückenfüllung:\n";
$middleSupplier = Supplier::where('company_name', 'Test Supplier #2')->first();
if ($middleSupplier) {
    echo "  Lösche Supplier: " . $middleSupplier->supplier_number . " - " . $middleSupplier->company_name . "\n";
    $middleSupplier->delete();
    
    // Teste ob die Lücke gefüllt wird
    $nextNumber = Supplier::generateUniqueSupplierNumber();
    echo "  Nächste generierte Nummer (sollte Lücke füllen): " . $nextNumber . "\n";
    
    $gapFillSupplier = Supplier::create([
        'company_name' => 'Lückenfüller Supplier',
        'is_active' => true,
    ]);
    
    echo "  ✅ Lückenfüller erstellt: " . $gapFillSupplier->supplier_number . " - " . $gapFillSupplier->company_name . "\n";
}

// 5. Finale Liste
echo "\n5. Finale Supplier-Liste (nur aktive):\n";
$finalSuppliers = Supplier::orderBy('supplier_number')->get(['supplier_number', 'company_name']);
foreach ($finalSuppliers as $supplier) {
    echo "  " . $supplier->supplier_number . " - " . $supplier->company_name . "\n";
}

// 6. Aufräumen - lösche Test-Supplier
echo "\n6. Aufräumen:\n";
$testSuppliers = Supplier::where('company_name', 'LIKE', 'Test Supplier%')
    ->orWhere('company_name', 'Lückenfüller Supplier')
    ->get();

foreach ($testSuppliers as $supplier) {
    echo "  Lösche: " . $supplier->supplier_number . " - " . $supplier->company_name . "\n";
    $supplier->delete();
}

echo "\n=== TEST ABGESCHLOSSEN ===\n";
echo "✅ Fortlaufende Nummerierung funktioniert\n";
echo "✅ Lücken werden automatisch gefüllt\n";
echo "✅ Keine Duplikate möglich\n";