<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\SupplierContractBilling;
use App\Models\SupplierContractBillingArticle;
use App\Models\SolarPlantBilling;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Verwende eine existierende Solaranlagen-Abrechnung
$solarPlantBillingId = '0198a53c-5ea5-72b2-b5d8-84499b0ba992';
$solarPlantBilling = SolarPlantBilling::find($solarPlantBillingId);

if (!$solarPlantBilling) {
    echo "Solaranlagen-Abrechnung nicht gefunden.\n";
    exit(1);
}

echo "Gefundene Solaranlagen-Abrechnung: {$solarPlantBilling->invoice_number}\n";
echo "Abrechnungsmonat: {$solarPlantBilling->billing_month}/{$solarPlantBilling->billing_year}\n\n";

// Finde alle SupplierContractBillings für diese Periode
$contractBillings = SupplierContractBilling::where('billing_year', $solarPlantBilling->billing_year)
    ->where('billing_month', $solarPlantBilling->billing_month)
    ->with('articles')
    ->get();

if ($contractBillings->isEmpty()) {
    echo "Keine Vertragsabrechnungen für diese Periode gefunden.\n";
    exit(1);
}

$updatedCount = 0;

foreach ($contractBillings as $contractBilling) {
    echo "Verarbeite Vertragsabrechnung: {$contractBilling->billing_number}\n";
    
    // Erstelle Artikel direkt in der Vertragsabrechnung wenn keine vorhanden
    if ($contractBilling->articles->isEmpty()) {
        echo "  Keine Artikel vorhanden - erstelle Beispielartikel mit detaillierten Beschreibungen...\n";
        
        // Artikel 1: Marktprämie
        SupplierContractBillingArticle::create([
            'supplier_contract_billing_id' => $contractBilling->id,
            'article_id' => null, // Kein Link zu Article-Tabelle
            'description' => 'Marktprämie PV April 2025',
            'quantity' => 15000.456,
            'unit_price' => 0.0856,
            'total_price' => 1284.04,
            'detailed_description' => "Die Marktprämie ist eine staatliche Förderung im Rahmen des EEG (Erneuerbare-Energien-Gesetz). Sie gleicht die Differenz zwischen dem anzulegenden Wert (feste EEG-Vergütung) und dem durchschnittlichen monatlichen Börsenpreis aus. Dadurch erhalten Sie eine stabile Vergütung für Ihren eingespeisten Strom, unabhängig von Schwankungen am Strommarkt. Die Marktprämie wird monatlich auf Basis der tatsächlich eingespeisten Energiemenge berechnet und ausgezahlt.",
        ]);
        echo "  - Artikel 'Marktprämie PV' mit detaillierter Beschreibung erstellt\n";
        $updatedCount++;
        
        // Artikel 2: Direktvermarktungserlös
        SupplierContractBillingArticle::create([
            'supplier_contract_billing_id' => $contractBilling->id,
            'article_id' => null, // Kein Link zu Article-Tabelle
            'description' => 'Direktvermarktungserlös April 2025',
            'quantity' => 15000.456,
            'unit_price' => 0.0450,
            'total_price' => 675.02,
            'detailed_description' => "Der Direktvermarktungserlös ist der Betrag, den Sie durch den Verkauf Ihres Stroms an der Strombörse erzielen. Ihr Strom wird von unserem Direktvermarkter gebündelt mit anderen Anlagen vermarktet und zu optimalen Zeiten an der Börse verkauft. Der Erlös richtet sich nach den aktuellen Börsenpreisen und kann je nach Marktlage variieren. Durch professionelle Vermarktung werden dabei bessere Preise erzielt als beim Eigenverkauf.",
        ]);
        echo "  - Artikel 'Direktvermarktungserlös' mit detaillierter Beschreibung erstellt\n";
        $updatedCount++;
        
        // Optional: Artikel 3 für negative Börsenpreise
        if (rand(0, 1)) { // Zufällig hinzufügen für Variation
            SupplierContractBillingArticle::create([
                'supplier_contract_billing_id' => $contractBilling->id,
                'article_id' => null,
                'description' => 'Negative Börsenpreise April 2025',
                'quantity' => 450.50,
                'unit_price' => -0.0234,
                'total_price' => -10.54,
                'detailed_description' => "Negative Börsenpreise entstehen, wenn an der Strombörse mehr Strom angeboten als nachgefragt wird. In solchen Zeiten müssen Stromerzeuger für die Abnahme ihres Stroms bezahlen. Diese Kosten werden anteilig auf alle Anlagenbetreiber umgelegt. Die Höhe richtet sich nach den tatsächlichen negativen Börsenpreisen und der Anzahl der betroffenen Stunden im Abrechnungszeitraum.",
            ]);
            echo "  - Artikel 'Negative Börsenpreise' mit detaillierter Beschreibung erstellt\n";
            $updatedCount++;
        }
    } else {
        // Artikel existieren - füge detaillierte Beschreibungen hinzu wo noch nicht vorhanden
        foreach ($contractBilling->articles as $article) {
            if (empty($article->detailed_description)) {
                $detailedDescription = null;
                $description = strtolower($article->description ?? '');
                
                if (str_contains($description, 'marktprämie')) {
                    $detailedDescription = "Die Marktprämie ist eine staatliche Förderung im Rahmen des EEG (Erneuerbare-Energien-Gesetz). Sie gleicht die Differenz zwischen dem anzulegenden Wert (feste EEG-Vergütung) und dem durchschnittlichen monatlichen Börsenpreis aus. Dadurch erhalten Sie eine stabile Vergütung für Ihren eingespeisten Strom, unabhängig von Schwankungen am Strommarkt.";
                } elseif (str_contains($description, 'direktvermarktung')) {
                    $detailedDescription = "Bei der Direktvermarktung wird der erzeugte Strom Ihrer Solaranlage direkt an der Strombörse verkauft. Sie erhalten dafür den aktuellen Marktpreis plus die Marktprämie. Dieses Modell ermöglicht höhere Erlöse als die feste EEG-Vergütung und ist für Anlagen über 100 kWp verpflichtend.";
                } elseif (str_contains($description, 'einspeis')) {
                    $detailedDescription = "Die Einspeisevergütung ist der Betrag, den Sie für die Einspeisung Ihres Solarstroms ins öffentliche Netz erhalten. Die Höhe richtet sich nach dem EEG-Tarif zum Zeitpunkt der Inbetriebnahme Ihrer Anlage und ist für 20 Jahre garantiert.";
                } elseif (str_contains($description, 'börsenpreis') || str_contains($description, 'negativ')) {
                    $detailedDescription = "Negative Börsenpreise entstehen, wenn an der Strombörse mehr Strom angeboten als nachgefragt wird. In solchen Zeiten müssen Stromerzeuger für die Abnahme ihres Stroms bezahlen. Diese Kosten werden anteilig auf alle Anlagenbetreiber umgelegt.";
                } elseif (str_contains($description, 'management') || str_contains($description, 'dienstleistung')) {
                    $detailedDescription = "Die Managementgebühr deckt die professionelle Verwaltung und Optimierung Ihrer Solaranlage ab. Dazu gehören die kontinuierliche Überwachung der Anlagenleistung, die Koordination von Wartungsarbeiten sowie die kaufmännische Abwicklung aller Abrechnungsprozesse.";
                } else {
                    // Generische Beschreibung
                    $detailedDescription = "Diese Position bezieht sich auf Ihre anteiligen Erlöse oder Kosten aus dem Betrieb der Solaranlage im Abrechnungszeitraum. Die Berechnung erfolgt auf Basis Ihres Beteiligungsanteils und der tatsächlich angefallenen Werte.";
                }
                
                $article->detailed_description = $detailedDescription;
                $article->save();
                echo "  - Artikel '{$article->description}': Detaillierte Beschreibung hinzugefügt\n";
                $updatedCount++;
            } else {
                echo "  - Artikel '{$article->description}': Hat bereits eine detaillierte Beschreibung\n";
            }
        }
    }
    echo "\n";
}

