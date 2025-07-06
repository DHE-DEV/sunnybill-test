<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\DocumentPathSetting;
use Illuminate\Foundation\Application;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Hinzufügen von Customer DocumentPathSettings ===\n\n";

try {
    $categories = [
        ['category' => 'invoice', 'path_template' => 'kunden/{customer_number}/rechnungen', 'description' => 'Kunden-Rechnungen'],
        ['category' => 'offer', 'path_template' => 'kunden/{customer_number}/angebote', 'description' => 'Kunden-Angebote'],
        ['category' => 'correspondence', 'path_template' => 'kunden/{customer_number}/korrespondenz', 'description' => 'Kunden-Korrespondenz'],
        ['category' => 'technical', 'path_template' => 'kunden/{customer_number}/technische-unterlagen', 'description' => 'Technische Unterlagen'],
        ['category' => 'legal', 'path_template' => 'kunden/{customer_number}/rechtsdokumente', 'description' => 'Rechtsdokumente'],
        ['category' => 'other', 'path_template' => 'kunden/{customer_number}/sonstiges', 'description' => 'Sonstige Dokumente']
    ];

    echo "Erstelle DocumentPathSettings für Customer-Kategorien:\n\n";

    foreach ($categories as $cat) {
        // Prüfe, ob bereits vorhanden
        $existing = DocumentPathSetting::where('documentable_type', 'App\Models\Customer')
            ->where('category', $cat['category'])
            ->first();

        if ($existing) {
            echo "   ⚠️  Kategorie '{$cat['category']}' bereits vorhanden - überspringe\n";
            continue;
        }

        // Erstelle neue DocumentPathSetting
        $setting = DocumentPathSetting::create([
            'documentable_type' => 'App\Models\Customer',
            'category' => $cat['category'],
            'path_template' => $cat['path_template'],
            'description' => $cat['description']
        ]);

        echo "   ✅ Erstellt: {$cat['category']} -> {$cat['path_template']}\n";
    }

    echo "\n=== Übersicht aller Customer DocumentPathSettings ===\n";
    $allCustomerSettings = DocumentPathSetting::where('documentable_type', 'App\Models\Customer')
        ->orderBy('category')
        ->get();

    foreach ($allCustomerSettings as $setting) {
        $category = $setting->category ?? 'NULL';
        echo "   - Kategorie: {$category} -> {$setting->path_template}\n";
    }

    echo "\n✅ Customer DocumentPathSettings erfolgreich hinzugefügt!\n";

} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}