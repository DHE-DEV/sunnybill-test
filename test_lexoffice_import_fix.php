<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\LexofficeService;
use App\Models\Customer;

echo "=== LEXOFFICE IMPORT TEST (NACH FIX) ===\n\n";

$service = new LexofficeService();

// Teste zuerst die Verbindung
echo "Teste Lexoffice-Verbindung...\n";
$connectionTest = $service->testConnection();
if (!$connectionTest['success']) {
    echo "✗ Verbindung fehlgeschlagen: {$connectionTest['error']}\n";
    exit;
}

echo "✓ Verbindung erfolgreich\n";
echo "Firma: {$connectionTest['company']}\n";
echo "Email: {$connectionTest['email']}\n\n";

// Anzahl Kunden vor Import
$customersBefore = Customer::count();
echo "Kunden vor Import: {$customersBefore}\n\n";

// Import starten
echo "Starte Kunden-Import...\n";
$result = $service->importCustomers();

if ($result['success']) {
    echo "✓ Import erfolgreich!\n";
    echo "Importierte Kunden: {$result['imported']}\n";
    
    if (!empty($result['errors'])) {
        echo "Fehler beim Import:\n";
        foreach ($result['errors'] as $error) {
            echo "- {$error}\n";
        }
    }
    
    // Anzahl Kunden nach Import
    $customersAfter = Customer::count();
    echo "\nKunden nach Import: {$customersAfter}\n";
    echo "Neue Kunden: " . ($customersAfter - $customersBefore) . "\n";
    
    // Zeige die letzten importierten Kunden
    echo "\n=== LETZTE IMPORTIERTE KUNDEN ===\n";
    $recentCustomers = Customer::whereNotNull('lexoffice_id')
                              ->orderBy('created_at', 'desc')
                              ->take(5)
                              ->get();
    
    foreach ($recentCustomers as $customer) {
        echo "- {$customer->name}";
        echo " (Kundennummer: " . ($customer->customer_number ?: 'KEINE') . ")";
        echo " (Lexoffice ID: {$customer->lexoffice_id})";
        echo " (Email: " . ($customer->email ?: 'KEINE') . ")";
        echo "\n";
    }
    
} else {
    echo "✗ Import fehlgeschlagen!\n";
    echo "Fehler: {$result['error']}\n";
}

echo "\n=== ZUSAMMENFASSUNG DER FIXES ===\n";
echo "1. ✓ Customer::generateCustomerNumber() - Überspringe Kunden ohne customer_number\n";
echo "2. ✓ CompanySetting::extractCustomerNumber() - Akzeptiert null-Werte\n";
echo "3. ✓ LexofficeService::createOrUpdateCustomer() - Bessere Namensbehandlung\n";
echo "4. ✓ Automatische Kundennummer-Generierung beim Import\n";
echo "5. ✓ Standard-Land 'DE' setzen (DB-Constraint)\n";
