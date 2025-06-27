<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\SolarPlant;
use App\Models\PlantParticipation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Article;
use App\Models\TaxRate;
use App\Models\TaxRateVersion;
use App\Models\InvoiceVersion;
use App\Models\CompanySetting;
use App\Models\CustomerNote;
use App\Models\SolarInverter;
use App\Models\SolarModule;
use App\Models\SolarBattery;
use App\Models\SolarPlantNote;
use App\Models\SolarPlantMilestone;
use App\Models\Supplier;
use App\Models\SupplierEmployee;
use App\Models\SupplierNote;
use App\Models\SolarPlantSupplier;
use App\Models\PhoneNumber;
use App\Models\LexofficeLog;
use App\Models\User;
use Database\Seeders\SupplierSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ManageTestDataCommand extends Command
{
    protected $signature = 'testdata:manage {action : create or reset}';
    protected $description = 'Manage test data - create or reset (delete all and recreate)';

    public function handle()
    {
        $action = $this->argument('action');

        if ($action === 'reset') {
            $this->resetData();
        } elseif ($action === 'create') {
            $this->createTestData();
        } else {
            $this->error('Invalid action. Use "create" or "reset"');
            return 1;
        }

        return 0;
    }

    private function resetData()
    {
        $this->info('🗑️ Lösche alle Daten...');
        
        // Lösche in der richtigen Reihenfolge (wegen Foreign Keys)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        InvoiceItem::truncate();
        InvoiceVersion::truncate();
        Invoice::truncate();
        CustomerNote::truncate();
        CompanySetting::truncate();
        PlantParticipation::truncate();
        SolarPlantSupplier::truncate();
        SolarPlantMilestone::truncate();
        SolarPlantNote::truncate();
        PhoneNumber::truncate();
        SupplierNote::truncate();
        SupplierEmployee::truncate();
        Supplier::truncate();
        SolarInverter::truncate();
        SolarModule::truncate();
        SolarBattery::truncate();
        SolarPlant::truncate();
        Customer::truncate();
        Article::truncate();
        TaxRateVersion::truncate();
        TaxRate::truncate();
        LexofficeLog::truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->info('✅ Alle Daten gelöscht');
        
        // Erstelle neue Testdaten
        $this->createTestData();
    }

    private function createTestData()
    {
        $this->info('📊 Erstelle Testdaten...');

        // 1. Standard-User erstellen
        $user = $this->createTestUser();
        
        // 2. Firmeneinstellungen mit Nummernpräfixen erstellen
        $this->createCompanySettings();
        
        // 3. Steuersätze erstellen
        $taxRates = $this->createTaxRates();
        
        // 4. Artikel erstellen
        $this->createArticles($taxRates);
        
        // 5. 40 Kunden erstellen (20 Privat, 20 Firma)
        $customers = $this->createCustomers();
        
        // 6. 10 Solaranlagen erstellen
        $solarPlants = $this->createSolarPlants();
        
        // 7. Solaranlagen-Zuordnungen erstellen
        $this->createSolarPlantAssignments($customers, $solarPlants);
        
        // 8. Notizen für Kunden erstellen
        $this->createCustomerNotes($customers, $user);
        
        // 9. Solaranlagen-Notizen erstellen
        $this->createSolarPlantNotes($solarPlants, $user);
        
        // 10. Lieferanten-Daten erstellen
        $this->createSupplierData();
        
        // 11. Projekttermine für spezifische Solaranlagen erstellen
        $this->createProjectMilestones($solarPlants, $user);
        
        // 12. Lieferanten-Zuordnungen für spezifische Solaranlagen erstellen
        $this->createSupplierAssignments($solarPlants);
        
        // 13. Beispiel-Rechnungen erstellen
        $this->createInvoices($customers, $taxRates);

        $this->info('✅ Testdaten erfolgreich erstellt!');
        $this->info('📈 40 Kunden (10 Privat, 30 Firma), 10 Solaranlagen, 6 zugeordnete Anlagen, 10 Lieferanten mit Mitarbeitern, 15 Rechnungen');
        $this->info('🎯 Projekttermine und Lieferanten-Zuordnungen für spezifische Anlagen erstellt');
    }

    private function createTestUser()
    {
        $user = User::firstOrCreate(
            ['email' => 'test@sunnybill.de'],
            [
                'name' => 'Test User',
                'email' => 'test@sunnybill.de',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->info('👤 Test-User erstellt/gefunden');
        return $user;
    }

    private function createCompanySettings()
    {
        // Erstelle oder aktualisiere Firmeneinstellungen mit vollständigen Demo-Daten
        $companySettings = CompanySetting::current();
        
        // Vollständige Demo-Firmendaten
        $updates = [];
        
        // Nummernpräfixe
        if (empty($companySettings->customer_number_prefix)) {
            $updates['customer_number_prefix'] = 'KD';
        }
        
        if (empty($companySettings->supplier_number_prefix)) {
            $updates['supplier_number_prefix'] = 'LF';
        }
        
        if (empty($companySettings->invoice_number_prefix)) {
            $updates['invoice_number_prefix'] = 'RE';
        }
        
        if ($companySettings->invoice_number_include_year === null) {
            $updates['invoice_number_include_year'] = true;
        }
        
        // Firmendaten
        if (empty($companySettings->company_name)) {
            $updates['company_name'] = 'SunnyBill Demo GmbH';
        }
        
        if (empty($companySettings->company_address)) {
            $updates['company_address'] = 'Sonnenstraße 42';
        }
        
        if (empty($companySettings->company_postal_code)) {
            $updates['company_postal_code'] = '80331';
        }
        
        if (empty($companySettings->company_city)) {
            $updates['company_city'] = 'München';
        }
        
        if (empty($companySettings->company_country)) {
            $updates['company_country'] = 'Deutschland';
        }
        
        // Kontaktdaten
        if (empty($companySettings->phone)) {
            $updates['phone'] = '+49 89 123456-0';
        }
        
        if (empty($companySettings->email)) {
            $updates['email'] = 'info@sunnybill-demo.de';
        }
        
        if (empty($companySettings->website)) {
            $updates['website'] = 'www.sunnybill-demo.de';
        }
        
        // Steuerliche Daten
        if (empty($companySettings->tax_number)) {
            $updates['tax_number'] = 'DE123456789';
        }
        
        if (empty($companySettings->vat_id)) {
            $updates['vat_id'] = 'DE123456789';
        }
        
        if (empty($companySettings->commercial_register)) {
            $updates['commercial_register'] = 'Amtsgericht München';
        }
        
        if (empty($companySettings->commercial_register_number)) {
            $updates['commercial_register_number'] = 'HRB 123456';
        }
        
        if (empty($companySettings->management)) {
            $updates['management'] = 'Max Mustermann, Anna Schmidt';
        }
        
        // Bankdaten
        if (empty($companySettings->bank_name)) {
            $updates['bank_name'] = 'Deutsche Bank AG';
        }
        
        if (empty($companySettings->iban)) {
            $updates['iban'] = 'DE89 3704 0044 0532 0130 00';
        }
        
        if (empty($companySettings->bic)) {
            $updates['bic'] = 'COBADEFFXXX';
        }
        
        // Zahlungseinstellungen
        if (empty($companySettings->default_payment_days)) {
            $updates['default_payment_days'] = 14;
        }
        
        // PDF-Einstellungen
        if (empty($companySettings->pdf_margins)) {
            $updates['pdf_margins'] = '2cm 1.5cm 2cm 1.5cm';
        }
        
        if (!empty($updates)) {
            $companySettings->update($updates);
            $this->info('🏢 Vollständige Firmeneinstellungen mit Demo-Daten erstellt/aktualisiert');
        } else {
            $this->info('🏢 Firmeneinstellungen bereits vorhanden');
        }
        
        return $companySettings;
    }

    private function createTaxRates()
    {
        $taxRatesData = [
            [
                'name' => 'Steuerbefreit',
                'description' => 'Steuerbefreite Produkte (0% MwSt.)',
                'rate' => 0.0000,
                'is_default' => false,
                'is_active' => true,
                'valid_from' => now()->subYear(),
                'valid_until' => null,
            ],
            [
                'name' => 'Ermäßigter Steuersatz',
                'description' => 'Ermäßigter Steuersatz (7% MwSt.)',
                'rate' => 0.0700,
                'is_default' => false,
                'is_active' => true,
                'valid_from' => now()->subYear(),
                'valid_until' => null,
            ],
            [
                'name' => 'Regelsteuersatz',
                'description' => 'Standard Steuersatz (19% MwSt.)',
                'rate' => 0.1900,
                'is_default' => true,
                'is_active' => true,
                'valid_from' => now()->subYear(),
                'valid_until' => null,
            ],
        ];

        $createdTaxRates = [];

        foreach ($taxRatesData as $taxRateData) {
            $taxRate = TaxRate::create([
                'id' => Str::uuid(),
                'name' => $taxRateData['name'],
                'description' => $taxRateData['description'],
                'rate' => $taxRateData['rate'],
                'is_default' => $taxRateData['is_default'],
                'is_active' => $taxRateData['is_active'],
                'valid_from' => $taxRateData['valid_from'],
                'valid_until' => $taxRateData['valid_until'],
            ]);

            // Erste Version automatisch erstellen (wird durch Model Event ausgelöst)
            // TaxRateVersion::createVersion wird automatisch aufgerufen

            $createdTaxRates[$taxRateData['rate']] = $taxRate;
        }

        $this->info('💰 3 Steuersätze erstellt (0%, 7%, 19%) mit Version 1');
        return $createdTaxRates;
    }

    private function createArticles($taxRates)
    {
        $articles = [
            ['name' => 'Solarmodul 450W', 'price' => 299.99, 'tax_rate' => 0.0000, 'type' => 'PRODUCT'],
            ['name' => 'Wechselrichter 5kW', 'price' => 1299.99, 'tax_rate' => 0.0000, 'type' => 'PRODUCT'],
            ['name' => 'Batteriespeicher 10kWh', 'price' => 8999.99, 'tax_rate' => 0.0000, 'type' => 'PRODUCT'],
            ['name' => 'Montagesystem', 'price' => 199.99, 'tax_rate' => 0.0000, 'type' => 'PRODUCT'],
            ['name' => 'Verkabelung', 'price' => 149.99, 'tax_rate' => 0.0000, 'type' => 'PRODUCT'],
            ['name' => 'Installation', 'price' => 2500.00, 'tax_rate' => 0.1900, 'type' => 'SERVICE'],
            ['name' => 'Wartung jährlich', 'price' => 299.99, 'tax_rate' => 0.1900, 'type' => 'SERVICE'],
            ['name' => 'Monitoring-System', 'price' => 499.99, 'tax_rate' => 0.1900, 'type' => 'SERVICE'],
            ['name' => 'Einspeisevergütung', 'price' => -0.081234, 'tax_rate' => 0.1900, 'type' => 'SERVICE'],
        ];

        foreach ($articles as $article) {
            // Finde den entsprechenden TaxRate
            $taxRate = $taxRates[$article['tax_rate']] ?? null;
            
            // Spezielle Behandlung für Einspeisevergütung
            if ($article['name'] === 'Einspeisevergütung') {
                $description = 'Abrechnung in KWh';
                $price = $article['price']; // Behält 6 Nachkommastellen
                $decimalPlaces = 4; // 4 Nachkommastellen für Einzelpreis
                $totalDecimalPlaces = 4; // 4 Nachkommastellen für Gesamtpreis
            } else {
                $description = 'Testdaten für ' . $article['name'];
                $price = round($article['price'], 2); // Rundet auf 2 Nachkommastellen
                $decimalPlaces = 2; // Standard 2 Nachkommastellen
                $totalDecimalPlaces = 2; // Standard 2 Nachkommastellen
            }
            
            Article::create([
                'id' => Str::uuid(),
                'name' => $article['name'],
                'price' => $price,
                'tax_rate' => $article['tax_rate'], // Behalte für Rückwärtskompatibilität
                'tax_rate_id' => $taxRate?->id, // Neue Verlinkung
                'type' => $article['type'],
                'description' => $description,
                'decimal_places' => $decimalPlaces,
                'total_decimal_places' => $totalDecimalPlaces,
            ]);
        }

        $this->info('📦 9 Artikel erstellt (5 Produkte mit 0% MwSt, 4 Services mit 19% MwSt) mit korrekten TaxRate-Verlinkungen');
    }

    private function createCustomers()
    {
        $customers = [];
        $firstNames = ['Max', 'Anna', 'Peter', 'Lisa', 'Thomas', 'Sarah', 'Michael', 'Julia', 'Stefan', 'Maria', 'Andreas', 'Nicole', 'Christian', 'Sandra', 'Daniel', 'Petra', 'Markus', 'Sabine', 'Oliver', 'Claudia', 'Alexander', 'Katharina', 'Benjamin', 'Laura', 'Sebastian', 'Jennifer', 'Florian', 'Melanie', 'Tobias', 'Stephanie', 'Matthias', 'Christina', 'Philipp', 'Nadine', 'Simon', 'Vanessa', 'Fabian', 'Tanja', 'Marcel', 'Anja', 'Dominik', 'Manuela', 'Patrick', 'Bianca', 'Kevin', 'Silvia', 'Tim', 'Kerstin', 'Sven', 'Heike'];
        $lastNames = ['Müller', 'Schmidt', 'Schneider', 'Fischer', 'Weber', 'Meyer', 'Wagner', 'Becker', 'Schulz', 'Hoffmann', 'Schäfer', 'Koch', 'Bauer', 'Richter', 'Klein', 'Wolf', 'Schröder', 'Neumann', 'Schwarz', 'Zimmermann', 'Braun', 'Krüger', 'Hofmann', 'Hartmann', 'Lange', 'Schmitt', 'Werner', 'Schmitz', 'Krause', 'Meier', 'Lehmann', 'Huber', 'Mayer', 'Herrmann', 'König', 'Walter', 'Schulze', 'Böhm', 'Fuchs', 'Keller', 'Schwab', 'Weiß', 'Schmid', 'Möller', 'Berger', 'Jung', 'Franke', 'Albrecht', 'Döring', 'Brandt'];
        $cities = ['Berlin', 'Hamburg', 'München', 'Köln', 'Frankfurt', 'Stuttgart', 'Düsseldorf', 'Dortmund', 'Essen', 'Leipzig', 'Bremen', 'Dresden', 'Hannover', 'Nürnberg', 'Duisburg', 'Bochum', 'Wuppertal', 'Bielefeld', 'Bonn', 'Münster'];
        $companyNames = [
            'Solar Tech GmbH', 'Grünstrom AG', 'Öko Energie UG', 'Nachhaltig Solutions', 'Klimaschutz Consulting',
            'Erneuerbare Energien GmbH', 'Photovoltaik Pro', 'Solarpark Management', 'Green Power Solutions', 'Umwelt Technik GmbH',
            'Energiewende Nord', 'Sonnenkraft Süd', 'Ökostrom Plus', 'Renewable Energy Systems', 'Clean Power GmbH',
            'Future Energy Solutions', 'Sustainable Tech AG', 'EcoVolt GmbH', 'PowerGreen Solutions', 'SolarMax Deutschland',
            'GreenTech Innovations', 'Energie Zukunft GmbH', 'CleanEnergy Partners', 'SolarVision AG', 'EnergyEfficient Solutions',
            'GreenPower Consulting', 'Renewable Solutions GmbH', 'EcoEnergy Systems', 'SolarTech Innovations', 'CleanTech Deutschland',
            'GreenEnergy Partners', 'SolarPro Services', 'EcoSolutions GmbH', 'PowerClean AG', 'GreenVolt Systems',
            'SolarEfficient GmbH', 'CleanPower Solutions', 'EnergyGreen Deutschland', 'SolarClean Technologies', 'GreenEfficient Systems',
            // Zusätzliche Firmennamen für 30 Firmenkunden
            'Photovoltaik Nord GmbH', 'Solarenergie Süd AG', 'Windkraft Plus UG', 'Bioenergie Solutions', 'Wasserkraft Pro GmbH',
            'Geothermie Tech AG', 'Energiespeicher GmbH', 'Smart Grid Solutions', 'Elektromobilität Plus', 'Energieberatung Pro',
            'Solardach Experten GmbH', 'Wärmepumpen Zentrale', 'Energieeffizienz AG', 'Klimaneutral GmbH', 'CO2-Neutral Solutions',
            'Nachhaltigkeit Plus UG', 'Umweltschutz Pro GmbH', 'Ressourcenschonung AG', 'Kreislaufwirtschaft GmbH', 'Zero Waste Solutions',
            'Energiewende Consulting', 'Dekarbonisierung GmbH', 'Klimaschutz Plus AG', 'Erneuerbar Pro Solutions', 'Zukunftsenergie GmbH'
        ];
        
        // Deutsche Banken mit korrekten BIC-Codes
        $banks = [
            ['name' => 'Deutsche Bank', 'bic' => 'DEUTDEFF'],
            ['name' => 'Commerzbank', 'bic' => 'COBADEFF'],
            ['name' => 'Sparkasse Köln/Bonn', 'bic' => 'COLSDE33'],
            ['name' => 'Volksbank', 'bic' => 'GENODED1'],
            ['name' => 'Postbank', 'bic' => 'PBNKDEFF'],
            ['name' => 'HypoVereinsbank', 'bic' => 'HYVEDEMM'],
            ['name' => 'DZ Bank', 'bic' => 'GENODEFF'],
            ['name' => 'Landesbank Baden-Württemberg', 'bic' => 'SOLADEST'],
            ['name' => 'Bayerische Landesbank', 'bic' => 'BYLADEMM'],
            ['name' => 'ING-DiBa', 'bic' => 'INGDDEFF'],
        ];
        
        // Notizen für Privatkunden
        $privateNotes = [
            'Interessiert an nachhaltiger Energieversorgung für das Eigenheim. Möchte Stromkosten reduzieren und umweltbewusst leben.',
            'Hausbesitzer mit Südausrichtung des Daches. Plant langfristige Investition in Solarenergie für die Familie.',
            'Sehr technikaffin und interessiert an Smart-Home-Integration der Solaranlage. Wünscht detaillierte Monitoring-Möglichkeiten.',
            'Rentner mit Zeit für ausführliche Beratung. Legt Wert auf deutsche Qualitätsprodukte und lokalen Service.',
            'Junge Familie mit Kindern. Möchte Vorbild für nachhaltige Lebensweise sein und Energiekosten für die Zukunft senken.',
            'Eigenheimbesitzer seit 10 Jahren. Hat bereits Erfahrung mit Energiesparmaßnahmen und möchte nun auf Solar umsteigen.',
            'Sehr preisbewusst und vergleicht verschiedene Anbieter. Benötigt detaillierte Wirtschaftlichkeitsberechnung.',
            'Empfehlung durch Nachbarn erhalten. Möchte ähnliche Anlage wie in der Nachbarschaft installieren lassen.',
            'Plant Elektroauto-Anschaffung und benötigt entsprechende Ladeinfrastruktur mit Solarstrom.',
            'Umweltbewusster Kunde, der bereits andere nachhaltige Maßnahmen umgesetzt hat. Solar als nächster Schritt.'
        ];
        
        // Notizen für Firmenkunden
        $businessNotes = [
            'Mittelständisches Unternehmen mit eigenem Betriebsgebäude. Möchte Energiekosten senken und CSR-Ziele erreichen.',
            'Produktionsbetrieb mit hohem Stromverbrauch tagsüber. Ideale Voraussetzungen für Eigenverbrauch von Solarstrom.',
            'Familienunternehmen in zweiter Generation. Plant langfristige Investition in nachhaltige Energieversorgung.',
            'Dienstleistungsunternehmen mit Bürogebäude. Möchte Vorreiterrolle in Sachen Nachhaltigkeit in der Branche übernehmen.',
            'Handwerksbetrieb mit großer Hallenfläche. Sucht nach Möglichkeiten zur Kostensenkung und Imageaufwertung.',
            'Technologieunternehmen mit hohem Energiebedarf für Server und Klimatisierung. Interesse an grüner IT-Infrastruktur.',
            'Logistikunternehmen mit großen Lagerhallen. Möchte Dachflächen optimal nutzen und Betriebskosten reduzieren.',
            'Einzelhandelsunternehmen mit mehreren Filialen. Plant schrittweise Umstellung auf erneuerbare Energien.',
            'Beratungsunternehmen, das Nachhaltigkeit als Geschäftsfeld hat. Möchte authentisch als Vorbild vorangehen.',
            'Traditioneller Betrieb, der sich modernisieren möchte. Sieht Solarenergie als Investition in die Zukunftsfähigkeit.'
        ];

        // Erstelle 40 Kunden (10 Privat, 30 Firma) in zufälliger Reihenfolge
        $customerTypes = array_merge(
            array_fill(0, 10, 'private'),
            array_fill(0, 30, 'business')
        );
        shuffle($customerTypes); // Mische die Reihenfolge
        
        // Arrays für eindeutige Namen
        $usedNames = [];
        $usedCompanyNames = [];
        
        for ($i = 0; $i < 40; $i++) {
            $customerType = $customerTypes[$i];
            $isPrivate = $customerType === 'private';
            
            // Zufällige Bank auswählen
            $bank = $banks[array_rand($banks)];
            
            // Zufällige IBAN generieren (DE + 20 Ziffern)
            $iban = 'DE' . rand(10, 99) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(10, 99);
            
            // Steuernummer im Format 220/1111/2222
            $taxNumber = rand(100, 999) . '/' . rand(1000, 9999) . '/' . rand(1000, 9999);
            
            // Eindeutige Namen für jeden Kunden generieren
            do {
                $firstNameIndex = array_rand($firstNames);
                $lastNameIndex = array_rand($lastNames);
                $nameKey = $firstNameIndex . '_' . $lastNameIndex;
            } while (in_array($nameKey, $usedNames));
            $usedNames[] = $nameKey;
            
            $cityIndex = $i % count($cities);
            
            $customerData = [
                'id' => Str::uuid(),
                'customer_type' => $customerType,
                'email' => strtolower($firstNames[$firstNameIndex] . '.' . $lastNames[$lastNameIndex] . $i) . '@example.com',
                'phone' => '0' . rand(30, 89) . rand(10000000, 99999999),
                'street' => 'Musterstraße ' . rand(1, 99),
                'city' => $cities[$cityIndex],
                'postal_code' => rand(10000, 99999),
                'country' => 'Deutschland',
                'country_code' => 'DE',
                'is_active' => true,
                // Bankdaten für alle Kunden
                'bank_name' => $bank['name'],
                'iban' => $iban,
                'bic' => $bank['bic'],
                'tax_number' => $taxNumber,
                // Individuelles Zahlungsziel für jeden Kunden (7, 14, 21, 30 oder 45 Tage)
                'payment_days' => [7, 14, 21, 30, 45][array_rand([7, 14, 21, 30, 45])],
            ];
            
            if ($isPrivate) {
                // Privatkunde
                $customerData['name'] = $firstNames[$firstNameIndex] . ' ' . $lastNames[$lastNameIndex];
                $customerData['notes'] = $privateNotes[$firstNameIndex % count($privateNotes)];
            } else {
                // Firmenkunde - eindeutige Firmennamen
                do {
                    $companyIndex = array_rand($companyNames);
                } while (in_array($companyIndex, $usedCompanyNames));
                $usedCompanyNames[] = $companyIndex;
                
                $customerData['name'] = $companyNames[$companyIndex];
                $customerData['company_name'] = $companyNames[$companyIndex];
                $customerData['contact_person'] = $firstNames[$firstNameIndex] . ' ' . $lastNames[$lastNameIndex];
                $customerData['department'] = ['Einkauf', 'Geschäftsführung', 'Technik', 'Vertrieb'][array_rand(['Einkauf', 'Geschäftsführung', 'Technik', 'Vertrieb'])];
                $customerData['website'] = 'www.' . strtolower(str_replace([' ', 'ä', 'ö', 'ü'], ['', 'ae', 'oe', 'ue'], $companyNames[$companyIndex])) . '.de';
                $customerData['vat_id'] = 'DE' . rand(100000000, 999999999);
                $customerData['notes'] = $businessNotes[$companyIndex % count($businessNotes)];
            }
            
            // Zufälliges Erstellungsdatum in den letzten 3 Monaten
            $randomDaysAgo = rand(0, 90); // 0-90 Tage zurück
            $customerCreationDate = now()->subDays($randomDaysAgo);
            
            // Kunde erstellen ohne automatische Timestamps
            $customer = new Customer($customerData);
            $customer->timestamps = false;
            $customer->created_at = $customerCreationDate;
            $customer->updated_at = $customerCreationDate;
            $customer->save();
            
            $customers[] = $customer;
        }

        // Setze einige Kunden aus dem aktuellen Monat auf inaktiv
        $this->setCurrentMonthCustomersInactive($customers);

        $this->info('👥 40 Kunden erstellt (10 Privatkunden, 30 Firmenkunden) in zufälliger Reihenfolge mit Bankdaten, korrekten Steuernummern und sinnvollen Notizen');
        return $customers;
    }

    private function setCurrentMonthCustomersInactive($customers)
    {
        $currentMonthStart = now()->startOfMonth();
        
        // Finde alle Kunden, die im aktuellen Monat erstellt wurden
        $currentMonthCustomers = array_filter($customers, function($customer) use ($currentMonthStart) {
            return $customer->created_at->gte($currentMonthStart);
        });
        
        // Separiere nach Kundentyp
        $privateCustomers = array_filter($currentMonthCustomers, function($customer) {
            return $customer->customer_type === 'private';
        });
        
        $businessCustomers = array_filter($currentMonthCustomers, function($customer) {
            return $customer->customer_type === 'business';
        });
        
        // Setze 3 zufällige Privatkunden auf inaktiv mit deactivated_at
        $privateCustomersArray = array_values($privateCustomers);
        $inactivePrivateCount = min(3, count($privateCustomersArray));
        if ($inactivePrivateCount > 0) {
            $randomPrivateKeys = array_rand($privateCustomersArray, $inactivePrivateCount);
            if (!is_array($randomPrivateKeys)) {
                $randomPrivateKeys = [$randomPrivateKeys];
            }
            
            foreach ($randomPrivateKeys as $key) {
                $customer = $privateCustomersArray[$key];
                $customer->timestamps = false;
                $customer->is_active = false;
                
                // Setze deactivated_at auf ein zufälliges Datum nach der Kundenerstellung
                $daysSinceCreation = now()->diffInDays($customer->created_at);
                $deactivationDaysAgo = rand(1, min($daysSinceCreation, 30)); // 1-30 Tage nach Erstellung
                $customer->deactivated_at = $customer->created_at->copy()->addDays($deactivationDaysAgo);
                
                $customer->save();
            }
        }
        
        // Setze 10 zufällige Firmenkunden auf inaktiv mit deactivated_at
        $businessCustomersArray = array_values($businessCustomers);
        $inactiveBusinessCount = min(10, count($businessCustomersArray));
        if ($inactiveBusinessCount > 0) {
            $randomBusinessKeys = array_rand($businessCustomersArray, $inactiveBusinessCount);
            if (!is_array($randomBusinessKeys)) {
                $randomBusinessKeys = [$randomBusinessKeys];
            }
            
            foreach ($randomBusinessKeys as $key) {
                $customer = $businessCustomersArray[$key];
                $customer->timestamps = false;
                $customer->is_active = false;
                
                // Setze deactivated_at auf ein zufälliges Datum nach der Kundenerstellung
                $daysSinceCreation = now()->diffInDays($customer->created_at);
                $deactivationDaysAgo = rand(1, min($daysSinceCreation, 30)); // 1-30 Tage nach Erstellung
                $customer->deactivated_at = $customer->created_at->copy()->addDays($deactivationDaysAgo);
                
                $customer->save();
            }
        }
        
        // Zusätzlich: Setze einige ältere Kunden auf inaktiv für bessere Testdaten
        $this->setHistoricalCustomersInactive($customers);
        
        $actualInactivePrivate = $inactivePrivateCount;
        $actualInactiveBusiness = $inactiveBusinessCount;
        
        if ($actualInactivePrivate > 0 || $actualInactiveBusiness > 0) {
            $this->info("🔴 {$actualInactivePrivate} Privatkunden und {$actualInactiveBusiness} Firmenkunden aus dem aktuellen Monat auf inaktiv gesetzt (mit deactivated_at)");
        }
    }

    private function setHistoricalCustomersInactive($customers)
    {
        // Finde Kunden aus den letzten 2-3 Monaten (aber nicht aktueller Monat)
        $twoMonthsAgo = now()->subMonths(2)->startOfMonth();
        $currentMonthStart = now()->startOfMonth();
        
        $historicalCustomers = array_filter($customers, function($customer) use ($twoMonthsAgo, $currentMonthStart) {
            return $customer->created_at->gte($twoMonthsAgo) && $customer->created_at->lt($currentMonthStart);
        });
        
        if (count($historicalCustomers) > 0) {
            // Setze 20% der historischen Kunden auf inaktiv
            $inactiveCount = max(1, round(count($historicalCustomers) * 0.2));
            $historicalCustomersArray = array_values($historicalCustomers);
            $randomKeys = array_rand($historicalCustomersArray, min($inactiveCount, count($historicalCustomersArray)));
            
            if (!is_array($randomKeys)) {
                $randomKeys = [$randomKeys];
            }
            
            foreach ($randomKeys as $key) {
                $customer = $historicalCustomersArray[$key];
                $customer->timestamps = false;
                $customer->is_active = false;
                
                // Setze deactivated_at auf ein zufälliges Datum zwischen Erstellung und heute
                $daysSinceCreation = now()->diffInDays($customer->created_at);
                $deactivationDaysAgo = rand(7, $daysSinceCreation - 1); // Mindestens 7 Tage nach Erstellung
                $customer->deactivated_at = $customer->created_at->copy()->addDays($deactivationDaysAgo);
                
                $customer->save();
            }
            
            $this->info("📊 {$inactiveCount} historische Kunden auf inaktiv gesetzt (für bessere Statistik-Testdaten)");
        }
    }

    private function createSolarPlants()
    {
        $solarPlants = [];
        $plantNames = [
            'Sonnenkraft Nord',
            'Photovoltaik Süd',
            'Solarpark Ost',
            'Energiefeld West',
            'Solardach Zentral',
            'Grünstrom Anlage',
            'Öko-Solar Park',
            'Nachhaltigkeits-PV',
            'Klimaschutz Solar',
            'Zukunftsenergie'
        ];

        // Realistische Koordinaten für deutsche Standorte
        $coordinates = [
            ['lat' => 52.520008, 'lng' => 13.404954, 'location' => 'Berlin, Mitte'],
            ['lat' => 53.551086, 'lng' => 9.993682, 'location' => 'Hamburg, Altstadt'],
            ['lat' => 48.137154, 'lng' => 11.576124, 'location' => 'München, Zentrum'],
            ['lat' => 50.937531, 'lng' => 6.960279, 'location' => 'Köln, Innenstadt'],
            ['lat' => 50.110922, 'lng' => 8.682127, 'location' => 'Frankfurt am Main'],
            ['lat' => 48.775846, 'lng' => 9.182932, 'location' => 'Stuttgart, Mitte'],
            ['lat' => 51.227741, 'lng' => 6.773456, 'location' => 'Düsseldorf, Altstadt'],
            ['lat' => 51.514244, 'lng' => 7.463054, 'location' => 'Dortmund, Zentrum'],
            ['lat' => 51.458069, 'lng' => 7.014761, 'location' => 'Essen, Innenstadt'],
            ['lat' => 51.339695, 'lng' => 12.373075, 'location' => 'Leipzig, Zentrum']
        ];

        for ($i = 0; $i < 10; $i++) {
            // Spezielle Anlagen mit hoher Leistung (250-2000 kW)
            if (in_array($plantNames[$i], ['Sonnenkraft Nord', 'Photovoltaik Süd', 'Solarpark Ost', 'Energiefeld West', 'Öko-Solar Park'])) {
                $capacity = rand(250, 2000); // 250-2000 kW für große Anlagen
                
                // Realistische technische Daten basierend auf Kapazität
                $panelCount = round($capacity / 0.45); // 450W Module
                $inverterCount = round($capacity / 50); // 50kW Wechselrichter
                $expectedYield = round($capacity * 1000); // ca. 1000 kWh pro kW installiert
                
                // Investition: ca. 1000-1500€ pro kW
                $totalInvestment = $capacity * rand(1000, 1500);
                
                // Betriebskosten: ca. 1-2% der Investition pro Jahr
                $operatingCosts = $totalInvestment * (rand(10, 20) / 1000);
                
                $description = "Große Solaranlage mit {$capacity} kW Leistung, bestehend aus {$panelCount} Modulen und {$inverterCount} Wechselrichtern. Erwarteter Jahresertrag: " . number_format($expectedYield, 0, ',', '.') . " kWh.";
            } else {
                // Kleinere Anlagen (5-50 kW)
                $capacity = rand(50, 500) / 10; // 5.0 bis 50.0 kW
                
                $panelCount = round($capacity / 0.45); // 450W Module
                $inverterCount = max(1, round($capacity / 5)); // 5kW Wechselrichter
                $expectedYield = round($capacity * 1100); // ca. 1100 kWh pro kW für kleinere Anlagen
                
                $totalInvestment = $capacity * rand(1200, 1800); // Höhere spezifische Kosten bei kleineren Anlagen
                $operatingCosts = $totalInvestment * (rand(15, 25) / 1000);
                
                $description = "Solaranlage mit {$capacity} kW Leistung, bestehend aus {$panelCount} Modulen und {$inverterCount} Wechselrichtern. Erwarteter Jahresertrag: " . number_format($expectedYield, 0, ',', '.') . " kWh.";
            }
            
            // Realistische Termine
            $plannedInstallation = now()->subDays(rand(180, 365)); // Geplant vor 6-12 Monaten
            $actualInstallation = $plannedInstallation->copy()->addDays(rand(-14, 30)); // ±2 Wochen bis +1 Monat
            $plannedCommissioning = $actualInstallation->copy()->addDays(rand(7, 21)); // 1-3 Wochen nach Installation
            $actualCommissioning = $plannedCommissioning->copy()->addDays(rand(-7, 14)); // ±1 Woche bis +2 Wochen
            
            // Einspeisevergütung zwischen 0.06 und 0.112 €/kWh (6 Nachkommastellen)
            $feedInTariff = rand(60000, 112000) / 1000000; // 0.060000 bis 0.112000
            
            // Strompreis zwischen 0.24 und 0.36 €/kWh (6 Nachkommastellen)
            $electricityPrice = rand(240000, 360000) / 1000000; // 0.240000 bis 0.360000
            
            // Koordinaten für diese Anlage
            $coord = $coordinates[$i];
            
            $solarPlant = SolarPlant::create([
                'id' => Str::uuid(),
                'name' => $plantNames[$i],
                'location' => $coord['location'],
                'latitude' => $coord['lat'],
                'longitude' => $coord['lng'],
                'total_capacity_kw' => $capacity,
                'panel_count' => $panelCount,
                'inverter_count' => $inverterCount,
                'expected_annual_yield_kwh' => $expectedYield,
                'total_investment' => $totalInvestment,
                'annual_operating_costs' => $operatingCosts,
                'feed_in_tariff_per_kwh' => $feedInTariff,
                'electricity_price_per_kwh' => $electricityPrice,
                'planned_installation_date' => $plannedInstallation,
                'installation_date' => $actualInstallation,
                'planned_commissioning_date' => $plannedCommissioning,
                'commissioning_date' => $actualCommissioning,
                'status' => 'active',
                'is_active' => true,
                'description' => $description,
            ]);
            $solarPlants[] = $solarPlant;
        }

        $this->info('☀️ 10 Solaranlagen erstellt (5 große Anlagen 250-2000kW, 5 kleinere Anlagen 5-50kW) mit realistischen Koordinaten');
        return $solarPlants;
    }

    private function createSolarPlantAssignments($customers, $solarPlants)
    {
        // 6 Solaranlagen sollen auf Kunden aufgeteilt werden
        $assignedPlants = array_slice($solarPlants, 0, 6);
        
        // 2 Kunden sollen je 6 Solaranlagen haben (alle 6)
        $specialCustomers = array_slice($customers, 0, 2);
        
        foreach ($assignedPlants as $plant) {
            $remainingPercentage = 100;
            $participations = [];
            
            // Erste 2 Kunden bekommen je einen Anteil
            foreach ($specialCustomers as $customer) {
                $percentage = rand(15, 25); // 15-25% pro Hauptkunde
                $participations[] = [
                    'customer_id' => $customer->id,
                    'percentage' => $percentage
                ];
                $remainingPercentage -= $percentage;
            }
            
            // Restliche Kunden bekommen die übrigen Anteile
            $remainingCustomers = array_slice($customers, 2);
            shuffle($remainingCustomers);
            $additionalCustomers = array_slice($remainingCustomers, 0, rand(2, 4));
            
            foreach ($additionalCustomers as $index => $customer) {
                $isLast = ($index === count($additionalCustomers) - 1);
                
                if ($isLast) {
                    // Letzter Kunde bekommt den Rest
                    $percentage = $remainingPercentage;
                } else {
                    // Andere bekommen 10-20%
                    $maxPercentage = min(20, $remainingPercentage - (count($additionalCustomers) - $index - 1) * 10);
                    $percentage = rand(10, max(10, $maxPercentage));
                }
                
                if ($percentage >= 10 && $remainingPercentage >= $percentage) {
                    $participations[] = [
                        'customer_id' => $customer->id,
                        'percentage' => $percentage
                    ];
                    $remainingPercentage -= $percentage;
                }
            }
            
            // Erstelle alle Participations für diese Anlage
            foreach ($participations as $participation) {
                PlantParticipation::create([
                    'customer_id' => $participation['customer_id'],
                    'solar_plant_id' => $plant->id,
                    'percentage' => $participation['percentage'],
                ]);
            }
        }

        $this->info('🔗 Solaranlagen-Zuordnungen erstellt (min. 10% pro Anteil, max. 100% pro Anlage)');
    }

    private function createCustomerNotes($customers, $user)
    {
        $noteTemplates = [
            'Kunde ist sehr interessiert an nachhaltigen Energielösungen',
            'Bevorzugt Kommunikation per E-Mail',
            'Hat bereits Erfahrung mit Solaranlagen',
            'Möchte Beratung zu Batteriespeichern',
            'Sehr preisbewusst, benötigt detaillierte Kostenaufstellung',
            'Empfehlung durch Nachbarn erhalten',
            'Plant Erweiterung der Anlage in 2 Jahren',
            'Benötigt Finanzierungsberatung',
            'Interessiert an Smart-Home Integration',
            'Möchte monatliche Ertragsberichte'
        ];

        $noteTypes = ['general', 'contact', 'issue', 'payment', 'contract'];

        foreach ($customers as $customer) {
            // 1-3 Notizen pro Kunde
            $noteCount = rand(1, 3);
            for ($i = 0; $i < $noteCount; $i++) {
                CustomerNote::create([
                    'customer_id' => $customer->id,
                    'user_id' => $user->id,
                    'title' => 'Notiz ' . ($i + 1),
                    'content' => $noteTemplates[array_rand($noteTemplates)],
                    'type' => $noteTypes[array_rand($noteTypes)],
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        $this->info('📝 Kundennotizen erstellt');
    }

    private function createInvoices($customers, $taxRates)
    {
        $articles = Article::with('taxRate')->get();
        
        // 15 Beispiel-Rechnungen erstellen, verteilt über die letzten 3 Monate
        for ($i = 0; $i < 15; $i++) {
            $customer = $customers[array_rand($customers)];
            
            // Rechnung darf nur nach Kundenerstellung erstellt werden
            $customerCreationDate = $customer->created_at;
            $maxDaysAgo = now()->diffInDays($customerCreationDate);
            
            // Zufälliges Datum zwischen Kundenerstellung und heute
            $randomDaysAgo = rand(0, min($maxDaysAgo, 90)); // Maximal 90 Tage oder seit Kundenerstellung
            $invoiceDate = now()->subDays($randomDaysAgo);
            
            // Sicherstellen, dass Rechnung nicht vor Kundenerstellung liegt
            if ($invoiceDate->lt($customerCreationDate)) {
                $invoiceDate = $customerCreationDate->copy()->addDays(rand(1, 7)); // 1-7 Tage nach Kundenerstellung
            }
            
            // Fälligkeitsdatum basierend auf kundenspezifischem Zahlungsziel berechnen
            $paymentDays = $customer->payment_days ?? 14; // Fallback: 14 Tage
            $dueDate = $invoiceDate->copy()->addDays($paymentDays);
            
            // Rechnung erstellen ohne automatische Timestamps
            $invoice = new Invoice([
                'customer_id' => $customer->id,
                // invoice_number wird automatisch generiert durch das Model
                'status' => ['draft', 'sent', 'paid'][array_rand(['draft', 'sent', 'paid'])],
                'total' => 0, // Wird nach Items berechnet
                'due_date' => $dueDate,
            ]);
            
            // Timestamps manuell setzen
            $invoice->timestamps = false;
            $invoice->created_at = $invoiceDate;
            $invoice->updated_at = $invoiceDate;
            $invoice->save();

            // 2-5 Artikel pro Rechnung
            $itemCount = rand(2, 5);
            $totalAmount = 0;
            
            for ($j = 0; $j < $itemCount; $j++) {
                $article = $articles->random();
                $quantity = rand(1, 5);
                $price = $article->price;
                $lineTotal = $quantity * $price;
                
                // Verwende den korrekten Steuersatz vom Artikel
                $taxRate = $article->getCurrentTaxRate();
                
                // Hole die aktuelle TaxRateVersion falls verfügbar
                $taxRateVersion = null;
                if ($article->taxRate) {
                    $taxRateVersion = TaxRateVersion::getCurrentVersion($article->taxRate);
                }
                
                // InvoiceItem erstellen ohne automatische Timestamps
                $invoiceItem = new InvoiceItem([
                    'invoice_id' => $invoice->id,
                    'article_id' => $article->id,
                    'description' => $article->name,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'tax_rate' => $taxRate,
                    'tax_rate_version_id' => $taxRateVersion?->id,
                    'total' => $lineTotal,
                ]);
                
                // Timestamps manuell setzen
                $invoiceItem->timestamps = false;
                $invoiceItem->created_at = $invoiceDate;
                $invoiceItem->updated_at = $invoiceDate;
                $invoiceItem->save();
                
                $totalAmount += $lineTotal;
            }

            // Rechnung aktualisieren mit korrektem Timestamp
            $invoice->timestamps = false;
            $invoice->total = $totalAmount;
            $invoice->updated_at = $invoiceDate;
            $invoice->save();
        }

        $this->info('🧾 15 Beispiel-Rechnungen erstellt (verteilt über die letzten 3 Monate)');
    }

    private function createSolarPlantNotes($solarPlants, $user)
    {
        $noteTemplates = [
            [
                'title' => 'Installation abgeschlossen',
                'content' => 'Die Installation wurde erfolgreich abgeschlossen. Alle Module sind ordnungsgemäß montiert und der Wechselrichter ist konfiguriert. Erste Messungen zeigen optimale Leistungswerte.',
                'type' => 'general'
            ],
            [
                'title' => 'Erste Wartung durchgeführt',
                'content' => 'Routinewartung nach 3 Monaten Betrieb. Alle Komponenten funktionieren einwandfrei. Reinigung der Module durchgeführt, Ertragssteigerung von 5% festgestellt.',
                'type' => 'maintenance'
            ],
            [
                'title' => 'Optimierungspotential identifiziert',
                'content' => 'Durch Anpassung der Wechselrichter-Einstellungen könnte der Ertrag um weitere 2-3% gesteigert werden. Empfehlung für nächsten Wartungstermin.',
                'type' => 'improvement'
            ],
            [
                'title' => 'Monitoring-System installiert',
                'content' => 'Umfassendes Monitoring-System für Echtzeitüberwachung installiert. Ermöglicht frühzeitige Erkennung von Leistungsabweichungen und optimiert Wartungsintervalle.',
                'type' => 'improvement'
            ],
            [
                'title' => 'Kleinere Reparatur durchgeführt',
                'content' => 'Defekter Optimierer ausgetauscht. Ursache war Feuchtigkeit durch undichte Stelle. Dichtung erneuert, Problem behoben.',
                'type' => 'issue'
            ]
        ];

        foreach ($solarPlants as $plant) {
            // 3 Notizen pro Anlage
            $selectedNotes = array_rand($noteTemplates, 3);
            foreach ($selectedNotes as $index) {
                $template = $noteTemplates[$index];
                SolarPlantNote::create([
                    'solar_plant_id' => $plant->id,
                    'user_id' => $user->id,
                    'title' => $template['title'],
                    'content' => $template['content'],
                    'type' => $template['type'],
                    'created_at' => now()->subDays(rand(1, 90)),
                ]);
            }
        }

        $this->info('📝 Solaranlagen-Notizen erstellt (3 pro Anlage)');
    }

    private function createSupplierData()
    {
        $seeder = new SupplierSeeder();
        $seeder->run();
        
        // Erstelle Telefonnummern für die Lieferanten-Unternehmen
        $this->createSupplierPhoneNumbers();
        
        // Erstelle zusätzliche Mitarbeiter für die Lieferanten
        $this->createSupplierEmployees();
        
        $this->info('🏢 Lieferanten-Daten erstellt (10 Lieferanten mit Telefonnummern und 3-10 Mitarbeitern pro Lieferant)');
    }

    private function createSupplierPhoneNumbers()
    {
        $suppliers = Supplier::all();
        
        foreach ($suppliers as $supplier) {
            // Zentrale (Hauptnummer)
            $centralPhone = $this->generateGermanBusinessPhone();
            PhoneNumber::create([
                'phoneable_type' => Supplier::class,
                'phoneable_id' => $supplier->id,
                'phone_number' => $centralPhone,
                'type' => 'business',
                'label' => 'Zentrale',
                'is_primary' => true,
            ]);
            
            // Hotline/Service
            $hotlinePhone = $this->generateGermanBusinessPhone();
            PhoneNumber::create([
                'phoneable_type' => Supplier::class,
                'phoneable_id' => $supplier->id,
                'phone_number' => $hotlinePhone,
                'type' => 'business',
                'label' => 'Hotline',
                'is_primary' => false,
            ]);
            
            // Optional: Weitere Nummern (Fax, Notfall, etc.)
            if (rand(0, 10) > 6) { // 30% Chance auf zusätzliche Nummer
                $additionalLabels = ['Fax', 'Notfall', 'Vertrieb', 'Service'];
                $additionalLabel = $additionalLabels[array_rand($additionalLabels)];
                
                $additionalPhone = $additionalLabel === 'Fax' ?
                    $this->generateGermanFaxNumber() :
                    $this->generateGermanBusinessPhone();
                
                PhoneNumber::create([
                    'phoneable_type' => Supplier::class,
                    'phoneable_id' => $supplier->id,
                    'phone_number' => $additionalPhone,
                    'type' => 'business', // Verwende immer 'business' da 'fax' nicht unterstützt wird
                    'label' => $additionalLabel,
                    'is_primary' => false,
                ]);
            }
        }
        
        $this->info('📞 Telefonnummern für Lieferanten erstellt (mindestens Zentrale + Hotline pro Lieferant)');
    }
    
    private function generateGermanBusinessPhone()
    {
        // Deutsche Geschäfts-Telefonnummern
        $areaCodes = ['30', '40', '89', '221', '211', '69', '711', '351', '431', '511'];
        $areaCode = $areaCodes[array_rand($areaCodes)];
        $number = rand(100000, 999999);
        $extension = rand(0, 99);
        
        return "+49 {$areaCode} {$number}-{$extension}";
    }
    
    private function generateGermanFaxNumber()
    {
        // Fax-Nummern sind oft ähnlich der Hauptnummer mit anderer Durchwahl
        $areaCodes = ['30', '40', '89', '221', '211', '69', '711', '351', '431', '511'];
        $areaCode = $areaCodes[array_rand($areaCodes)];
        $number = rand(100000, 999999);
        $faxExtension = rand(90, 99); // Fax oft mit 9x Durchwahl
        
        return "+49 {$areaCode} {$number}-{$faxExtension}";
    }

    private function createSupplierEmployees()
    {
        $suppliers = Supplier::all();
        
        // Vorname und Nachname Arrays für realistische deutsche Namen
        $firstNames = [
            'Alexander', 'Andreas', 'Christian', 'Daniel', 'David', 'Frank', 'Jan', 'Jürgen', 'Klaus', 'Markus',
            'Michael', 'Oliver', 'Peter', 'Stefan', 'Thomas', 'Uwe', 'Wolfgang', 'Andrea', 'Angela', 'Anna',
            'Birgit', 'Claudia', 'Daniela', 'Eva', 'Gabriele', 'Heike', 'Julia', 'Karin', 'Katrin', 'Maria',
            'Martina', 'Monika', 'Nicole', 'Petra', 'Sabine', 'Sandra', 'Silke', 'Susanne', 'Ute', 'Yvonne'
        ];
        
        $lastNames = [
            'Müller', 'Schmidt', 'Schneider', 'Fischer', 'Weber', 'Meyer', 'Wagner', 'Becker', 'Schulz', 'Hoffmann',
            'Schäfer', 'Koch', 'Bauer', 'Richter', 'Klein', 'Wolf', 'Schröder', 'Neumann', 'Schwarz', 'Zimmermann',
            'Braun', 'Krüger', 'Hofmann', 'Hartmann', 'Lange', 'Schmitt', 'Werner', 'Schmitz', 'Krause', 'Meier'
        ];
        
        // Positionen für verschiedene Lieferanten-Typen
        $positions = [
            'Geschäftsführer', 'Vertriebsleiter', 'Projektmanager', 'Techniker', 'Servicetechniker',
            'Verkaufsberater', 'Kundenberater', 'Monteur', 'Elektriker', 'Teamleiter',
            'Sachbearbeiter', 'Einkäufer', 'Logistikkoordinator', 'Qualitätsprüfer', 'Außendienstmitarbeiter'
        ];
        
        foreach ($suppliers as $supplier) {
            // Bestimme Anzahl Mitarbeiter: mindestens 3, manche haben 5-10
            $employeeCount = rand(0, 10) < 7 ? rand(3, 5) : rand(5, 10); // 70% haben 3-5, 30% haben 5-10
            
            for ($i = 0; $i < $employeeCount; $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $position = $positions[array_rand($positions)];
                
                // Erster Mitarbeiter ist immer Hauptansprechpartner
                $isPrimary = ($i === 0);
                
                $employee = SupplierEmployee::create([
                    'supplier_id' => $supplier->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'position' => $position,
                    'email' => strtolower($firstName . '.' . $lastName) . '@' . $this->getDomainFromSupplier($supplier),
                    'is_primary_contact' => $isPrimary,
                    'is_active' => rand(0, 10) > 1, // 90% aktiv, 10% inaktiv
                ]);
                
                // Erstelle 1-3 Telefonnummern pro Mitarbeiter
                $phoneCount = rand(1, 3);
                $phoneTypes = ['business', 'mobile', 'private'];
                
                for ($j = 0; $j < $phoneCount; $j++) {
                    $phoneType = $phoneTypes[$j % count($phoneTypes)];
                    $isPhonePrimary = ($j === 0); // Erste Nummer ist Hauptnummer
                    
                    // Deutsche Telefonnummern generieren
                    if ($phoneType === 'mobile') {
                        $phoneNumber = '+49 1' . rand(50, 79) . ' ' . rand(1000000, 9999999);
                    } else {
                        $areaCode = rand(30, 89) . rand(1, 9);
                        $phoneNumber = '+49 ' . $areaCode . ' ' . rand(100000, 999999) . '-' . rand(10, 99);
                    }
                    
                    PhoneNumber::create([
                        'phoneable_type' => SupplierEmployee::class,
                        'phoneable_id' => $employee->id,
                        'phone_number' => $phoneNumber,
                        'type' => $phoneType,
                        'label' => $this->getPhoneLabel($phoneType),
                        'is_primary' => $isPhonePrimary,
                    ]);
                }
            }
        }
        
        $this->info('👥 Mitarbeiter für alle Lieferanten erstellt (3-10 pro Lieferant mit Telefonnummern)');
    }
    
    private function getDomainFromSupplier($supplier)
    {
        // Extrahiere Domain aus Website oder erstelle eine basierend auf Firmenname
        if ($supplier->website) {
            $domain = str_replace(['www.', 'http://', 'https://'], '', $supplier->website);
            return $domain;
        }
        
        // Fallback: Erstelle Domain aus Firmenname
        $domain = strtolower(str_replace([' ', 'ä', 'ö', 'ü', 'ß', '&', '.', ','], ['', 'ae', 'oe', 'ue', 'ss', '', '', ''], $supplier->company_name));
        return $domain . '.de';
    }
    
    private function getPhoneLabel($type)
    {
        return match($type) {
            'business' => 'Büro',
            'mobile' => 'Mobil',
            'private' => 'Privat',
            default => 'Telefon'
        };
    }

    private function createProjectMilestones($solarPlants, $user)
    {
        // Spezifische Solaranlagen für Projekttermine
        $targetPlantNames = ['Sonnenkraft Nord', 'Photovoltaik Süd', 'Solarpark Ost', 'Energiefeld West'];
        
        // Finde die entsprechenden Solaranlagen
        $targetPlants = collect($solarPlants)->filter(function($plant) use ($targetPlantNames) {
            return in_array($plant->name, $targetPlantNames);
        });

        // Projekttermin-Templates für verschiedene Phasen
        $milestoneTemplates = [
            [
                'title' => 'Baugenehmigung erhalten',
                'description' => 'Offizielle Baugenehmigung von der zuständigen Behörde erhalten. Alle rechtlichen Voraussetzungen für den Baubeginn sind erfüllt.',
                'status' => 'completed',
                'days_before_installation' => 60
            ],
            [
                'title' => 'Netzanschluss beantragt',
                'description' => 'Antrag auf Netzanschluss beim örtlichen Netzbetreiber gestellt. Technische Prüfung der Anschlussmöglichkeiten läuft.',
                'status' => 'completed',
                'days_before_installation' => 45
            ],
            [
                'title' => 'Komponenten geliefert',
                'description' => 'Alle Solarmodule, Wechselrichter und Montagesysteme wurden termingerecht geliefert und auf der Baustelle bereitgestellt.',
                'status' => 'completed',
                'days_before_installation' => 7
            ],
            [
                'title' => 'Installation abgeschlossen',
                'description' => 'Montage aller Komponenten erfolgreich abgeschlossen. Verkabelung und erste Funktionsprüfungen durchgeführt.',
                'status' => 'completed',
                'days_after_installation' => 0
            ],
            [
                'title' => 'Inbetriebnahme durchgeführt',
                'description' => 'Offizielle Inbetriebnahme mit Netzbetreiber durchgeführt. Anlage ist vollständig funktionsfähig und speist ins Netz ein.',
                'status' => 'completed',
                'days_after_installation' => 14
            ],
            [
                'title' => 'Erste Wartung geplant',
                'description' => 'Erste routinemäßige Wartung nach 6 Monaten Betrieb geplant. Überprüfung aller Komponenten und Leistungsoptimierung.',
                'status' => 'planned',
                'days_after_installation' => 180
            ],
            [
                'title' => 'Monitoring-System optimiert',
                'description' => 'Überwachungssystem konfiguriert und optimiert. Automatische Benachrichtigungen bei Leistungsabweichungen eingerichtet.',
                'status' => 'in_progress',
                'days_after_installation' => 30
            ],
            [
                'title' => 'Ertragsanalyse erstellt',
                'description' => 'Detaillierte Analyse der ersten Betriebsmonate. Vergleich zwischen prognostizierten und tatsächlichen Erträgen.',
                'status' => 'completed',
                'days_after_installation' => 90
            ]
        ];

        foreach ($targetPlants as $plant) {
            // Wähle 3 zufällige Meilensteine für jede Anlage
            $selectedMilestones = collect($milestoneTemplates)->random(3);
            $sortOrder = 1;

            foreach ($selectedMilestones as $template) {
                // Berechne Datum basierend auf Installation
                $installationDate = $plant->installation_date;
                
                if (isset($template['days_before_installation'])) {
                    $plannedDate = $installationDate->copy()->subDays($template['days_before_installation']);
                    $actualDate = $plannedDate->copy()->addDays(rand(-3, 7)); // Leichte Abweichung
                } else {
                    $plannedDate = $installationDate->copy()->addDays($template['days_after_installation']);
                    $actualDate = $template['status'] === 'completed' ?
                        $plannedDate->copy()->addDays(rand(-5, 10)) : null;
                }

                SolarPlantMilestone::create([
                    'solar_plant_id' => $plant->id,
                    'title' => $template['title'],
                    'description' => $template['description'],
                    'planned_date' => $plannedDate,
                    'actual_date' => $actualDate,
                    'status' => $template['status'],
                    'is_active' => true,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        $this->info('📅 Projekttermine erstellt (3 pro Anlage für: ' . implode(', ', $targetPlantNames) . ')');
    }

    private function createSupplierAssignments($solarPlants)
    {
        // Spezifische Solaranlagen für Lieferanten-Zuordnungen
        $targetPlantNames = ['Sonnenkraft Nord', 'Photovoltaik Süd', 'Solarpark Ost', 'Energiefeld West'];
        
        // Finde die entsprechenden Solaranlagen
        $targetPlants = collect($solarPlants)->filter(function($plant) use ($targetPlantNames) {
            return in_array($plant->name, $targetPlantNames);
        });

        // Hole alle verfügbaren Lieferanten
        $suppliers = Supplier::with('employees')->get();
        
        if ($suppliers->count() < 3) {
            $this->warn('⚠️ Nicht genügend Lieferanten vorhanden. Mindestens 3 Lieferanten erforderlich.');
            return;
        }

        // Rollen-Templates für verschiedene Lieferanten-Typen
        $roleTemplates = [
            'Installateur' => [
                'roles' => ['Installateur', 'Montage', 'Elektroinstallation'],
                'notes' => 'Verantwortlich für die komplette Installation der Solaranlage. Erfahrenes Team mit Spezialisierung auf Dachmontage und Elektroinstallation.'
            ],
            'Komponenten' => [
                'roles' => ['Komponenten', 'Lieferant', 'Großhandel'],
                'notes' => 'Lieferung aller Hauptkomponenten inkl. Module, Wechselrichter und Montagesystem. Garantiert kurze Lieferzeiten und Qualitätsprodukte.'
            ],
            'Wartung' => [
                'roles' => ['Wartung', 'Service', 'Support'],
                'notes' => 'Zuständig für regelmäßige Wartung und technischen Support. 24/7 Notfallservice und präventive Instandhaltung.'
            ],
            'Planung' => [
                'roles' => ['Planung', 'Beratung', 'Projektleitung'],
                'notes' => 'Technische Planung und Projektmanagement. Begleitung von der ersten Beratung bis zur Inbetriebnahme.'
            ]
        ];

        foreach ($targetPlants as $plant) {
            // Wähle 3 zufällige Lieferanten für jede Anlage
            $selectedSuppliers = $suppliers->random(3);
            $usedRoleTypes = [];

            foreach ($selectedSuppliers as $index => $supplier) {
                // Wähle eine Rolle, die noch nicht verwendet wurde
                $availableRoleTypes = array_diff(array_keys($roleTemplates), $usedRoleTypes);
                if (empty($availableRoleTypes)) {
                    $availableRoleTypes = array_keys($roleTemplates); // Reset wenn alle verwendet
                }
                
                $roleType = $availableRoleTypes[array_rand($availableRoleTypes)];
                $usedRoleTypes[] = $roleType;
                
                $roleTemplate = $roleTemplates[$roleType];
                $selectedRole = $roleTemplate['roles'][array_rand($roleTemplate['roles'])];

                // Wähle zufälligen Mitarbeiter als Ansprechpartner (falls vorhanden)
                $activeEmployees = $supplier->employees->where('is_active', true);
                $supplierEmployee = $activeEmployees->count() > 0 ? $activeEmployees->random(1)->first() : null;

                // Zeitraum basierend auf Anlageninstallation
                $startDate = $plant->installation_date->copy()->subDays(rand(30, 90));
                $endDate = rand(0, 1) ? null : $plant->installation_date->copy()->addMonths(rand(12, 36));

                SolarPlantSupplier::create([
                    'solar_plant_id' => $plant->id,
                    'supplier_id' => $supplier->id,
                    'supplier_employee_id' => $supplierEmployee?->id,
                    'role' => $selectedRole,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'notes' => $roleTemplate['notes'],
                    'is_active' => true,
                ]);
            }
        }

        $this->info('🤝 Lieferanten-Zuordnungen erstellt (3 pro Anlage für: ' . implode(', ', $targetPlantNames) . ')');
    }
}