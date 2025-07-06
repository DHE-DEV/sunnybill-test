<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DocumentPathSettings Check ===\n\n";

try {
    // Prüfe Tabellen-Schema
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('document_path_settings');
    echo "Tabellen-Spalten: " . implode(', ', $columns) . "\n\n";
    
    // Hole alle Einträge
    $settings = \Illuminate\Support\Facades\DB::table('document_path_settings')->get();
    
    echo "Anzahl Einträge: " . $settings->count() . "\n\n";
    
    foreach ($settings as $setting) {
        echo "ID: " . $setting->id . "\n";
        echo "Entity Type: " . ($setting->entity_type ?? 'N/A') . "\n";
        echo "Category: " . ($setting->category ?? 'N/A') . "\n";
        echo "Path Template: " . ($setting->path_template ?? 'N/A') . "\n";
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
}
