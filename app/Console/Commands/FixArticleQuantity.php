<?php

namespace App\Console\Commands;

use App\Models\SupplierContractBillingArticle;
use Illuminate\Console\Command;

class FixArticleQuantity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:fix-article-quantity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix incorrect article quantity for Einspeisung Marktwert article';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Korrigiere falsche Artikel-Quantity...');
        $this->newLine();

        // Finde den problematischen Artikel mit ID 01983cdc-9d21-7227-9dad-b11a7e33ebd6
        $article = SupplierContractBillingArticle::find('01983cdc-9d21-7227-9dad-b11a7e33ebd6');
        
        if ($article) {
            $this->info('Artikel gefunden:');
            $this->line("ID: {$article->id}");
            $this->line("Description: {$article->description}");
            $this->line("Alte Quantity: {$article->quantity}");
            $this->line("Unit Price: {$article->unit_price}");
            $this->line("Aktueller Total Price: {$article->total_price}");
            
            // Die Quantity sollte 1000 sein, nicht 1
            // Basierend auf total_price / unit_price
            $correctQuantity = $article->total_price / $article->unit_price;
            
            $this->line("Berechnete korrekte Quantity: {$correctQuantity}");
            
            if ($this->confirm('Soll die Quantity von 1.0000 auf 1000.0000 korrigiert werden?')) {
                // Setze die Quantity auf 1000
                $article->updateQuietly(['quantity' => 1000]);
                
                $this->info('Quantity wurde korrigiert!');
                $this->line("Neue Quantity: 1000.0000");
                $this->line("Unit Price: {$article->unit_price}");
                $this->line("Erwarteter Total Price: " . (1000 * $article->unit_price));
                $this->line("Aktueller Total Price: {$article->total_price}");
                
                // Hinweis: Der total_price bleibt gleich, da er bereits korrekt ist
                $this->info('Der total_price bleibt bei 0.018430 €, da dies der korrekte Wert ist.');
            } else {
                $this->info('Korrektur abgebrochen.');
            }
        } else {
            $this->error('Artikel nicht gefunden!');
        }
        
        return 0;
    }
}
