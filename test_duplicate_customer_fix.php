<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;

echo "=== DUPLIKAT-KUNDEN ANALYSE ===\n\n";

// Finde Kunden mit gleicher Lexoffice ID
$duplicates = Customer::whereNotNull('lexoffice_id')
    ->get()
    ->groupBy('lexoffice_id')
    ->filter(function ($group) {
        return $group->count() > 1;
    });

if ($duplicates->isEmpty()) {
    echo "✅ Keine Duplikate gefunden.\n";
} else {
    echo "⚠️  Duplikate gefunden:\n\n";
    
    foreach ($duplicates as $lexofficeId => $customers) {
        echo "Lexoffice ID: {$lexofficeId}\n";
        echo "Anzahl Duplikate: " . $customers->count() . "\n";
        
        foreach ($customers as $customer) {
            echo "- ID: {$customer->id}, Name: {$customer->name}, Erstellt: {$customer->created_at}\n";
        }
        echo "\n";
    }
}

// Finde Kunden mit ähnlichen Namen (potentielle Duplikate ohne Lexoffice ID)
echo "=== POTENTIELLE DUPLIKATE (ÄHNLICHE NAMEN) ===\n";
$allCustomers = Customer::all();
$potentialDuplicates = [];

foreach ($allCustomers as $customer1) {
    foreach ($allCustomers as $customer2) {
        if ($customer1->id !== $customer2->id && 
            strtolower(trim($customer1->name)) === strtolower(trim($customer2->name))) {
            $key = min($customer1->id, $customer2->id) . '-' . max($customer1->id, $customer2->id);
            if (!isset($potentialDuplicates[$key])) {
                $potentialDuplicates[$key] = [$customer1, $customer2];
            }
        }
    }
}

if (empty($potentialDuplicates)) {
    echo "✅ Keine potentiellen Duplikate gefunden.\n";
} else {
    echo "⚠️  Potentielle Duplikate gefunden:\n\n";
    
    foreach ($potentialDuplicates as $pair) {
        $customer1 = $pair[0];
        $customer2 = $pair[1];
        
        echo "Name: {$customer1->name}\n";
        echo "- ID: {$customer1->id}, Lexoffice: " . ($customer1->lexoffice_id ?: 'KEINE') . ", Erstellt: {$customer1->created_at}\n";
        echo "- ID: {$customer2->id}, Lexoffice: " . ($customer2->lexoffice_id ?: 'KEINE') . ", Erstellt: {$customer2->created_at}\n";
        echo "\n";
    }
}

echo "\n=== EMPFEHLUNG ===\n";
if (!$duplicates->isEmpty()) {
    echo "1. Duplikate mit gleicher Lexoffice ID sollten zusammengeführt werden\n";
    echo "2. Der älteste Kunde sollte behalten werden\n";
    echo "3. Neuere Duplikate sollten gelöscht werden (nach Datenübertragung)\n";
}

if (!empty($potentialDuplicates)) {
    echo "4. Potentielle Duplikate sollten manuell überprüft werden\n";
}

echo "\n=== NÄCHSTE SCHRITTE ===\n";
echo "1. LexofficeService::createOrUpdateCustomer() Methode überprüfen\n";
echo "2. Sicherstellen, dass updateOrCreate() korrekt verwendet wird\n";
echo "3. Test-Import durchführen um weitere Duplikate zu verhindern\n";
