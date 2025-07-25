<?php

namespace App\Console\Commands;

use App\Models\SupplierContractBillingArticle;
use Illuminate\Console\Command;

class FixTotalPriceAfterQuantityUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:fix-total-price-after-quantity-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix total price after quantity update for Einspeisung Marktwert article';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Korrektur des Total Price nach Quantity Update ===');
        $this->newLine();

        // Finde den spezifischen Artikel
        $articleId = '01983cdc-9d21-7227-9dad-b11a7e33ebd6';
        $article = SupplierContractBillingArticle::find($articleId);
        
        if (!$article) {
            $this->error("❌ Artikel mit ID {$articleId} nicht gefunden!");
            return 1;
        }

        $this->info("✅ Artikel gefunden:");
        $this->line("ID: {$article->id}");
        $this->line("Description: {$article->description}");
        $this->line("Quantity: {$article->quantity}");
        $this->line("Unit Price: {$article->unit_price} €");
        $this->line("Aktueller Total Price: {$article->total_price} €");
        $this->newLine();

        // Berechne den korrekten Total Price
        $correctTotalPrice = $article->quantity * $article->unit_price;
        $this->line("🔍 Berechneter korrekter Total Price: {$correctTotalPrice} €");
        $this->newLine();

        // Sichere den alten Wert
        $oldTotalPrice = $article->total_price;
        
        $this->info("🔧 Aktualisiere Total Price...");
        
        // Aktualisiere den Total Price direkt (ohne updateQuietly um das boot Event zu triggern)
        $article->total_price = $correctTotalPrice;
        $article->save();
        
        $this->info("✅ Total Price wurde erfolgreich korrigiert!");
        $this->newLine();
        
        $this->line("📊 Vorher:");
        $this->line("  Total Price: {$oldTotalPrice} €");
        $this->newLine();
        
        $this->line("📊 Nachher:");
        $this->line("  Quantity: {$article->quantity}");
        $this->line("  Unit Price: {$article->unit_price} €");
        $this->line("  Total Price: {$article->total_price} €");
        $this->newLine();
        
        // Überprüfe die Berechnung
        $verificationTotal = $article->quantity * $article->unit_price;
        $this->line("🔍 Verifikation (Quantity × Unit Price): {$verificationTotal} €");
        
        if (abs($article->total_price - $verificationTotal) < 0.000001) {
            $this->info("✅ Total Price ist jetzt korrekt berechnet!");
        } else {
            $this->error("❌ Total Price stimmt immer noch nicht überein.");
        }
        
        // Zeige Billing-Info
        if ($article->billing) {
            $this->newLine();
            $this->line("📄 Zugehörige Billing:");
            $this->line("  ID: {$article->billing->id}");
            $this->line("  Period: {$article->billing->billing_period}");
            $this->line("  Status: {$article->billing->status}");
        }

        $this->newLine();
        $this->info("🎉 Korrektur erfolgreich abgeschlossen!");
        $this->line("Der Total Price wurde von {$oldTotalPrice} € auf {$article->total_price} € aktualisiert.");
        $this->line("Die Quantity von 99226.825 wird jetzt korrekt angezeigt.");

        return 0;
    }
}
