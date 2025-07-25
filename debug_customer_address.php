<?php

require_once 'vendor/autoload.php';

use App\Models\SolarPlantBilling;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING CUSTOMER ADDRESS DATA ===\n\n";

// ID aus der URL: admin/solar-plant-billings/019840c8-5157-7316-aa18-adc39afa124e
$billingId = '019840c8-5157-7316-aa18-adc39afa124e';

$billing = SolarPlantBilling::with(['customer', 'solarPlant'])->find($billingId);

if (!$billing) {
    echo "Billing not found!\n";
    exit(1);
}

$customer = $billing->customer;

echo "Customer Data Analysis:\n";
echo "- ID: {$customer->id}\n";
echo "- Customer Type: '{$customer->customer_type}'\n";
echo "- Name: '{$customer->name}'\n";
echo "- Company Name: '" . ($customer->company_name ?? 'NULL') . "'\n";
echo "- Address: '" . ($customer->address ?? 'NULL') . "'\n";
echo "- Postal Code: '" . ($customer->postal_code ?? 'NULL') . "'\n";
echo "- City: '" . ($customer->city ?? 'NULL') . "'\n";
echo "- Country: '" . ($customer->country ?? 'NULL') . "'\n\n";

echo "NEW PDF Address Block would display:\n";
echo "--- START ---\n";
echo "Rechnungsempfänger:\n";

if($customer->customer_type === 'business' && $customer->company_name) {
    echo $customer->company_name . "\n";
    echo $customer->name . "\n";
} else {
    echo $customer->name . "\n";
}

if ($customer->street) {
    echo $customer->street . "\n";
} else {
    echo "[NO STREET]\n";
}

if ($customer->postal_code || $customer->city) {
    echo ($customer->postal_code ?? '[NO ZIP]') . " " . ($customer->city ?? '[NO CITY]') . "\n";
} else {
    echo "[NO ZIP/CITY]\n";
}

if($customer->country && $customer->country !== 'Deutschland') {
    echo $customer->country . "\n";
}

echo "--- END ---\n\n";

// Prüfe alle verfügbaren Attribute des Customer-Models
echo "All Customer Attributes:\n";
$customerAttributes = $customer->getAttributes();
foreach ($customerAttributes as $key => $value) {
    if (in_array($key, ['address', 'street', 'postal_code', 'zip_code', 'city', 'state', 'country'])) {
        echo "- {$key}: '" . ($value ?? 'NULL') . "'\n";
    }
}