if ($updatedCount > 0) {
    echo "Aktualisiere Solaranlagen-Abrechnung...\n";
    
    // Berechne die Kosten neu mit den aktualisierten Artikeln
    $costData = SolarPlantBilling::calculateCostsForCustomer(
        $solarPlantBilling->solar_plant_id,
        $solarPlantBilling->customer_id,
        $solarPlantBilling->billing_year,
        $solarPlantBilling->billing_month,
        $solarPlantBilling->participation_percentage
    );
    
    // Aktualisiere die Abrechnung mit den neuen Daten
    $solarPlantBilling->update([
        'credit_breakdown' => $costData['credit_breakdown'],
        'cost_breakdown' => $costData['cost_breakdown'],
    ]);
    
    echo "\n✅ Erfolgreich {$updatedCount} Artikel mit detaillierten Beschreibungen versehen.\n";
    echo "\nDie detaillierten Beschreibungen wurden hinzugefügt und werden nun im PDF angezeigt:\n";
    echo "- Im Abschnitt 'Aufschlüsselung der Einnahmen/Gutschriften'\n";
    echo "- Unter 'Erklärung der Artikel' mit ausführlichen Beschreibungen\n";
} else {
    echo "\n✅ Alle Artikel haben bereits detaillierte Beschreibungen.\n";
}

echo "\n📄 Öffnen Sie die Abrechnung im Admin-Panel und generieren Sie das PDF erneut:\n";
echo "URL: https://sunnybill-test.test/admin/solar-plant-billings/{$solarPlantBillingId}\n";
echo "\nOder verwenden Sie die ursprünglich angegebene URL:\n";
echo "URL: https://sunnybill-test.test/admin/solar-plant-billings/0198a534-ac26-7119-887a-3c08af42fd3e\n";
