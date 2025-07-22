<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentType;

echo "Alle DocumentTypes:\n";
echo str_repeat("=", 50) . "\n";

try {
    $documentTypes = DocumentType::all(['id', 'name', 'key']);
    
    foreach ($documentTypes as $dt) {
        echo sprintf("ID: %s | Name: %s | Key: %s\n", 
            $dt->id, 
            $dt->name, 
            $dt->key ?: 'NULL'
        );
    }
    
    echo str_repeat("=", 50) . "\n";
    echo "Gesamt: " . $documentTypes->count() . " Einträge\n";
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
}
