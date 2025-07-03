<?php

require_once 'vendor/autoload.php';

use App\Models\SolarPlant;
use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\SupplierContractSolarPlant;
use Illuminate\Support\Facades\DB;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Erstelle Testdaten für Solaranlagen-Abrechnungsübersicht...\n";

try {
    DB::beginTransaction();
    
    // Hole die erste Solaranlage
    $solarPlant = SolarPlant::first();
    if (!$solarPlant) {
        echo "Keine Solaranlage gefunden!\n";
        exit(1);
    }
    
    echo "Verwende Solaranlage: {$solarPlant->name}\n";
    
    // Erstelle einen Testlieferanten falls nicht vorhanden
    $supplier = Supplier::firstOrCreate([
        'company_name' => 'E.ON Energie Deutschland GmbH'
    ], [
        'display_name' => 'E.ON Energie Deutschland GmbH',
        'contact_person' => 'Max Mustermann',
        'email' => 'max.mustermann@eon.de',
        'phone' => '+49 123 456789',
        'address' => 'Musterstraße 1',
        'city' => 'München',
        'postal_code' => '80331',
        'country' => 'Deutschland',
        'tax_number' => 'DE123456789',
        'status' => 'active'
    ]);
    
    echo "Lieferant erstellt/gefunden: {$supplier->company_name}\n";
    
    // Erstelle einen aktiven Lieferantenvertrag
    $contract = SupplierContract::firstOrCreate([
        'supplier_id' => $supplier->id,
        'contract_number' => 'EON-2024-001'
    ], [
        'title' => 'Energieversorgung E.ON',
        'start_date' => now()->subYear(),
        'end_date' => now()->addYear(),
        'status' => 'active',
        'contract_value' => 15000.00,
        'currency' => 'EUR',
        'payment_terms' => 'net_30',
        'description' => 'Energieversorgungsvertrag mit E.ON',
        'is_active' => true
    ]);
    
    echo "Lieferantenvertrag erstellt/gefunden: {$contract->contract_number}\n";
    
    // Verknüpfe die Solaranlage mit dem Vertrag falls nicht bereits verknüpft
    $existingAssignment = SupplierContractSolarPlant::where('supplier_contract_id', $contract->id)
        ->where('solar_plant_id', $solarPlant->id)
        ->first();
        
    if (!$existingAssignment) {
        SupplierContractSolarPlant::create([
            'supplier_contract_id' => $contract->id,
            'solar_plant_id' => $solarPlant->id,
            'percentage' => 100.0,
            'is_active' => true,
            'notes' => 'Vollständige Energieversorgung'
        ]);
        echo "Solaranlage mit Vertrag verknüpft\n";
    } else {
        echo "Solaranlage bereits mit Vertrag verknüpft\n";
    }
    
    DB::commit();
    
    echo "\n✅ Testdaten erfolgreich erstellt!\n";
    echo "Solaranlage: {$solarPlant->name}\n";
    echo "Lieferant: {$supplier->company_name}\n";
    echo "Vertrag: {$contract->contract_number} - {$contract->title}\n";
    echo "\nJetzt sollten in der Abrechnungsübersicht fehlende Abrechnungen angezeigt werden.\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "❌ Fehler beim Erstellen der Testdaten: " . $e->getMessage() . "\n";
    exit(1);
}