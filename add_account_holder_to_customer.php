<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $customerId = '01981a09-0c9d-7177-bc57-eb131669bcd2';
    
    $customer = DB::table('customers')->where('id', $customerId)->first();
    
    if (!$customer) {
        echo "❌ Kunde nicht gefunden\n";
        exit(1);
    }
    
    echo "📋 Aktueller Kunde:\n";
    echo "Name: {$customer->name}\n";
    echo "IBAN: {$customer->iban}\n";
    echo "BIC: {$customer->bic}\n";
    echo "Account Holder: " . ($customer->account_holder ?: 'LEER') . "\n\n";
    
    // Kontoinhaber auf Kundennamen setzen, falls leer
    if (!$customer->account_holder) {
        $accountHolder = $customer->customer_type === 'business' && $customer->company_name
            ? $customer->company_name
            : $customer->name;
            
        DB::table('customers')
            ->where('id', $customerId)
            ->update(['account_holder' => $accountHolder]);
            
        echo "✅ Kontoinhaber gesetzt auf: {$accountHolder}\n";
    } else {
        echo "ℹ️ Kontoinhaber bereits gesetzt: {$customer->account_holder}\n";
    }
    
    // Alle Kunden ohne account_holder aktualisieren
    echo "\n📊 Aktualisiere alle Kunden ohne Kontoinhaber...\n";
    
    $customersWithoutAccountHolder = DB::table('customers')
        ->whereNull('account_holder')
        ->orWhere('account_holder', '')
        ->get();
        
    $updated = 0;
    foreach ($customersWithoutAccountHolder as $cust) {
        $holder = $cust->customer_type === 'business' && $cust->company_name
            ? $cust->company_name
            : $cust->name;
            
        DB::table('customers')
            ->where('id', $cust->id)
            ->update(['account_holder' => $holder]);
            
        $updated++;
    }
    
    echo "✅ {$updated} Kunden aktualisiert\n";
    
    // Zeige positive Abrechnungen für Test
    echo "\n🔍 Suche nach positiven Abrechnungen für QR-Code Test...\n";
    
    $positiveBillings = DB::table('solar_plant_billings')
        ->where('net_amount', '>', 0)
        ->orderBy('net_amount', 'desc')
        ->limit(5)
        ->get(['id', 'invoice_number', 'net_amount', 'customer_id']);
        
    if ($positiveBillings->count() > 0) {
        echo "💰 Positive Abrechnungen gefunden:\n";
        foreach ($positiveBillings as $billing) {
            echo "- ID: {$billing->id}, Rechnung: {$billing->invoice_number}, Betrag: €" . number_format($billing->net_amount, 2) . "\n";
        }
        echo "\nTesten Sie den QR-Code mit einer dieser positiven Abrechnungen!\n";
    } else {
        echo "⚠️ Keine positiven Abrechnungen gefunden. QR-Codes werden nur für Rechnungen (positive Beträge) angezeigt.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}
