<?php

namespace App\Console\Commands;

use App\Models\SupplierContractBilling;
use App\Models\SupplierContractBillingArticle;
use Illuminate\Console\Command;

class SearchSpecificId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:search-id {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search for a specific ID in billings and articles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $searchId = $this->argument('id');
        
        $this->info("=== Suche nach ID: {$searchId} ===");
        $this->newLine();

        // Suche in Billings
        $this->info("🔍 Suche in SupplierContractBilling...");
        $billing = SupplierContractBilling::find($searchId);
        
        if ($billing) {
            $this->info("✅ Billing gefunden!");
            $this->line("ID: {$billing->id}");
            $this->line("Period: {$billing->billing_period}");
            $this->line("Status: {$billing->status}");
            $this->newLine();
        } else {
            $this->line("❌ Keine Billing mit dieser ID gefunden.");
            $this->newLine();
        }

        // Suche in Artikeln
        $this->info("🔍 Suche in SupplierContractBillingArticle...");
        $article = SupplierContractBillingArticle::find($searchId);
        
        if ($article) {
            $this->info("✅ Artikel gefunden!");
            $this->line("ID: {$article->id}");
            $this->line("Description: {$article->description}");
            $this->line("Quantity: {$article->quantity}");
            $this->line("Unit Price: {$article->unit_price} €");
            $this->line("Total Price: {$article->total_price} €");
            
            // Hole die zugehörige Billing
            if ($article->billing) {
                $this->line("Gehört zu Billing:");
                $this->line("  - Billing ID: {$article->billing->id}");
                $this->line("  - Period: {$article->billing->billing_period}");
                $this->line("  - Status: {$article->billing->status}");
            }
            $this->newLine();
        } else {
            $this->line("❌ Kein Artikel mit dieser ID gefunden.");
            $this->newLine();
        }

        // Wenn weder Billing noch Artikel gefunden wurden, suche nach ähnlichen IDs
        if (!$billing && !$article) {
            $this->info("🔍 Suche nach ähnlichen IDs...");
            
            // Suche nach IDs die mit den ersten Zeichen beginnen
            $prefix = substr($searchId, 0, 8);
            
            $similarBillings = SupplierContractBilling::where('id', 'LIKE', "{$prefix}%")->limit(5)->get();
            $similarArticles = SupplierContractBillingArticle::where('id', 'LIKE', "{$prefix}%")->limit(5)->get();
            
            if ($similarBillings->isNotEmpty()) {
                $this->info("Ähnliche Billing IDs gefunden:");
                foreach ($similarBillings as $b) {
                    $this->line("- {$b->id} (Period: {$b->billing_period})");
                }
                $this->newLine();
            }
            
            if ($similarArticles->isNotEmpty()) {
                $this->info("Ähnliche Artikel IDs gefunden:");
                foreach ($similarArticles as $a) {
                    $this->line("- {$a->id} (Description: " . substr($a->description, 0, 50) . "...)");
                }
                $this->newLine();
            }
        }

        // Suche nach allen "Einspeisung Marktwert" Artikeln
        $this->info("🔍 Suche nach allen 'Einspeisung Marktwert' Artikeln...");
        $einspeisungArticles = SupplierContractBillingArticle::whereRaw("LOWER(description) LIKE '%einspeisung%' AND LOWER(description) LIKE '%marktwert%'")
            ->with('billing')
            ->get();
            
        if ($einspeisungArticles->isNotEmpty()) {
            $this->info("✅ Einspeisung Marktwert Artikel gefunden:");
            foreach ($einspeisungArticles as $article) {
                $this->line("Artikel ID: {$article->id}");
                $this->line("  Description: {$article->description}");
                $this->line("  Quantity: {$article->quantity}");
                $this->line("  Unit Price: {$article->unit_price} €");
                $this->line("  Total Price: {$article->total_price} €");
                if ($article->billing) {
                    $this->line("  Billing ID: {$article->billing->id}");
                    $this->line("  Billing Period: {$article->billing->billing_period}");
                }
                $this->newLine();
            }
        }

        return 0;
    }
}
