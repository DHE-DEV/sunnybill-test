<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SolarPlantBilling;

echo "Checking existing invoice numbers:\n";
echo "=================================\n";

// Get all invoice numbers (including deleted)
$billings = SolarPlantBilling::withTrashed()
    ->select('invoice_number', 'deleted_at')
    ->orderBy('invoice_number')
    ->get();

if ($billings->isEmpty()) {
    echo "No invoice numbers found.\n";
} else {
    echo "Found " . $billings->count() . " invoice numbers:\n";
    foreach ($billings->take(20) as $billing) {
        $status = $billing->deleted_at ? '[DELETED]' : '[ACTIVE]';
        echo $status . ' ' . $billing->invoice_number . "\n";
    }
    if ($billings->count() > 20) {
        echo "... (" . ($billings->count() - 20) . " more)\n";
    }
    
    // Check for duplicates
    $numbers = $billings->pluck('invoice_number');
    $duplicates = $numbers->duplicates();
    if ($duplicates->isNotEmpty()) {
        echo "\nDUPLICATES FOUND:\n";
        foreach ($duplicates as $duplicate) {
            echo "- " . $duplicate . "\n";
        }
    }
    
    // Check highest number using proper sorting
    $numericNumbers = $billings->filter(function($billing) {
        return preg_match('/^[0-9]+$/', $billing->invoice_number);
    });
    
    if ($numericNumbers->isNotEmpty()) {
        $highest = $numericNumbers->sortByDesc(function($billing) {
            return intval($billing->invoice_number);
        })->first();
        echo "\nHighest numeric invoice number: " . $highest->invoice_number . "\n";
    }
}

// Test the current generateInvoiceNumber method
echo "\nTesting generateInvoiceNumber method:\n";
try {
    $nextNumber = SolarPlantBilling::generateInvoiceNumber();
    echo "Next number would be: " . $nextNumber . "\n";
} catch (Exception $e) {
    echo "Error generating number: " . $e->getMessage() . "\n";
}
