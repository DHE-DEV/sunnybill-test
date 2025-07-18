<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\SolarPlant;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SupplierType;
use App\Models\SupplierContract;
use App\Models\SupplierContractSolarPlant;
use App\Models\SupplierContractBilling;
use App\Models\Article;
use App\Models\TaxRate;
use App\Models\TaskType;
use App\Models\CompanySetting;
use App\Models\StorageSetting;
use App\Models\DocumentPathSetting;

class TestDataManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static string $view = 'filament.pages.test-data-manager';
    protected static ?string $title = 'Test-Datenmanager';
    protected static ?string $navigationLabel = 'Test-Datenmanager';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 999;

    public static function canAccess(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetDatabase')
                ->label('Testdaten zurücksetzen')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Testdaten zurücksetzen')
                ->modalDescription('Alle Daten werden gelöscht und durch Testdaten ersetzt. Diese Aktion kann nicht rückgängig gemacht werden.')
                ->modalSubmitActionLabel('Ja, zurücksetzen')
                ->action('resetToTestData'),
                
            Action::make('createTestData')
                ->label('Testdaten erstellen')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->action('createTestData'),
        ];
    }

    public function resetToTestData(): void
    {
        try {
            // Alle Tabellen leeren (außer Migrationen und Admin-User)
            $this->clearAllTables();

            // Testdaten erstellen (createTestData hat bereits eigene Transaktion)
            $this->createTestDataWithoutTransaction();

            Notification::make()
                ->title('Testdaten erfolgreich zurückgesetzt')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Zurücksetzen der Testdaten')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function createTestData(): void
    {
        try {
            DB::beginTransaction();

            $this->createTestDataWithoutTransaction();

            DB::commit();

            Notification::make()
                ->title('Testdaten erfolgreich erstellt')
                ->success()
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Fehler beim Erstellen der Testdaten')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function createTestDataWithoutTransaction(): void
    {
        // 1. Firmeneinstellungen
        $this->createCompanySettings();

        // 2. Steuersätze
        $this->createTaxRates();

        // 3. Artikel
        $this->createArticles();

        // 4. Aufgabentypen
        $this->createTaskTypes();

        // 5. Speichereinstellungen
        $this->createStorageSettings();

        // 6. Dokumentpfade
        $this->createDocumentPathSettings();

        // 7. Lieferantentypen
        $this->createSupplierTypes();

        // 8. Solaranlagen
        $solarPlants = $this->createSolarPlants();

        // 9. Kunden
        $customers = $this->createCustomers();

        // 10. Lieferanten
        $suppliers = $this->createSuppliers();

        // 11. Lieferantenverträge
        $contracts = $this->createSupplierContracts($suppliers, $solarPlants);

        // 12. Abrechnungen
        $this->createSupplierContractBillings($contracts);
    }

    private function clearAllTables(): void
    {
        // Foreign Key Checks deaktivieren
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Reihenfolge wichtig wegen Foreign Key Constraints
        $tables = [
            'supplier_contract_billing_allocations',
            'supplier_contract_billings',
            'supplier_contract_solar_plants',
            'supplier_contract_notes',
            'supplier_contracts',
            'suppliers',
            'supplier_types',
            'documents',
            'tasks',
            'task_types',
            'customer_monthly_credits',
            'plant_monthly_results',
            'solar_plant_milestones',
            'solar_plants',
            'customers',
            'invoice_items',
            'invoice_versions',
            'invoices',
            'credit_notes',
            'articles',
            'tax_rates',
            'document_path_settings',
            'storage_settings',
            'company_settings',
        ];

        foreach ($tables as $table) {
            // Prüfen ob Tabelle existiert
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        // Foreign Key Checks wieder aktivieren
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function createCompanySettings(): void
    {
        CompanySetting::create([
            'company_name' => 'SunnyBill Test GmbH',
            'address' => 'Teststraße 123',
            'postal_code' => '12345',
            'city' => 'Teststadt',
            'country' => 'Deutschland',
            'phone' => '+49 123 456789',
            'email' => 'test@voltmaster.cloud',
            'website' => 'https://voltmaster.cloud',
            'tax_number' => 'DE123456789',
            'vat_id' => 'DE987654321',
            'invoice_number_prefix' => 'RE-',
            'invoice_number_start' => 1000,
            'credit_note_prefix' => 'GS-',
            'credit_note_start' => 1000,
            'solar_plant_prefix' => 'SA-',
            'project_prefix' => 'PRJ-',
        ]);
    }

    private function createTaxRates(): array
    {
        return [
            TaxRate::create([
                'name' => 'Standard (19%)',
                'description' => 'Regulärer Steuersatz',
                'rate' => 0.1900,
                'valid_from' => '2023-01-01',
                'valid_until' => null,
                'is_active' => true,
                'is_default' => true,
            ]),
            TaxRate::create([
                'name' => 'Ermäßigt (7%)',
                'description' => 'Ermäßigter Steuersatz',
                'rate' => 0.0700,
                'valid_from' => '2023-01-01',
                'valid_until' => null,
                'is_active' => true,
                'is_default' => false,
            ]),
            TaxRate::create([
                'name' => 'Steuerfrei (0%)',
                'description' => 'Steuerfreie Lieferung',
                'rate' => 0.0000,
                'valid_from' => '2023-01-01',
                'valid_until' => null,
                'is_active' => true,
                'is_default' => false,
            ]),
        ];
    }

    private function createArticles(): array
    {
        $taxRate = TaxRate::where('is_default', true)->first();
        
        return [
            Article::create([
                'name' => 'Stromlieferung',
                'description' => 'Stromlieferung aus Solaranlage',
                'price' => 0.25,
                'tax_rate' => $taxRate->rate,
                'tax_rate_id' => $taxRate->id,
                'unit' => 'kWh',
            ]),
            Article::create([
                'name' => 'Grundgebühr',
                'description' => 'Monatliche Grundgebühr',
                'price' => 15.00,
                'tax_rate' => $taxRate->rate,
                'tax_rate_id' => $taxRate->id,
                'unit' => 'Monat',
            ]),
        ];
    }

    private function createTaskTypes(): array
    {
        return [
            TaskType::create([
                'name' => 'Installation',
                'description' => 'Installation von Solaranlagen',
                'color' => '#10B981',
            ]),
            TaskType::create([
                'name' => 'Wartung',
                'description' => 'Wartung und Instandhaltung',
                'color' => '#F59E0B',
            ]),
            TaskType::create([
                'name' => 'Reparatur',
                'description' => 'Reparaturarbeiten',
                'color' => '#EF4444',
            ]),
        ];
    }

    private function createStorageSettings(): void
    {
        StorageSetting::create([
            'storage_type' => 'local',
            'base_path' => 'storage/app/private',
            'public_path' => 'storage/app/public',
            'customer_documents_path' => 'customers/{customer_id}/documents',
            'supplier_documents_path' => 'suppliers/{supplier_id}/documents',
            'billing_documents_path' => 'suppliers/billings',
        ]);
    }

    private function createDocumentPathSettings(): array
    {
        return [
            DocumentPathSetting::create([
                'documentable_type' => 'App\Models\Customer',
                'category' => 'contracts',
                'path_template' => 'customers/{customer_id}/contracts',
                'description' => 'Pfad für Kundenverträge',
                'is_active' => true,
            ]),
            DocumentPathSetting::create([
                'documentable_type' => 'App\Models\Supplier',
                'category' => 'invoices',
                'path_template' => 'suppliers/{supplier_id}/invoices',
                'description' => 'Pfad für Lieferantenrechnungen',
                'is_active' => true,
            ]),
        ];
    }

    private function createSupplierTypes(): array
    {
        return [
            SupplierType::create([
                'name' => 'Energieversorger',
                'description' => 'Stromversorger und Energieunternehmen',
            ]),
            SupplierType::create([
                'name' => 'Wartungsunternehmen',
                'description' => 'Unternehmen für Wartung und Service',
            ]),
        ];
    }

    private function createSolarPlants(): array
    {
        return [
            SolarPlant::create([
                'plant_number' => 'SA-001',
                'name' => 'Aurich 1',
                'location' => 'Aurich, Niedersachsen',
                'total_capacity_kw' => 500.0,
                'installation_date' => '2023-01-15',
                'status' => 'active',
                'is_active' => true,
                'notes' => 'Erste Testanlage in Aurich',
            ]),
            SolarPlant::create([
                'plant_number' => 'SA-002',
                'name' => 'Aurich 2',
                'location' => 'Aurich, Niedersachsen',
                'total_capacity_kw' => 750.0,
                'installation_date' => '2023-06-20',
                'status' => 'active',
                'is_active' => true,
                'notes' => 'Zweite Testanlage in Aurich',
            ]),
        ];
    }

    private function createCustomers(): array
    {
        return [
            Customer::create([
                'name' => 'Max Mustermann',
                'customer_number' => 'KD-001',
                'company_name' => 'Stadtwerke Aurich',
                'contact_person' => 'Max Mustermann',
                'email' => 'max.mustermann@stadtwerke-aurich.de',
                'phone' => '+49 4941 123456',
                'street' => 'Hauptstraße 1',
                'postal_code' => '26603',
                'city' => 'Aurich',
                'country' => 'Deutschland',
                'customer_type' => 'business',
                'is_active' => true,
            ]),
            Customer::create([
                'name' => 'Anna Schmidt',
                'customer_number' => 'KD-002',
                'company_name' => 'Energie Nord GmbH',
                'contact_person' => 'Anna Schmidt',
                'email' => 'anna.schmidt@energie-nord.de',
                'phone' => '+49 4941 654321',
                'street' => 'Industriestraße 15',
                'postal_code' => '26603',
                'city' => 'Aurich',
                'country' => 'Deutschland',
                'customer_type' => 'business',
                'is_active' => true,
            ]),
        ];
    }

    private function createSuppliers(): array
    {
        $energieType = SupplierType::where('name', 'Energieversorger')->first();
        $wartungsType = SupplierType::where('name', 'Wartungsunternehmen')->first();

        return [
            Supplier::create([
                'supplier_number' => 'LF-001',
                'company_name' => 'EWE Energie AG',
                'supplier_type_id' => $energieType->id,
                'contact_person' => 'Thomas Weber',
                'email' => 'thomas.weber@ewe.de',
                'phone' => '+49 441 123456',
                'address' => 'Donnerschweer Str. 22-26',
                'postal_code' => '26123',
                'city' => 'Oldenburg',
                'country' => 'Deutschland',
            ]),
            Supplier::create([
                'supplier_number' => 'LF-002',
                'company_name' => 'Solar Service Nord',
                'supplier_type_id' => $wartungsType->id,
                'contact_person' => 'Lisa Müller',
                'email' => 'lisa.mueller@solar-service-nord.de',
                'phone' => '+49 4941 987654',
                'address' => 'Gewerbepark 5',
                'postal_code' => '26603',
                'city' => 'Aurich',
                'country' => 'Deutschland',
            ]),
        ];
    }

    private function createSupplierContracts(array $suppliers, array $solarPlants): array
    {
        $contracts = [];

        // Vertrag 1: EWE für beide Anlagen
        $contract1 = SupplierContract::create([
            'supplier_id' => $suppliers[0]->id,
            'contract_number' => 'VTR-001',
            'title' => 'Stromliefervertrag EWE',
            'description' => 'Stromlieferung für Solaranlagen Aurich',
            'start_date' => '2023-01-01',
            'end_date' => '2025-12-31',
            'contract_value' => 50000.00,
            'status' => 'active',
            'payment_terms' => 'Zahlung innerhalb 30 Tage',
        ]);

        // Zuordnung zu beiden Solaranlagen über das Model
        SupplierContractSolarPlant::create([
            'supplier_contract_id' => $contract1->id,
            'solar_plant_id' => $solarPlants[0]->id,
            'percentage' => 100.00,
            'is_active' => true,
        ]);
        
        SupplierContractSolarPlant::create([
            'supplier_contract_id' => $contract1->id,
            'solar_plant_id' => $solarPlants[1]->id,
            'percentage' => 100.00,
            'is_active' => true,
        ]);

        $contracts[] = $contract1;

        // Vertrag 2: Solar Service für Wartung
        $contract2 = SupplierContract::create([
            'supplier_id' => $suppliers[1]->id,
            'contract_number' => 'VTR-002',
            'title' => 'Wartungsvertrag Solar Service',
            'description' => 'Wartung und Service für Solaranlagen',
            'start_date' => '2023-01-01',
            'end_date' => '2024-12-31',
            'contract_value' => 15000.00,
            'status' => 'active',
            'payment_terms' => 'Zahlung innerhalb 14 Tage',
        ]);

        SupplierContractSolarPlant::create([
            'supplier_contract_id' => $contract2->id,
            'solar_plant_id' => $solarPlants[0]->id,
            'percentage' => 50.00,
            'is_active' => true,
        ]);
        
        SupplierContractSolarPlant::create([
            'supplier_contract_id' => $contract2->id,
            'solar_plant_id' => $solarPlants[1]->id,
            'percentage' => 50.00,
            'is_active' => true,
        ]);

        $contracts[] = $contract2;

        return $contracts;
    }

    private function createSupplierContractBillings(array $contracts): array
    {
        $billings = [];

        // Abrechnungen für EWE Vertrag
        for ($month = 1; $month <= 6; $month++) {
            $billing = SupplierContractBilling::create([
                'supplier_contract_id' => $contracts[0]->id,
                'billing_number' => sprintf('ABR-EWE-%04d-%02d', 2024, $month),
                'title' => sprintf('Stromlieferung %02d/2024', $month),
                'description' => sprintf('Monatliche Stromlieferung für %02d/2024', $month),
                'billing_date' => sprintf('2024-%02d-01', $month),
                'due_date' => sprintf('2024-%02d-31', $month),
                'total_amount' => rand(3000, 5000),
                'status' => $month <= 4 ? 'paid' : 'pending',
                'billing_type' => 'invoice',
                'billing_year' => 2024,
                'billing_month' => $month,
            ]);
            $billings[] = $billing;
        }

        // Abrechnungen für Wartungsvertrag
        for ($quarter = 1; $quarter <= 2; $quarter++) {
            $billing = SupplierContractBilling::create([
                'supplier_contract_id' => $contracts[1]->id,
                'billing_number' => sprintf('ABR-SSN-%04d-Q%d', 2024, $quarter),
                'title' => sprintf('Wartung Q%d/2024', $quarter),
                'description' => sprintf('Quartalsweise Wartung Q%d/2024', $quarter),
                'billing_date' => sprintf('2024-%02d-01', $quarter * 3),
                'due_date' => sprintf('2024-%02d-15', $quarter * 3),
                'total_amount' => 2500.00,
                'status' => $quarter == 1 ? 'paid' : 'approved',
                'billing_type' => 'invoice',
                'billing_year' => 2024,
                'billing_month' => $quarter * 3,
            ]);
            $billings[] = $billing;
        }

        return $billings;
    }
}