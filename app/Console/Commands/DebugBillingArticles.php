<?php

namespace App\Console\Commands;

use App\Models\SupplierContractBillingArticle;
use Illuminate\Console\Command;

class DebugBillingArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:debug-articles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug billing articles to find quantity issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Debug Billing Articles:');
        $this->newLine();

        // Finde den problematischen Artikel mit total_price 0.01843
        $problematicArticle = SupplierContractBillingArticle::where('total_price', 0.01843)->first();
        
        if ($problematicArticle) {
            $this->info('Problematischer Artikel gefunden:');
            $this->line("ID: {$problematicArticle->id}");
            $this->line("Quantity: {$problematicArticle->quantity}");
            $this->line("Unit Price: {$problematicArticle->unit_price}");
            $this->line("Total Price: {$problematicArticle->total_price}");
            $this->line("Description: {$problematicArticle->description}");
            $this->line("Erwartete Calculation: {$problematicArticle->quantity} × {$problematicArticle->unit_price} = " . ($problematicArticle->quantity * $problematicArticle->unit_price));
        } else {
            $this->warn('Artikel mit total_price 0.01843 nicht gefunden');
        }
        
        $this->newLine();
        
        // Zeige alle Artikel mit quantity = 1
        $articlesWithOne = SupplierContractBillingArticle::where('quantity', 1)->get();
        
        if ($articlesWithOne->count() > 0) {
            $this->info("Artikel mit Quantity = 1 ({$articlesWithOne->count()} gefunden):");
            foreach ($articlesWithOne as $article) {
                $this->line("ID: {$article->id} | Quantity: {$article->quantity} | Unit Price: {$article->unit_price} | Total: {$article->total_price} | Calculation: " . ($article->quantity * $article->unit_price));
            }
        }
        
        $this->newLine();
        
        // Zeige alle Artikel mit falscher Berechnung
        $allArticles = SupplierContractBillingArticle::all();
        $wrongCalculations = [];
        
        foreach ($allArticles as $article) {
            $expected = $article->quantity * $article->unit_price;
            if (abs($article->total_price - $expected) > 0.0001) {
                $wrongCalculations[] = $article;
            }
        }
        
        if (count($wrongCalculations) > 0) {
            $this->error("Artikel mit falscher Berechnung (" . count($wrongCalculations) . " gefunden):");
            foreach ($wrongCalculations as $article) {
                $expected = $article->quantity * $article->unit_price;
                $this->line("ID: {$article->id} | Q: {$article->quantity} | P: {$article->unit_price} | Total: {$article->total_price} | Expected: {$expected} | Diff: " . ($article->total_price - $expected));
            }
        } else {
            $this->info('Alle Artikel haben korrekte Berechnungen!');
        }
        
        return 0;
    }
}
