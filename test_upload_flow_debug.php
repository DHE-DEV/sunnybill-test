<?php

require_once 'vendor/autoload.php';

use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;
use App\Models\Document;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debug: Upload-Flow-Analyse ===\n\n";

try {
    // 1. Hole den SupplierContract und eine Billing
    $contractId = '0197cf8d-f15e-7234-9dad-e6d7bc5b1e49';
    $contract = SupplierContract::with(['supplier', 'billings'])->find($contractId);
    
    if (!$contract) {
        echo "❌ SupplierContract nicht gefunden!\n";
        exit(1);
    }
    
    $billing = $contract->billings()->first();
    if (!$billing) {
        echo "❌ Keine SupplierContractBilling gefunden!\n";
        exit(1);
    }
    
    echo "1. Daten:\n";
    echo "   Contract ID: " . $contract->id . "\n";
    echo "   Contract Number: " . $contract->contract_number . "\n";
    echo "   Billing ID: " . $billing->id . "\n";
    echo "   Billing Number: " . $billing->billing_number . "\n\n";
    
    // 2. Prüfe alle Dokumente des Contracts
    echo "2. SupplierContract Dokumente:\n";
    $contractDocuments = $contract->documents()->get();
    foreach ($contractDocuments as $doc) {
        echo "   - " . $doc->filename . " (ID: " . $doc->id . ")\n";
        echo "     Pfad: " . $doc->file_path . "\n";
        echo "     Documentable: " . $doc->documentable_type . " (ID: " . $doc->documentable_id . ")\n\n";
    }
    
    // 3. Prüfe alle Dokumente der Billing
    echo "3. SupplierContractBilling Dokumente:\n";
    $billingDocuments = $billing->documents()->get();
    foreach ($billingDocuments as $doc) {
        echo "   - " . $doc->filename . " (ID: " . $doc->id . ")\n";
        echo "     Pfad: " . $doc->file_path . "\n";
        echo "     Documentable: " . $doc->documentable_type . " (ID: " . $doc->documentable_id . ")\n\n";
    }
    
    // 4. Prüfe alle Dokumente mit alten Pfaden
    echo "4. Dokumente mit alten Pfaden (supplier_contracts-documents):\n";
    $wrongPathDocs = Document::where('path', 'like', 'supplier_contracts-documents%')->get();
    foreach ($wrongPathDocs as $doc) {
        echo "   - " . $doc->filename . " (ID: " . $doc->id . ")\n";
        echo "     Pfad: " . $doc->file_path . "\n";
        echo "     Documentable: " . $doc->documentable_type . " (ID: " . $doc->documentable_id . ")\n";
        
        // Prüfe, ob es ein Billing-Dokument ist
        if ($doc->documentable_type === 'App\\Models\\SupplierContractBilling') {
            $docBilling = SupplierContractBilling::find($doc->documentable_id);
            if ($docBilling) {
                echo "     ❌ PROBLEM: Billing-Dokument im Contract-Pfad!\n";
                echo "     Billing: " . $docBilling->billing_number . "\n";
                echo "     Contract: " . $docBilling->supplierContract?->contract_number . "\n";
            }
        }
        echo "\n";
    }
    
    // 5. Analysiere die URL-Struktur
    echo "5. URL-Analyse:\n";
    echo "   Problem-URL: https://sunnybill-test.test/admin/supplier-contracts/{$contract->id}/edit?activeRelationManager=4\n";
    echo "   activeRelationManager=4 = DocumentsRelationManager\n";
    echo "   Aber: Dokument wird an SupplierContractBilling angehängt!\n\n";
    
    // 6. Prüfe, ob es eine Verbindung zwischen Contract und Billing-Dokumenten gibt
    echo "6. Polymorphe Beziehungen:\n";
    echo "   SupplierContract documents(): " . $contract->documents()->count() . " Dokumente\n";
    echo "   SupplierContractBilling documents(): " . $billing->documents()->count() . " Dokumente\n\n";
    
    // 7. Finde heraus, wie Billing-Dokumente über Contract-Interface hochgeladen werden
    echo "7. Mögliche Upload-Szenarien:\n";
    echo "   A) Upload über SupplierContract DocumentsRelationManager\n";
    echo "      → Dokument wird fälschlicherweise an SupplierContract angehängt\n";
    echo "      → Verwendet forSupplierContracts() Konfiguration\n\n";
    echo "   B) Upload über SupplierContract DocumentsRelationManager\n";
    echo "      → Dokument wird korrekt an SupplierContractBilling angehängt\n";
    echo "      → Aber verwendet falsche forSupplierContracts() Konfiguration\n\n";
    echo "   C) Upload über versteckten/indirekten Weg\n";
    echo "      → Dokument wird an SupplierContractBilling angehängt\n";
    echo "      → Verwendet alte/falsche Konfiguration\n\n";
    
    echo "✅ Debug abgeschlossen!\n";
    echo "\nNächster Schritt: Teste den tatsächlichen Upload-Flow in der Anwendung.\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}