<?php

require_once 'vendor/autoload.php';

use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Support\Str;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Erstelle zusätzliche Kunden und Lieferanten ===\n\n";

try {
    // 20 weitere Kunden erstellen (Mix aus Privat und Firmen)
    echo "🏢 Erstelle 20 zusätzliche Kunden...\n";
    
    $additionalCustomers = [
        // Privatkunden (10)
        [
            'type' => 'private',
            'first_name' => 'Michael',
            'last_name' => 'Fischer',
            'email' => 'michael.fischer@gmail.com',
            'phone' => '+49 171 2345678',
            'street' => 'Hauptstraße 15',
            'postal_code' => '10115',
            'city' => 'Berlin',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'private',
            'first_name' => 'Sarah',
            'last_name' => 'Wagner',
            'email' => 'sarah.wagner@web.de',
            'phone' => '+49 172 8765432',
            'street' => 'Müllerstraße 23',
            'postal_code' => '80331',
            'city' => 'München',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'private',
            'first_name' => 'Andreas',
            'last_name' => 'Becker',
            'email' => 'andreas.becker@t-online.de',
            'phone' => '+49 173 5555666',
            'street' => 'Parkstraße 8',
            'postal_code' => '50667',
            'city' => 'Köln',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'private',
            'first_name' => 'Julia',
            'last_name' => 'Schulz',
            'email' => 'julia.schulz@yahoo.de',
            'phone' => '+49 174 9988776',
            'street' => 'Lindenallee 42',
            'postal_code' => '70173',
            'city' => 'Stuttgart',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'private',
            'first_name' => 'Robert',
            'last_name' => 'Klein',
            'email' => 'robert.klein@gmx.de',
            'phone' => '+49 175 1122334',
            'street' => 'Rosenweg 12',
            'postal_code' => '20095',
            'city' => 'Hamburg',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'private',
            'first_name' => 'Petra',
            'last_name' => 'Hoffmann',
            'email' => 'petra.hoffmann@freenet.de',
            'phone' => '+49 176 7766554',
            'street' => 'Bergstraße 7',
            'postal_code' => '01067',
            'city' => 'Dresden',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'private',
            'first_name' => 'Stefan',
            'last_name' => 'Richter',
            'email' => 'stefan.richter@online.de',
            'phone' => '+49 177 3344556',
            'street' => 'Gartenstraße 19',
            'postal_code' => '30159',
            'city' => 'Hannover',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'private',
            'first_name' => 'Nicole',
            'last_name' => 'Krüger',
            'email' => 'nicole.krueger@arcor.de',
            'phone' => '+49 178 8899001',
            'street' => 'Kirchgasse 5',
            'postal_code' => '90402',
            'city' => 'Nürnberg',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'private',
            'first_name' => 'Martin',
            'last_name' => 'Neumann',
            'email' => 'martin.neumann@kabel1.de',
            'phone' => '+49 179 4455667',
            'street' => 'Waldweg 33',
            'postal_code' => '45127',
            'city' => 'Essen',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'private',
            'first_name' => 'Sabine',
            'last_name' => 'Braun',
            'email' => 'sabine.braun@alice.de',
            'phone' => '+49 180 1122334',
            'street' => 'Eichenstraße 11',
            'postal_code' => '76131',
            'city' => 'Karlsruhe',
            'country' => 'Deutschland'
        ],
        
        // Firmenkunden (10)
        [
            'type' => 'business',
            'company_name' => 'TechStart GmbH',
            'contact_person' => 'Dr. Klaus Zimmermann',
            'email' => 'info@techstart-gmbh.de',
            'phone' => '+49 30 12345678',
            'street' => 'Potsdamer Platz 1',
            'postal_code' => '10785',
            'city' => 'Berlin',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'business',
            'company_name' => 'Grüne Zukunft AG',
            'contact_person' => 'Maria Schneider',
            'email' => 'kontakt@gruene-zukunft.de',
            'phone' => '+49 89 87654321',
            'street' => 'Marienplatz 8',
            'postal_code' => '80331',
            'city' => 'München',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'business',
            'company_name' => 'Innovativ Solutions UG',
            'contact_person' => 'Thorsten Müller',
            'email' => 'service@innovativ-solutions.com',
            'phone' => '+49 221 9876543',
            'street' => 'Domkloster 4',
            'postal_code' => '50667',
            'city' => 'Köln',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'business',
            'company_name' => 'Nachhaltig Bauen GmbH & Co. KG',
            'contact_person' => 'Anja Weber',
            'email' => 'info@nachhaltig-bauen.de',
            'phone' => '+49 711 5544332',
            'street' => 'Königstraße 1A',
            'postal_code' => '70173',
            'city' => 'Stuttgart',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'business',
            'company_name' => 'Norddeutsche Energie eG',
            'contact_person' => 'Jörg Hansen',
            'email' => 'verwaltung@norddeutsche-energie.de',
            'phone' => '+49 40 3344556',
            'street' => 'Speicherstadt 15',
            'postal_code' => '20457',
            'city' => 'Hamburg',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'business',
            'company_name' => 'Elbtal Immobilien GmbH',
            'contact_person' => 'Christine Wolf',
            'email' => 'info@elbtal-immobilien.de',
            'phone' => '+49 351 7788990',
            'street' => 'Altmarkt 25',
            'postal_code' => '01067',
            'city' => 'Dresden',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'business',
            'company_name' => 'Hannover Consulting Partners',
            'contact_person' => 'Frank Lehmann',
            'email' => 'kontakt@hcp-consulting.de',
            'phone' => '+49 511 1234567',
            'street' => 'Ernst-August-Platz 2',
            'postal_code' => '30159',
            'city' => 'Hannover',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'business',
            'company_name' => 'Franken Solar Systems AG',
            'contact_person' => 'Markus Lange',
            'email' => 'vertrieb@franken-solar.de',
            'phone' => '+49 911 8899001',
            'street' => 'Hauptmarkt 14',
            'postal_code' => '90403',
            'city' => 'Nürnberg',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'business',
            'company_name' => 'Ruhrgebiet Entwicklungsgesellschaft mbH',
            'contact_person' => 'Sandra Koch',
            'email' => 'info@ruhrgebiet-entwicklung.de',
            'phone' => '+49 201 4455667',
            'street' => 'Zollverein 1',
            'postal_code' => '45309',
            'city' => 'Essen',
            'country' => 'Deutschland'
        ],
        [
            'type' => 'business',
            'company_name' => 'Baden Solar Technik GmbH',
            'contact_person' => 'Alexander Roth',
            'email' => 'service@baden-solar-technik.de',
            'phone' => '+49 721 6677889',
            'street' => 'Kaiserstraße 76',
            'postal_code' => '76133',
            'city' => 'Karlsruhe',
            'country' => 'Deutschland'
        ]
    ];

    $createdCustomers = [];
    foreach ($additionalCustomers as $index => $customerData) {
        // Name basierend auf Kundentyp generieren
        $name = $customerData['type'] === 'business' 
            ? $customerData['company_name'] 
            : $customerData['first_name'] . ' ' . $customerData['last_name'];
            
        $customer = Customer::create([
            'customer_number' => 'KD' . str_pad(1000 + $index, 4, '0', STR_PAD_LEFT),
            'name' => $name,
            'type' => $customerData['type'],
            'company_name' => $customerData['company_name'] ?? null,
            'first_name' => $customerData['first_name'] ?? null,
            'last_name' => $customerData['last_name'] ?? null,
            'contact_person' => $customerData['contact_person'] ?? null,
            'email' => $customerData['email'],
            'phone' => $customerData['phone'],
            'street' => $customerData['street'],
            'postal_code' => $customerData['postal_code'],
            'city' => $customerData['city'],
            'country' => $customerData['country'],
            'status' => 'active',
            'notes' => 'Zusätzlicher Testkunde - automatisch erstellt',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $createdCustomers[] = $customer;
        echo "   ✅ Kunde erstellt: {$name} (Nr: {$customer->customer_number})\n";
    }

    echo "\n🏭 Erstelle 10 zusätzliche Lieferanten...\n";
    
    $additionalSuppliers = [
        [
            'company_name' => 'SolarTech Nord GmbH',
            'contact_person' => 'Henrik Andersen',
            'email' => 'info@solartech-nord.de',
            'phone' => '+49 40 1234567',
            'street' => 'Hafenstraße 12',
            'city' => 'Hamburg',
            'services' => 'Solarmodule, Wechselrichter, Montagesysteme'
        ],
        [
            'company_name' => 'Bayern Energie Solutions AG',
            'contact_person' => 'Maximilian Huber',
            'email' => 'vertrieb@bayern-energie.de',
            'phone' => '+49 89 9876543',
            'street' => 'Maximilianstraße 35',
            'city' => 'München',
            'services' => 'Batteriespeicher, Energiemanagementsysteme'
        ],
        [
            'company_name' => 'Rheinland Solar Components',
            'contact_person' => 'Petra Köhler',
            'email' => 'orders@rheinland-solar.com',
            'phone' => '+49 211 5566778',
            'street' => 'Königsallee 88',
            'city' => 'Düsseldorf',
            'services' => 'Photovoltaik-Module, Verkabelung, Überwachungssysteme'
        ],
        [
            'company_name' => 'Ostdeutsche Solartechnik eG',
            'contact_person' => 'Thomas Richter',
            'email' => 'info@ostdeutsche-solar.de',
            'phone' => '+49 341 2233445',
            'street' => 'Augustusplatz 9',
            'city' => 'Leipzig',
            'services' => 'Wechselrichter, Monitoring-Systeme, Service'
        ],
        [
            'company_name' => 'Westfalen Energy Partners GmbH',
            'contact_person' => 'Claudia Bergmann',
            'email' => 'kontakt@westfalen-energy.de',
            'phone' => '+49 231 4455667',
            'street' => 'Phoenix See 1',
            'city' => 'Dortmund',
            'services' => 'Komplette PV-Anlagen, Planung, Installation'
        ],
        [
            'company_name' => 'Nordsee Wind & Solar GmbH',
            'contact_person' => 'Jan Petersen',
            'email' => 'service@nordsee-energy.de',
            'phone' => '+49 4921 778899',
            'street' => 'Deichstraße 25',
            'city' => 'Emden',
            'services' => 'Offshore-taugliche Solarsysteme, Windkraft-Hybridlösungen'
        ],
        [
            'company_name' => 'Süddeutsche Montagesysteme AG',
            'contact_person' => 'Andreas Mayer',
            'email' => 'info@sueddeutsche-montage.de',
            'phone' => '+49 89 6677889',
            'street' => 'Leopoldstraße 156',
            'city' => 'München',
            'services' => 'Dach- und Freiflächenmontage, Spezialhalterungen'
        ],
        [
            'company_name' => 'Green Power Sachsen GmbH',
            'contact_person' => 'Katrin Hoffmann',
            'email' => 'vertrieb@greenpower-sachsen.de',
            'phone' => '+49 351 3344556',
            'street' => 'Prager Straße 10',
            'city' => 'Dresden',
            'services' => 'Energiespeicher, Smart Grid Lösungen'
        ],
        [
            'company_name' => 'Hessen Solar Distribution',
            'contact_person' => 'Michael Stein',
            'email' => 'orders@hessen-solar.de',
            'phone' => '+49 69 1122334',
            'street' => 'Zeil 106',
            'city' => 'Frankfurt am Main',
            'services' => 'Großhandel PV-Komponenten, Logistik'
        ],
        [
            'company_name' => 'Atlantic Solar Systems GmbH',
            'contact_person' => 'Birgit Wagner',
            'email' => 'info@atlantic-solar.de',
            'phone' => '+49 421 9988776',
            'street' => 'Am Markt 15',
            'city' => 'Bremen',
            'services' => 'Marine Solarlösungen, schwimmende PV-Anlagen'
        ]
    ];

    $createdSuppliers = [];
    foreach ($additionalSuppliers as $index => $supplierData) {
        $supplier = Supplier::create([
            'supplier_number' => 'LF' . str_pad(2000 + $index, 4, '0', STR_PAD_LEFT),
            'company_name' => $supplierData['company_name'],
            'contact_person' => $supplierData['contact_person'],
            'email' => $supplierData['email'],
            'phone' => $supplierData['phone'],
            'street' => $supplierData['street'],
            'postal_code' => rand(10000, 99999),
            'city' => $supplierData['city'],
            'country' => 'Deutschland',
            'status' => 'active',
            'services_offered' => $supplierData['services'],
            'notes' => 'Zusätzlicher Test-Lieferant - automatisch erstellt',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $createdSuppliers[] = $supplier;
        echo "   ✅ Lieferant erstellt: {$supplier->company_name} (Nr: {$supplier->supplier_number})\n";
    }

    // Zusammenfassung
    echo "\n" . str_repeat('=', 70) . "\n";
    echo "📊 ZUSÄTZLICHE TESTDATEN ERFOLGREICH ERSTELLT\n";
    echo str_repeat('=', 70) . "\n\n";
    
    echo "🏢 KUNDEN:\n";
    echo "   • Privatkunden: " . count(array_filter($createdCustomers, fn($c) => $c->type === 'private')) . "\n";
    echo "   • Geschäftskunden: " . count(array_filter($createdCustomers, fn($c) => $c->type === 'business')) . "\n";
    echo "   • Gesamt neue Kunden: " . count($createdCustomers) . "\n\n";
    
    echo "🏭 LIEFERANTEN:\n";
    echo "   • Neue Lieferanten: " . count($createdSuppliers) . "\n\n";
    
    // Gesamtanzahl in Datenbank
    $totalCustomers = Customer::count();
    $totalSuppliers = Supplier::count();
    
    echo "📈 GESAMTANZAHL IN DATENBANK:\n";
    echo "   • Kunden gesamt: {$totalCustomers}\n";
    echo "   • Lieferanten gesamt: {$totalSuppliers}\n\n";
    
    echo "✅ Alle zusätzlichen Testdaten wurden erfolgreich erstellt!\n";
    echo "💡 Sie können diese in der Admin-Oberfläche unter /admin/customers und /admin/suppliers einsehen.\n";

} catch (Exception $e) {
    echo "❌ Fehler beim Erstellen der Testdaten: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
