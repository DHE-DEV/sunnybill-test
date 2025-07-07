<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Erstelle Lieferantenverträge und Solaranlagen-Verknüpfungen...\n\n";

try {
    DB::beginTransaction();

    // Alle Lieferanten abrufen
    $suppliers = DB::table('suppliers')->get();
    
    if ($suppliers->isEmpty()) {
        echo "FEHLER: Keine Lieferanten gefunden!\n";
        exit(1);
    }

    // Alle Solaranlagen abrufen
    $solarPlants = DB::table('solar_plants')->get();
    
    if ($solarPlants->isEmpty()) {
        echo "FEHLER: Keine Solaranlagen gefunden!\n";
        exit(1);
    }

    echo "Gefundene Lieferanten: " . $suppliers->count() . "\n";
    echo "Gefundene Solaranlagen: " . $solarPlants->count() . "\n\n";

    // Verträge für jeden Lieferanten erstellen
    echo "Erstelle Lieferantenverträge...\n";
    
    $contracts = [];
    $contractCounter = 1;
    
    foreach ($suppliers as $supplier) {
        $contractId = Str::uuid();
        
        $contract = [
            'id' => $contractId,
            'supplier_id' => $supplier->id,
            'contract_number' => 'VTR-' . str_pad($contractCounter, 4, '0', STR_PAD_LEFT),
            'title' => "Servicevertrag {$supplier->company_name}",
            'description' => "Wartungs- und Servicevertrag für Solaranlagen mit {$supplier->company_name}",
            'start_date' => Carbon::parse('2024-01-01'),
            'end_date' => Carbon::parse('2025-12-31'),
            'contract_value' => rand(50000, 200000),
            'currency' => 'EUR',
            'status' => 'active',
            'payment_terms' => 'Zahlung innerhalb 30 Tagen nach Rechnungsstellung',
            'notes' => "Automatisch generierter Vertrag für Demo-Zwecke",
            'is_active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        
        DB::table('supplier_contracts')->insert($contract);
        $contracts[] = $contract;
        
        echo "✓ Vertrag erstellt: {$contract['contract_number']} - {$supplier->company_name}\n";
        $contractCounter++;
    }

    // Verträge mit Solaranlagen verknüpfen
    echo "\nVerknüpfe Verträge mit Solaranlagen...\n";
    
    $linkCounter = 0;
    
    foreach ($contracts as $contract) {
        // Jeden Vertrag mit allen Solaranlagen verknüpfen (für Demo-Zwecke)
        foreach ($solarPlants as $plant) {
            $percentage = rand(10, 30); // Zufälliger Prozentsatz zwischen 10-30%
            
            $link = [
                'id' => Str::uuid(),
                'supplier_contract_id' => $contract['id'],
                'solar_plant_id' => $plant->id,
                'percentage' => $percentage,
                'notes' => "Automatische Verknüpfung für Demo-Zwecke",
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            
            DB::table('supplier_contract_solar_plants')->insert($link);
            $linkCounter++;
        }
        
        $supplier = $suppliers->firstWhere('id', $contract['supplier_id']);
        echo "✓ Vertrag {$contract['contract_number']} mit allen " . $solarPlants->count() . " Solaranlagen verknüpft\n";
    }

    DB::commit();

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "LIEFERANTENVERTRÄGE ERFOLGREICH ERSTELLT!\n";
    echo str_repeat("=", 60) . "\n\n";

    echo "ZUSAMMENFASSUNG:\n";
    echo "• " . count($contracts) . " Lieferantenverträge erstellt\n";
    echo "• {$linkCounter} Solaranlagen-Verknüpfungen erstellt\n";
    echo "• Alle Verträge sind aktiv und gültig bis Ende 2025\n\n";

    echo "ERSTELLTE VERTRÄGE:\n";
    foreach ($contracts as $contract) {
        $supplier = $suppliers->firstWhere('id', $contract['supplier_id']);
        echo "• {$contract['contract_number']}: {$supplier->company_name} (€" . number_format($contract['contract_value'], 0, ',', '.') . ")\n";
    }

    echo "\nJetzt sollten die Abrechnungsübersichten funktionieren!\n";
    echo "Testen Sie erneut:\n";
    foreach ($solarPlants as $plant) {
        echo "• https://sunnybill-test.test/admin/solar-plant-billing-overviews/{$plant->id}\n";
    }

} catch (Exception $e) {
    DB::rollBack();
    echo "FEHLER beim Erstellen der Verträge: " . $e->getMessage() . "\n";
    echo "Alle Änderungen wurden rückgängig gemacht.\n";
    exit(1);
}
