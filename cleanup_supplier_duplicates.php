<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Entferne Duplikate aus der Supplier-Tabelle...\n";
echo "===============================================\n";

// Finde alle Duplikate
$duplicates = \App\Models\Supplier::select('company_name')
    ->groupBy('company_name')
    ->havingRaw('COUNT(*) > 1')
    ->get();

$totalRemoved = 0;

foreach ($duplicates as $duplicate) {
    echo "Bearbeite Duplikate für: " . $duplicate->company_name . "\n";
    
    // Hole alle Einträge mit diesem Namen
    $entries = \App\Models\Supplier::where('company_name', $duplicate->company_name)
        ->orderBy('created_at')
        ->get();
    
    // Behalte den ersten (ältesten) Eintrag, lösche die anderen
    $keepFirst = true;
    foreach ($entries as $entry) {
        if ($keepFirst) {
            echo "  Behalte: " . $entry->id . " (erstellt: " . $entry->created_at . ")\n";
            $keepFirst = false;
        } else {
            echo "  Lösche: " . $entry->id . " (erstellt: " . $entry->created_at . ")\n";
            
            // Prüfe ob dieser Lieferant bereits Zuordnungen hat
            $assignments = \App\Models\SolarPlantSupplier::where('supplier_id', $entry->id)->count();
            $employees = \App\Models\SupplierEmployee::where('supplier_id', $entry->id)->count();
            
            if ($assignments > 0 || $employees > 0) {
                echo "    WARNUNG: Lieferant hat " . $assignments . " Zuordnungen und " . $employees . " Mitarbeiter - NICHT gelöscht!\n";
            } else {
                $entry->delete();
                $totalRemoved++;
                echo "    Erfolgreich gelöscht.\n";
            }
        }
    }
    echo "\n";
}

echo "Bereinigung abgeschlossen. " . $totalRemoved . " Duplikate entfernt.\n";

// Zeige finale Liste
echo "\nVerbleibende Lieferanten:\n";
echo "========================\n";
$remaining = \App\Models\Supplier::all(['id', 'company_name']);
foreach ($remaining as $supplier) {
    echo $supplier->id . " - " . $supplier->company_name . "\n";
}