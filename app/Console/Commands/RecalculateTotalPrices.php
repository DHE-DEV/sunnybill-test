<?php

namespace App\Console\Commands;

use App\Models\SupplierContractBillingArticle;
use Illuminate\Console\Command;

class RecalculateTotalPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:recalculate-total-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate total prices for billing articles with higher precision';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Neu-Berechnung der Gesamtpreise für höhere Präzision...');
        $this->newLine();

        $articles = SupplierContractBillingArticle::all();
        $updated = 0;

        $bar = $this->output->createProgressBar($articles->count());
        $bar->start();

        foreach ($articles as $article) {
            $oldTotal = $article->total_price;
            $newTotal = $article->quantity * $article->unit_price;
            
            if (abs($oldTotal - $newTotal) > 0.0001) {
                $this->newLine();
                $this->info("Artikel ID {$article->id}:");
                $this->line("  Menge: {$article->quantity}");
                $this->line("  Einzelpreis: {$article->unit_price}");
                $this->line("  Alt: {$oldTotal} € (gerundet)");
                $this->line("  Neu: {$newTotal} € (präzise)");
                $this->line("  Differenz: " . ($newTotal - $oldTotal) . " €");
                $this->newLine();
                
                // Direkte Datenbankaktualisierung ohne Event-Trigger
                $article->updateQuietly(['total_price' => $newTotal]);
                $updated++;
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Fertig! {$updated} Artikel-Gesamtpreise wurden neu berechnet.");
        
        return 0;
    }
}
