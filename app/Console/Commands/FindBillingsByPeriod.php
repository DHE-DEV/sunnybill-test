<?php

namespace App\Console\Commands;

use App\Models\SupplierContractBilling;
use App\Models\SupplierContractBillingArticle;
use Illuminate\Console\Command;

class FindBillingsByPeriod extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:find-by-period {period?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find billings by period (e.g., 2025-06)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = $this->argument('period') ?? '2025-06';
        
        $this->info("=== Suche nach Billings für Period: {$period} ===");
        $this->newLine();

        // Parse period (z.B. "2025-06" zu Jahr und Monat)
        $parts = explode('-', $period);
        $year = $parts[0] ?? null;
        $month = isset($parts[1]) ? (int)$parts[1] : null;
        
        // Suche nach Billings mit dem entsprechenden Jahr und Monat
        $query = SupplierContractBilling::query();
        
        if ($year) {
            $query->where('billing_year', $year);
        }
        if ($month) {
            $query->where('billing_month', $month);
        }
        
        $billings = $query->get();

        if ($billings->isEmpty()) {
            $this->error("❌ Keine Billings für Period {$period} gefunden!");
            $this->newLine();
            
            // Zeige alle verfügbaren Periods
            $this->info("Verfügbare Billing-Periods:");
            $allBillings = SupplierContractBilling::select('id', 'billing_year', 'billing_month', 'billing_date')
                ->orderBy('billing_year', 'desc')
                ->orderBy('billing_month', 'desc')
                ->limit(20)
                ->get();
                
            foreach ($allBillings as $billing) {
                $this->line("- ID: {$billing->id}");
                $this->line("  Period: {$billing->billing_period}");
                $this->line("  Year: {$billing->billing_year}, Month: {$billing->billing_month}");
                $this->line("  Billing Date: {$billing->billing_date}");
                $this->newLine();
            }
        } else {
            $this->info("✅ Billings für Period {$period} gefunden:");
            $this->newLine();
            
            foreach ($billings as $billing) {
                $this->line("Billing ID: {$billing->id}");
                $this->line("Period: {$billing->billing_period}");
                $this->line("Year: {$billing->billing_year}, Month: {$billing->billing_month}");
                $this->line("Status: {$billing->status}");
                $this->line("Billing Date: {$billing->billing_date}");
                
                // Suche nach Einspeisung Marktwert Artikeln in dieser Billing
                $einspeisungArticles = SupplierContractBillingArticle::where('supplier_contract_billing_id', $billing->id)
                    ->whereRaw("LOWER(description) LIKE '%einspeisung%' AND LOWER(description) LIKE '%marktwert%'")
                    ->get();
                
                if ($einspeisungArticles->isNotEmpty()) {
                    $this->info("  🎯 GEFUNDEN: Einspeisung Marktwert Artikel!");
                    foreach ($einspeisungArticles as $article) {
                        $this->line("    - Artikel ID: {$article->id}");
                        $this->line("    - Description: {$article->description}");
                        $this->line("    - Quantity: {$article->quantity}");
                        $this->line("    - Unit Price: {$article->unit_price} €");
                        $this->line("    - Total Price: {$article->total_price} €");
                    }
                }
                
                $this->newLine();
            }
        }

        return 0;
    }
}
