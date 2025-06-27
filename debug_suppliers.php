<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Alle Lieferanten in der Datenbank:\n";
echo "==================================\n";

$suppliers = \App\Models\Supplier::all(['id', 'company_name', 'is_active']);

foreach ($suppliers as $supplier) {
    echo $supplier->id . " - " . $supplier->company_name . " (aktiv: " . ($supplier->is_active ? 'ja' : 'nein') . ")\n";
}

echo "\nAnzahl Lieferanten: " . $suppliers->count() . "\n";

// Prüfe auf Duplikate
echo "\nDuplikate nach Firmenname:\n";
echo "==========================\n";

$duplicates = \App\Models\Supplier::select('company_name')
    ->groupBy('company_name')
    ->havingRaw('COUNT(*) > 1')
    ->get();

if ($duplicates->count() > 0) {
    foreach ($duplicates as $duplicate) {
        echo "Duplikat gefunden: " . $duplicate->company_name . "\n";
        $entries = \App\Models\Supplier::where('company_name', $duplicate->company_name)->get(['id', 'company_name']);
        foreach ($entries as $entry) {
            echo "  - ID: " . $entry->id . "\n";
        }
    }
} else {
    echo "Keine Duplikate gefunden.\n";
}