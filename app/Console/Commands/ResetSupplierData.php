<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Supplier;
use App\Models\SupplierEmployee;
use App\Models\SupplierNote;
use App\Models\SolarPlantSupplier;
use App\Models\SolarPlantNote;
use App\Models\PhoneNumber;
use Database\Seeders\SupplierSeeder;
use Database\Seeders\SolarPlantSeeder;

class ResetSupplierData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suppliers:reset {--force : Force reset without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all supplier data and recreate test data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will delete ALL supplier data including employees, notes, and assignments. Are you sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting supplier data reset...');

        // 1. Lösche alle Lieferanten-Zuordnungen zu Solaranlagen
        $this->info('Deleting solar plant supplier assignments...');
        $assignmentCount = SolarPlantSupplier::count();
        SolarPlantSupplier::query()->delete();
        $this->info("Deleted {$assignmentCount} supplier assignments.");

        // 2. Lösche alle Solaranlagen-Notizen
        $this->info('Deleting solar plant notes...');
        $plantNoteCount = SolarPlantNote::count();
        SolarPlantNote::query()->delete();
        $this->info("Deleted {$plantNoteCount} solar plant notes.");

        // 3. Lösche alle Telefonnummern von Lieferanten-Mitarbeitern
        $this->info('Deleting supplier employee phone numbers...');
        $phoneCount = PhoneNumber::where('phoneable_type', SupplierEmployee::class)->count();
        PhoneNumber::where('phoneable_type', SupplierEmployee::class)->delete();
        $this->info("Deleted {$phoneCount} phone numbers.");

        // 4. Lösche alle Lieferanten-Notizen
        $this->info('Deleting supplier notes...');
        $noteCount = SupplierNote::count();
        SupplierNote::query()->delete();
        $this->info("Deleted {$noteCount} supplier notes.");

        // 5. Lösche alle Lieferanten-Mitarbeiter
        $this->info('Deleting supplier employees...');
        $employeeCount = SupplierEmployee::count();
        SupplierEmployee::query()->delete();
        $this->info("Deleted {$employeeCount} supplier employees.");

        // 6. Lösche alle Lieferanten
        $this->info('Deleting suppliers...');
        $supplierCount = Supplier::count();
        Supplier::query()->delete();
        $this->info("Deleted {$supplierCount} suppliers.");

        // 7. Erstelle neue Testdaten
        $this->info('Creating new test data...');
        
        // Zuerst Solaranlagen-Notizen erstellen (falls Solaranlagen existieren)
        $solarPlantSeeder = new SolarPlantSeeder();
        $solarPlantSeeder->run();
        $this->info('Solar plant notes created successfully.');
        
        // Dann Lieferanten-Daten erstellen
        $supplierSeeder = new SupplierSeeder();
        $supplierSeeder->run();
        $this->info('New supplier test data created successfully.');

        // 8. Zeige Zusammenfassung
        $this->info('');
        $this->info('Reset completed successfully!');
        $this->info('New data summary:');
        $this->info('- Suppliers: ' . Supplier::count());
        $this->info('- Employees: ' . SupplierEmployee::count());
        $this->info('- Phone numbers: ' . PhoneNumber::where('phoneable_type', SupplierEmployee::class)->count());
        $this->info('- Supplier notes: ' . SupplierNote::count());
        $this->info('- Solar plant notes: ' . SolarPlantNote::count());
        $this->info('- Solar plant assignments: ' . SolarPlantSupplier::count());

        return 0;
    }
}