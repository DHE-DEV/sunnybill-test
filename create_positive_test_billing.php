<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Finde eine existierende Abrechnung und dupliziere sie mit positivem Betrag
    $existingBilling = DB::table('solar_plant_billings')
        ->where('net_amount', '<', 0)
        ->orderBy('created_at', 'desc')
        ->first();
    
    if (!$existingBilling) {
        echo "❌ Keine existierende Abrechnung gefunden\n";
        exit(1);
    }
    
    echo "📋 Basis-Abrechnung gefunden:\n";
    echo "ID: {$existingBilling->id}\n";
    echo "Betrag: €" . number_format($existingBilling->net_amount, 2) . "\n";
    echo "Kunde: {$existingBilling->customer_id}\n\n";
    
    // Neue positive Test-Abrechnung erstellen mit anderem Monat
    $testMonth = $existingBilling->billing_month == 12 ? 1 : $existingBilling->billing_month + 1;
    $testYear = $existingBilling->billing_month == 12 ? $existingBilling->billing_year + 1 : $existingBilling->billing_year;
    
    $newBillingId = DB::table('solar_plant_billings')->insertGetId([
        'id' => \Illuminate\Support\Str::uuid(),
        'customer_id' => $existingBilling->customer_id,
        'solar_plant_id' => $existingBilling->solar_plant_id,
        'billing_year' => $testYear,
        'billing_month' => $testMonth,
        'invoice_number' => 'TEST-QR-' . date('Ymd') . '-001',
        'participation_percentage' => $existingBilling->participation_percentage,
        'produced_energy_kwh' => $existingBilling->produced_energy_kwh,
        'total_costs' => 2500.00, // Positive Kosten
        'total_credits' => 0.00,   // Keine Gutschriften
        'total_vat_amount' => 475.00, // 19% MwSt
        'net_amount' => 2975.00,   // Positive Rechnung
        'status' => 'finalized',
        'finalized_at' => now(),
        'created_by' => $existingBilling->created_by,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "✅ Test-Abrechnung mit positivem Betrag erstellt:\n";
    echo "ID: {$newBillingId}\n";
    echo "Rechnungsnummer: TEST-QR-" . date('Ymd') . "-001\n";
    echo "Betrag: €2.975,00 (Rechnung)\n";
    echo "Status: Finalisiert\n\n";
    
    // Kundendaten prüfen
    $customer = DB::table('customers')->where('id', $existingBilling->customer_id)->first();
    echo "🏦 Kundendaten für QR-Code:\n";
    echo "Name: {$customer->name}\n";
    echo "Kontoinhaber: {$customer->account_holder}\n";
    echo "IBAN: {$customer->iban}\n";
    echo "BIC: {$customer->bic}\n\n";
    
    echo "🎯 QR-Code Test:\n";
    echo "1. Gehen Sie zu Solar Plant Billings\n";
    echo "2. Öffnen Sie die Abrechnung mit Nummer: TEST-QR-" . date('Ymd') . "-001\n";
    echo "3. In der 'Zahlungsinformationen' Section sollte jetzt der QR-Code angezeigt werden\n";
    echo "4. Die Debug-Info sollte 'Can Generate QR: JA' zeigen\n\n";
    
    // Erstelle auch eine zweite Test-Abrechnung mit anderem Kunden
    $anotherCustomer = DB::table('customers')
        ->where('id', '!=', $existingBilling->customer_id)
        ->whereNotNull('iban')
        ->whereNotNull('account_holder')
        ->where('account_holder', '!=', '')
        ->first();
        
    if ($anotherCustomer) {
        $secondBillingId = DB::table('solar_plant_billings')->insertGetId([
            'id' => \Illuminate\Support\Str::uuid(),
            'customer_id' => $anotherCustomer->id,
            'solar_plant_id' => $existingBilling->solar_plant_id,
            'billing_year' => $existingBilling->billing_year,
            'billing_month' => $existingBilling->billing_month,
            'invoice_number' => 'TEST-QR-' . date('Ymd') . '-002',
            'participation_percentage' => 50.00,
            'produced_energy_kwh' => 1000.0,
            'total_costs' => 1200.00,
            'total_credits' => 0.00,
            'total_vat_amount' => 228.00,
            'net_amount' => 1428.00,
            'status' => 'finalized',
            'finalized_at' => now(),
            'created_by' => $existingBilling->created_by,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "✅ Zweite Test-Abrechnung erstellt:\n";
        echo "Kunde: {$anotherCustomer->name}\n";
        echo "Rechnungsnummer: TEST-QR-" . date('Ymd') . "-002\n";
        echo "Betrag: €1.428,00\n\n";
    }
    
    echo "🧪 Sie haben jetzt Test-Abrechnungen mit positiven Beträgen zum Testen der QR-Codes!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
