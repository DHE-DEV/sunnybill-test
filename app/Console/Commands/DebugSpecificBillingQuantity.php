<?php

namespace App\Console\Commands;

use App\Models\SupplierContractBilling;
use App\Models\SupplierContractBillingArticle;
use Illuminate\Console\Command;

class DebugSpecificBillingQuantity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:debug-specific-quantity {billing_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug specific billing quantity issue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $billingId = $this->argument('billing_id');
        
        $this->info("=== Debug für Billing ID: {$billingId} ===");
        $this->newLine();

        // Suche nach der Billing
        $billing = SupplierContractBilling::find($billingId);

        if (!$billing) {
            $this->error("❌ Billing mit ID {$billingId} nicht gefunden!");
            return 1;
        }

        $this->info("✅ Billing gefunden:");
        $this->line("ID: {$billing->id}");
        $this->line("Period: {$billing->billing_period}");
        $this->line("Status: {$billing->status}");
        $this->newLine();

        // Suche nach Artikeln mit "Einspeisung Marktwert" 
        $articles = SupplierContractBillingArticle::where('supplier_contract_billing_id', $billing->id)
            ->whereRaw("LOWER(description) LIKE '%einspeisung%' AND LOWER(description) LIKE '%marktwert%'")
            ->get();

        if ($articles->isEmpty()) {
            $this->error("❌ Keine Artikel mit 'Einspeisung Marktwert' in dieser Billing gefunden!");
            $this->newLine();
            $this->info("Alle Artikel in dieser Billing:");
            
            $allArticles = SupplierContractBillingArticle::where('supplier_contract_billing_id', $billing->id)->get();
            foreach ($allArticles as $article) {
                $this->line("- ID: {$article->id}");
                $this->line("  Description: {$article->description}");
                $this->line("  Quantity: {$article->quantity}");
                $this->line("  Unit Price: {$article->unit_price}");
                $this->line("  Total Price: {$article->total_price}");
                $this->newLine();
            }
        } else {
            $this->info("✅ Einspeisung Marktwert Artikel gefunden:");
            $this->newLine();
            
            foreach ($articles as $article) {
                $this->line("Artikel ID: {$article->id}");
                $this->line("Description: {$article->description}");
                $this->line("❗ Aktuelle Quantity: {$article->quantity}");
                $this->line("Unit Price: {$article->unit_price} €");
                $this->line("Total Price: {$article->total_price} €");
                
                // Berechne was die Quantity sein sollte basierend auf total_price / unit_price
                $calculatedQuantity = $article->unit_price != 0 ? $article->total_price / $article->unit_price : 0;
                $this->line("🔍 Berechnete Quantity (total_price / unit_price): {$calculatedQuantity}");
                $this->line("🎯 Gewünschte Quantity (laut Problem): 99226.825");
                $this->newLine();
                
                // Berechne was der total_price sein sollte mit 99226.825
                $expectedTotalPrice = 99226.825 * $article->unit_price;
                $this->line("💰 Erwarteter Total Price mit 99226.825: {$expectedTotalPrice} €");
                $this->newLine();
                
                // Biete die Korrektur an
                if ($this->confirm("Soll die Quantity auf 99226.825 korrigiert werden?")) {
                    $oldQuantity = $article->quantity;
                    $article->updateQuietly(['quantity' => 99226.825]);
                    
                    $this->info("✅ Quantity wurde korrigiert!");
                    $this->line("Alte Quantity: {$oldQuantity}");
                    $this->line("Neue Quantity: 99226.825");
                    $this->line("Unit Price: {$article->unit_price} €");
                    $this->line("Alter Total Price: {$article->total_price} €");
                    $this->line("Neuer erwarteter Total Price: " . (99226.825 * $article->unit_price) . " €");
                    
                    // Reload das Model um zu sehen was tatsächlich gespeichert wurde
                    $article->refresh();
                    $this->line("Tatsächlicher Total Price nach Update: {$article->total_price} €");
                }
            }
        }

        return 0;
    }
}
