<?php

namespace App\Console\Commands;

use App\Models\SupplierContractBillingArticle;
use Illuminate\Console\Command;

class AutoFixEinspeisungMarktwertQuantity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:auto-fix-einspeisung-marktwert-quantity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically fix Einspeisung Marktwert quantity to 99226.825 without confirmation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Automatische Korrektur der Einspeisung Marktwert Quantity ===');
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
        $this->line("❗ Aktuelle Quantity: {$article->quantity}");
        $this->line("Unit Price: {$article->unit_price} €");
        $this->line("Aktueller Total Price: {$article->total_price} €");
        $this->newLine();

        // Zeige die Berechnung
        $targetQuantity = 99226.825;
        $expectedTotalPrice = $targetQuantity * $article->unit_price;
        
        $this->line("🎯 Gewünschte Quantity: {$targetQuantity}");
        $this->line("💰 Erwarteter Total Price: {$expectedTotalPrice} €");
        $this->newLine();

        // Sichere die alten Werte
        $oldQuantity = $article->quantity;
        $oldTotalPrice = $article->total_price;
        
        $this->info("🔧 Führe Korrektur durch...");
        
        // Aktualisiere die Quantity
        $article->updateQuietly(['quantity' => $targetQuantity]);
        
        // Reload um die aktuellen Werte zu sehen
        $article->refresh();
        
        $this->info("✅ Artikel wurde erfolgreich korrigiert!");
        $this->newLine();
        
        $this->line("📊 Vorher:");
        $this->line("  Quantity: {$oldQuantity}");
        $this->line("  Total Price: {$oldTotalPrice} €");
        $this->newLine();
        
        $this->line("📊 Nachher:");
        $this->line("  Quantity: {$article->quantity}");
        $this->line("  Unit Price: {$article->unit_price} €");
        $this->line("  Total Price: {$article->total_price} €");
        $this->newLine();
        
        // Überprüfe die Berechnung
        $calculatedTotal = $article->quantity * $article->unit_price;
        $this->line("🔍 Berechneter Total Price: {$calculatedTotal} €");
        
        if (abs($article->total_price - $calculatedTotal) < 0.01) {
            $this->info("✅ Total Price wurde korrekt berechnet!");
        } else {
            $this->warn("⚠️ Total Price stimmt nicht mit der Berechnung überein.");
            $this->line("   Erwartet: {$calculatedTotal} €");
            $this->line("   Tatsächlich: {$article->total_price} €");
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
        $this->line("Die Quantity für 'Einspeisung Marktwert - 2025/06' wurde von {$oldQuantity} auf {$article->quantity} geändert.");

        return 0;
    }
}
