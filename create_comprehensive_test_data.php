<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;
use App\Models\Article;
use App\Models\CompanySetting;
use App\Models\Team;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Erstelle umfassende Testdaten für SunnyBill System ===\n\n";

try {
    // 1. Company Settings
    echo "1. Company Settings...\n";
    $companySettings = CompanySetting::firstOrCreate(['company_name' => 'SunnyBill Solar GmbH'], [
        'company_name' => 'SunnyBill Solar GmbH',
        'company_legal_form' => 'GmbH',
        'company_address' => 'Sonnenstraße 123',
        'company_postal_code' => '12345',
        'company_city' => 'Solarstadt',
        'company_country' => 'Deutschland',
        'phone' => '+49 30 12345678',
        'email' => 'info@sunnybill-solar.de',
        'website' => 'www.sunnybill-solar.de',
        'tax_number' => 'DE123456789',
        'vat_id' => 'DE987654321',
        'default_payment_days' => 30,
        'article_price_decimal_places' => 2,
        'total_price_decimal_places' => 2,
        'customer_number_prefix' => 'K',
        'supplier_number_prefix' => 'L',
        'invoice_number_prefix' => 'RE',
        'invoice_number_include_year' => true,
    ]);

    // 2. Teams
    echo "2. Teams...\n";
    $adminTeam = Team::firstOrCreate(['name' => 'Administratoren', 'description' => 'System-Administratoren']);
    $managerTeam = Team::firstOrCreate(['name' => 'Projektmanager', 'description' => 'Projektmanagement Team']);
    $techTeam = Team::firstOrCreate(['name' => 'Techniker', 'description' => 'Technisches Personal']);

    // 3. Users
    echo "3. Benutzer...\n";
    $admin = User::firstOrCreate([
        'email' => 'admin@sunnybill.de'
    ], [
        'name' => 'System Administrator',
        'password' => Hash::make('admin123'),
        'email_verified_at' => now(),
        'is_admin' => true,
        'company_setting_id' => $companySettings->id,
    ]);

    $manager = User::firstOrCreate([
        'email' => 'manager@sunnybill.de'
    ], [
        'name' => 'Max Mustermann',
        'password' => Hash::make('manager123'),
        'email_verified_at' => now(),
        'company_setting_id' => $companySettings->id,
    ]);

    $technician = User::firstOrCreate([
        'email' => 'tech@sunnybill.de'
    ], [
        'name' => 'Anna Schmidt',
        'password' => Hash::make('tech123'),
        'email_verified_at' => now(),
        'company_setting_id' => $companySettings->id,
    ]);

    // Team-Zuordnungen
    $admin->teams()->syncWithoutDetaching([$adminTeam->id]);
    $manager->teams()->syncWithoutDetaching([$managerTeam->id]);
    $technician->teams()->syncWithoutDetaching([$techTeam->id]);

    // 4. Task Types
    echo "4. Task Types...\n";
    $taskTypes = [
        'Planung' => 'Planungsaufgaben',
        'Installation' => 'Installationsarbeiten',
        'Wartung' => 'Wartungsarbeiten',
        'Reparatur' => 'Reparaturarbeiten',
        'Dokumentation' => 'Dokumentationsaufgaben',
        'Kundenkommunikation' => 'Kommunikation mit Kunden',
        'Behördengänge' => 'Behördliche Angelegenheiten',
    ];

    foreach ($taskTypes as $name => $description) {
        TaskType::firstOrCreate([
            'name' => $name
        ], [
            'description' => $description,
            'is_active' => true,
        ]);
    }

    // 5. Articles
    echo "5. Artikel...\n";
    $articles = [
        ['name' => 'Solarmodul 400W', 'type' => 'product', 'price' => 280.00, 'unit' => 'Stück', 'tax_rate' => 0.19],
        ['name' => 'Wechselrichter 10kW', 'type' => 'product', 'price' => 1200.00, 'unit' => 'Stück', 'tax_rate' => 0.19],
        ['name' => 'Montagesystem', 'type' => 'product', 'price' => 150.00, 'unit' => 'Stück', 'tax_rate' => 0.19],
        ['name' => 'Installationsarbeit', 'type' => 'service', 'price' => 65.00, 'unit' => 'Stunden', 'tax_rate' => 0.19],
        ['name' => 'Planung und Projektierung', 'type' => 'service', 'price' => 85.00, 'unit' => 'Stunden', 'tax_rate' => 0.19],
        ['name' => 'Wartungsservice', 'type' => 'service', 'price' => 75.00, 'unit' => 'Stunden', 'tax_rate' => 0.19],
    ];

    foreach ($articles as $articleData) {
        Article::firstOrCreate([
            'name' => $articleData['name']
        ], $articleData);
    }

    // 6. Customers
    echo "6. Kunden...\n";
    $customers = [
        [
            'customer_number' => 'K-2025-001',
            'name' => 'Hans Müller',
            'company_name' => 'Müller GmbH',
            'contact_person' => 'Hans Müller',
            'email' => 'hans.mueller@mueller-gmbh.de',
            'phone' => '+49 30 111111',
            'street' => 'Hauptstraße 10',
            'postal_code' => '10115',
            'city' => 'Berlin',
            'country' => 'Deutschland',
            'customer_type' => 'business',
        ],
        [
            'customer_number' => 'K-2025-002', 
            'name' => 'Maria Schmidt',
            'company_name' => 'Schmidt & Co KG',
            'contact_person' => 'Maria Schmidt',
            'email' => 'maria.schmidt@schmidt-co.de',
            'phone' => '+49 40 222222',
            'street' => 'Industrieweg 25',
            'postal_code' => '20095',
            'city' => 'Hamburg',
            'country' => 'Deutschland',
            'customer_type' => 'business',
        ],
        [
            'customer_number' => 'K-2025-003',
            'name' => 'Thomas Weber',
            'email' => 'thomas.weber@web.de',
            'phone' => '+49 89 333333',
            'street' => 'Sonnenallee 42',
            'postal_code' => '80331',
            'city' => 'München',
            'country' => 'Deutschland',
            'customer_type' => 'private',
        ],
    ];

    $createdCustomers = [];
    foreach ($customers as $customerData) {
        $customer = Customer::firstOrCreate([
            'customer_number' => $customerData['customer_number']
        ], $customerData);
        $createdCustomers[] = $customer;
    }

    // 7. Suppliers
    echo "7. Lieferanten...\n";
    $suppliers = [
        [
            'supplier_number' => 'L-001',
            'company_name' => 'SolarTech Deutschland GmbH',
            'first_name' => 'Klaus',
            'last_name' => 'Richter',
            'email' => 'klaus.richter@solartech.de',
            'phone' => '+49 351 444444',
            'street' => 'Technologiepark 15',
            'postal_code' => '01109',
            'city' => 'Dresden',
            'country' => 'Deutschland',
        ],
        [
            'supplier_number' => 'L-002',
            'company_name' => 'Grüne Energie AG',
            'first_name' => 'Petra',
            'last_name' => 'Klein',
            'email' => 'petra.klein@gruene-energie.de',
            'phone' => '+49 711 555555',
            'street' => 'Umweltstraße 8',
            'postal_code' => '70173',
            'city' => 'Stuttgart',
            'country' => 'Deutschland',
        ],
    ];

    $createdSuppliers = [];
    foreach ($suppliers as $supplierData) {
        $supplier = Supplier::firstOrCreate([
            'supplier_number' => $supplierData['supplier_number']
        ], $supplierData);
        $createdSuppliers[] = $supplier;
    }

    // 8. Solar Plants
    echo "8. Solaranlagen...\n";
    $solarPlants = [
        [
            'plant_number' => 'SP-2025-001',
            'name' => 'Müller GmbH Dachanlage',
            'location' => 'Hauptstraße 10, 10115 Berlin, Deutschland',
            'total_capacity_kw' => 45.6,
            'panel_count' => 114,
            'status' => 'active',
            'installation_date' => now()->subDays(30),
            'latitude' => 52.5200,
            'longitude' => 13.4050,
            'description' => 'Dachanlage für Müller GmbH',
        ],
        [
            'plant_number' => 'SP-2025-002',
            'name' => 'Schmidt & Co Industrieanlage',
            'location' => 'Industrieweg 25, 20095 Hamburg, Deutschland',
            'total_capacity_kw' => 98.4,
            'panel_count' => 246,
            'status' => 'in_planning',
            'planned_installation_date' => now()->addDays(45),
            'latitude' => 53.5511,
            'longitude' => 9.9937,
            'description' => 'Industrieanlage für Schmidt & Co KG',
        ],
        [
            'plant_number' => 'SP-2025-003',
            'name' => 'Weber Einfamilienhaus',
            'location' => 'Sonnenallee 42, 80331 München, Deutschland',
            'total_capacity_kw' => 12.8,
            'panel_count' => 32,
            'status' => 'active',
            'installation_date' => now()->subDays(60),
            'latitude' => 48.1351,
            'longitude' => 11.5820,
            'description' => 'Einfamilienhaus-Anlage für Thomas Weber',
        ],
    ];

    $createdSolarPlants = [];
    foreach ($solarPlants as $plantData) {
        $plant = SolarPlant::firstOrCreate([
            'plant_number' => $plantData['plant_number']
        ], $plantData);
        $createdSolarPlants[] = $plant;
    }

    // 9. Projects
    echo "9. Projekte...\n";
    $projects = [
        [
            'project_number' => 'PRJ-2025-001',
            'name' => 'Müller GmbH Solarprojekt',
            'type' => 'solar_plant',
            'status' => 'completed',
            'priority' => 'medium',
            'start_date' => now()->subDays(90),
            'planned_end_date' => now()->subDays(30),
            'actual_end_date' => now()->subDays(25),
            'budget' => 35000.00,
            'actual_costs' => 33500.00,
            'progress_percentage' => 100,
            'customer_id' => $createdCustomers[0]->id,
            'solar_plant_id' => $createdSolarPlants[0]->id,
            'project_manager_id' => $manager->id,
            'created_by' => $admin->id,
            'description' => 'Installation einer 45,6 kW Dachanlage',
        ],
        [
            'project_number' => 'PRJ-2025-002',
            'name' => 'Schmidt & Co Großprojekt',
            'type' => 'solar_plant',
            'status' => 'active',
            'priority' => 'high',
            'start_date' => now()->subDays(15),
            'planned_end_date' => now()->addDays(30),
            'budget' => 85000.00,
            'actual_costs' => 12000.00,
            'progress_percentage' => 25,
            'customer_id' => $createdCustomers[1]->id,
            'solar_plant_id' => $createdSolarPlants[1]->id,
            'project_manager_id' => $manager->id,
            'created_by' => $admin->id,
            'description' => 'Installation einer 98,4 kW Industrieanlage',
        ],
        [
            'project_number' => 'PRJ-2025-003',
            'name' => 'Weber Einfamilienhaus Projekt',
            'type' => 'solar_plant',
            'status' => 'completed',
            'priority' => 'low',
            'start_date' => now()->subDays(120),
            'planned_end_date' => now()->subDays(60),
            'actual_end_date' => now()->subDays(55),
            'budget' => 15000.00,
            'actual_costs' => 14200.00,
            'progress_percentage' => 100,
            'customer_id' => $createdCustomers[2]->id,
            'solar_plant_id' => $createdSolarPlants[2]->id,
            'project_manager_id' => $technician->id,
            'created_by' => $admin->id,
            'description' => 'Installation einer 12,8 kW Einfamilienhausanlage',
        ],
    ];

    $createdProjects = [];
    foreach ($projects as $projectData) {
        $project = Project::firstOrCreate([
            'project_number' => $projectData['project_number']
        ], $projectData);
        $createdProjects[] = $project;
    }

    // 10. Tasks
    echo "10. Tasks...\n";
    $installationTaskType = TaskType::where('name', 'Installation')->first();
    $planningTaskType = TaskType::where('name', 'Planung')->first();
    $documentationTaskType = TaskType::where('name', 'Dokumentation')->first();

    $tasks = [
        [
            'title' => 'Standortbegehung Müller GmbH',
            'description' => 'Vor-Ort-Termin zur Begutachtung des Dachs und der Gegebenheiten',
            'priority' => 'medium',
            'status' => 'completed',
            'due_date' => now()->subDays(80),
            'task_type_id' => $planningTaskType->id,
            'customer_id' => $createdCustomers[0]->id,
            'solar_plant_id' => $createdSolarPlants[0]->id,
            'assigned_to' => $technician->id,
            'created_by' => $manager->id,
            'completed_at' => now()->subDays(78),
            'estimated_minutes' => 120,
            'actual_minutes' => 105,
        ],
        [
            'title' => 'Installation Solarmodule Müller GmbH',
            'description' => 'Montage der Solarmodule und des Montagesystems',
            'priority' => 'high',
            'status' => 'completed',
            'due_date' => now()->subDays(35),
            'task_type_id' => $installationTaskType->id,
            'customer_id' => $createdCustomers[0]->id,
            'solar_plant_id' => $createdSolarPlants[0]->id,
            'assigned_to' => $technician->id,
            'created_by' => $manager->id,
            'completed_at' => now()->subDays(32),
            'estimated_minutes' => 480,
            'actual_minutes' => 510,
        ],
        [
            'title' => 'Planung Schmidt & Co Anlage',
            'description' => 'Technische Planung und Dimensionierung der Industrieanlage',
            'priority' => 'high',
            'status' => 'in_progress',
            'due_date' => now()->addDays(7),
            'task_type_id' => $planningTaskType->id,
            'customer_id' => $createdCustomers[1]->id,
            'solar_plant_id' => $createdSolarPlants[1]->id,
            'assigned_to' => $manager->id,
            'created_by' => $admin->id,
            'estimated_minutes' => 360,
        ],
        [
            'title' => 'Dokumentation Weber Projekt',
            'description' => 'Erstellung der Abnahmedokumentation',
            'priority' => 'medium',
            'status' => 'completed',
            'due_date' => now()->subDays(50),
            'task_type_id' => $documentationTaskType->id,
            'customer_id' => $createdCustomers[2]->id,
            'solar_plant_id' => $createdSolarPlants[2]->id,
            'assigned_to' => $technician->id,
            'created_by' => $manager->id,
            'completed_at' => now()->subDays(48),
            'estimated_minutes' => 180,
            'actual_minutes' => 195,
        ],
        [
            'title' => 'Behördenabstimmung Schmidt & Co',
            'description' => 'Abstimmung mit dem Netzbetreiber und Behörden',
            'priority' => 'urgent',
            'status' => 'open',
            'due_date' => now()->addDays(3),
            'task_type_id' => TaskType::where('name', 'Behördengänge')->first()->id,
            'customer_id' => $createdCustomers[1]->id,
            'solar_plant_id' => $createdSolarPlants[1]->id,
            'assigned_to' => $manager->id,
            'created_by' => $admin->id,
            'estimated_minutes' => 240,
        ],
    ];

    $createdTasks = [];
    foreach ($tasks as $taskData) {
        $task = Task::create($taskData);
        $createdTasks[] = $task;
        
        // Verknüpfe Tasks mit Projekten
        if ($task->solar_plant_id) {
            $project = collect($createdProjects)->firstWhere('solar_plant_id', $task->solar_plant_id);
            if ($project) {
                $project->tasks()->attach($task->id);
            }
        }
    }

    // 11. Supplier Contracts
    echo "11. Lieferantenverträge...\n";
    $contracts = [
        [
            'contract_number' => 'LV-2025-001',
            'title' => 'Solarmodule Rahmenvertrag',
            'supplier_id' => $createdSuppliers[0]->id,
            'start_date' => now()->subDays(180),
            'end_date' => now()->addDays(185),
            'contract_value' => 50000.00,
            'currency' => 'EUR',
            'status' => 'active',
            'description' => 'Rahmenvertrag für Solarmodule und Wechselrichter',
            'payment_terms' => '30 Tage netto',
        ],
        [
            'contract_number' => 'LV-2025-002',
            'title' => 'Montagesysteme Lieferung',
            'supplier_id' => $createdSuppliers[1]->id,
            'start_date' => now()->subDays(60),
            'end_date' => now()->addDays(305),
            'contract_value' => 25000.00,
            'currency' => 'EUR',
            'status' => 'active',
            'description' => 'Liefervertrag für Montagesysteme',
            'payment_terms' => '14 Tage netto',
        ],
    ];

    $createdContracts = [];
    foreach ($contracts as $contractData) {
        $contract = SupplierContract::firstOrCreate([
            'contract_number' => $contractData['contract_number']
        ], $contractData);
        $createdContracts[] = $contract;
    }

    // 12. Supplier Contract Billings
    echo "12. Lieferantenrechnungen...\n";
    $billings = [
        [
            'supplier_contract_id' => $createdContracts[0]->id,
            'billing_number' => 'AB-2025-0001',
            'supplier_invoice_number' => 'RE-ST-2025-001',
            'title' => 'Solarmodule Lieferung Q1',
            'description' => 'Lieferung von 200 Solarmodulen à 400W',
            'billing_date' => now()->subDays(45),
            'due_date' => now()->subDays(15),
            'total_amount' => 56000.00,
            'net_amount' => 47058.82,
            'vat_rate' => 19.00,
            'status' => 'paid',
            'billing_type' => 'invoice',
            'billing_year' => now()->year,
            'billing_month' => now()->subDays(45)->month,
        ],
        [
            'supplier_contract_id' => $createdContracts[1]->id,
            'billing_number' => 'AB-2025-0002',
            'supplier_invoice_number' => 'RE-GE-2025-015',
            'title' => 'Montagesysteme Teillieferung',
            'description' => 'Montagesysteme für 3 Projekte',
            'billing_date' => now()->subDays(20),
            'due_date' => now()->addDays(10),
            'total_amount' => 12500.00,
            'net_amount' => 10504.20,
            'vat_rate' => 19.00,
            'status' => 'approved',
            'billing_type' => 'invoice',
            'billing_year' => now()->year,
            'billing_month' => now()->subDays(20)->month,
        ],
    ];

    foreach ($billings as $billingData) {
        SupplierContractBilling::firstOrCreate([
            'billing_number' => $billingData['billing_number']
        ], $billingData);
    }

    // Statistiken ausgeben
    echo "\n=== TESTDATEN ERFOLGREICH ERSTELLT ===\n";
    echo "📊 Übersicht:\n";
    echo "- Benutzer: " . User::count() . "\n";
    echo "- Teams: " . Team::count() . "\n";
    echo "- Kunden: " . Customer::count() . "\n";
    echo "- Lieferanten: " . Supplier::count() . "\n";
    echo "- Solaranlagen: " . SolarPlant::count() . "\n";
    echo "- Projekte: " . Project::count() . "\n";
    echo "- Tasks: " . Task::count() . "\n";
    echo "- Task Types: " . TaskType::count() . "\n";
    echo "- Artikel: " . Article::count() . "\n";
    echo "- Lieferantenverträge: " . SupplierContract::count() . "\n";
    echo "- Lieferantenrechnungen: " . SupplierContractBilling::count() . "\n\n";

    echo "🔑 Login-Daten:\n";
    echo "Admin: admin@sunnybill.de / admin123\n";
    echo "Manager: manager@sunnybill.de / manager123\n";
    echo "Techniker: tech@sunnybill.de / tech123\n\n";

    echo "✅ Alle Testdaten wurden erfolgreich erstellt!\n";

} catch (Exception $e) {
    echo "❌ Fehler beim Erstellen der Testdaten: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
