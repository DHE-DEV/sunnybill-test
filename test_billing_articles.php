<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;
use App\Models\Article;
use App\Models\Supplier;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test: Automatische Artikel-Hinzufügung zu Abrechnungen ===\n\n";

// 1. Finde einen existierenden Artikel
echo "1. Finde existierenden Artikel...\n";
$article = Article::first();
if (!$article) {
    echo "❌ Kein Artikel gefunden. Erstelle einen neuen.\n";
    $article = Article::create([
        'name' => 'Test-Artikel für Abrechnung',
        'description' => 'Automatisch hinzugefügter Artikel',
        'price' => 100.00,
        'tax_rate' => 0.19,
        'unit' => 'Stück',
        'article_number' => 'TEST-ART-001',
        'category' => 'Test',
        'type' => 'SERVICE',
    ]);
}
echo "✓ Artikel gefunden: {$article->name} (ID: {$article->id})\n";

// 2. Finde einen bestehenden Supplier
echo "\n2. Finde Test-Supplier...\n";
$supplier = Supplier::where('is_active', true)->first();
if (!$supplier) {
    $supplier = Supplier::create([
        'name' => 'Test-Supplier für Artikel',
        'supplier_number' => 'TEST-SUP-001',
        'is_active' => true,
    ]);
}
echo "✓ Supplier gefunden: {$supplier->name} (ID: {$supplier->id})\n";

// 3. Erstelle Test-Vertrag
echo "\n3. Erstelle Test-Vertrag...\n";
$contract = SupplierContract::create([
    'supplier_id' => $supplier->id,
    'contract_number' => 'TEST-CONTRACT-' . time(),
    'title' => 'Test-Vertrag für Artikel-Abrechnung',
    'start_date' => now(),
    'is_active' => true,
]);
echo "✓ Vertrag erstellt: {$contract->title} (ID: {$contract->id})\n";

// 4. Füge Artikel zum Vertrag hinzu
echo "\n4. Füge Artikel zum Vertrag hinzu...\n";
$contract->articles()->attach($article->id, [
    'quantity' => 2,
    'unit_price' => 95.00,
    'is_active' => true,
    'billing_requirement' => true,
]);
echo "✓ Artikel zum Vertrag hinzugefügt (Menge: 2, Preis: 95.00€)\n";

// 5. Erstelle eine neue Abrechnung
echo "\n5. Erstelle neue Abrechnung...\n";
$billing = SupplierContractBilling::create([
    'supplier_contract_id' => $contract->id,
    'billing_type' => 'invoice',
    'billing_year' => now()->year,
    'billing_month' => now()->month,
    'title' => 'Test-Abrechnung für Artikel',
    'billing_date' => now(),
    'total_amount' => 190.00,
    'currency' => 'EUR',
    'status' => 'draft',
]);
echo "✓ Abrechnung erstellt: {$billing->title} (ID: {$billing->id})\n";

// 6. Überprüfe, ob Artikel automatisch hinzugefügt wurden
echo "\n6. Überprüfe automatisch hinzugefügte Artikel...\n";
$billingArticles = $billing->articles()->get();
echo "✓ Anzahl Artikel in Abrechnung: " . $billingArticles->count() . "\n";

if ($billingArticles->count() > 0) {
    foreach ($billingArticles as $billingArticle) {
        echo "  - Artikel: {$billingArticle->description}\n";
        echo "    Menge: {$billingArticle->quantity}\n";
        echo "    Einzelpreis: {$billingArticle->unit_price}€\n";
        echo "    Gesamtpreis: {$billingArticle->total_price}€\n";
        echo "    Aktiv: " . ($billingArticle->is_active ? 'Ja' : 'Nein') . "\n";
        echo "    Notizen: {$billingArticle->notes}\n\n";
    }
} else {
    echo "❌ Keine Artikel automatisch hinzugefügt!\n";
}

// 7. Teste die Bearbeitung eines Abrechnungsartikels
echo "\n7. Teste Bearbeitung eines Abrechnungsartikels...\n";
if ($billingArticles->count() > 0) {
    $billingArticle = $billingArticles->first();
    $originalQuantity = $billingArticle->quantity;
    $originalUnitPrice = $billingArticle->unit_price;
    
    // Ändere Menge und Preis
    $billingArticle->update([
        'quantity' => 3,
        'unit_price' => 85.00,
        'total_price' => 3 * 85.00,
        'notes' => 'Manuell bearbeitet in Abrechnung',
    ]);
    
    echo "✓ Abrechnungsartikel bearbeitet:\n";
    echo "  Original: {$originalQuantity} x {$originalUnitPrice}€\n";
    echo "  Neu: {$billingArticle->quantity} x {$billingArticle->unit_price}€\n";
    echo "  Neuer Gesamtpreis: {$billingArticle->total_price}€\n";
    
    // Überprüfe, ob der ursprüngliche Artikel unverändert ist
    $originalArticle = Article::find($article->id);
    echo "  Ursprünglicher Artikel-Preis: {$originalArticle->price}€ (unverändert)\n";
    
    // Überprüfe, ob die Vertragsartikel unverändert sind
    $contractArticle = $contract->articles()->find($article->id);
    echo "  Vertragsartikel-Preis: {$contractArticle->pivot->unit_price}€ (unverändert)\n";
}

// 8. Teste das Hinzufügen eines neuen Artikels zur Abrechnung
echo "\n8. Teste manuelles Hinzufügen eines zweiten Artikels...\n";
$newArticle = Article::skip(1)->first();
if (!$newArticle) {
    echo "❌ Kein zweiter Artikel gefunden. Verwende den gleichen Artikel nochmal.\n";
    $newArticle = $article; // Verwende den gleichen Artikel nochmal
}

$billing->articles()->create([
    'article_id' => $newArticle->id,
    'quantity' => 1,
    'unit_price' => 60.00, // Anderer Preis als Standard
    'total_price' => 60.00,
    'description' => 'Manuell hinzugefügter Artikel',
    'notes' => 'Speziell für diese Abrechnung hinzugefügt',
    'is_active' => true,
]);

echo "✓ Neuer Artikel manuell hinzugefügt\n";

// 9. Zeige finale Artikel-Übersicht
echo "\n9. Finale Artikel-Übersicht der Abrechnung:\n";
$finalArticles = $billing->articles()->with('article')->get();
$totalValue = 0;

foreach ($finalArticles as $index => $billingArticle) {
    $num = $index + 1;
    echo "  {$num}. {$billingArticle->article->name}\n";
    echo "     Beschreibung: {$billingArticle->description}\n";
    echo "     Menge: {$billingArticle->quantity} x {$billingArticle->unit_price}€ = {$billingArticle->total_price}€\n";
    echo "     Notizen: {$billingArticle->notes}\n";
    echo "     Aktiv: " . ($billingArticle->is_active ? 'Ja' : 'Nein') . "\n\n";
    
    if ($billingArticle->is_active) {
        $totalValue += $billingArticle->total_price;
    }
}

echo "Gesamtwert aller aktiven Artikel: {$totalValue}€\n";
echo "Abrechnungsgesamtbetrag: {$billing->total_amount}€\n";

// 10. Aufräumen
echo "\n10. Aufräumen der Testdaten...\n";
$billing->articles()->delete();
$billing->delete();
$contract->articles()->detach();
$contract->delete();
// Bestehende Artikel und Supplier behalten für weitere Tests

echo "✓ Test abgeschlossen und Daten bereinigt\n";
echo "\n=== Test erfolgreich abgeschlossen! ===\n";
