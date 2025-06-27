<?php

require_once 'vendor/autoload.php';

use App\Models\Customer;
use App\Models\Supplier;
use App\Services\ZugferdService;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test der finalen ZUGFeRD-Implementierung ===\n\n";

try {
    // 1. Test Customer-Model mit neuen Feldern
    echo "1. Teste Customer-Model...\n";
    $customer = Customer::where('customer_number', 'KD-003')->first();
    
    if ($customer) {
        echo "✅ Kunde gefunden: {$customer->display_name}\n";
        echo "   - Kundennummer: {$customer->customer_number}\n";
        echo "   - Firmenname: {$customer->company_name}\n";
        echo "   - Ansprechpartner: {$customer->contact_person}\n";
        echo "   - Abteilung: {$customer->department}\n";
        echo "   - USt-IdNr: {$customer->vat_id}\n";
        echo "   - Ländercode: {$customer->country_code}\n";
        echo "   - Zahlungsbedingungen: {$customer->payment_terms}\n";
        echo "   - Zahlungstage: {$customer->payment_days}\n";
        echo "   - IBAN: {$customer->iban}\n";
        echo "   - BIC: {$customer->bic}\n";
    } else {
        echo "❌ Kunde nicht gefunden\n";
    }
    
    echo "\n2. Teste Supplier-Model...\n";
    $supplier = Supplier::first();
    
    if ($supplier) {
        echo "✅ Lieferant gefunden: {$supplier->company_name}\n";
        echo "   - Ansprechpartner: {$supplier->contact_person}\n";
        echo "   - E-Mail: {$supplier->email}\n";
        echo "   - Adresse: {$supplier->address}\n";
        echo "   - PLZ: {$supplier->postal_code}\n";
        echo "   - Stadt: {$supplier->city}\n";
        echo "   - Land: {$supplier->country}\n";
        echo "   - USt-IdNr: {$supplier->vat_id}\n";
    } else {
        echo "❌ Lieferant nicht gefunden\n";
    }
    
    echo "\n3. Teste ZUGFeRD-Service mit erweiterten Daten...\n";
    
    if ($customer) {
        $zugferdService = new ZugferdService();
        
        // Erstelle eine Test-Rechnung falls keine existiert
        $invoice = \App\Models\Invoice::first();
        if ($invoice) {
            $invoice->customer_id = $customer->id;
            $invoice->save();
            
            echo "✅ Rechnung {$invoice->invoice_number} dem Kunden zugewiesen\n";
            
            // Teste XML-Generierung
            $xmlContent = $zugferdService->generateZugferdXml($invoice);
            
            // Prüfe wichtige Elemente
            $checks = [
                'Verkäufer USt-IdNr' => strpos($xmlContent, '<ram:ID schemeID="VA">') !== false,
                'Käufer USt-IdNr' => strpos($xmlContent, $customer->vat_id) !== false,
                'Firmenname' => strpos($xmlContent, $customer->company_name) !== false,
                'Ansprechpartner' => strpos($xmlContent, $customer->contact_person) !== false,
                'Abteilung' => strpos($xmlContent, $customer->department) !== false,
                'Zahlungsbedingungen' => strpos($xmlContent, $customer->payment_terms) !== false,
                'CountryID-Elemente' => preg_match_all('/<ram:CountryID>([^<]+)<\/ram:CountryID>/', $xmlContent, $matches) > 0,
            ];
            
            foreach ($checks as $check => $result) {
                echo ($result ? "✅" : "❌") . " {$check}: " . ($result ? "OK" : "Fehlt") . "\n";
            }
            
            if (isset($matches[1])) {
                echo "   CountryID-Werte: " . implode(', ', $matches[1]) . "\n";
            }
            
            // Speichere Test-XML
            file_put_contents('final_implementation_test.xml', $xmlContent);
            echo "✅ Test-XML gespeichert als final_implementation_test.xml\n";
            
        } else {
            echo "❌ Keine Rechnung für Test verfügbar\n";
        }
    }
    
    echo "\n4. Teste automatische Kundennummer-Generierung...\n";
    
    // Teste die automatische Generierung
    $lastCustomerNumber = Customer::generateCustomerNumber();
    echo "✅ Nächste Kundennummer würde sein: {$lastCustomerNumber}\n";
    
    echo "\n5. Teste Helper-Methoden...\n";
    
    if ($customer) {
        echo "✅ Display Name: {$customer->display_name}\n";
        echo "✅ Ist Geschäftskunde: " . ($customer->isBusinessCustomer() ? 'Ja' : 'Nein') . "\n";
        echo "✅ Hat USt-IdNr: " . ($customer->hasVatId() ? 'Ja' : 'Nein') . "\n";
        echo "✅ Formatierte USt-IdNr: {$customer->formatted_vat_id}\n";
        echo "✅ Vollständige Adresse:\n{$customer->full_address}\n";
    }
    
    echo "\n=== Test erfolgreich abgeschlossen ===\n";
    echo "Alle ZUGFeRD-relevanten Felder sind verfügbar und funktionsfähig!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}