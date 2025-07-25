<?php

require_once 'bootstrap/app.php';

use App\Models\SupplierContractBillingArticle;

echo "Neu-Berechnung der Gesamtpreise für höhere Präzision...\n\n";

$articles = SupplierContractBillingArticle::all();
$updated = 0;

foreach ($articles as $article) {
    $oldTotal = $article->total_price;
    $newTotal = $article->quantity * $article->unit_price;
    
    if (abs($oldTotal - $newTotal) > 0.0001) {
        echo "Artikel ID {$article->id}:\n";
        echo "  Menge: {$article->quantity}\n";
        echo "  Einzelpreis: {$article->unit_price}\n";
        echo "  Alt: {$oldTotal} € (gerundet)\n";
        echo "  Neu: {$newTotal} € (präzise)\n";
        echo "  Differenz: " . ($newTotal - $oldTotal) . " €\n\n";
        
        // Direkte Datenbankaktualisierung ohne Event-Trigger
        $article->updateQuietly(['total_price' => $newTotal]);
        $updated++;
    }
}

echo "Fertig! {$updated} Artikel-Gesamtpreise wurden neu berechnet.\n";
