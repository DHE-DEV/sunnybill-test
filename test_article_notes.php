<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Article;
use App\Models\SupplierContractBilling;
use App\Models\SupplierContractBillingArticle;

// Test 1: Check if articles have notes
echo "=== Checking Articles with Notes ===\n";
$articlesWithNotes = Article::whereNotNull('notes')
    ->where('notes', '!=', '')
    ->get(['id', 'name', 'notes']);

if ($articlesWithNotes->isEmpty()) {
    echo "No articles with notes found. Let's add some test data.\n\n";
    
    // Add notes to some articles
    $articles = Article::limit(5)->get();
    foreach ($articles as $index => $article) {
        $article->notes = "Dies ist eine ausführliche Beschreibung für Artikel {$article->name}. Diese Beschreibung enthält detaillierte Informationen über den Artikel, seine Verwendung und besondere Eigenschaften.";
        $article->save();
        echo "Added notes to article: {$article->name}\n";
    }
    
    $articlesWithNotes = Article::whereNotNull('notes')
        ->where('notes', '!=', '')
        ->get(['id', 'name', 'notes']);
}

echo "\nArticles with notes:\n";
foreach ($articlesWithNotes as $article) {
    echo "ID: {$article->id}, Name: {$article->name}\n";
    echo "Notes: " . substr($article->notes, 0, 100) . "...\n\n";
}

// Test 2: Check a specific billing article
echo "=== Checking Specific Billing Article ===\n";
$billingId = '0198420e-ba50-713e-819b-dda6cf0205ef';
$billing = SupplierContractBilling::find($billingId);

if ($billing) {
    $billingArticles = $billing->articles()->with('article')->get();
    
    if ($billingArticles->isEmpty()) {
        echo "No articles found for this billing.\n";
    } else {
        echo "Articles in billing {$billingId}:\n";
        foreach ($billingArticles as $billingArticle) {
            echo "\n- Article: " . ($billingArticle->article ? $billingArticle->article->name : 'N/A') . "\n";
            echo "  Description: {$billingArticle->description}\n";
            echo "  Article Notes: " . ($billingArticle->article && $billingArticle->article->notes 
                ? substr($billingArticle->article->notes, 0, 100) . "..." 
                : 'No notes') . "\n";
        }
    }
} else {
    echo "Billing with ID {$billingId} not found.\n";
}

echo "\n=== Test Complete ===\n";
