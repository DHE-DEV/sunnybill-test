<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Erstelle Lieferanten und Abrechnungen...\n\n";

try {
    DB::beginTransaction();

    // Zuerst prüfen, ob die Solaranlage existiert
    $solarPlantId = '4f950c02-41de-430b-9204-1d4278baeb96';
    $solarPlant = DB::table('solar_plants')->where('id', $solarPlantId)->first();
    
    if (!$solarPlant) {
        echo "FEHLER: Solaranlage mit ID {$solarPlantId} nicht gefunden!\n";
        echo "Verfügbare Solaranlagen:\n";
        $plants = DB::table('solar_plants')->select('id', 'name')->get();
        foreach ($plants as $plant) {
            echo "- {$plant->id}: {$plant->name}\n";
        }
        exit(1);
    }

    echo "Arbeite mit Solaranlage: {$solarPlant->name}\n\n";

    // Lieferanten erstellen
    echo "Erstelle Lieferanten...\n";
    $suppliers = [
        [
            'id' => Str::uuid(),
            'company_name' => 'SolarMaintenance GmbH',
            'contact_person' => 'Klaus Wartung',
            'email' => 'klaus.wartung@solarmaintenance.de',
            'website' => 'https://www.solarmaintenance.de',
            'address' => 'Wartungsstraße 15',
            'postal_code' => '10115',
            'city' => 'Berlin',
            'country' => 'Deutschland',
            'tax_number' => 'DE111222333',
            'vat_id' => 'DE111222333',
            'notes' => 'Spezialist für Solaranlagen-Wartung und -Reparatur',
            'is_active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'company_name' => 'GreenClean Services AG',
            'contact_person' => 'Maria Reinigung',
            'email' => 'maria.reinigung@greenclean.de',
            'website' => 'https://www.greenclean-services.de',
            'address' => 'Sauberkeitsweg 42',
            'postal_code' => '80331',
            'city' => 'München',
            'country' => 'Deutschland',
            'tax_number' => 'DE444555666',
            'vat_id' => 'DE444555666',
            'notes' => 'Professionelle Reinigung von Solaranlagen',
            'is_active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'company_name' => 'ElektroTech Solutions',
            'contact_person' => 'Thomas Elektrik',
            'email' => 'thomas.elektrik@elektrotech.de',
            'website' => 'https://www.elektrotech-solutions.de',
            'address' => 'Stromstraße 88',
            'postal_code' => '20095',
            'city' => 'Hamburg',
            'country' => 'Deutschland',
            'tax_number' => 'DE777888999',
            'vat_id' => 'DE777888999',
            'notes' => 'Elektrische Installationen und Reparaturen',
            'is_active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
    ];

    foreach ($suppliers as $supplier) {
        DB::table('suppliers')->insert($supplier);
        echo "✓ Lieferant erstellt: {$supplier['company_name']}\n";
    }

    // Beteiligungen für diese Solaranlage abrufen
    $participations = DB::table('plant_participations')
        ->where('solar_plant_id', $solarPlantId)
        ->get();

    if ($participations->isEmpty()) {
        echo "WARNUNG: Keine Beteiligungen für diese Solaranlage gefunden!\n";
        DB::rollBack();
        exit(1);
    }

    echo "\nGefundene Beteiligungen: " . $participations->count() . "\n";

    // Abrechnungen für November 2024 - Juni 2025 erstellen
    // Januar 2025 und Juni 2025 auslassen wie gewünscht
    echo "\nErstelle Abrechnungen...\n";
    
    $billingPeriods = [
        ['year' => 2024, 'month' => 11], // November 2024
        ['year' => 2024, 'month' => 12], // Dezember 2024
        // Januar 2025 auslassen
        ['year' => 2025, 'month' => 2],  // Februar 2025
        ['year' => 2025, 'month' => 3],  // März 2025
        ['year' => 2025, 'month' => 4],  // April 2025
        ['year' => 2025, 'month' => 5],  // Mai 2025
        // Juni 2025 auslassen
    ];

    $billingCounter = 0;
    
    foreach ($billingPeriods as $period) {
        foreach ($participations as $participation) {
            // Realistische Kosten und Gutschriften generieren
            $baseCosts = rand(500, 2000); // Grundkosten zwischen 500-2000€
            $baseCredits = rand(800, 3000); // Grundgutschriften zwischen 800-3000€
            
            // Anteilig nach Beteiligung
            $costs = round($baseCosts * ($participation->percentage / 100), 2);
            $credits = round($baseCredits * ($participation->percentage / 100), 2);
            $netAmount = $credits - $costs;

            $costBreakdown = [
                'wartung' => round($costs * 0.4, 2),
                'reinigung' => round($costs * 0.3, 2),
                'versicherung' => round($costs * 0.2, 2),
                'verwaltung' => round($costs * 0.1, 2),
            ];

            $creditBreakdown = [
                'einspeiseverguetung' => round($credits * 0.7, 2),
                'direktvermarktung' => round($credits * 0.3, 2),
            ];

            $billing = [
                'id' => Str::uuid(),
                'solar_plant_id' => $solarPlantId,
                'customer_id' => $participation->customer_id,
                'billing_year' => $period['year'],
                'billing_month' => $period['month'],
                'participation_percentage' => $participation->percentage,
                'total_costs' => $costs,
                'total_credits' => $credits,
                'net_amount' => $netAmount,
                'status' => 'finalized',
                'cost_breakdown' => json_encode($costBreakdown),
                'credit_breakdown' => json_encode($creditBreakdown),
                'finalized_at' => Carbon::create($period['year'], $period['month'], 28)->addDays(rand(1, 3)),
                'notes' => "Automatisch generierte Abrechnung für {$period['month']}/{$period['year']}",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            DB::table('solar_plant_billings')->insert($billing);
            $billingCounter++;
        }
        
        echo "✓ Abrechnungen für {$period['month']}/{$period['year']} erstellt\n";
    }

    DB::commit();

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "LIEFERANTEN UND ABRECHNUNGEN ERFOLGREICH ERSTELLT!\n";
    echo str_repeat("=", 60) . "\n\n";

    echo "ZUSAMMENFASSUNG:\n";
    echo "• " . count($suppliers) . " Lieferanten erstellt\n";
    echo "• {$billingCounter} Abrechnungen erstellt\n";
    echo "• Zeitraum: November 2024 - Mai 2025 (Januar und Juni ausgelassen)\n";
    echo "• Solaranlage: {$solarPlant->name}\n\n";

    echo "ERSTELLTE LIEFERANTEN:\n";
    foreach ($suppliers as $supplier) {
        echo "• {$supplier['company_name']} - {$supplier['notes']}\n";
    }

    echo "\nABRECHNUNGS-ZEITRÄUME:\n";
    foreach ($billingPeriods as $period) {
        $monthName = [
            11 => 'November', 12 => 'Dezember', 2 => 'Februar', 
            3 => 'März', 4 => 'April', 5 => 'Mai'
        ][$period['month']];
        echo "• {$monthName} {$period['year']}\n";
    }

    echo "\nAUSGELASSENE MONATE (für Demo-Zwecke):\n";
    echo "• Januar 2025\n";
    echo "• Juni 2025\n";

    echo "\nDie Daten sind jetzt im Admin-Panel verfügbar!\n";
    echo "URL: https://sunnybill-test.test/admin/solar-plant-billing-overviews/{$solarPlantId}\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "FEHLER beim Erstellen der Daten: " . $e->getMessage() . "\n";
    echo "Alle Änderungen wurden rückgängig gemacht.\n";
    exit(1);
}
