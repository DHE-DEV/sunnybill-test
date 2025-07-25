<?php

namespace App\Console\Commands;

use App\Models\SupplierContractBillingArticle;
use Illuminate\Console\Command;

class FixCompleteArticleData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:fix-complete-article-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix the article data to correct quantity from 1 to 1000';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Korrigiere Artikel-Daten...');
        $this->newLine();

        // Finde den problematischen Artikel
        $article = SupplierContractBillingArticle::find('01983cdc-9d21-7227-9dad-b11a7e33ebd6');
        
        if ($article) {
            $this->info('Artikel gefunden:');
            $this->line("ID: {$article->id}");
            $this->line("Description: {$article->description}");
            $this->line("Alte Quantity: {$article->quantity}");
            $this->line("Unit Price: {$article->unit_price}");
            $this->line("Alter Total Price: {$article->total_price}");
            
            $newQuantity = 1000;
            $newTotalPrice = $newQuantity * $article->unit_price;
            
            $this->line("Neue Quantity: {$newQuantity}");
            $this->line("Neuer Total Price: {$newTotalPrice}");
            
            if ($this->confirm('Soll die Korrektur durchgeführt werden?')) {
                // Aktualisiere sowohl Quantity als auch Total Price
                $article->updateQuietly([
                    'quantity' => $newQuantity,
                    'total_price' => $newTotalPrice
                ]);
                
                // Lade frische Daten
                $article->refresh();
                
                $this->info('Artikel wurde erfolgreich korrigiert!');
                $this->line("Quantity: {$article->quantity}");
                $this->line("Unit Price: {$article->unit_price}");
                $this->line("Total Price: {$article->total_price}");
                $this->line("Berechnung: {$article->quantity} × {$article->unit_price} = " . ($article->quantity * $article->unit_price));
            } else {
                $this->info('Korrektur abgebrochen.');
            }
        } else {
            $this->error('Artikel nicht gefunden!');
        }
        
        return 0;
    }
}
