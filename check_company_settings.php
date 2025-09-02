<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CompanySetting;

$settings = CompanySetting::current();

echo "Current Company Settings for Invoice Numbers:\n";
echo "=============================================\n";
echo "Invoice Number Prefix: '" . ($settings->invoice_number_prefix ?: '') . "'\n";
echo "Include Year: " . ($settings->invoice_number_include_year ? 'YES' : 'NO') . "\n";

// Test the company method for generating invoice numbers
echo "\nTesting Company Settings generateInvoiceNumber method:\n";
echo "Next invoice number would be: " . $settings->generateInvoiceNumber(39, 2025) . "\n";
