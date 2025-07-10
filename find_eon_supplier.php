<?php

require_once 'vendor/autoload.php';

use App\Models\Supplier;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Suche nach EON-Lieferanten...\n";

$supplier = Supplier::where('company_name', 'like', '%E.ON%')
    ->orWhere('email', 'like', '%@eon.de')
    ->first();

if ($supplier) {
    echo "Gefunden: ID {$supplier->id} - {$supplier->company_name}\n";
    echo "Email: {$supplier->email}\n";
} else {
    echo "Kein EON-Lieferant gefunden.\n";
    
    // Erstelle einen EON-Lieferanten
    $supplier = Supplier::create([
        'name' => 'E.ON Energie Deutschland GmbH',
        'company_name' => 'E.ON Energie Deutschland GmbH',
        'email' => 'max.mustermann@eon.de',
        'phone' => '+49 201 12345',
        'website' => 'www.eon.de',
        'street' => 'E.ON-Platz 1',
        'postal_code' => '45141',
        'city' => 'Essen',
        'country' => 'Deutschland',
        'tax_number' => 'DE123456789',
        'is_active' => true,
    ]);
    
    echo "EON-Lieferant erstellt: ID {$supplier->id} - {$supplier->company_name}\n";
}

echo "Supplier ID: {$supplier->id}\n";
