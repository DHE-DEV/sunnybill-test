<?php

namespace App\Console\Commands;

use App\Models\SolarPlantBilling;
use Illuminate\Console\Command;

class GenerateInvoiceNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:generate-invoice-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invoice numbers for existing solar plant billings without invoice numbers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to generate invoice numbers for existing billings...');

        // Hole alle Abrechnungen ohne Rechnungsnummer, sortiert nach Erstellungsdatum
        $billings = SolarPlantBilling::whereNull('invoice_number')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($billings->isEmpty()) {
            $this->info('No billings found without invoice numbers.');
            return;
        }

        $this->info("Found {$billings->count()} billings without invoice numbers.");

        $progressBar = $this->output->createProgressBar($billings->count());
        $progressBar->start();

        $updated = 0;

        foreach ($billings as $billing) {
            try {
                // Generiere eine Rechnungsnummer basierend auf dem Jahr der Abrechnung
                $year = $billing->billing_year ?? date('Y', strtotime($billing->created_at));
                $invoiceNumber = $this->generateInvoiceNumberForYear($year);
                
                $billing->invoice_number = $invoiceNumber;
                $billing->save();
                
                $updated++;
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("\nError updating billing ID {$billing->id}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Successfully updated {$updated} billings with invoice numbers.");
    }

    /**
     * Generiert eine fortlaufende Rechnungsnummer für ein bestimmtes Jahr
     */
    private function generateInvoiceNumberForYear(int $year): string
    {
        $prefix = "RG-{$year}-";
        
        // Hole die letzte Rechnungsnummer für das Jahr
        $lastBilling = SolarPlantBilling::where('invoice_number', 'LIKE', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastBilling) {
            // Extrahiere die Nummer aus der letzten Rechnungsnummer
            $lastNumber = intval(substr($lastBilling->invoice_number, strlen($prefix)));
            $nextNumber = $lastNumber + 1;
        } else {
            // Erste Rechnung des Jahres
            $nextNumber = 1;
        }
        
        // Formatiere die Nummer mit führenden Nullen (6 Stellen)
        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
