<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Erstelle Lieferantenabrechnungen für Juli 2024 bis April 2025...\n\n";

try {
    DB::beginTransaction();

    $solarPlantId = '4f950c02-41de-430b-9204-1d4278baeb96';

    // Alle aktiven Verträge für diese Solaranlage abrufen
    $contracts = DB::table('supplier_contracts as sc')
        ->join('supplier_contract_solar_plants as scsp', 'sc.id', '=', 'scsp.supplier_contract_id')
        ->join('suppliers as s', 'sc.supplier_id', '=', 's.id')
        ->where('scsp.solar_plant_id', $solarPlantId)
        ->where('sc.status', 'active')
        ->where('sc.is_active', true)
        ->where('scsp.is_active', true)
        ->select('sc.*', 's.company_name', 'scsp.percentage')
        ->get();

    if ($contracts->isEmpty()) {
        echo "FEHLER: Keine aktiven Verträge für diese Solaranlage gefunden!\n";
        exit(1);
    }

    echo "Gefundene aktive Verträge: " . $contracts->count() . "\n\n";

    // Zeiträume definieren: Juli 2024 bis April 2025
    $billingPeriods = [
        ['year' => 2024, 'month' => 7],  // Juli 2024
        ['year' => 2024, 'month' => 8],  // August 2024
        ['year' => 2024, 'month' => 9],  // September 2024
        ['year' => 2024, 'month' => 10], // Oktober 2024
        ['year' => 2024, 'month' => 11], // November 2024
        ['year' => 2024, 'month' => 12], // Dezember 2024
        ['year' => 2025, 'month' => 1],  // Januar 2025
        ['year' => 2025, 'month' => 2],  // Februar 2025
        ['year' => 2025, 'month' => 3],  // März 2025
        ['year' => 2025, 'month' => 4],  // April 2025
    ];

    $billingCounter = 0;
    $solarMaintenanceContractId = null;

    // SolarMaintenance GmbH Contract ID finden
    foreach ($contracts as $contract) {
        if (str_contains($contract->company_name, 'SolarMaintenance')) {
            $solarMaintenanceContractId = $contract->id;
            break;
        }
    }

    echo "Erstelle Lieferantenabrechnungen...\n";

    foreach ($billingPeriods as $period) {
        foreach ($contracts as $contract) {
            // Status bestimmen
            $status = 'paid'; // Standard: bezahlt (grün)
            
            // Mai 2025: Alle bezahlt außer SolarMaintenance
            if ($period['year'] == 2025 && $period['month'] == 5) {
                if ($contract->id === $solarMaintenanceContractId) {
                    $status = 'pending'; // Ausstehend (rot/orange)
                } else {
                    $status = 'paid'; // Bezahlt (grün)
                }
            }

            // Realistische Beträge generieren
            $baseAmount = rand(2000, 8000);
            $totalAmount = round($baseAmount * ($contract->percentage / 100), 2);

            $monthNames = [
                1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
            ];

            $billing = [
                'id' => Str::uuid(),
                'supplier_contract_id' => $contract->id,
                'billing_number' => 'AB-' . $period['year'] . '-' . str_pad($billingCounter + 1, 4, '0', STR_PAD_LEFT),
                'supplier_invoice_number' => 'RE-' . $period['year'] . str_pad($period['month'], 2, '0', STR_PAD_LEFT) . '-' . rand(1000, 9999),
                'billing_type' => 'invoice',
                'billing_year' => $period['year'],
                'billing_month' => $period['month'],
                'title' => "Abrechnung {$monthNames[$period['month']]} {$period['year']} - {$contract->company_name}",
                'description' => "Monatliche Abrechnung für Serviceleistungen an Solarpark Brandenburg Nord",
                'billing_date' => Carbon::create($period['year'], $period['month'], rand(25, 28)),
                'due_date' => Carbon::create($period['year'], $period['month'], rand(25, 28))->addDays(30),
                'total_amount' => $totalAmount,
                'currency' => 'EUR',
                'status' => $status,
                'notes' => "Automatisch generierte Abrechnung für Demo-Zwecke",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            DB::table('supplier_contract_billings')->insert($billing);
            $billingCounter++;
        }
        
        echo "✓ Abrechnungen für {$monthNames[$period['month']]} {$period['year']} erstellt\n";
    }

    // Mai 2025 zusätzlich erstellen (alle grün außer SolarMaintenance)
    echo "\nErstelle spezielle Abrechnungen für Mai 2025...\n";
    
    foreach ($contracts as $contract) {
        $status = ($contract->id === $solarMaintenanceContractId) ? 'pending' : 'paid';
        
        $baseAmount = rand(2000, 8000);
        $totalAmount = round($baseAmount * ($contract->percentage / 100), 2);

        $billing = [
            'id' => Str::uuid(),
            'supplier_contract_id' => $contract->id,
            'billing_number' => 'AB-2025-' . str_pad($billingCounter + 1, 4, '0', STR_PAD_LEFT),
            'supplier_invoice_number' => 'RE-202505-' . rand(1000, 9999),
            'billing_type' => 'invoice',
            'billing_year' => 2025,
            'billing_month' => 5,
            'title' => "Abrechnung Mai 2025 - {$contract->company_name}",
            'description' => "Monatliche Abrechnung für Serviceleistungen an Solarpark Brandenburg Nord",
            'billing_date' => Carbon::create(2025, 5, rand(25, 28)),
            'due_date' => Carbon::create(2025, 5, rand(25, 28))->addDays(30),
            'total_amount' => $totalAmount,
            'currency' => 'EUR',
            'status' => $status,
            'notes' => $status === 'pending' ? "Ausstehende Zahlung für Demo-Zwecke" : "Automatisch generierte Abrechnung für Demo-Zwecke",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        DB::table('supplier_contract_billings')->insert($billing);
        $billingCounter++;
    }

    echo "✓ Spezielle Abrechnungen für Mai 2025 erstellt\n";

    DB::commit();

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "LIEFERANTENABRECHNUNGEN ERFOLGREICH ERSTELLT!\n";
    echo str_repeat("=", 60) . "\n\n";

    echo "ZUSAMMENFASSUNG:\n";
    echo "• {$billingCounter} Lieferantenabrechnungen erstellt\n";
    echo "• Zeitraum: Juli 2024 - Mai 2025\n";
    echo "• Solaranlage: Solarpark Brandenburg Nord\n";
    echo "• Verträge: " . $contracts->count() . "\n\n";

    echo "STATUS-VERTEILUNG:\n";
    echo "• Juli 2024 - April 2025: Alle bezahlt (grün)\n";
    echo "• Mai 2025: Alle bezahlt außer SolarMaintenance GmbH (ausstehend)\n\n";

    echo "Die Abrechnungsübersicht sollte jetzt vollständig sein!\n";
    echo "URL: https://sunnybill-test.test/admin/solar-plant-billing-overviews/{$solarPlantId}\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "FEHLER beim Erstellen der Abrechnungen: " . $e->getMessage() . "\n";
    echo "Alle Änderungen wurden rückgängig gemacht.\n";
    exit(1);
}
