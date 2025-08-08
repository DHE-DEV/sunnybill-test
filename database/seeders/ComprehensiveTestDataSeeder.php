<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;
use App\Models\PhoneNumber;
use App\Models\AppToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ComprehensiveTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starte Comprehensive Test Data Seeder...');
        
        // 1. Zusätzliche Kunden erstellen
        $this->seedAdditionalCustomers();
        
        // 2. Zusätzliche Lieferanten erstellen
        $this->seedAdditionalSuppliers();
        
        // 3. Zusätzliche Solaranlagen erstellen
        $this->seedAdditionalSolarPlants();
        
        // 4. Telefonnummern für Kunden erstellen
        $this->seedPhoneNumbers();
        
        // 5. API-Tokens erstellen
        $this->seedApiTokens();
        
        $this->command->info('✅ Comprehensive Test Data Seeder abgeschlossen!');
    }

    /**
     * Erstelle zusätzliche Kunden
     */
    private function seedAdditionalCustomers(): void
    {
        $this->command->info('🏢 Erstelle zusätzliche Kunden...');
        
        $customers = [
            // Privatkunden
            ['name' => 'Michael Fischer', 'type' => 'private', 'city' => 'Berlin'],
            ['name' => 'Sarah Wagner', 'type' => 'private', 'city' => 'München'],
            ['name' => 'Andreas Becker', 'type' => 'private', 'city' => 'Köln'],
            ['name' => 'Julia Schulz', 'type' => 'private', 'city' => 'Stuttgart'],
            ['name' => 'Robert Klein', 'type' => 'private', 'city' => 'Hamburg'],
            ['name' => 'Petra Hoffmann', 'type' => 'private', 'city' => 'Dresden'],
            ['name' => 'Stefan Richter', 'type' => 'private', 'city' => 'Hannover'],
            ['name' => 'Nicole Krüger', 'type' => 'private', 'city' => 'Nürnberg'],
            ['name' => 'Martin Neumann', 'type' => 'private', 'city' => 'Essen'],
            ['name' => 'Sabine Braun', 'type' => 'private', 'city' => 'Karlsruhe'],
            
            // Geschäftskunden
            ['name' => 'TechStart GmbH', 'type' => 'business', 'city' => 'Berlin'],
            ['name' => 'Grüne Zukunft AG', 'type' => 'business', 'city' => 'München'],
            ['name' => 'Innovativ Solutions UG', 'type' => 'business', 'city' => 'Köln'],
            ['name' => 'Nachhaltig Bauen GmbH & Co. KG', 'type' => 'business', 'city' => 'Stuttgart'],
            ['name' => 'Norddeutsche Energie eG', 'type' => 'business', 'city' => 'Hamburg'],
            ['name' => 'Elbtal Immobilien GmbH', 'type' => 'business', 'city' => 'Dresden'],
            ['name' => 'Hannover Consulting Partners', 'type' => 'business', 'city' => 'Hannover'],
            ['name' => 'Franken Solar Systems AG', 'type' => 'business', 'city' => 'Nürnberg'],
            ['name' => 'Ruhrgebiet Entwicklungsgesellschaft mbH', 'type' => 'business', 'city' => 'Essen'],
            ['name' => 'Baden Solar Technik GmbH', 'type' => 'business', 'city' => 'Karlsruhe'],
        ];

        foreach ($customers as $index => $customerData) {
            if (!Customer::where('name', $customerData['name'])->exists()) {
                $customer = Customer::create([
                    'customer_number' => 'KD' . str_pad(1000 + $index, 4, '0', STR_PAD_LEFT),
                    'name' => $customerData['name'],
                    'type' => $customerData['type'],
                    'city' => $customerData['city'],
                    'country' => 'Deutschland',
                    'postal_code' => rand(10000, 99999),
                    'street' => 'Musterstraße ' . rand(1, 100),
                    'email' => strtolower(str_replace([' ', '&', '.'], ['', '', ''], $customerData['name'])) . '@example.com',
                    'phone' => '+49' . rand(100, 999) . rand(1000000, 9999999),
                    'is_active' => true,
                    'notes' => 'Erstellt durch ComprehensiveTestDataSeeder',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("   ✅ Kunde erstellt: {$customerData['name']}");
            }
        }
    }

    /**
     * Erstelle zusätzliche Lieferanten
     */
    private function seedAdditionalSuppliers(): void
    {
        $this->command->info('🏭 Erstelle zusätzliche Lieferanten...');
        
        $suppliers = [
            ['name' => 'SolarTech Nord GmbH', 'city' => 'Hamburg'],
            ['name' => 'Bayern Energie Solutions AG', 'city' => 'München'],
            ['name' => 'Rheinland Solar Components', 'city' => 'Düsseldorf'],
            ['name' => 'Ostdeutsche Solartechnik eG', 'city' => 'Leipzig'],
            ['name' => 'Westfalen Energy Partners GmbH', 'city' => 'Dortmund'],
            ['name' => 'Nordsee Wind & Solar GmbH', 'city' => 'Emden'],
            ['name' => 'Süddeutsche Montagesysteme AG', 'city' => 'München'],
            ['name' => 'Green Power Sachsen GmbH', 'city' => 'Dresden'],
            ['name' => 'Hessen Solar Distribution', 'city' => 'Frankfurt am Main'],
            ['name' => 'Atlantic Solar Systems GmbH', 'city' => 'Bremen'],
        ];

        foreach ($suppliers as $index => $supplierData) {
            if (!Supplier::where('name', $supplierData['name'])->exists()) {
                $supplier = Supplier::create([
                    'supplier_number' => 'LF' . str_pad(2000 + $index, 4, '0', STR_PAD_LEFT),
                    'name' => $supplierData['name'],
                    'city' => $supplierData['city'],
                    'country' => 'Deutschland',
                    'postal_code' => rand(10000, 99999),
                    'street' => 'Industriestraße ' . rand(1, 50),
                    'email' => strtolower(str_replace([' ', '&', '.'], ['', '', ''], $supplierData['name'])) . '@company.de',
                    'phone' => '+49' . rand(100, 999) . rand(1000000, 9999999),
                    'is_active' => true,
                    'notes' => 'Erstellt durch ComprehensiveTestDataSeeder',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("   ✅ Lieferant erstellt: {$supplierData['name']}");
            }
        }
    }

    /**
     * Erstelle zusätzliche Solaranlagen
     */
    private function seedAdditionalSolarPlants(): void
    {
        $this->command->info('☀️ Erstelle zusätzliche Solaranlagen...');
        
        $customers = Customer::all();
        if ($customers->isEmpty()) {
            $this->command->warn('⚠️ Keine Kunden gefunden - überspringe Solaranlagen');
            return;
        }

        $solarPlants = [
            // Kleinere Anlagen
            ['name' => 'Einfamilienhaus Sonnenstraße', 'capacity' => 9.8, 'location' => 'Berlin-Charlottenburg'],
            ['name' => 'Mehrfamilienhaus Bergblick', 'capacity' => 15.4, 'location' => 'München-Schwabing'],
            ['name' => 'Gewerbeanlage TechPark', 'capacity' => 89.6, 'location' => 'Köln-Ehrenfeld'],
            ['name' => 'Lagerhalle Südwest', 'capacity' => 125.0, 'location' => 'Stuttgart-Feuerbach'],
            ['name' => 'Landwirtschaftsbetrieb Nord', 'capacity' => 67.2, 'location' => 'Hamburg-Bergedorf'],
            ['name' => 'Krankenhaus Elisenhof', 'capacity' => 156.8, 'location' => 'Dresden-Neustadt'],
            ['name' => 'Schule Am Lindenberg', 'capacity' => 78.4, 'location' => 'Hannover-Linden'],
            ['name' => 'Einkaufszentrum Plaza', 'capacity' => 198.2, 'location' => 'Nürnberg-Südstadt'],
            ['name' => 'Fabrik Maschinenbau', 'capacity' => 234.6, 'location' => 'Essen-Kettwig'],
            ['name' => 'Wohnanlage Gartenstadt', 'capacity' => 45.6, 'location' => 'Karlsruhe-Durlach'],
            ['name' => 'Sportverein FC Sonnenfeld', 'capacity' => 32.8, 'location' => 'Leipzig-Süd'],
            ['name' => 'Autohaus Elektromobil', 'capacity' => 87.4, 'location' => 'Frankfurt-Höchst'],
            
            // Große Solarparks
            ['name' => 'Solarpark Brandenburg Ost', 'capacity' => 15750.0, 'location' => 'Cottbus-Ost'],
            ['name' => 'Energiepark Bayern Süd', 'capacity' => 22850.0, 'location' => 'Ingolstadt-Nord'],
            ['name' => 'Offshore-Solar Nordsee I', 'capacity' => 8920.0, 'location' => 'Wilhelmshaven-Außenbereich'],
        ];

        foreach ($solarPlants as $plantData) {
            if (!SolarPlant::where('name', $plantData['name'])->exists()) {
                $customer = $customers->random();
                SolarPlant::create([
                    'name' => $plantData['name'],
                    'total_capacity_kw' => $plantData['capacity'],
                    'location' => $plantData['location'],
                    'installation_date' => now()->subMonths(rand(1, 24)),
                    'status' => 'active',
                    'is_active' => true,
                    'notes' => 'Erstellt durch ComprehensiveTestDataSeeder',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("   ✅ Solaranlage erstellt: {$plantData['name']} ({$plantData['capacity']} kWp)");
            }
        }
    }

    /**
     * Erstelle Telefonnummern für Kunden
     */
    private function seedPhoneNumbers(): void
    {
        $this->command->info('📞 Erstelle Telefonnummern...');
        
        $customers = Customer::all();
        foreach ($customers as $customer) {
            // Jeder Kunde bekommt 1-3 Telefonnummern
            $phoneCount = rand(1, 3);
            for ($i = 0; $i < $phoneCount; $i++) {
                $types = ['mobile', 'home', 'work'];
                $type = $types[$i % count($types)];
                
                if (!PhoneNumber::where('customer_id', $customer->id)->where('type', $type)->exists()) {
                    PhoneNumber::create([
                        'customer_id' => $customer->id,
                        'type' => $type,
                        'number' => '+49' . rand(100, 999) . rand(1000000, 9999999),
                        'is_primary' => $i === 0,
                        'is_active' => true,
                        'notes' => $type === 'mobile' ? 'Mobilnummer' : ($type === 'work' ? 'Geschäftsnummer' : 'Privatnummer'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
        $this->command->info("   ✅ Telefonnummern für alle Kunden erstellt");
    }

    /**
     * Erstelle API-Tokens
     */
    private function seedApiTokens(): void
    {
        $this->command->info('🔑 Erstelle API-Tokens...');
        
        // Suche nach User ID 57 oder erstelle ihn
        $user = User::find(57);
        if (!$user) {
            $user = User::create([
                'id' => 57,
                'name' => 'API Test User',
                'email' => 'api.test@sunnybill.de',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("   ✅ Test-User erstellt: {$user->name}");
        }

        $tokens = [
            [
                'name' => 'Full Access Token',
                'token' => 'sb_live_full_access_' . Str::random(40),
                'abilities' => ['*'],
                'description' => 'Vollzugriff für alle API-Operationen'
            ],
            [
                'name' => 'Phone Numbers Token',
                'token' => 'sb_live_phone_' . Str::random(40),
                'abilities' => ['phone-numbers:read', 'phone-numbers:write'],
                'description' => 'Zugriff auf Telefonnummern-API'
            ],
            [
                'name' => 'Solar Plants Token',
                'token' => 'sb_live_plants_' . Str::random(40),
                'abilities' => ['solar-plants:read', 'solar-plants:write'],
                'description' => 'Zugriff auf Solaranlagen-API'
            ],
            [
                'name' => 'Customers Token',
                'token' => 'sb_live_customers_' . Str::random(40),
                'abilities' => ['customers:read', 'customers:write'],
                'description' => 'Zugriff auf Kunden-API'
            ],
        ];

        foreach ($tokens as $tokenData) {
            if (!AppToken::where('name', $tokenData['name'])->exists()) {
                AppToken::create([
                    'user_id' => $user->id,
                    'name' => $tokenData['name'],
                    'token' => hash('sha256', $tokenData['token']),
                    'abilities' => json_encode($tokenData['abilities']),
                    'description' => $tokenData['description'],
                    'is_active' => true,
                    'expires_at' => now()->addYear(),
                    'last_used_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("   ✅ API-Token erstellt: {$tokenData['name']}");
            }
        }
    }
}
