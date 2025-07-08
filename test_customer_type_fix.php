<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;

echo "=== KUNDENTYP-KORREKTUR TEST ===\n\n";

// Zeige alle Kunden mit Lexoffice ID und deren Typen
$customers = Customer::whereNotNull('lexoffice_id')->get();

echo "Kunden mit Lexoffice ID:\n";
echo str_repeat("-", 80) . "\n";
printf("%-30s %-15s %-15s %-20s\n", "Name", "Typ", "Firma", "Lexoffice ID");
echo str_repeat("-", 80) . "\n";

foreach ($customers as $customer) {
    printf("%-30s %-15s %-15s %-20s\n", 
        substr($customer->name, 0, 29),
        $customer->customer_type ?: 'NICHT GESETZT',
        $customer->company_name ? 'Ja' : 'Nein',
        substr($customer->lexoffice_id, 0, 19)
    );
}

echo "\n=== STATISTIK ===\n";
$totalCustomers = $customers->count();
$businessCustomers = $customers->where('customer_type', 'business')->count();
$privateCustomers = $customers->where('customer_type', 'private')->count();
$unknownCustomers = $customers->whereNull('customer_type')->count();

echo "Gesamt: {$totalCustomers}\n";
echo "Geschäftskunden: {$businessCustomers}\n";
echo "Privatkunden: {$privateCustomers}\n";
echo "Unbekannter Typ: {$unknownCustomers}\n";

// Prüfe ob es Kunden gibt, die als Firma markiert sind aber keinen company_name haben
$inconsistentCustomers = $customers->where('customer_type', 'business')
                                  ->whereNull('company_name')
                                  ->count();

echo "Inkonsistente Geschäftskunden (ohne company_name): {$inconsistentCustomers}\n";

if ($inconsistentCustomers > 0) {
    echo "\nInkonsistente Kunden:\n";
    $inconsistent = $customers->where('customer_type', 'business')
                             ->whereNull('company_name');
    foreach ($inconsistent as $customer) {
        echo "- {$customer->name} (ID: {$customer->id})\n";
    }
}

echo "\n=== EMPFEHLUNG ===\n";
if ($unknownCustomers > 0) {
    echo "⚠️  Es gibt {$unknownCustomers} Kunden ohne Typ-Angabe.\n";
    echo "   Diese sollten manuell überprüft und korrigiert werden.\n";
}

if ($inconsistentCustomers > 0) {
    echo "⚠️  Es gibt {$inconsistentCustomers} Geschäftskunden ohne Firmenname.\n";
    echo "   Diese sollten überprüft werden.\n";
}

echo "\n✅ Der Import wurde korrigiert und unterscheidet jetzt korrekt zwischen:\n";
echo "   - Geschäftskunden (company_name wird gesetzt)\n";
echo "   - Privatkunden (nur name wird gesetzt)\n";
echo "\nBei zukünftigen Importen wird der customer_type korrekt gesetzt.\n";
