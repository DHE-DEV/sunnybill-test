<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\PlantParticipation;

echo "=== Geschäftskunden mit leerem company_name ===\n";

$businessCustomers = Customer::where('customer_type', 'business')->get();

foreach ($businessCustomers as $customer) {
    $companyNameEmpty = empty($customer->company_name);
    $nameEmpty = empty($customer->name);
    
    echo "Kunde ID {$customer->id}:\n";
    echo "  - customer_type: '{$customer->customer_type}'\n";
    echo "  - name: '" . ($customer->name ?? 'NULL') . "' (leer: " . ($nameEmpty ? 'JA' : 'NEIN') . ")\n";
    echo "  - company_name: '" . ($customer->company_name ?? 'NULL') . "' (leer: " . ($companyNameEmpty ? 'JA' : 'NEIN') . ")\n";
    
    // Prüfe Beteiligungen
    $participations = PlantParticipation::where('customer_id', $customer->id)->count();
    echo "  - Beteiligungen: {$participations}\n";
    
    // Test der neuen Logik
    $displayName = $customer->customer_type === 'business'
        ? ($customer->company_name ?: $customer->name)
        : $customer->name;
    echo "  - Neue Anzeige: '" . ($displayName ?? 'LEER') . "'\n\n";
}

echo "\n=== Zusammenfassung ===\n";
$totalBusiness = $businessCustomers->count();
$emptyCompanyName = $businessCustomers->filter(fn($c) => empty($c->company_name))->count();
$emptyBothNames = $businessCustomers->filter(fn($c) => empty($c->company_name) && empty($c->name))->count();

echo "Geschäftskunden gesamt: {$totalBusiness}\n";
echo "Mit leerem company_name: {$emptyCompanyName}\n";
echo "Mit beiden Namen leer: {$emptyBothNames}\n";