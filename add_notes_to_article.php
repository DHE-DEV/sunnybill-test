<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Article;

// Find the article "Negativer Börsenpreis - 2025/04" and add notes
$article = Article::where('name', 'LIKE', '%Negativer Börsenpreis%')->first();

if ($article) {
    $article->notes = "Detaillierte Erläuterung zum negativen Börsenpreis:\n\n" .
                      "Wenn die Börsenpreise negativ sind, entstehen zusätzliche Kosten. " .
                      "Diese werden nach dem vereinbarten Schlüssel auf die betroffenen Anlagen umgelegt. " .
                      "Die genaue Berechnung erfolgt auf Basis der tatsächlichen Einspeisemengen während der negativen Preisphasen.";
    $article->save();
    echo "Added notes to article: {$article->name}\n";
} else {
    echo "Article 'Negativer Börsenpreis' not found.\n";
}

echo "Done!\n";
