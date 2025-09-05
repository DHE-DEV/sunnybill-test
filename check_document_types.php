<?php

require_once 'vendor/autoload.php';

use App\Models\DocumentType;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Available Document Types:\n";
echo "=========================\n";

$documentTypes = DocumentType::all();

if ($documentTypes->isEmpty()) {
    echo "No document types found in database.\n";
} else {
    foreach ($documentTypes as $type) {
        echo sprintf("Key: %-20s | Name: %s\n", $type->key ?? 'N/A', $type->name ?? 'N/A');
    }
}

echo "\nTotal: " . $documentTypes->count() . " document types\n";
