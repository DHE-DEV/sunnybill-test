<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\SupplierContractBilling;
use App\Models\SupplierContractBillingArticle;
use App\Models\SolarPlantBilling;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Verwende eine existierende Solaranlagen-Abrechnung mit Artikeln
$solarPlantBillingId = '0198a53c-5ea5-72b2-b5d8-84499b0ba992';
$solarPlantBilling = SolarPlantBilling::find($solarPlantBillingId);

if (!$solarPlantBilling) {
    echo "Solaranlagen-Abrechnung nicht gefunden.\n";
    exit(1);
}

echo "Gefundene Solaranlagen-Abrechnung: {$solarPlantBilling->invoice_number}\n";
echo "Abrechnungsmonat: {$solarPlantBilling->billing_month}/{$solarPlantBilling->billing_year}\n\n";

// Hole die Vertragsabrechnungen aus dem credit_breakdown
$creditBreakdown = $solarPlantBilling->credit_breakdown ?? [];

foreach ($creditBreakdown as $index => $credit) {
    if (isset($credit['contract_billing_id'])) {
        $contractBilling = SupplierContractBilling::with('articles')->find($credit['contract_billing_id']);
        
        if ($contractBilling) {
            echo "Verarbeite Vertragsabrechnung: {$contractBilling->billing_number}\n";
            echo "Lieferant: {$credit['supplier_name']}\n";
            
            // Füge detaillierte Beschreibungen zu den Artikeln hinzu
            foreach ($contractBilling->articles as $article) {
                // Setze unterschiedliche Beispiel-Erklärungen basierend auf dem Artikelnamen
                $detailedDescription = null;
                
                if (str_contains(strtolower($article->description ?? ''), 'einspeis')) {
                    $detailedDescription = "Die Reduzierte Einspeisemenge bezieht sich auf die tatsächlich ins Stromnetz eingespeiste Energie Ihrer Solaranlage für den Abrechnungszeitraum. Diese Menge wird mit dem aktuellen Einspeisetarif vergütet und bildet die Grundlage für Ihre Erlöse aus der Direktvermarktung. Die Berechnung erfolgt auf Basis der gemessenen Zählerdaten unter Berücksichtigung von Punkt 1 der Erläuterungen.";
                } elseif (str_contains(strtolower($article->description ?? ''), 'börsenpreis')) {
                    $detailedDescription = "Der Negative Börsenpreis entsteht, wenn an der Strombörse mehr Strom angeboten als nachgefragt wird. In solchen Zeiten müssen Stromerzeuger für die Abnahme ihres Stroms bezahlen. Diese Kosten werden gemäß Punkt 2 der Erläuterungen anteilig auf alle Anlagenbetreiber umgelegt. Die Höhe richtet sich nach den tatsächlichen Börsenpreisen im Abrechnungszeitraum.";
                } elseif (str_contains(strtolower($article->description ?? ''), 'marktprämie')) {
                    $detailedDescription = "Die Marktprämie ist eine staatliche Förderung im Rahmen des EEG (Erneuerbare-Energien-Gesetz). Sie gleicht die Differenz zwischen dem anzulegenden Wert (feste EEG-Vergütung) und dem durchschnittlichen Börsenpreis aus. Dadurch erhalten Sie eine stabile Vergütung für Ihren eingespeisten Strom, unabhängig von Schwankungen am Strommarkt.";
                } elseif (str_contains(strtolower($article->description ?? ''), 'direktvermarktung')) {
                    $detailedDescription = "Bei der Direktvermarktung wird der erzeugte Strom Ihrer Solaranlage direkt an der Strombörse verkauft. Sie erhalten dafür den aktuellen Marktpreis plus die Marktprämie. Dieses Modell ermöglicht höhere Erlöse als die feste EEG-Vergütung und ist für Anlagen über 100 kWp verpflichtend.";
                }
                
                if ($detailedDescription) {
                    $article->detailed_description = $detailedDescription;
                    $article->save();
                    echo "  - Artikel '{$article->description}': Detaillierte Beschreibung hinzugefügt\n";
                }
            }
            
            echo "\n";
        }
    }
}

// Jetzt müssen wir die Abrechnung neu berechnen, damit die detailed_descriptions in credit_breakdown aufgenommen werden
echo "\nAktualisiere Solaranlagen-Abrechnung...\n";

// Berechne die Kosten neu
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

echo "Solaranlagen-Abrechnung erfolgreich aktualisiert.\n";
echo "\nDie detaillierten Beschreibungen wurden hinzugefügt und sollten nun im PDF unter 'Erklärung der Artikel' erscheinen.\n";
echo "Bitte generieren Sie das PDF erneut, um die Änderungen zu sehen.\n";
