<?php

require_once 'vendor/autoload.php';

use App\Models\SolarPlant;
use App\Models\Customer;
use Illuminate\Support\Str;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Erstelle 15 zusätzliche Solaranlagen ===\n\n";

try {
    // Hole verfügbare Kunden
    $customers = Customer::all();
    
    if ($customers->isEmpty()) {
        echo "❌ Keine Kunden gefunden!\n";
        exit;
    }

    echo "🏢 Erstelle 12 kleinere Solaranlagen + 3 große Solarparks...\n";
    
    $solarPlants = [
        // Kleinere Solaranlagen (12)
        [
            'name' => 'Einfamilienhaus Sonnenstraße',
            'type' => 'residential',
            'capacity_kwp' => 9.8,
            'location' => 'Berlin-Charlottenburg',
            'street' => 'Sonnenstraße 15',
            'postal_code' => '10585',
            'city' => 'Berlin',
            'installation_date' => '2024-03-15',
            'status' => 'active',
            'modules_count' => 28,
            'inverter_type' => 'SMA Sunny Boy 3.0',
            'notes' => 'Private Dachanlage mit Eigenverbrauchsoptimierung'
        ],
        [
            'name' => 'Mehrfamilienhaus Bergblick',
            'type' => 'residential',
            'capacity_kwp' => 15.4,
            'location' => 'München-Schwabing',
            'street' => 'Bergstraße 42',
            'postal_code' => '80333',
            'city' => 'München',
            'installation_date' => '2024-04-22',
            'status' => 'active',
            'modules_count' => 44,
            'inverter_type' => 'Fronius Primo 5.0',
            'notes' => 'Gemeinschaftsanlage für Mehrfamilienhaus'
        ],
        [
            'name' => 'Gewerbeanlage TechPark',
            'type' => 'commercial',
            'capacity_kwp' => 89.6,
            'location' => 'Köln-Ehrenfeld',
            'street' => 'Industriestraße 12',
            'postal_code' => '50823',
            'city' => 'Köln',
            'installation_date' => '2024-02-10',
            'status' => 'active',
            'modules_count' => 256,
            'inverter_type' => 'Huawei SUN2000-100KTL',
            'notes' => 'Gewerbedach mit hohem Eigenverbrauch'
        ],
        [
            'name' => 'Lagerhalle Südwest',
            'type' => 'commercial',
            'capacity_kwp' => 125.0,
            'location' => 'Stuttgart-Feuerbach',
            'street' => 'Logistikweg 8',
            'postal_code' => '70469',
            'city' => 'Stuttgart',
            'installation_date' => '2024-01-18',
            'status' => 'active',
            'modules_count' => 357,
            'inverter_type' => 'SMA Sunny Tripower CORE1',
            'notes' => 'Große Lagerhalle mit Volleinspeisung'
        ],
        [
            'name' => 'Landwirtschaftsbetrieb Nord',
            'type' => 'agricultural',
            'capacity_kwp' => 67.2,
            'location' => 'Hamburg-Bergedorf',
            'street' => 'Feldweg 23',
            'postal_code' => '21029',
            'city' => 'Hamburg',
            'installation_date' => '2024-05-08',
            'status' => 'active',
            'modules_count' => 192,
            'inverter_type' => 'Kostal Plenticore plus 50',
            'notes' => 'Agri-PV Anlage mit Tierhaltung darunter'
        ],
        [
            'name' => 'Krankenhaus Elisenhof',
            'type' => 'commercial',
            'capacity_kwp' => 156.8,
            'location' => 'Dresden-Neustadt',
            'street' => 'Elisenstraße 45',
            'postal_code' => '01097',
            'city' => 'Dresden',
            'installation_date' => '2023-11-12',
            'status' => 'active',
            'modules_count' => 448,
            'inverter_type' => 'ABB TRIO-50.0-TL-OUTD',
            'notes' => 'Kritische Infrastruktur mit Batteriespeicher'
        ],
        [
            'name' => 'Schule Am Lindenberg',
            'type' => 'public',
            'capacity_kwp' => 78.4,
            'location' => 'Hannover-Linden',
            'street' => 'Lindenbergstraße 12',
            'postal_code' => '30449',
            'city' => 'Hannover',
            'installation_date' => '2024-07-05',
            'status' => 'active',
            'modules_count' => 224,
            'inverter_type' => 'SolarEdge SE82.8K',
            'notes' => 'Bildungseinrichtung mit Monitoring-Display'
        ],
        [
            'name' => 'Einkaufszentrum Plaza',
            'type' => 'commercial',
            'capacity_kwp' => 198.2,
            'location' => 'Nürnberg-Südstadt',
            'street' => 'Platz der Freiheit 1',
            'postal_code' => '90459',
            'city' => 'Nürnberg',
            'installation_date' => '2024-03-28',
            'status' => 'active',
            'modules_count' => 566,
            'inverter_type' => 'Huawei SUN2000-215KTL',
            'notes' => 'Shopping Center mit E-Ladestationen'
        ],
        [
            'name' => 'Fabrik Maschinenbau',
            'type' => 'industrial',
            'capacity_kwp' => 234.6,
            'location' => 'Essen-Kettwig',
            'street' => 'Industriepark 15',
            'postal_code' => '45219',
            'city' => 'Essen',
            'installation_date' => '2023-12-14',
            'status' => 'active',
            'modules_count' => 670,
            'inverter_type' => 'SMA Sunny Central 250',
            'notes' => 'Industrieanlage mit Prozesswärme-Kopplung'
        ],
        [
            'name' => 'Wohnanlage Gartenstadt',
            'type' => 'residential',
            'capacity_kwp' => 45.6,
            'location' => 'Karlsruhe-Durlach',
            'street' => 'Gartenstraße 88',
            'postal_code' => '76227',
            'city' => 'Karlsruhe',
            'installation_date' => '2024-06-19',
            'status' => 'active',
            'modules_count' => 130,
            'inverter_type' => 'Fronius Symo 20.0-3',
            'notes' => 'Mieterstrommodell in Wohnanlage'
        ],
        [
            'name' => 'Sportverein FC Sonnenfeld',
            'type' => 'public',
            'capacity_kwp' => 32.8,
            'location' => 'Leipzig-Süd',
            'street' => 'Sportplatzweg 7',
            'postal_code' => '04229',
            'city' => 'Leipzig',
            'installation_date' => '2024-04-03',
            'status' => 'active',
            'modules_count' => 94,
            'inverter_type' => 'SMA Sunny Tripower 25000TL',
            'notes' => 'Vereinsheim mit LED-Flutlichtanlage'
        ],
        [
            'name' => 'Autohaus Elektromobil',
            'type' => 'commercial',
            'capacity_kwp' => 87.4,
            'location' => 'Frankfurt-Höchst',
            'street' => 'Automobilstraße 44',
            'postal_code' => '65929',
            'city' => 'Frankfurt am Main',
            'installation_date' => '2024-05-25',
            'status' => 'active',
            'modules_count' => 250,
            'inverter_type' => 'Huawei SUN2000-100KTL',
            'notes' => 'E-Auto Autohaus mit Schnellladesäulen'
        ],

        // Große Solarparks (3)
        [
            'name' => 'Solarpark Brandenburg Ost',
            'type' => 'utility',
            'capacity_kwp' => 15750.0,
            'location' => 'Cottbus-Ost',
            'street' => 'Energiepark 1',
            'postal_code' => '03050',
            'city' => 'Cottbus',
            'installation_date' => '2023-09-22',
            'status' => 'active',
            'modules_count' => 45000,
            'inverter_type' => 'SMA Sunny Central 2500-EV',
            'notes' => 'Großer Freiflächen-Solarpark auf ehemaligem Tagebaugelände'
        ],
        [
            'name' => 'Energiepark Bayern Süd',
            'type' => 'utility', 
            'capacity_kwp' => 22850.0,
            'location' => 'Ingolstadt-Nord',
            'street' => 'Solarallee 10',
            'postal_code' => '85049',
            'city' => 'Ingolstadt',
            'installation_date' => '2024-01-10',
            'status' => 'active',
            'modules_count' => 65286,
            'inverter_type' => 'ABB PVS980-Central-2500',
            'notes' => 'Einer der größten Solarparks Süddeutschlands mit Agri-PV Elementen'
        ],
        [
            'name' => 'Offshore-Solar Nordsee I',
            'type' => 'utility',
            'capacity_kwp' => 8920.0,
            'location' => 'Wilhelmshaven-Außenbereich',
            'street' => 'Hafenstraße 200',
            'postal_code' => '26382',
            'city' => 'Wilhelmshaven',
            'installation_date' => '2024-08-01',
            'status' => 'active',
            'modules_count' => 25486,
            'inverter_type' => 'SMA Sunny Central 2200-EV-MV',
            'notes' => 'Pilotprojekt für schwimmende Solarpanels - erste Offshore-Solaranlage'
        ]
    ];

    $createdSolarPlants = [];
    foreach ($solarPlants as $index => $plantData) {
        // Zufälligen Kunden zuweisen
        $customer = $customers->random();
        
        $solarPlant = SolarPlant::create([
            'name' => $plantData['name'],
            'total_capacity_kw' => $plantData['capacity_kwp'], // Korrigiert: total_capacity_kw
            'location' => $plantData['location'],
            'installation_date' => $plantData['installation_date'],
            'status' => $plantData['status'],
            'panel_count' => $plantData['modules_count'],
            'notes' => $plantData['notes'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $createdSolarPlants[] = $solarPlant;
        $typeLabel = match($plantData['type']) {
            'residential' => '🏠 Privat',
            'commercial' => '🏢 Gewerbe',
            'industrial' => '🏭 Industrie',
            'agricultural' => '🚜 Landwirtschaft',
            'public' => '🏛️ Öffentlich',
            'utility' => '⚡ Großanlage',
            default => '📍 Sonstiges'
        };
        
        echo "   ✅ {$typeLabel} erstellt: {$plantData['name']} ({$plantData['capacity_kwp']} kWp) - Kunde: {$customer->name}\n";
    }

    // Zusammenfassung
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "☀️ ZUSÄTZLICHE SOLARANLAGEN ERFOLGREICH ERSTELLT\n";
    echo str_repeat('=', 80) . "\n\n";
    
    // Statistik nach Typen (basierend auf ursprünglichen Daten)
    $typeStats = [];
    foreach ($solarPlants as $index => $plantData) {
        $type = $plantData['type'];
        if (!isset($typeStats[$type])) {
            $typeStats[$type] = ['count' => 0, 'capacity' => 0];
        }
        $typeStats[$type]['count']++;
        $typeStats[$type]['capacity'] += $plantData['capacity_kwp'];
    }
    
    echo "📊 ANLAGEN NACH TYPEN:\n";
    foreach ($typeStats as $type => $stats) {
        $typeLabel = match($type) {
            'residential' => '🏠 Privat/Wohnen',
            'commercial' => '🏢 Gewerbe',
            'industrial' => '🏭 Industrie', 
            'agricultural' => '🚜 Landwirtschaft',
            'public' => '🏛️ Öffentlich',
            'utility' => '⚡ Großanlagen/Parks',
            default => '📍 Sonstiges'
        };
        echo "   • {$typeLabel}: {$stats['count']} Anlagen ({$stats['capacity']} kWp)\n";
    }
    
    $totalCapacity = array_sum(array_column($solarPlants, 'capacity_kwp'));
    echo "\n📈 GESAMTSTATISTIK:\n";
    echo "   • Neue Solaranlagen: " . count($createdSolarPlants) . "\n";
    echo "   • Gesamtkapazität: " . number_format($totalCapacity, 1) . " kWp (" . number_format($totalCapacity/1000, 2) . " MWp)\n";
    echo "   • Durchschnittskapazität: " . number_format($totalCapacity / count($createdSolarPlants), 1) . " kWp/Anlage\n";
    
    // Gesamtanzahl in Datenbank
    $totalSolarPlants = SolarPlant::count();
    $totalDatabaseCapacity = SolarPlant::sum('total_capacity_kw');
    
    echo "\n🌞 GESAMTE DATENBANK:\n";
    echo "   • Solaranlagen gesamt: {$totalSolarPlants}\n";
    echo "   • Gesamtkapazität DB: " . number_format($totalDatabaseCapacity, 1) . " kWp (" . number_format($totalDatabaseCapacity/1000, 2) . " MWp)\n\n";
    
    // Highlight der großen Solarparks
    echo "🔥 BESONDERS ERWÄHNENSWERT - DIE 3 GROSSEN SOLARPARKS:\n";
    echo "   🌞 Solarpark Brandenburg Ost: 15.750 kWp (45.000 Module)\n";
    echo "   🌞 Energiepark Bayern Süd: 22.850 kWp (65.286 Module) - GRÖSSTER PARK\n";
    echo "   🌞 Offshore-Solar Nordsee I: 8.920 kWp (25.486 schwimmende Module)\n\n";
    
    echo "✅ Alle 15 Solaranlagen (12 kleinere + 3 Großparks) wurden erfolgreich erstellt!\n";
    echo "💡 Sie können diese in der Admin-Oberfläche unter /admin/solar-plants einsehen.\n";

} catch (Exception $e) {
    echo "❌ Fehler beim Erstellen der Solaranlagen: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
