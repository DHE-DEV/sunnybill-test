<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Erstelle Testdaten für Kunden und Solaranlagen...\n\n";

try {
    DB::beginTransaction();

    // Firmenkunden erstellen
    echo "Erstelle Firmenkunden...\n";
    $businessCustomers = [
        [
            'id' => Str::uuid(),
            'name' => 'Max Mustermann',
            'company_name' => 'SolarTech GmbH',
            'contact_person' => 'Max Mustermann',
            'email' => 'max.mustermann@solartech.de',
            'phone' => '+49 30 12345678',
            'website' => 'https://www.solartech.de',
            'street' => 'Sonnenstraße 15',
            'postal_code' => '10115',
            'city' => 'Berlin',
            'country' => 'Deutschland',
            'tax_number' => 'DE123456789',
            'vat_id' => 'DE123456789',
            'customer_type' => 'business',
            'is_active' => true,
            'notes' => 'Großkunde mit mehreren Solaranlagen-Beteiligungen',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'name' => 'Anna Schmidt',
            'company_name' => 'GreenEnergy Solutions AG',
            'contact_person' => 'Anna Schmidt',
            'email' => 'anna.schmidt@greenenergy.de',
            'phone' => '+49 89 98765432',
            'website' => 'https://www.greenenergy-solutions.de',
            'street' => 'Energieweg 42',
            'postal_code' => '80331',
            'city' => 'München',
            'country' => 'Deutschland',
            'tax_number' => 'DE987654321',
            'vat_id' => 'DE987654321',
            'customer_type' => 'business',
            'is_active' => true,
            'notes' => 'Spezialist für nachhaltige Energielösungen',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'name' => 'Thomas Weber',
            'company_name' => 'Renewable Power AG',
            'contact_person' => 'Thomas Weber',
            'email' => 'thomas.weber@renewable-power.de',
            'phone' => '+49 40 55667788',
            'website' => 'https://www.renewable-power.de',
            'street' => 'Windkraftstraße 8',
            'postal_code' => '20095',
            'city' => 'Hamburg',
            'country' => 'Deutschland',
            'tax_number' => 'DE456789123',
            'vat_id' => 'DE456789123',
            'customer_type' => 'business',
            'is_active' => true,
            'notes' => 'Fokus auf Wind- und Solarenergie',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'name' => 'Michael Krüger',
            'company_name' => 'EcoInvest GmbH & Co. KG',
            'contact_person' => 'Michael Krüger',
            'email' => 'michael.krueger@ecoinvest.de',
            'phone' => '+49 711 33445566',
            'website' => 'https://www.ecoinvest.de',
            'street' => 'Nachhaltigkeitsallee 23',
            'postal_code' => '70173',
            'city' => 'Stuttgart',
            'country' => 'Deutschland',
            'tax_number' => 'DE789123456',
            'vat_id' => 'DE789123456',
            'customer_type' => 'business',
            'is_active' => true,
            'notes' => 'Investmentfirma für ökologische Projekte',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'name' => 'Sarah Hoffmann',
            'company_name' => 'CleanTech Innovations GmbH',
            'contact_person' => 'Sarah Hoffmann',
            'email' => 'sarah.hoffmann@cleantech.de',
            'phone' => '+49 221 77889900',
            'website' => 'https://www.cleantech-innovations.de',
            'street' => 'Innovationspark 12',
            'postal_code' => '50667',
            'city' => 'Köln',
            'country' => 'Deutschland',
            'tax_number' => 'DE321654987',
            'vat_id' => 'DE321654987',
            'customer_type' => 'business',
            'is_active' => true,
            'notes' => 'Technologie-Startup im Bereich saubere Energie',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
    ];

    foreach ($businessCustomers as $customer) {
        DB::table('customers')->insert($customer);
        echo "✓ Firmenkunde erstellt: {$customer['company_name']}\n";
    }

    // Privatkunden erstellen
    echo "\nErstelle Privatkunden...\n";
    $privateCustomers = [
        [
            'id' => Str::uuid(),
            'name' => 'Hans Müller',
            'company_name' => null,
            'contact_person' => null,
            'email' => 'hans.mueller@email.de',
            'phone' => '+49 30 11223344',
            'website' => null,
            'street' => 'Musterstraße 12',
            'postal_code' => '10117',
            'city' => 'Berlin',
            'country' => 'Deutschland',
            'tax_number' => null,
            'vat_id' => null,
            'customer_type' => 'private',
            'is_active' => true,
            'notes' => 'Privatinvestor mit Interesse an nachhaltigen Anlagen',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'name' => 'Maria Fischer',
            'company_name' => null,
            'contact_person' => null,
            'email' => 'maria.fischer@email.de',
            'phone' => '+49 89 44556677',
            'website' => null,
            'street' => 'Gartenweg 7',
            'postal_code' => '80333',
            'city' => 'München',
            'country' => 'Deutschland',
            'tax_number' => null,
            'vat_id' => null,
            'customer_type' => 'private',
            'is_active' => true,
            'notes' => 'Umweltbewusste Privatperson',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'name' => 'Peter Wagner',
            'company_name' => null,
            'contact_person' => null,
            'email' => 'peter.wagner@email.de',
            'phone' => '+49 40 99887766',
            'website' => null,
            'street' => 'Alsterblick 34',
            'postal_code' => '20099',
            'city' => 'Hamburg',
            'country' => 'Deutschland',
            'tax_number' => null,
            'vat_id' => null,
            'customer_type' => 'private',
            'is_active' => true,
            'notes' => 'Rentner mit Interesse an grünen Investments',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'name' => 'Julia Becker',
            'company_name' => null,
            'contact_person' => null,
            'email' => 'julia.becker@email.de',
            'phone' => '+49 711 22334455',
            'website' => null,
            'street' => 'Rosenstraße 19',
            'postal_code' => '70174',
            'city' => 'Stuttgart',
            'country' => 'Deutschland',
            'tax_number' => null,
            'vat_id' => null,
            'customer_type' => 'private',
            'is_active' => true,
            'notes' => 'Junge Investorin mit Fokus auf Nachhaltigkeit',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'name' => 'Robert Klein',
            'company_name' => null,
            'contact_person' => null,
            'email' => 'robert.klein@email.de',
            'phone' => '+49 221 66778899',
            'website' => null,
            'street' => 'Rheinufer 56',
            'postal_code' => '50668',
            'city' => 'Köln',
            'country' => 'Deutschland',
            'tax_number' => null,
            'vat_id' => null,
            'customer_type' => 'private',
            'is_active' => true,
            'notes' => 'Selbstständiger mit eigenem Solaranlage-Investment',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'name' => 'Claudia Richter',
            'company_name' => null,
            'contact_person' => null,
            'email' => 'claudia.richter@email.de',
            'phone' => '+49 69 12345678',
            'website' => null,
            'street' => 'Mainstraße 88',
            'postal_code' => '60311',
            'city' => 'Frankfurt am Main',
            'country' => 'Deutschland',
            'tax_number' => null,
            'vat_id' => null,
            'customer_type' => 'private',
            'is_active' => true,
            'notes' => 'Bankangestellte mit Interesse an alternativen Investments',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
    ];

    foreach ($privateCustomers as $customer) {
        DB::table('customers')->insert($customer);
        echo "✓ Privatkunde erstellt: {$customer['name']}\n";
    }

    // Solaranlagen erstellen
    echo "\nErstelle Solaranlagen...\n";
    $solarPlants = [
        [
            'id' => Str::uuid(),
            'name' => 'Solarpark Brandenburg Nord',
            'location' => 'Brandenburg an der Havel',
            'description' => 'Großer Solarpark mit 500 kWp Leistung auf ehemaligem Industriegelände',
            'installation_date' => Carbon::parse('2023-03-15'),
            'commissioning_date' => Carbon::parse('2023-04-01'),
            'total_capacity_kw' => 500.000000,
            'panel_count' => 1250,
            'inverter_count' => 10,
            'battery_capacity_kwh' => 200.000000,
            'expected_annual_yield_kwh' => 550000.000000,
            'total_investment' => 750000.00,
            'annual_operating_costs' => 15000.00,
            'feed_in_tariff_per_kwh' => 0.082000,
            'electricity_price_per_kwh' => 0.280000,
            'status' => 'active',
            'is_active' => true,
            'notes' => 'Großer Solarpark mit hoher Rendite',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'name' => 'Dachanlage München Süd',
            'location' => 'München',
            'description' => 'Dachanlage auf Gewerbegebäude mit 150 kWp Leistung',
            'installation_date' => Carbon::parse('2023-06-20'),
            'commissioning_date' => Carbon::parse('2023-07-01'),
            'total_capacity_kw' => 150.000000,
            'panel_count' => 375,
            'inverter_count' => 3,
            'battery_capacity_kwh' => 75.000000,
            'expected_annual_yield_kwh' => 165000.000000,
            'total_investment' => 225000.00,
            'annual_operating_costs' => 4500.00,
            'feed_in_tariff_per_kwh' => 0.082000,
            'electricity_price_per_kwh' => 0.280000,
            'status' => 'active',
            'is_active' => true,
            'notes' => 'Effiziente Dachanlage in München',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'name' => 'Agri-PV Anlage Niedersachsen',
            'location' => 'Hannover',
            'description' => 'Innovative Agri-Photovoltaik-Anlage mit 300 kWp auf landwirtschaftlicher Fläche',
            'installation_date' => Carbon::parse('2023-09-10'),
            'commissioning_date' => Carbon::parse('2023-10-01'),
            'total_capacity_kw' => 300.000000,
            'panel_count' => 750,
            'inverter_count' => 6,
            'battery_capacity_kwh' => 150.000000,
            'expected_annual_yield_kwh' => 330000.000000,
            'total_investment' => 450000.00,
            'annual_operating_costs' => 9000.00,
            'feed_in_tariff_per_kwh' => 0.082000,
            'electricity_price_per_kwh' => 0.280000,
            'status' => 'active',
            'is_active' => true,
            'notes' => 'Innovative Agri-PV mit Doppelnutzung',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'id' => Str::uuid(),
            'name' => 'Floating Solar Rheinland',
            'location' => 'Köln',
            'description' => 'Schwimmende Solaranlage auf Baggersee mit 200 kWp Leistung',
            'installation_date' => Carbon::parse('2023-11-05'),
            'commissioning_date' => Carbon::parse('2023-12-01'),
            'total_capacity_kw' => 200.000000,
            'panel_count' => 500,
            'inverter_count' => 4,
            'battery_capacity_kwh' => 100.000000,
            'expected_annual_yield_kwh' => 220000.000000,
            'total_investment' => 350000.00,
            'annual_operating_costs' => 7000.00,
            'feed_in_tariff_per_kwh' => 0.082000,
            'electricity_price_per_kwh' => 0.280000,
            'status' => 'active',
            'is_active' => true,
            'notes' => 'Schwimmende Solaranlage - Pilotprojekt',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
    ];

    foreach ($solarPlants as $plant) {
        DB::table('solar_plants')->insert($plant);
        echo "✓ Solaranlage erstellt: {$plant['name']}\n";
    }

    // Beteiligungen erstellen
    echo "\nErstelle Beteiligungen...\n";
    
    // Alle Kunden und Anlagen IDs sammeln
    $allCustomers = array_merge($businessCustomers, $privateCustomers);
    $customerIds = array_column($allCustomers, 'id');
    $plantIds = array_column($solarPlants, 'id');

    $participations = [
        // Solarpark Brandenburg Nord - Beteiligungen
        ['customer_id' => $businessCustomers[0]['id'], 'solar_plant_id' => $plantIds[0], 'percentage' => 35.00], // SolarTech GmbH
        ['customer_id' => $businessCustomers[1]['id'], 'solar_plant_id' => $plantIds[0], 'percentage' => 25.00], // GreenEnergy Solutions AG
        ['customer_id' => $privateCustomers[0]['id'], 'solar_plant_id' => $plantIds[0], 'percentage' => 15.00], // Hans Müller
        ['customer_id' => $privateCustomers[1]['id'], 'solar_plant_id' => $plantIds[0], 'percentage' => 12.50], // Maria Fischer
        ['customer_id' => $privateCustomers[2]['id'], 'solar_plant_id' => $plantIds[0], 'percentage' => 12.50], // Peter Wagner

        // Dachanlage München Süd - Beteiligungen
        ['customer_id' => $businessCustomers[1]['id'], 'solar_plant_id' => $plantIds[1], 'percentage' => 40.00], // GreenEnergy Solutions AG
        ['customer_id' => $businessCustomers[3]['id'], 'solar_plant_id' => $plantIds[1], 'percentage' => 30.00], // EcoInvest GmbH & Co. KG
        ['customer_id' => $privateCustomers[1]['id'], 'solar_plant_id' => $plantIds[1], 'percentage' => 20.00], // Maria Fischer
        ['customer_id' => $privateCustomers[3]['id'], 'solar_plant_id' => $plantIds[1], 'percentage' => 10.00], // Julia Becker

        // Agri-PV Anlage Niedersachsen - Beteiligungen
        ['customer_id' => $businessCustomers[2]['id'], 'solar_plant_id' => $plantIds[2], 'percentage' => 45.00], // Renewable Power AG
        ['customer_id' => $businessCustomers[4]['id'], 'solar_plant_id' => $plantIds[2], 'percentage' => 25.00], // CleanTech Innovations GmbH
        ['customer_id' => $privateCustomers[2]['id'], 'solar_plant_id' => $plantIds[2], 'percentage' => 15.00], // Peter Wagner
        ['customer_id' => $privateCustomers[4]['id'], 'solar_plant_id' => $plantIds[2], 'percentage' => 10.00], // Robert Klein
        ['customer_id' => $privateCustomers[5]['id'], 'solar_plant_id' => $plantIds[2], 'percentage' => 5.00],  // Claudia Richter

        // Floating Solar Rheinland - Beteiligungen
        ['customer_id' => $businessCustomers[0]['id'], 'solar_plant_id' => $plantIds[3], 'percentage' => 30.00], // SolarTech GmbH
        ['customer_id' => $businessCustomers[4]['id'], 'solar_plant_id' => $plantIds[3], 'percentage' => 25.00], // CleanTech Innovations GmbH
        ['customer_id' => $privateCustomers[0]['id'], 'solar_plant_id' => $plantIds[3], 'percentage' => 20.00], // Hans Müller
        ['customer_id' => $privateCustomers[3]['id'], 'solar_plant_id' => $plantIds[3], 'percentage' => 15.00], // Julia Becker
        ['customer_id' => $privateCustomers[4]['id'], 'solar_plant_id' => $plantIds[3], 'percentage' => 10.00], // Robert Klein
    ];

    foreach ($participations as $participation) {
        $participation['created_at'] = Carbon::now();
        $participation['updated_at'] = Carbon::now();
        DB::table('plant_participations')->insert($participation);
        
        // Kundennamen und Anlagennamen für bessere Ausgabe finden
        $customer = collect($allCustomers)->firstWhere('id', $participation['customer_id']);
        $plant = collect($solarPlants)->firstWhere('id', $participation['solar_plant_id']);
        $customerName = $customer['company_name'] ?? $customer['name'];
        
        echo "✓ Beteiligung erstellt: {$customerName} - {$plant['name']} ({$participation['percentage']}%)\n";
    }

    DB::commit();

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "TESTDATEN ERFOLGREICH ERSTELLT!\n";
    echo str_repeat("=", 60) . "\n\n";

    echo "ZUSAMMENFASSUNG:\n";
    echo "• " . count($businessCustomers) . " Firmenkunden erstellt\n";
    echo "• " . count($privateCustomers) . " Privatkunden erstellt\n";
    echo "• " . count($solarPlants) . " Solaranlagen erstellt\n";
    echo "• " . count($participations) . " Beteiligungen erstellt\n\n";

    echo "SOLARANLAGEN ÜBERSICHT:\n";
    foreach ($solarPlants as $plant) {
        $plantParticipations = array_filter($participations, function($p) use ($plant) {
            return $p['solar_plant_id'] === $plant['id'];
        });
        $totalPercentage = array_sum(array_column($plantParticipations, 'percentage'));
        echo "• {$plant['name']}: {$plant['total_capacity_kw']} kWp, {$totalPercentage}% verteilt\n";
    }

    echo "\nDie Testdaten sind jetzt in der Datenbank verfügbar!\n";
    echo "Sie können diese im VoltMaster Admin-Panel unter /admin einsehen.\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "FEHLER beim Erstellen der Testdaten: " . $e->getMessage() . "\n";
    echo "Alle Änderungen wurden rückgängig gemacht.\n";
    exit(1);
}
